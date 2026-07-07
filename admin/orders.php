<?php
/**
 * admin/orders.php — Order list with filters
 *
 * Supports GET filters: ?status=received&payment=paid&design=1&date=2025-01-28
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$db = getDB();

/* ── Build filtered query ──────────────────────────────────────── */
$conditions = ['1=1'];
$params     = [];

if (!empty($_GET['status'])) {
    $conditions[] = 'o.status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['payment'])) {
    $conditions[] = 'o.payment_status = :payment';
    $params[':payment'] = $_GET['payment'];
}
if (!empty($_GET['date'])) {
    $conditions[] = 'DATE(o.created_at) = :date';
    $params[':date'] = $_GET['date'];
}
if (!empty($_GET['design'])) {
    $conditions[] = 'oi_sub.product_id = :design';
    $params[':design'] = (int)$_GET['design'];
}

$where = implode(' AND ', $conditions);

/* Join with a subquery to filter by design without duplicating rows */
$sql = "SELECT o.id, o.order_number, o.customer_name, o.total_ksh,
               o.status, o.payment_status, o.delivery_mode, o.created_at,
               GROUP_CONCAT(oi.product_name, ' (', oi.size, ')' SEPARATOR ', ') AS items_summary
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        " . (!empty($_GET['design']) ?
            "JOIN (SELECT DISTINCT order_id FROM order_items WHERE product_id = :design2) oi_sub ON oi_sub.order_id = o.id" : '') . "
        WHERE {$where}
        GROUP BY o.id
        ORDER BY o.created_at DESC";

if (!empty($_GET['design'])) {
    $params[':design2'] = (int)$_GET['design'];
}

$stmt   = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

/* Products for the design filter dropdown */
$products = $db->query('SELECT id, name FROM products WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

$statusLabels = [
    'received'           => ['label' => 'Received',     'class' => 'status-received'],
    'in_production'      => ['label' => 'In Production','class' => 'status-production'],
    'ready_for_dispatch' => ['label' => 'Ready',        'class' => 'status-ready'],
    'handed_to_pm'       => ['label' => 'With PM',      'class' => 'status-pm'],
    'in_transit'         => ['label' => 'In Transit',   'class' => 'status-transit'],
    'out_for_delivery'   => ['label' => 'Out for Del.', 'class' => 'status-out'],
    'delivered'          => ['label' => 'Delivered',    'class' => 'status-delivered'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders — Injili Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <div>
      <h1 class="page-title">Orders</h1>
      <span class="page-subtitle"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> found</span>
    </div>
  </div>

  <!-- Filter bar -->
  <form method="GET" class="filter-bar">
    <select name="status" class="filter-select">
      <option value="">All Statuses</option>
      <?php foreach (array_keys($statusLabels) as $s): ?>
        <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>>
          <?= $statusLabels[$s]['label'] ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="payment" class="filter-select">
      <option value="">All Payments</option>
      <option value="pending"  <?= ($_GET['payment'] ?? '') === 'pending'  ? 'selected' : '' ?>>Pending</option>
      <option value="paid"     <?= ($_GET['payment'] ?? '') === 'paid'     ? 'selected' : '' ?>>Paid</option>
      <option value="failed"   <?= ($_GET['payment'] ?? '') === 'failed'   ? 'selected' : '' ?>>Failed</option>
    </select>
    <select name="design" class="filter-select">
      <option value="">All Designs</option>
      <?php foreach ($products as $p): ?>
        <option value="<?= $p['id'] ?>" <?= ($_GET['design'] ?? '') == $p['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="date" class="filter-input" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
    <button type="submit" class="btn-primary-sm"><i class="fa-solid fa-filter"></i> Filter</button>
    <a href="orders.php" class="btn-ghost-sm">Clear</a>
  </form>

  <div class="admin-card">
    <?php if (empty($orders)): ?>
      <div class="empty-state">No orders match this filter.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Delivery</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
          <tr>
            <td class="order-num-cell"><?= htmlspecialchars($order['order_number']) ?></td>
            <td><?= htmlspecialchars($order['customer_name']) ?></td>
            <td class="items-cell"><?= htmlspecialchars($order['items_summary']) ?></td>
            <td>KSh <?= number_format($order['total_ksh']) ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $order['delivery_mode'])) ?></td>
            <td>
              <?php $s = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'class' => '']; ?>
              <span class="status-pill <?= $s['class'] ?>"><?= $s['label'] ?></span>
            </td>
            <td><span class="pay-pill pay-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span></td>
            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
            <td><a href="order_detail.php?id=<?= $order['id'] ?>" class="tbl-action"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
