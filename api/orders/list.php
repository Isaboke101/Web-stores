<?php
/**
 * api/orders/list.php — List orders for the logged-in customer
 *
 * GET /api/orders/list.php
 * Returns: { success, orders: [...] }
 *
 * Requires an active customer session. Returns the customer's
 * order history with a summary of items per order.
 */

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

session_name('injili_customer');
session_start();

/* Require authentication */
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

/* Fetch orders with a concatenated item summary */
$stmt = $db->prepare(
    'SELECT o.*,
            GROUP_CONCAT(oi.product_name, " (", oi.size, ")" SEPARATOR ", ") AS items_summary
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = :uid
     GROUP BY o.id
     ORDER BY o.created_at DESC'
);
$stmt->execute([':uid' => $userId]);
$orders = $stmt->fetchAll();

/* Also return total spend for the account panel stats */
$totalStmt = $db->prepare(
    'SELECT COALESCE(SUM(total_ksh), 0) AS total_spent FROM orders
     WHERE user_id = :uid AND payment_status = "paid"'
);
$totalStmt->execute([':uid' => $userId]);
$totalSpent = (int)$totalStmt->fetchColumn();

echo json_encode([
    'success'     => true,
    'orders'      => $orders,
    'order_count' => count($orders),
    'total_spent' => $totalSpent,
]);
