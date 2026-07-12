<?php
/** ADMIN — ads manager: book, toggle, and remove advertiser placements */
require __DIR__ . '/auth.php';
require_admin();

$msg = ''; $err = '';
$SLOTS = [
  'hub_top'     => 'Blog Hub — Top Banner (wide, above the article grid)',
  'post_inline' => 'Inside Articles — after 3rd paragraph',
  'sidebar'     => 'Below Articles — before subscribe box',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  if (isset($_POST['add'])) {
    $slot = $_POST['slot'] ?? '';
    $adv  = trim($_POST['advertiser'] ?? '');
    $img  = trim($_POST['image'] ?? '');
    $link = trim($_POST['link'] ?? '');
    if (!isset($SLOTS[$slot]) || !$adv || !$img || !$link) {
      $err = 'All fields are required (upload the banner in the post editor, then paste its uploads/ path here).';
    } else {
      db()->prepare("INSERT INTO ads (slot, advertiser, image, link, starts, ends, active)
                     VALUES (?,?,?,?,?,?,1)")
         ->execute([$slot, $adv, $img, $link,
                    $_POST['starts'] ?: null, $_POST['ends'] ?: null]);
      $msg = 'Ad placement added and live.';
    }
  }
  if (isset($_POST['toggle_id'])) {
    db()->prepare("UPDATE ads SET active = 1 - active WHERE id = ?")->execute([(int)$_POST['toggle_id']]);
    $msg = 'Ad status toggled.';
  }
  if (isset($_POST['remove_id'])) {
    db()->prepare("DELETE FROM ads WHERE id = ?")->execute([(int)$_POST['remove_id']]);
    $msg = 'Ad removed.';
  }
}

$ads = db()->query("SELECT * FROM ads ORDER BY id DESC")->fetchAll();

admin_head('Ads');
admin_topbar();
?>
<div class="wrap">
  <h1>Ad Placements</h1>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-err"><?= esc($err) ?></div><?php endif; ?>

  <div class="card">
    <strong>Book a New Placement</strong>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="add" value="1">
      <label>Slot</label>
      <select name="slot" required>
        <?php foreach ($SLOTS as $k => $label): ?>
          <option value="<?= $k ?>"><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div>
          <label>Advertiser name</label>
          <input type="text" name="advertiser" required placeholder="e.g. Acme Hosting Ltd">
        </div>
        <div>
          <label>Banner image path</label>
          <input type="text" name="image" required placeholder="uploads/acme-banner.jpg">
        </div>
      </div>
      <label>Destination link</label>
      <input type="url" name="link" required placeholder="https://advertiser-website.com">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div><label>Start date (optional)</label><input type="date" name="starts"></div>
        <div><label>End date (optional)</label><input type="date" name="ends"></div>
      </div>
      <button class="btn" style="margin-top:16px">Add Placement</button>
      <p style="font-size:12px;color:#6b7280;margin-top:10px">
        💡 Recommended banner sizes: Hub Top &amp; Below Articles — 1200×200px. Inside Articles — 720×200px.
        Upload the banner via the 📷 button in the post editor, then paste the <code>uploads/…</code> path here.
      </p>
    </form>
  </div>

  <div class="card">
    <strong>Current Placements</strong>
    <?php if (!$ads): ?>
      <p style="color:#6b7280;font-size:14px;margin-top:8px">No ads booked — empty slots automatically show a branded "Advertise here" placeholder.</p>
    <?php else: ?>
    <table style="margin-top:10px">
      <tr><th>Advertiser</th><th>Slot</th><th>Runs</th><th>Status</th><th></th></tr>
      <?php foreach ($ads as $a): ?>
      <tr>
        <td data-label="Advertiser"><a href="<?= esc($a['link']) ?>" target="_blank"><?= esc($a['advertiser']) ?></a></td>
        <td data-label="Slot" style="font-size:12px"><?= esc($a['slot']) ?></td>
        <td data-label="Runs" style="font-size:12px"><?= $a['starts'] ? nice_date($a['starts']) : 'now' ?> → <?= $a['ends'] ? nice_date($a['ends']) : 'until stopped' ?></td>
        <td data-label="Status"><span class="badge <?= $a['active'] ? 'b-pub' : 'b-draft' ?>"><?= $a['active'] ? 'live' : 'paused' ?></span></td>
        <td style="white-space:nowrap">
          <form method="post" style="display:inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="toggle_id" value="<?= $a['id'] ?>">
            <button style="background:none;border:none;color:#052F5F;font-size:12px;cursor:pointer;font-weight:600"><?= $a['active'] ? 'Pause' : 'Resume' ?></button>
          </form> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Remove this ad?')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="remove_id" value="<?= $a['id'] ?>">
            <button style="background:none;border:none;color:#b91c1c;font-size:12px;cursor:pointer;font-weight:600">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body></html>