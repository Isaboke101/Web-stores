<?php
/**
 * admin/analytics.php — Basic sales analytics for Clarice
 *
 * Shows: total revenue, orders by status, top designs by units sold,
 * delivery mode breakdown, and a daily order count for the last 14 days.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$db = getDB();

/* Total revenue (paid) */
$revenue = (int) $db->query(
    'SELECT COALESCE(SUM(total_ksh),0) FROM orders WHERE payment_status = "paid"'
)->fetchColumn();

/* Orders by status */
$byStatus = $db->query(
    'SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC'
)->fetchAll();

/* Top designs by units sold */
$topDesigns = $db->query(
    'SELECT p.name, SUM(oi.quantity) AS units, SUM(oi.line_total_ksh) AS revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN orders o ON o.id = oi.order_id
     WHERE o.payment_status = "paid"
     GROUP BY p.id ORDER BY units DESC LIMIT 7'
)->fetchAll();

/* Delivery mode breakdown */
$byDelivery = $db->query(
    'SELECT delivery_mode, COUNT(*) AS cnt FROM orders GROUP BY delivery_mode'
)->fetchAll();

/* Daily orders (last 14 days) */
$daily = $db->query(
    'SELECT DATE(created_at) AS day, COUNT(*) AS cnt, COALESCE(SUM(total_ksh),0) AS rev
     FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
     GROUP BY DATE(created_at) ORDER BY day ASC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics — Injili Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <h1 class="page-title">Analytics</h1>
  </div>

  <div class="kpi-grid">
    <div class="kpi-card kpi-gold">
      <div class="kpi-label">Total Revenue (Paid)</div>
      <div class="kpi-value">KSh <?= number_format($revenue) ?></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Total Orders</div>
      <div class="kpi-value"><?= array_sum(array_column($byStatus, 'cnt')) ?></div>
    </div>
  </div>

  <div class="analytics-grid">
    <!-- Orders by status -->
    <div class="admin-card">
      <div class="card-head"><h2 class="card-title">Orders by Status</h2></div>
      <?php foreach ($byStatus as $row): ?>
      <div class="analytics-bar-row">
        <span class="analytics-label"><?= ucwords(str_replace('_', ' ', $row['status'])) ?></span>
        <div class="analytics-bar-wrap">
          <div class="analytics-bar" style="width:<?= min(100, ($row['cnt'] / max(1, array_sum(array_column($byStatus, 'cnt')))) * 100) ?>%"></div>
        </div>
        <span class="analytics-val"><?= $row['cnt'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Top designs -->
    <div class="admin-card">
      <div class="card-head"><h2 class="card-title">Top Designs (by Units)</h2></div>
      <?php if (empty($topDesigns)): ?>
        <div class="empty-state">No paid orders yet.</div>
      <?php else: ?>
        <?php $maxUnits = max(array_column($topDesigns, 'units') ?: [1]); ?>
        <?php foreach ($topDesigns as $d): ?>
        <div class="analytics-bar-row">
          <span class="analytics-label"><?= htmlspecialchars($d['name']) ?></span>
          <div class="analytics-bar-wrap">
            <div class="analytics-bar" style="width:<?= ($d['units']/$maxUnits)*100 ?>%;background:#C98A41"></div>
          </div>
          <span class="analytics-val"><?= $d['units'] ?> units</span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Delivery mode split -->
    <div class="admin-card">
      <div class="card-head"><h2 class="card-title">Delivery Mode</h2></div>
      <?php $totalDel = array_sum(array_column($byDelivery, 'cnt')) ?: 1; ?>
      <?php foreach ($byDelivery as $d): ?>
      <div class="analytics-bar-row">
        <span class="analytics-label"><?= ucwords(str_replace('_', ' ', $d['delivery_mode'])) ?></span>
        <div class="analytics-bar-wrap">
          <div class="analytics-bar" style="width:<?= ($d['cnt']/$totalDel)*100 ?>%;background:#1C2329"></div>
        </div>
        <span class="analytics-val"><?= $d['cnt'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Daily orders (last 14 days) -->
    <div class="admin-card">
      <div class="card-head"><h2 class="card-title">Daily Orders — Last 14 Days</h2></div>
      <?php if (empty($daily)): ?>
        <div class="empty-state">No orders in the last 14 days.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="admin-table">
            <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
            <tbody>
              <?php foreach ($daily as $day): ?>
              <tr>
                <td><?= date('D, d M', strtotime($day['day'])) ?></td>
                <td><?= $day['cnt'] ?></td>
                <td>KSh <?= number_format($day['rev']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>
