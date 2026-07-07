<?php
/**
 * api/payment/mpesa_stk.php — Initiate M-Pesa STK Push
 *
 * POST /api/payment/mpesa_stk.php
 * Body (JSON): { order_id, phone }
 * Returns: { success, message, checkout_request_id? }
 *
 * Called by the frontend after creating an order.
 * Triggers a payment prompt on the customer's phone.
 * The actual payment confirmation comes via the Daraja callback
 * to mpesa_callback.php — not from this endpoint.
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/mpesa.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($body['order_id'] ?? 0);
$phone   = trim($body['phone'] ?? '');

if (!$orderId || !$phone) {
    echo json_encode(['success' => false, 'message' => 'order_id and phone are required']);
    exit;
}

/* Fetch the order to get the correct total */
$db   = getDB();
$stmt = $db->prepare(
    'SELECT id, order_number, total_ksh, payment_status FROM orders WHERE id = :id'
);
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

/* Prevent double-payment on already-paid orders */
if ($order['payment_status'] === 'paid') {
    echo json_encode(['success' => false, 'message' => 'Order is already paid']);
    exit;
}

try {
    /* Initiate the STK Push via the MpesaService */
    $result = MpesaService::stkPush(
        phone:   $phone,
        amount:  (int) $order['total_ksh'],
        orderId: $order['order_number'],
        desc:    'Injili Apparel'
    );

    /* Save the CheckoutRequestID so the callback can match this payment to the order */
    $checkoutId = $result['CheckoutRequestID'] ?? null;

    if ($checkoutId) {
        $upd = $db->prepare(
            'UPDATE orders SET mpesa_checkout_id = :cid WHERE id = :id'
        );
        $upd->execute([':cid' => $checkoutId, ':id' => $orderId]);
    }

    echo json_encode([
        'success'              => true,
        'message'              => 'STK push sent. Check your phone and enter your M-Pesa PIN.',
        'checkout_request_id'  => $checkoutId,
        'merchant_request_id'  => $result['MerchantRequestID'] ?? null,
    ]);

} catch (RuntimeException $e) {
    error_log('[STK] Exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'M-Pesa payment could not be initiated. Please try again or contact Clarice on WhatsApp.',
    ]);
}
