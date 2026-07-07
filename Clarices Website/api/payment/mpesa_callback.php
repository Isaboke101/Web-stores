<?php
/**
 * api/payment/mpesa_callback.php — Daraja M-Pesa payment callback
 *
 * POST (from Safaricom Daraja servers only)
 *
 * This endpoint is called by Safaricom asynchronously after the customer
 * completes or cancels an STK Push payment. It is NOT called by the frontend.
 *
 * Daraja sends a JSON body. We:
 *   1. Parse the callback body
 *   2. Match the payment to our order via CheckoutRequestID
 *   3. Update orders.payment_status and mpesa_receipt
 *   4. Trigger customer and admin notifications
 *
 * Security note: Daraja callbacks don't include authentication headers in
 * sandbox mode. In live mode, you should whitelist Safaricom IP ranges
 * and verify the ResultCode. We validate ResultCode here.
 *
 * This URL must be publicly reachable (ngrok for local dev).
 * Set MPESA_CALLBACK_URL in .env accordingly.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/notifier.php';

/* Daraja always calls this endpoint as POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

/* Read the raw JSON body from Daraja */
$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);

/* Log the raw callback for debugging purposes */
error_log('[M-Pesa Callback] Received: ' . $rawBody);

/* Daraja wraps the callback in Body.stkCallback */
$callback = $data['Body']['stkCallback'] ?? null;

if (!$callback) {
    /* Malformed payload — return a 200 anyway so Daraja doesn't retry */
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

$resultCode       = (int)($callback['ResultCode'] ?? -1);
$checkoutRequestId = $callback['CheckoutRequestID'] ?? '';

if (!$checkoutRequestId) {
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare(
    'SELECT id, order_number, customer_name, customer_email, customer_phone, total_ksh
     FROM orders WHERE mpesa_checkout_id = :cid'
);
$stmt->execute([':cid' => $checkoutRequestId]);
$order = $stmt->fetch();

if (!$order) {
    /* No matching order — log and accept gracefully */
    error_log('[M-Pesa Callback] No order found for CheckoutRequestID: ' . $checkoutRequestId);
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

if ($resultCode === 0) {
    /* ── Payment SUCCESSFUL ───────────────────────────────────── */
    /* Extract the M-Pesa receipt number and amount from CallbackMetadata */
    $receipt = '';
    $amount  = 0;

    $items = $callback['CallbackMetadata']['Item'] ?? [];
    foreach ($items as $item) {
        if ($item['Name'] === 'MpesaReceiptNumber') {
            $receipt = $item['Value'] ?? '';
        }
        if ($item['Name'] === 'Amount') {
            $amount = (int)($item['Value'] ?? 0);
        }
    }

    /* Update the order: mark as paid and store the M-Pesa receipt */
    $upd = $db->prepare(
        'UPDATE orders
         SET payment_status = "paid", mpesa_receipt = :receipt, status = "received"
         WHERE id = :id'
    );
    $upd->execute([':receipt' => $receipt, ':id' => $order['id']]);

    /* Fire order-placed notifications (email, SMS to customer + admin) */
    Notifier::dispatch('order_placed', (int)$order['id']);

    error_log('[M-Pesa Callback] Payment confirmed for order ' . $order['order_number'] . ' — receipt ' . $receipt);

} else {
    /* ── Payment FAILED or CANCELLED ────────────────────────────── */
    $resultDesc = $callback['ResultDesc'] ?? 'Unknown failure';
    error_log('[M-Pesa Callback] Payment failed for ' . $order['order_number'] . ': ' . $resultDesc);

    $upd = $db->prepare('UPDATE orders SET payment_status = "failed" WHERE id = :id');
    $upd->execute([':id' => $order['id']]);
}

/* Always return 200 to Daraja — any non-200 causes Daraja to retry */
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
