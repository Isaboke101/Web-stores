<?php
/** ADMIN — subscriber list with CSV export and removal */
require __DIR__ . '/auth.php';
require_admin();

// CSV export
if (isset($_GET['export'])) {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="kugeria-subscribers-' . date('Y-m-d') . '.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Email', 'Subscribed On']);
  foreach (db()->query("SELECT email, created_at FROM subscribers ORDER BY created_at DESC") as $r) {
    fputcsv($out, [$r['email'], $r['created_at']]);
  }
  exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
  csrf_check();
  db()->prepare("DELETE FROM subscribers WHERE id = ?")->execute([(int)$_POST['remove_id']]);
  $msg = 'Subscriber removed.';
}

$subs = db()->query("SELECT * FROM subscribers ORDER BY created_at DESC")->fetchAll();

admin_head('Subscribers');
admin_topbar();
?>
<div class="wrap">
  <h1>Subscribers (<?= count($subs) ?>)</h1>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>
  <div class="card">
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
      <a href="?export=1" class="btn btn-navy">⬇ Export CSV</a>
    </div>
    <?php if (!$subs): ?>
      <p style="color:#6b7280;font-size:14px">No subscribers yet.</p>
    <?php else: ?>
    <table>
      <tr><th>Email</th><th>Subscribed</th><th></th></tr>
      <?php foreach ($subs as $s): ?>
      <tr>
        <td data-label="Email"><?= esc($s['email']) ?></td>
        <td data-label="Subscribed"><?= nice_date($s['created_at']) ?></td>
        <td>
          <form method="post" style="display:inline" onsubmit="return confirm('Remove this subscriber?')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="remove_id" value="<?= $s['id'] ?>">
            <button style="background:none;border:none;color:#b91c1c;font-size:12px;cursor:pointer;font-weight:600">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>