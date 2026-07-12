<?php
/** AUTHOR PORTAL — login */
require __DIR__ . '/auth.php';

$error  = '';
$banned = isset($_GET['banned']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_SESSION['fails'] ?? 0) >= 8) {
    $error = 'Too many attempts — close the browser and try again later.';
  } else {
    $q = db()->prepare("SELECT id, pass_hash, status FROM authors WHERE username = ?");
    $q->execute([trim($_POST['username'] ?? '')]);
    $a = $q->fetch();
    if ($a && password_verify($_POST['password'] ?? '', $a['pass_hash'])) {
      if ($a['status'] !== 'active') {
        $error = 'This account has been suspended. Contact Kūgeria Ltd if you believe this is a mistake.';
      } else {
        session_regenerate_id(true);
        $_SESSION['author_id'] = $a['id'];
        unset($_SESSION['fails']);
        header('Location: index.php');
        exit;
      }
    } else {
      $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
      $error = 'Wrong username or password.';
    }
  }
}

author_head('Login');
?>
<div style="min-height:90vh;display:flex;align-items:center;justify-content:center;padding:20px">
  <div class="card" style="max-width:380px;width:100%">
    <h1 style="margin-bottom:4px">Author Sign-In</h1>
    <p style="font-size:13px;color:#6b7280;margin-bottom:10px">Kūgeria Insights — guest author portal.</p>
    <?php if ($banned): ?><div class="msg msg-err">Your session ended because this account is suspended.</div><?php endif; ?>
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
