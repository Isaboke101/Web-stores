<?php
/**
 * admin/login.php — Clarice's admin login page
 *
 * Uses a separate session name ('injili_admin') so it is completely
 * isolated from customer sessions. A customer session can never
 * grant admin access.
 *
 * Default credentials (seeded in schema.sql):
 *   Email: clarice@injiliapparel.com
 *   Password: admin1234  ← CHANGE THIS before going live
 */

session_name('injili_admin');
session_start();

require_once __DIR__ . '/../config/db.php';

/* If already logged in, redirect to dashboard */
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, email, password FROM admins WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            /* Regenerate session ID to prevent fixation */
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Injili Apparel</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--navy:#1C2329;--gold:#C98A41;--cream:#F8F5F0;--stone:#EDEDE9;--muted:#888882;--fd:'Cormorant Garamond',Georgia,serif;--fb:'DM Sans',system-ui,sans-serif}
body{background:var(--navy);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:var(--fb)}
.login-card{background:#fff;border-radius:4px;padding:3rem;width:100%;max-width:400px}
.brand{text-align:center;margin-bottom:2.5rem}
.brand-word{font-family:var(--fd);font-size:1.8rem;font-weight:500;color:var(--navy);display:block;letter-spacing:.05em}
.brand-sub{font-size:.6rem;letter-spacing:.4em;text-transform:uppercase;color:var(--gold)}
.admin-label{font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);text-align:center;display:block;margin-bottom:2rem;border-top:1px solid var(--stone);padding-top:.75rem}
.form-group{margin-bottom:1.25rem}
.form-label{display:block;font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem}
.form-input{width:100%;padding:.85rem 1rem;border:1px solid var(--stone);background:var(--cream);color:var(--navy);font-size:.88rem;border-radius:2px;outline:none;font-family:var(--fb);transition:border-color .2s}
.form-input:focus{border-color:var(--gold);background:#fff}
.btn-login{width:100%;background:var(--navy);color:#fff;border:none;padding:1rem;font-size:.78rem;letter-spacing:.15em;text-transform:uppercase;font-weight:500;cursor:pointer;border-radius:2px;font-family:var(--fb);transition:background .2s}
.btn-login:hover{background:#252D35}
.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:.75rem 1rem;border-radius:2px;font-size:.82rem;margin-bottom:1.25rem}
</style>
</head>
<body>
<div class="login-card">
  <div class="brand">
    <span class="brand-word">injili</span>
    <span class="brand-sub">Apparel</span>
  </div>
  <span class="admin-label">Admin Portal</span>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input class="form-input" type="email" name="email" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-input" type="password" name="password" required>
    </div>
    <button class="btn-login" type="submit">Sign In to Dashboard</button>
  </form>
</div>
</body>
</html>
