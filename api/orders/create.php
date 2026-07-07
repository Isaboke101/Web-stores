<?php
/**
 * api/orders/create.php — Create a new order
 *
 * POST /api/orders/create.php
 * Body (JSON): {
 *   customer_name, customer_email, customer_phone,
 *   delivery_mode,   (agent_pickup | doorstep | errand)
 *   delivery_address, delivery_city, delivery_area, delivery_notes,
 *   items: [{ product_id, variant_id, size, quantity }],
 * }
 * Returns: { success, order_id, order_number, total_ksh }
 *
 * Creates the order record, calculates totals, and stores everything.
 * Payment is initiated separately via /api/payment/mpesa_stk.php.
 * The order starts with status='received' and payment_status='pending'.
 */

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

session_name('injili_customer');
session_start();

$body = json_decode(file_get_contents('php://input'), true);

/* ── Validate required fields ──────────────────────────────────── */
$name    = trim($body['customer_name']  ?? '');
$email   = trim($body['customer_email'] ?? '');
$phone   = trim($body['customer_phone'] ?? '');
$mode    = $body['delivery_mode'] ?? 'doorstep';
$items   = $body['items'] ?? [];

$allowedModes = ['agent_pickup', 'doorstep', 'errand'];
if (!$name || !$email || !$phone) {
    echo json_encode(['success' => false, 'message' => 'Customer name, email and phone are required']);
    exit;
}
if (!in_array($mode, $allowedModes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid delivery mode']);
    exit;
}
if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Order must contain at least one item']);
    exit;
}

$db = getDB();

/* ── Calculate order totals ────────────────────────────────────── */
$subtotal = 0;
$lineItems = [];

foreach ($items as $item) {
    $productId = (int)($item['product_id'] ?? 0);
    $variantId = (int)($item['variant_id'] ?? 0);
    $size      = trim($item['size'] ?? '');
    $quantity  = max(1, (int)($item['quantity'] ?? 1));

    if (!$productId || !$variantId || !$size) {
        echo json_encode(['success' => false, 'message' => 'Invalid item data']);
        exit;
    }

    /* Fetch the product price and names — never trust client-submitted prices */
    $pStmt = $db->prepare(
        'SELECT p.id, p.name, p.price_ksh, pv.color_name
         FROM products p
         JOIN product_variants pv ON pv.id = :vid AND pv.product_id = p.id
         WHERE p.id = :pid AND p.is_active = 1 AND pv.is_active = 1'
    );
    $pStmt->execute([':pid' => $productId, ':vid' => $variantId]);
    $product = $pStmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => "Product ID {$productId} not found"]);
        exit;
    }

    $lineTotal  = $product['price_ksh'] * $quantity;
    $subtotal  += $lineTotal;

    $lineItems[] = [
        'product_id'     => $productId,
        'variant_id'     => $variantId,
        'product_name'   => $product['name'],
        'color_name'     => $product['color_name'],
        'size'           => $size,
        'quantity'       => $quantity,
        'unit_price_ksh' => $product['price_ksh'],
        'line_total_ksh' => $lineTotal,
    ];
}

/* Delivery fee: free over KSh 5,000, else KSh 200 for standard doorstep */
$deliveryFee = 0;
if ($mode === 'doorstep' || $mode === 'errand') {
    $deliveryFee = $subtotal >= 5000 ? 0 : 200;
}
/* Agent pickup is always free */

$total = $subtotal + $deliveryFee;

/* ── Generate order number ─────────────────────────────────────── */
/* Format: INJ-YYYY-XXXX where XXXX is zero-padded sequential */
$counterStmt = $db->query('SELECT COUNT(*) FROM orders');
$count       = (int) $counterStmt->fetchColumn();
$orderNumber = 'INJ-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

/* ── Insert the order ──────────────────────────────────────────── */
$userId = $_SESSION['user_id'] ?? null;

$orderStmt = $db->prepare(
    'INSERT INTO orders
       (order_number, user_id, customer_name, customer_email, customer_phone,
        delivery_mode, delivery_address, delivery_city, delivery_area, delivery_notes,
        delivery_fee_ksh, subtotal_ksh, total_ksh, status, payment_status, payment_method)
     VALUES
       (:onum, :uid, :name, :email, :phone,
        :mode, :addr, :city, :area, :notes,
        :dfee, :sub, :total, "received", "pending", "mpesa")'
);
$orderStmt->execute([
    ':onum'  => $orderNumber,
    ':uid'   => $userId,
    ':name'  => $name,
    ':email' => $email,
    ':phone' => $phone,
    ':mode'  => $mode,
    ':addr'  => $body['delivery_address'] ?? null,
    ':city'  => $body['delivery_city']    ?? null,
    ':area'  => $body['delivery_area']    ?? null,
    ':notes' => $body['delivery_notes']   ?? null,
    ':dfee'  => $deliveryFee,
    ':sub'   => $subtotal,
    ':total' => $total,
]);

$orderId = (int) $db->lastInsertId();

/* ── Insert order items ────────────────────────────────────────── */
$itemStmt = $db->prepare(
    'INSERT INTO order_items
       (order_id, product_id, variant_id, product_name, color_name, size, quantity,
        unit_price_ksh, line_total_ksh)
     VALUES
       (:oid, :pid, :vid, :pname, :cname, :size, :qty, :uprice, :ltotal)'
);

foreach ($lineItems as $li) {
    $itemStmt->execute([
        ':oid'    => $orderId,
        ':pid'    => $li['product_id'],
        ':vid'    => $li['variant_id'],
        ':pname'  => $li['product_name'],
        ':cname'  => $li['color_name'],
        ':size'   => $li['size'],
        ':qty'    => $li['quantity'],
        ':uprice' => $li['unit_price_ksh'],
        ':ltotal' => $li['line_total_ksh'],
    ]);
}

/* ── Insert initial status history entry ─────────────────────── */
$histStmt = $db->prepare(
    'INSERT INTO order_status_history (order_id, status, note, changed_by)
     VALUES (:oid, "received", "Order placed", "system")'
);
$histStmt->execute([':oid' => $orderId]);

echo json_encode([
    'success'      => true,
    'order_id'     => $orderId,
    'order_number' => $orderNumber,
    'subtotal_ksh' => $subtotal,
    'delivery_fee' => $deliveryFee,
    'total_ksh'    => $total,
]);
