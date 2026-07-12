<?php
/** KŪGERIA INSIGHTS — one-click unsubscribe (token-verified) */
require __DIR__ . '/config.php';

$email = strtolower(trim($_GET['email'] ?? ''));
$token = $_GET['token'] ?? '';
$ok = false; $already = false;

if (filter_var($email, FILTER_VALIDATE_EMAIL) && hash_equals(unsub_token($email), $token)) {
  $q = db()->prepare("DELETE FROM subscribers WHERE email = ?");
  $q->execute([$email]);
  $ok = true;
  $already = ($q->rowCount() === 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Unsubscribe — Kūgeria Insights</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#FAFAFA;min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:20px;color:#212121}
  .box{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:40px 32px;max-width:420px;width:100%;text-align:center;box-shadow:0 10px 40px rgba(5,47,95,.1)}
  .ic{font-size:44px;margin-bottom:14px}
  h1{font-family:'Cormorant Garamond',serif;font-size:26px;color:#052F5F;margin:0 0 10px}
  p{font-size:14px;color:#6b7280;line-height:1.7;margin:0 0 22px}
  a.btn{display:inline-block;padding:12px 24px;border-radius:9px;background:#C5A059;color:#212121;font-weight:700;font-size:13.5px;text-decoration:none}
  a.btn:hover{background:#b8903e}
</style>
</head>
<body>
<div class="box">
<?php if ($ok && !$already): ?>
  <div class="ic">👋</div>
  <h1>You're Unsubscribed</h1>
  <p><?= esc($email) ?> will no longer receive Kūgeria Insights emails. Changed your mind? You can re-subscribe on the blog any time.</p>
<?php elseif ($ok && $already): ?>
  <div class="ic">✅</div>
  <h1>Already Unsubscribed</h1>
  <p>That address wasn't on the list — you're all clear.</p>
<?php else: ?>
  <div class="ic">🔗</div>
  <h1>Invalid Link</h1>
  <p>This unsubscribe link is incomplete or expired. Please use the link exactly as it appears in one of our emails.</p>
<?php endif; ?>
  <a class="btn" href="index.php">← Back to Insights</a>
</div>
</body>
</html>
