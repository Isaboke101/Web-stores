<?php
/**
 * admin/dashboard.php — Clarice's main admin dashboard
 *
 * Shows: today's orders, this week's revenue, pending production count,
 * and a recent orders table. Links to order detail, products, analytics.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$db = getDB();

/* ── KPI queries ──────────────────────────────────────────────── */

/* Orders placed today */
$todayStmt = $db->query(
    'SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()'
);
$todayCount = (int) $todayStmt->fetchColumn();

/* Revenue this week (paid orders only) */
$weekStmt = $db->query(
    'SELECT COALESCE(SUM(total_ksh), 0) FROM orders
     WHERE payment_status = "paid"
       AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)'
);
$weekRevenue = (int) $weekStmt->fetchColumn();

/* Pending production count */
$pendingStmt = $db->query(
    'SELECT COUNT(*) FROM orders WHERE status = "received" AND payment_status = "paid"'
);
$pendingCount = (int) $pendingStmt->fetchColumn();

/* Total orders all time */
$totalStmt  = $db->query('SELECT COUNT(*) FROM orders');
$totalOrders = (int) $totalStmt->fetchColumn();

/* Recent 10 orders */
$recentStmt = $db->query(
    'SELECT o.id, o.order_number, o.customer_name, o.total_ksh, o.status,
            o.payment_status, o.delivery_mode, o.created_at,
            GROUP_CONCAT(oi.product_name SEPARATOR ", ") AS items_summary
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10'
);
$recentOrders = $recentStmt->fetchAll();

/* Status label map for display */
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
<title>Dashboard — Injili Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">
  <div class="admin-topbar">
    <div>
      <h1 class="page-title">Dashboard</h1>
      <span class="page-subtitle">Good to see you, <?= htmlspecialchars($adminName) ?>.</span>
    </div>
    <a href="orders.php?status=received&payment=paid" class="btn-primary-sm">
      <i class="fa-solid fa-list"></i> View New Orders
    </a>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-label">Today's Orders</div>
      <div class="kpi-value"><?= $todayCount ?></div>
      <div class="kpi-sub">since midnight</div>
    </div>
    <div class="kpi-card kpi-gold">
      <div class="kpi-label">This Week — Revenue</div>
      <div class="kpi-value">KSh <?= number_format($weekRevenue) ?></div>
      <div class="kpi-sub">paid orders only</div>
    </div>
    <div class="kpi-card kpi-warn">
      <div class="kpi-label">Awaiting Production</div>
      <div class="kpi-value"><?= $pendingCount ?></div>
      <div class="kpi-sub">paid, not yet started</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Total Orders</div>
      <div class="kpi-value"><?= $totalOrders ?></div>
      <div class="kpi-sub">all time</div>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="admin-card">
    <div class="card-head">
      <h2 class="card-title">Recent Orders</h2>
      <a href="orders.php" class="card-link">View all <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <?php if (empty($recentOrders)): ?>
      <div class="empty-state">No orders yet. Share the store link!</div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $order): ?>
          <tr>
            <td class="order-num-cell"><?= htmlspecialchars($order['order_number']) ?></td>
            <td><?= htmlspecialchars($order['customer_name']) ?></td>
            <td class="items-cell"><?= htmlspecialchars($order['items_summary']) ?></td>
            <td>KSh <?= number_format($order['total_ksh']) ?></td>
            <td>
              <?php $s = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'class' => '']; ?>
              <span class="status-pill <?= $s['class'] ?>"><?= $s['label'] ?></span>
            </td>
            <td>
              <span class="pay-pill pay-<?= $order['payment_status'] ?>">
                <?= ucfirst($order['payment_status']) ?>
              </span>
            </td>
            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
            <td>
              <a href="order_detail.php?id=<?= $order['id'] ?>" class="tbl-action">
                <i class="fa-solid fa-eye"></i>
              </a>
            </td>
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
