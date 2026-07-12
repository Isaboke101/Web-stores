<?php
/** ADMIN — login */
require __DIR__ . '/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // simple brute-force damper
  if (($_SESSION['fails'] ?? 0) >= 8) {
    $error = 'Too many attempts — close the browser and try again later.';
  } else {
    $q = db()->prepare("SELECT id, pass_hash FROM admin_users WHERE username = ?");
    $q->execute([trim($_POST['username'] ?? '')]);
    $u = $q->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['pass_hash'])) {
      session_regenerate_id(true);
      $_SESSION['admin_id'] = $u['id'];
      unset($_SESSION['fails']);
      header('Location: index.php');
      exit;
    }
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    $error = 'Wrong username or password.';
  }
}

admin_head('Login');
?>
<div style="min-height:90vh;display:flex;align-items:center;justify-content:center;padding:20px">
  <div class="card" style="max-width:380px;width:100%">
    <h1 style="margin-bottom:4px">Kūgeria Admin</h1>
    <p style="font-size:13px;color:#6b7280;margin-bottom:10px">Sign in to manage your blog.</p>
    <?php if ($error): ?><div class="msg msg-err"><?= esc($error) ?></div><?php endif; ?>
    <form method="post">
      <label>Username</label>
      <input type="text" name="username" required autofocus autocomplete="username">
      <label>Password</label>
      <input type="password" name="password" required autocomplete="current-password">
      <button class="btn" style="margin-top:18px;width:100%">Sign In</button>
    </form>
  </div>
</div>
</body></html>
