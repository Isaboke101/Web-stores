<?php
/**
 * admin/order_detail.php — Full order detail + status management
 *
 * Shows all order fields, line items, status history timeline,
 * and PM tracking. Clarice updates status here.
 */

require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/pickupmtaani.php';

$db      = getDB();
$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$orderStmt = $db->prepare('SELECT * FROM orders WHERE id = :id');
$orderStmt->execute([':id' => $orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

/* Fetch line items */
$items = $db->prepare(
    'SELECT oi.*, p.graphic_html FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid'
);
$items->execute([':oid' => $orderId]);
$items = $items->fetchAll();

/* Fetch status history */
$hist = $db->prepare(
    'SELECT * FROM order_status_history WHERE order_id = :oid ORDER BY id ASC'
);
$hist->execute([':oid' => $orderId]);
$history = $hist->fetchAll();

/* Fetch delivery tracking */
$trackStmt = $db->prepare('SELECT * FROM delivery_tracking WHERE order_id = :oid');
$trackStmt->execute([':oid' => $orderId]);
$tracking = $trackStmt->fetch();
if ($tracking) {
    $tracking['pm_events'] = json_decode($tracking['pm_events'], true) ?? [];
}

/* Flash message from redirect */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$statusOptions = [
    'received'           => 'Order Received',
    'in_production'      => 'In Production',
    'ready_for_dispatch' => 'Ready for Dispatch',
    'handed_to_pm'       => 'Handed to Pickup Mtaani',
    'in_transit'         => 'In Transit',
    'out_for_delivery'   => 'Out for Delivery',
    'delivered'          => 'Delivered',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($order['order_number']) ?> — Injili Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/sidebar.php'; ?>
<main class="admin-main">
  <div class="admin-topbar">
    <div>
      <a href="orders.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> All Orders</a>
      <h1 class="page-title"><?= htmlspecialchars($order['order_number']) ?></h1>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="detail-grid">
    <!-- LEFT: Order info + items -->
    <div>
      <!-- Customer info -->
      <div class="admin-card">
        <div class="card-head"><h2 class="card-title">Customer</h2></div>
        <div class="detail-field"><span class="detail-label">Name</span><span><?= htmlspecialchars($order['customer_name']) ?></span></div>
        <div class="detail-field"><span class="detail-label">Email</span><span><?= htmlspecialchars($order['customer_email']) ?></span></div>
        <div class="detail-field"><span class="detail-label">Phone</span><span><?= htmlspecialchars($order['customer_phone']) ?></span></div>
        <div class="detail-field"><span class="detail-label">Delivery</span><span><?= ucfirst(str_replace('_', ' ', $order['delivery_mode'])) ?></span></div>
        <?php if ($order['delivery_address']): ?>
          <div class="detail-field"><span class="detail-label">Address</span><span><?= htmlspecialchars($order['delivery_address']) ?>, <?= htmlspecialchars($order['delivery_area'] ?? '') ?>, <?= htmlspecialchars($order['delivery_city'] ?? '') ?></span></div>
        <?php endif; ?>
        <?php if ($order['delivery_notes']): ?>
          <div class="detail-field"><span class="detail-label">Notes</span><span><?= htmlspecialchars($order['delivery_notes']) ?></span></div>
        <?php endif; ?>
      </div>

      <!-- Line items -->
      <div class="admin-card">
        <div class="card-head"><h2 class="card-title">Items Ordered</h2></div>
        <?php foreach ($items as $item): ?>
        <div class="order-item-row">
          <div class="order-item-info">
            <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
            <div class="order-item-meta"><?= htmlspecialchars($item['color_name']) ?> / Size <?= htmlspecialchars($item['size']) ?> × <?= $item['quantity'] ?></div>
          </div>
          <div class="order-item-price">KSh <?= number_format($item['line_total_ksh']) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="order-totals">
          <div class="total-row"><span>Subtotal</span><span>KSh <?= number_format($order['subtotal_ksh']) ?></span></div>
          <div class="total-row"><span>Delivery</span><span>KSh <?= number_format($order['delivery_fee_ksh']) ?></span></div>
          <div class="total-row total-final"><span>Total</span><strong>KSh <?= number_format($order['total_ksh']) ?></strong></div>
        </div>
      </div>

      <!-- Payment -->
      <div class="admin-card">
        <div class="card-head"><h2 class="card-title">Payment</h2></div>
        <div class="detail-field"><span class="detail-label">Method</span><span><?= ucfirst($order['payment_method']) ?></span></div>
        <div class="detail-field"><span class="detail-label">Status</span><span class="pay-pill pay-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span></div>
        <?php if ($order['mpesa_receipt']): ?>
          <div class="detail-field"><span class="detail-label">M-Pesa Receipt</span><span><code><?= htmlspecialchars($order['mpesa_receipt']) ?></code></span></div>
        <?php endif; ?>
        <?php if ($order['payment_status'] === 'pending'): ?>
          <form method="POST" action="update_status.php" style="margin-top:1rem">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <input type="hidden" name="payment_status" value="manual">
            <button class="btn-primary-sm" name="action" value="mark_paid">
              <i class="fa-solid fa-check"></i> Mark as Paid (Manual)
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: Status management + history + tracking -->
    <div>
      <!-- Update Status -->
      <div class="admin-card">
        <div class="card-head"><h2 class="card-title">Update Order Status</h2></div>

        <?php
        /* Define what admin can control vs what PM controls */
        $adminFlow    = [
            'received'           => 'in_production',
            'in_production'      => 'ready_for_dispatch',
            'ready_for_dispatch' => 'handed_to_pm',
        ];
        $pmControlled = ['handed_to_pm','in_transit','out_for_delivery','delivered'];
        $nextStatus   = $adminFlow[$order['status']] ?? null;
        $isWithPM     = in_array($order['status'], $pmControlled);

        $nextLabels = [
            'in_production'      => 'Mark In Production',
            'ready_for_dispatch' => 'Mark Ready for Dispatch',
            'handed_to_pm'       => 'Hand Over to Pickup Mtaani',
        ];
        ?>

        <?php if ($isWithPM): ?>
          <!-- Order is with PM — admin cannot change status -->
          <div style="padding:1.25rem 1.5rem">
            <div style="display:flex;align-items:center;gap:.6rem;font-size:.82rem;
                        color:#7d3c98;background:rgba(142,68,173,.08);
                        border:1px solid rgba(142,68,173,.2);border-radius:3px;
                        padding:.85rem 1rem;margin-bottom:1rem">
              <i class="fa-solid fa-truck-fast"></i>
              This order is now with <strong>Pickup Mtaani</strong>.
              Use the Simulate PM Status button below to advance tracking.
            </div>
            <div style="font-size:.78rem;color:var(--muted)">
              Current PM status: <strong><?= htmlspecialchars($tracking['pm_status'] ?? 'Parcel Created') ?></strong>
            </div>
          </div>

        <?php elseif ($nextStatus): ?>
          <!-- Show only the single next valid step -->
          <form method="POST" action="update_status.php" style="padding:1.25rem 1.5rem">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <input type="hidden" name="status"   value="<?= $nextStatus ?>">

            <div style="font-size:.78rem;color:var(--muted);margin-bottom:.75rem">
              Current status:
              <strong><?= ucwords(str_replace('_',' ',$order['status'])) ?></strong>
            </div>

            <div class="form-group">
              <label class="form-label">Note for customer (optional)</label>
              <textarea name="note" class="form-input" rows="2"
                placeholder="e.g. Your tee is being printed and will be ready soon."></textarea>
            </div>

            <button class="btn-primary-sm" name="action" value="update_status" style="width:100%">
              <i class="fa-solid fa-arrow-right"></i>
              <?= $nextLabels[$nextStatus] ?? ucwords(str_replace('_',' ',$nextStatus)) ?>
            </button>
          </form>

        <?php else: ?>
          <!-- Delivered — nothing left to do -->
          <div style="padding:1.25rem 1.5rem;font-size:.82rem;color:#1e8449;
                      background:rgba(39,174,96,.08);border:1px solid rgba(39,174,96,.2);
                      border-radius:3px;display:flex;align-items:center;gap:.6rem">
            <i class="fa-solid fa-circle-check"></i> This order has been delivered. All done!
          </div>
        <?php endif; ?>

        <!-- PM simulate button — only shown when tracking record exists AND using mock -->
        <?php if ($tracking && PM_USE_MOCK && !in_array($tracking['pm_status'], ['Delivered'])): ?>
        <div style="padding:0 1.5rem 1.25rem">
          <form method="POST" action="update_status.php">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <button class="btn-ghost-sm" name="action" value="simulate_pm" style="width:100%">
              <i class="fa-solid fa-forward-step"></i> Simulate Next PM Status
            </button>
          </form>
          <div style="font-size:.72rem;color:var(--muted);margin-top:.4rem;text-align:center">
            PM: <strong><?= htmlspecialchars($tracking['pm_status']) ?></strong>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Status history timeline -->
      <div class="admin-card">
        <div class="card-head"><h2 class="card-title">Status Timeline</h2></div>
        <div class="timeline">
          <?php foreach (array_reverse($history) as $h): ?>
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
              <div class="timeline-status"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $h['status']))) ?></div>
              <?php if ($h['note']): ?>
                <div class="timeline-note"><?= htmlspecialchars($h['note']) ?></div>
              <?php endif; ?>
              <div class="timeline-time"><?= date('d M Y, H:i', strtotime($h['created_at'])) ?> · <?= htmlspecialchars($h['changed_by']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- PM Tracking -->
      <?php if ($tracking): ?>
      <div class="admin-card">
        <div class="card-head"><h2 class="card-title">Pickup Mtaani Tracking</h2></div>
        <div class="detail-field"><span class="detail-label">PM Ref</span><span><code><?= htmlspecialchars($tracking['pm_reference']) ?></code></span></div>
        <div class="detail-field"><span class="detail-label">PM Status</span><span><?= htmlspecialchars($tracking['pm_status']) ?></span></div>
        <?php if ($tracking['agent_name']): ?>
          <div class="detail-field"><span class="detail-label">Agent</span><span><?= htmlspecialchars($tracking['agent_name']) ?> — <?= htmlspecialchars($tracking['agent_location']) ?></span></div>
        <?php endif; ?>
        <div style="margin-top:1rem">
          <?php foreach (array_reverse($tracking['pm_events']) as $ev): ?>
          <div class="timeline-item" style="margin-bottom:.5rem">
            <div class="timeline-dot" style="background:#C98A41"></div>
            <div class="timeline-content">
              <div class="timeline-status"><?= htmlspecialchars($ev['status']) ?></div>
              <div class="timeline-time"><?= htmlspecialchars($ev['timestamp']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>
