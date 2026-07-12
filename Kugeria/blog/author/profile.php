<?php
/** AUTHOR PORTAL — profile: name, profession, LinkedIn, photo + visibility, password */
require __DIR__ . '/auth.php';
$me = require_author();

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  a_csrf_check();

  // ── Profile details ──
  if (isset($_POST['save_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $prof = trim($_POST['profession'] ?? '');
    $li   = trim($_POST['linkedin'] ?? '');
    $show = !empty($_POST['show_photo']) ? 1 : 0;
    if (!$name || !$prof) {
      $err = 'Name and profession are required — they appear publicly on your posts for accountability.';
    } elseif ($li && !preg_match('~^https://(www\.)?linkedin\.com/~i', $li)) {
      $err = 'LinkedIn URL must start with https://linkedin.com/ or https://www.linkedin.com/';
    } else {
      db()->prepare("UPDATE authors SET name=?, profession=?, linkedin=?, show_photo=? WHERE id=?")
         ->execute([$name, $prof, $li, $show, $me['id']]);
      $msg = 'Profile saved.';
      $me = require_author(); // refresh
    }
  }

  // ── Profile photo upload ──
  if (isset($_POST['save_photo']) && !empty($_FILES['photo']['name'])) {
    $f = $_FILES['photo'];
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime = $f['error']===UPLOAD_ERR_OK ? mime_content_type($f['tmp_name']) : '';
    if (!isset($allowed[$mime])) {
      $err = 'Photo must be JPG, PNG, or WEBP.';
    } elseif ($f['size'] > 5*1024*1024) {
      $err = 'Photo too large (max 5MB).';
    } else {
      $name = 'author-' . $me['id'] . '-' . time() . '.' . $allowed[$mime];
      if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
      if (move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) {
        db()->prepare("UPDATE authors SET photo=? WHERE id=?")->execute([UPLOAD_URL.$name, $me['id']]);
        $msg = 'Photo updated.';
        $me = require_author();
      } else $err = 'Could not save photo — check uploads/ permissions.';
    }
  }

  // ── Password change ──
  if (isset($_POST['save_password'])) {
    $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? '';
    if (!password_verify($cur, $me['pass_hash'])) $err = 'Current password is wrong.';
    elseif (strlen($new) < 8) $err = 'New password must be 8+ characters.';
    else {
      db()->prepare("UPDATE authors SET pass_hash=? WHERE id=?")
         ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
      $msg = 'Password changed.';
    }
  }
}

author_head('My Profile');
author_topbar($me['name']);
?>
<div class="wrap">
  <h1>My Public Profile</h1>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-err"><?= esc($err) ?></div><?php endif; ?>

  <div class="card">
    <strong>Public Details</strong>
    <p style="font-size:12.5px;color:#6b7280;margin-top:4px">Your <b>name and profession are always shown</b> on your published posts — this is the accountability policy of Kūgeria Insights. LinkedIn and photo are optional.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= a_csrf_token() ?>">
      <input type="hidden" name="save_profile" value="1">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div><label>Full name (public) *</label><input type="text" name="name" value="<?= esc($me['name']) ?>" required></div>
        <div><label>Profession (public) *</label><input type="text" name="profession" value="<?= esc($me['profession']) ?>" required></div>
      </div>
      <label>LinkedIn profile URL (optional, shown publicly)</label>
      <input type="url" name="linkedin" value="<?= esc($me['linkedin']) ?>" placeholder="https://linkedin.com/in/your-handle">
      <label style="display:flex;align-items:center;gap:8px;margin-top:16px;font-weight:500">
        <input type="checkbox" name="show_photo" <?= $me['show_photo'] ? 'checked' : '' ?>>
        Display my profile photo publicly on my posts
      </label>
      <button class="btn" style="margin-top:16px">Save Profile</button>
    </form>
  </div>

  <div class="card">
    <strong>Profile Photo</strong>
    <div style="display:flex;align-items:center;gap:18px;margin-top:12px;flex-wrap:wrap">
      <?php if ($me['photo']): ?>
        <img src="../<?= esc($me['photo']) ?>" alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #C5A059">
      <?php else: ?>
        <div style="width:72px;height:72px;border-radius:50%;background:#052F5F;color:#C5A059;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700"><?= esc(initial($me['name'])) ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" style="flex:1;min-width:240px">
        <input type="hidden" name="csrf" value="<?= a_csrf_token() ?>">
        <input type="hidden" name="save_photo" value="1">
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required style="font-size:13px">
        <button class="btn btn-navy" style="margin-top:10px">Upload Photo</button>
        <p style="font-size:11.5px;color:#9ca3af;margin-top:6px">Square headshot works best (e.g. 400×400px). Max 5MB.</p>
      </form>
    </div>
  </div>

  <div class="card">
    <strong>Change Password</strong>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= a_csrf_token() ?>">
      <input type="hidden" name="save_password" value="1">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div><label>Current password</label><input type="password" name="current" required></div>
        <div><label>New password (8+)</label><input type="password" name="new" required minlength="8"></div>
      </div>
      <button class="btn btn-ghost" style="margin-top:16px">Change Password</button>
    </form>
  </div>
</div>
</body></html>
