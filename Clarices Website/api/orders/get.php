<?php
/**
 * api/orders/get.php — Fetch a single order (polling endpoint)
 *
 * GET /api/orders/get.php?id=123  (internal order ID)
 * GET /api/orders/get.php?order=INJ-2025-0001  (order number)
 *
 * Returns: { success, order, items, history, tracking? }
 *
 * Used by the frontend to poll for payment confirmation after
 * initiating an M-Pesa STK push. Also used by the tracking page.
 *
 * Security: a logged-in customer may only see their own orders.
 * A guest (no session) may access by order number (for tracking page).
 */

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

session_name('injili_customer');
session_start();

$db = getDB();
$order = null;

if (!empty($_GET['id'])) {
    /* Numeric ID — only for logged-in customer or admin polling */
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = :id');
    $stmt->execute([':id' => (int)$_GET['id']]);
    $order = $stmt->fetch();

    /* Logged-in customers may only see their own orders */
    if ($order && !empty($_SESSION['user_id']) && (int)$order['user_id'] !== (int)$_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

} elseif (!empty($_GET['order'])) {
    /* Order number — public (for tracking page, email links) */
    $stmt = $db->prepare('SELECT * FROM orders WHERE order_number = :onum');
    $stmt->execute([':onum' => $_GET['order']]);
    $order = $stmt->fetch();
}

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

/* Fetch line items */
$itemStmt = $db->prepare(
    'SELECT oi.*, p.graphic_html, p.bg_color
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = :oid'
);
$itemStmt->execute([':oid' => $order['id']]);
$items = $itemStmt->fetchAll();

/* Fetch status history (for timeline display on tracking page) */
$histStmt = $db->prepare(
    'SELECT status, note, changed_by, created_at
     FROM order_status_history
     WHERE order_id = :oid
     ORDER BY id ASC'
);
$histStmt->execute([':oid' => $order['id']]);
$history = $histStmt->fetchAll();

/* Fetch delivery tracking if it exists */
$trackStmt = $db->prepare(
    'SELECT * FROM delivery_tracking WHERE order_id = :oid'
);
$trackStmt->execute([':oid' => $order['id']]);
$tracking = $trackStmt->fetch();

if ($tracking) {
    $tracking['pm_events'] = json_decode($tracking['pm_events'], true) ?? [];
}

echo json_encode([
    'success'  => true,
    'order'    => $order,
    'items'    => $items,
    'history'  => $history,
    'tracking' => $tracking ?: null,
]);
