<!DOCTYPE html>
<?php
/**
 * track.php — Public order tracking page
 *
 * Accessible at: /injili/track.php?order=INJ-2025-0001
 * No login required — accessible via the link in SMS and email.
 *
 * Fetches order data, status history, and PM tracking events.
 * Renders a visual timeline of the parcel's journey.
 */

require_once __DIR__ . '/config/db.php';

$db          = getDB();
$orderNumber = trim($_GET['order'] ?? '');
$order       = null;
$items       = [];
$history     = [];
$tracking    = null;
$error       = '';

if ($orderNumber) {
    $stmt = $db->prepare('SELECT * FROM orders WHERE order_number = :onum');
    $stmt->execute([':onum' => $orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $db->prepare(
            'SELECT oi.product_name, oi.color_name, oi.size, oi.quantity, oi.line_total_ksh
             FROM order_items oi WHERE oi.order_id = :oid'
        );
        $itemStmt->execute([':oid' => $order['id']]);
        $items = $itemStmt->fetchAll();

        $histStmt = $db->prepare(
            'SELECT status, note, created_at FROM order_status_history
             WHERE order_id = :oid ORDER BY id ASC'
        );
        $histStmt->execute([':oid' => $order['id']]);
        $history = $histStmt->fetchAll();

        $trkStmt = $db->prepare('SELECT * FROM delivery_tracking WHERE order_id = :oid');
        $trkStmt->execute([':oid' => $order['id']]);
        $tracking = $trkStmt->fetch();
        if ($tracking) {
            $tracking['pm_events'] = json_decode($tracking['pm_events'], true) ?? [];
        }
    } else {
        $error = 'Order not found. Please check your order number and try again.';
    }
}

/* All possible statuses in display order */
$allStatuses = [
    'received'           => ['label' => 'Order Received',              'icon' => 'fa-bag-shopping'],
    'in_production'      => ['label' => 'In Production',               'icon' => 'fa-shirt'],
    'ready_for_dispatch' => ['label' => 'Ready for Dispatch',          'icon' => 'fa-box'],
    'handed_to_pm'       => ['label' => 'Handed to Pickup Mtaani',     'icon' => 'fa-truck'],
    'in_transit'         => ['label' => 'In Transit',                  'icon' => 'fa-route'],
    'out_for_delivery'   => ['label' => 'Out for Delivery / Collection','icon' => 'fa-person-walking'],
    'delivered'          => ['label' => 'Delivered / Collected',       'icon' => 'fa-circle-check'],
];

/* Map completed statuses from history for display */
$completedStatuses = array_column($history, 'status');
$currentStatus     = $order['status'] ?? '';
?>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Your Order — Injili Apparel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--navy:#1C2329;--gold:#C98A41;--gold-dk:#a87232;--cream:#F8F5F0;--stone:#EDEDE9;--white:#FFFFFF;--muted:#888882;--fd:'Cormorant Garamond',Georgia,serif;--fb:'DM Sans',system-ui,sans-serif}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{background:var(--cream);color:var(--navy);font-family:var(--fb);font-weight:300;min-height:100vh}
a{text-decoration:none;color:inherit}

/* Navbar */
.nav{background:var(--navy);padding:0 2.5rem;height:64px;display:flex;align-items:center;justify-content:space-between}
.nav-logo{font-family:var(--fd);font-size:1.4rem;color:var(--white);letter-spacing:.05em}
.nav-logo span{display:block;font-size:.52rem;letter-spacing:.35em;text-transform:uppercase;color:var(--gold);margin-top:1px}
.nav-back{font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:.4rem;transition:color .2s}
.nav-back:hover{color:var(--white)}

/* Page wrapper */
.page-wrap{max-width:700px;margin:0 auto;padding:3rem 1.5rem 5rem}

/* Lookup form */
.lookup-card{background:var(--white);border-radius:4px;padding:2.5rem;margin-bottom:2rem;border:1px solid var(--stone)}
.lookup-title{font-family:var(--fd);font-size:1.8rem;font-weight:300;margin-bottom:.35rem}
.lookup-sub{font-size:.85rem;color:var(--muted);margin-bottom:1.75rem;line-height:1.6}
.lookup-form{display:flex;gap:.75rem}
.lookup-input{flex:1;padding:.85rem 1rem;border:1px solid var(--stone);background:var(--cream);color:var(--navy);font-size:.88rem;border-radius:2px;outline:none;font-family:var(--fb);transition:border-color .2s}
.lookup-input:focus{border-color:var(--gold);background:var(--white)}
.lookup-btn{background:var(--navy);color:var(--white);border:none;padding:.85rem 1.5rem;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;font-weight:500;cursor:pointer;border-radius:2px;font-family:var(--fb);white-space:nowrap}
.lookup-btn:hover{background:#252D35}
.error-msg{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:.8rem 1rem;border-radius:2px;font-size:.83rem;margin-top:1rem}

/* Order summary header */
.order-header{background:var(--navy);border-radius:4px;padding:2rem;margin-bottom:1.5rem;position:relative;overflow:hidden}
.order-header::before{content:'';position:absolute;top:-40px;right:-40px;width:150px;height:150px;border-radius:50%;border:1px solid rgba(201,138,65,.1)}
.order-number{font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:.35rem}
.order-customer{font-family:var(--fd);font-size:1.5rem;font-weight:300;color:var(--white);margin-bottom:.15rem}
.order-meta{font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.6}
.order-total{position:absolute;top:2rem;right:2rem;text-align:right}
.order-total-label{font-size:.62rem;letter-spacing:.15em;text-transform:uppercase;color:rgba(255,255,255,.3)}
.order-total-val{font-family:var(--fd);font-size:1.6rem;font-weight:300;color:var(--white)}
.order-total-pay{font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;margin-top:.2rem}
.pay-badge-paid{color:#4CAF50}.pay-badge-pending{color:var(--gold)}.pay-badge-failed{color:#e74c3c}

/* Items */
.section-title{font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);margin-bottom:.75rem;display:block}
.items-card{background:var(--white);border-radius:4px;border:1px solid var(--stone);margin-bottom:1.5rem}
.item-row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 1.25rem;border-bottom:1px solid var(--stone);font-size:.85rem}
.item-row:last-child{border-bottom:none}
.item-name{font-weight:400}
.item-sub{font-size:.72rem;color:var(--muted);margin-top:.1rem}
.item-price{font-weight:500;flex-shrink:0}

/* Status timeline */
.timeline-card{background:var(--white);border-radius:4px;border:1px solid var(--stone);margin-bottom:1.5rem;overflow:hidden}
.timeline-list{padding:1.5rem}
.tl-item{display:flex;gap:1.1rem;margin-bottom:1.5rem;position:relative}
.tl-item:last-child{margin-bottom:0}
.tl-line{position:absolute;left:15px;top:28px;bottom:-20px;width:1px;background:var(--stone)}
.tl-item:last-child .tl-line{display:none}
.tl-icon-wrap{width:32px;height:32px;border-radius:50%;border:2px solid var(--stone);background:var(--white);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.75rem;color:var(--muted);transition:all .3s;z-index:1}
.tl-item.done .tl-icon-wrap{background:var(--navy);border-color:var(--navy);color:var(--white)}
.tl-item.current .tl-icon-wrap{background:var(--gold);border-color:var(--gold);color:var(--white)}
.tl-body{flex:1;padding-top:.3rem}
.tl-label{font-size:.88rem;font-weight:400;color:var(--muted);margin-bottom:.15rem}
.tl-item.done .tl-label,.tl-item.current .tl-label{color:var(--navy);font-weight:500}
.tl-note{font-size:.78rem;color:var(--muted);margin-bottom:.15rem}
.tl-time{font-size:.68rem;color:rgba(28,35,41,.4)}

/* PM tracking */
.pm-card{background:var(--navy);border-radius:4px;padding:1.5rem;margin-bottom:1.5rem}
.pm-title{font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:.75rem;display:block}
.pm-ref{font-size:.8rem;color:rgba(255,255,255,.5);margin-bottom:.5rem}
.pm-status{font-family:var(--fd);font-size:1.3rem;font-weight:300;color:var(--white);margin-bottom:1rem}
.pm-event{display:flex;gap:.75rem;margin-bottom:.6rem;font-size:.78rem}
.pm-event-dot{width:8px;height:8px;border-radius:50%;background:var(--gold);flex-shrink:0;margin-top:.25rem}
.pm-event-label{color:rgba(255,255,255,.7)}
.pm-event-time{color:rgba(255,255,255,.3);margin-top:.1rem;font-size:.68rem}

/* Delivery info */
.delivery-card{background:var(--white);border-radius:4px;border:1px solid var(--stone);padding:1.25rem;margin-bottom:1.5rem;font-size:.85rem}
.del-label{font-size:.62rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.25rem;display:block}

/* Contact CTA */
.contact-cta{text-align:center;padding:2rem 1.5rem;background:var(--white);border-radius:4px;border:1px solid var(--stone)}
.contact-cta-title{font-family:var(--fd);font-size:1.25rem;font-weight:300;margin-bottom:.35rem}
.contact-cta-sub{font-size:.82rem;color:var(--muted);margin-bottom:1.25rem}
.btn-wa{display:inline-flex;align-items:center;gap:.5rem;background:#25D366;color:var(--white);padding:.8rem 1.5rem;border-radius:2px;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;font-weight:500;transition:background .2s}
.btn-wa:hover{background:#1ebe5b}

@media(max-width:500px){
  .lookup-form{flex-direction:column}
  .order-total{position:static;text-align:left;margin-top:1rem}
  .order-header{padding:1.5rem}
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="nav">
  <a href="index.html" class="nav-logo">
    injili
    <span>Apparel</span>
  </a>
  <a href="index.html" class="nav-back"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>
</nav>

<div class="page-wrap">

  <!-- Order lookup form (always shown) -->
  <div class="lookup-card">
    <div class="lookup-title">Track Your Order</div>
    <div class="lookup-sub">Enter your order number to see real-time updates on your Injili Apparel delivery.</div>
    <form method="GET" action="" class="lookup-form">
      <input class="lookup-input" type="text" name="order"
             placeholder="e.g. INJ-2025-0001"
             value="<?= htmlspecialchars($orderNumber) ?>">
      <button class="lookup-btn" type="submit">Track</button>
    </form>
    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($order): ?>

  <!-- Order header -->
  <div class="order-header">
    <div class="order-number"><?= htmlspecialchars($order['order_number']) ?></div>
    <div class="order-customer"><?= htmlspecialchars($order['customer_name']) ?></div>
    <div class="order-meta">
      <?= htmlspecialchars($order['customer_email']) ?><br>
      Placed <?= date('d F Y', strtotime($order['created_at'])) ?>
    </div>
    <div class="order-total">
      <div class="order-total-label">Total</div>
      <div class="order-total-val">KSh <?= number_format($order['total_ksh']) ?></div>
      <div class="order-total-pay <?= 'pay-badge-' . $order['payment_status'] ?>">
        <?= ucfirst($order['payment_status']) ?>
      </div>
    </div>
  </div>

  <!-- Items -->
  <span class="section-title">Items in this Order</span>
  <div class="items-card">
    <?php foreach ($items as $item): ?>
    <div class="item-row">
      <div>
        <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
        <div class="item-sub"><?= htmlspecialchars($item['color_name']) ?> / Size <?= htmlspecialchars($item['size']) ?> × <?= $item['quantity'] ?></div>
      </div>
      <div class="item-price">KSh <?= number_format($item['line_total_ksh']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Delivery info -->
  <?php if ($order['delivery_address']): ?>
  <div class="delivery-card">
    <span class="del-label">Delivery Address</span>
    <?= htmlspecialchars($order['delivery_address']) ?>
    <?= $order['delivery_area'] ? ', ' . htmlspecialchars($order['delivery_area']) : '' ?>
    <?= $order['delivery_city'] ? ', ' . htmlspecialchars($order['delivery_city']) : '' ?>
  </div>
  <?php endif; ?>

  <!-- PM tracking widget (if shipment created) -->
  <?php if ($tracking): ?>
  <div class="pm-card">
    <span class="pm-title">Pickup Mtaani Tracking</span>
    <div class="pm-ref">Ref: <?= htmlspecialchars($tracking['pm_reference']) ?></div>
    <div class="pm-status"><?= htmlspecialchars($tracking['pm_status']) ?></div>
    <?php if (!empty($tracking['pm_events'])): ?>
      <?php foreach (array_reverse($tracking['pm_events']) as $ev): ?>
      <div class="pm-event">
        <div class="pm-event-dot"></div>
        <div>
          <div class="pm-event-label"><?= htmlspecialchars($ev['status']) ?></div>
          <div class="pm-event-time"><?= htmlspecialchars($ev['timestamp']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($tracking['agent_name']): ?>
      <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,.08);font-size:.8rem;color:rgba(255,255,255,.5)">
        <i class="fa-solid fa-location-dot" style="color:var(--gold)"></i>
        <?= htmlspecialchars($tracking['agent_name']) ?> — <?= htmlspecialchars($tracking['agent_location']) ?>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Order status timeline -->
  <span class="section-title">Order Status</span>
  <div class="timeline-card">
    <div class="timeline-list">
      <?php
      /* Map history events by status for quick lookup */
      $histByStatus = [];
      foreach ($history as $h) {
          $histByStatus[$h['status']] = $h;
      }

      $statusKeys  = array_keys($allStatuses);
      $currentIdx  = array_search($currentStatus, $statusKeys);

      foreach ($allStatuses as $statusKey => $meta):
          $idx  = array_search($statusKey, $statusKeys);
          $done = in_array($statusKey, $completedStatuses);
          $curr = $statusKey === $currentStatus;
          $cls  = $done ? 'done' : ($curr ? 'current' : '');
          $hist = $histByStatus[$statusKey] ?? null;
      ?>
      <div class="tl-item <?= $cls ?>">
        <div class="tl-line"></div>
        <div class="tl-icon-wrap">
          <i class="fa-solid <?= $meta['icon'] ?>"></i>
        </div>
        <div class="tl-body">
          <div class="tl-label"><?= $meta['label'] ?></div>
          <?php if ($hist && $hist['note']): ?>
            <div class="tl-note"><?= htmlspecialchars($hist['note']) ?></div>
          <?php endif; ?>
          <?php if ($hist): ?>
            <div class="tl-time"><?= date('d M Y, H:i', strtotime($hist['created_at'])) ?></div>
          <?php elseif ($curr): ?>
            <div class="tl-time">Current status</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Contact CTA -->
  <div class="contact-cta">
    <div class="contact-cta-title">Need help with your order?</div>
    <div class="contact-cta-sub">Reach Clarice directly on WhatsApp for any questions about your order.</div>
    <a class="btn-wa" href="https://254716341540/<?= ltrim(ADMIN_PHONE, '+') ?>?text=Hi+Clarice%2C+I+have+a+question+about+my+order+<?= urlencode($order['order_number']) ?>">
      <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
    </a>
  </div>

  <?php endif; ?>
</div>
</body>
</html>
