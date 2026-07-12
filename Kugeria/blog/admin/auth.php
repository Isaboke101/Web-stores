<?php
/** ADMIN — shared auth guard. Include at the top of every admin page. */
require_once dirname(__DIR__) . '/config.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

/** Require a logged-in admin, else bounce to login. */
function require_admin(): void {
  if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
  }
}

/** CSRF token helpers */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
  return $_SESSION['csrf'];
}
function csrf_check(): void {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '_')) {
    http_response_code(403);
    die('Invalid CSRF token — go back, refresh, and try again.');
  }
}

/** Shared admin page chrome */
function admin_head(string $title): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title><?= esc($title) ?> — Kūgeria Admin</title>
<style>
  :root{--bg:#FAFAFA;--green:#004B23;--navy:#052F5F;--gold:#C5A059;--charcoal:#212121;--gray:#e5e7eb}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--charcoal);line-height:1.6}
  a{color:var(--navy);text-decoration:none;font-weight:600}
  a:hover{color:var(--gold)}
  .topbar{background:var(--navy);color:#fff;padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:56px}
  .topbar .brand{font-weight:700;font-size:15px;color:#fff}
  .topbar .brand span{color:var(--gold)}
  .topbar nav{display:flex;gap:18px}
  .topbar nav a{color:rgba(255,255,255,.75);font-size:13px;font-weight:500}
  .topbar nav a:hover{color:var(--gold)}
  .ham{display:none;flex-direction:column;justify-content:center;gap:5px;width:38px;height:38px;background:none;border:none;cursor:pointer;padding:6px}
  .ham span{height:2px;background:#fff;border-radius:2px;transition:all .28s ease}
  .ham.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
  .ham.open span:nth-child(2){opacity:0}
  .ham.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
  @media(max-width:760px){
    .topbar{position:relative}
    .ham{display:flex}
    .topbar nav{
      display:none;position:absolute;top:56px;left:0;right:0;z-index:200;
      flex-direction:column;gap:0;background:var(--navy);
      border-top:1px solid rgba(255,255,255,.12);
      box-shadow:0 14px 30px rgba(0,0,0,.25);
    }
    .topbar nav.open{display:flex}
    .topbar nav a{
      padding:14px 20px;font-size:14px;
      border-bottom:1px solid rgba(255,255,255,.08);
    }
    .topbar nav a:last-child{border-bottom:none}
  }
  @media(max-width:760px){
    .card{padding:16px}
    table thead{display:none}
    table, tbody, tr, td{display:block;width:100%}
    tr{border:1px solid var(--gray);border-radius:10px;margin-bottom:12px;background:#fff;overflow:hidden;padding:4px 0}
    td{border-bottom:none;padding:7px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    td::before{content:attr(data-label);font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af;text-align:left;flex-shrink:0}
    td:not([data-label])::before,td[data-label=""]::before{display:none}
    td:not([data-label]){justify-content:flex-end}
  }
  .wrap{max-width:960px;margin:0 auto;padding:28px 20px 70px}
  h1{font-size:22px;color:var(--navy);margin-bottom:18px}
  .card{background:#fff;border:1px solid var(--gray);border-radius:12px;padding:22px;margin-bottom:18px}
  table{width:100%;border-collapse:collapse;font-size:13.5px}
  th{text-align:left;padding:10px 12px;background:#f3f4f6;font-size:11.5px;text-transform:uppercase;letter-spacing:.06em;color:#6b7280}
  td{padding:10px 12px;border-bottom:1px solid var(--gray);vertical-align:middle}
  label{display:block;font-size:12.5px;font-weight:600;margin:14px 0 5px}
  input[type=text],input[type=password],input[type=email],input[type=url],input[type=date],select,textarea{
    width:100%;padding:10px 12px;border:1.5px solid var(--gray);border-radius:8px;font-size:14px;font-family:inherit}
  textarea{resize:vertical}
  .btn{display:inline-block;padding:10px 18px;border:none;border-radius:8px;background:var(--gold);color:var(--charcoal);font-weight:700;font-size:13.5px;cursor:pointer}
  .btn:hover{background:#b8903e}
  .btn-navy{background:var(--navy);color:#fff}
  .btn-navy:hover{background:#0a4080}
  .btn-ghost{background:transparent;border:1.5px solid var(--gray);color:var(--charcoal)}
  .btn-danger{background:#fee;color:#b91c1c;border:1.5px solid #fca5a5}
  .badge{display:inline-block;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700}
  .b-pub{background:rgba(0,75,35,.1);color:var(--green)}
  .b-draft{background:rgba(33,33,33,.08);color:#555}
  .msg{padding:12px 16px;border-radius:0 8px 8px 0;font-size:13.5px;margin-bottom:16px}
  .msg-ok{background:#ecfdf5;border-left:3px solid var(--green);color:#065f46}
  .msg-err{background:#fee;border-left:3px solid #e53e3e;color:#b91c1c}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px}
  .stat{background:#fff;border:1px solid var(--gray);border-radius:12px;padding:18px;text-align:center}
  .stat .n{font-size:28px;font-weight:800;color:var(--navy)}
  .stat .l{font-size:11.5px;color:#6b7280;text-transform:uppercase;letter-spacing:.06em}
</style>
</head>
<body>
<?php }

function admin_topbar(): void { ?>
<div class="topbar">
  <span class="brand">Kūgeria <span>Admin</span></span>
  <button class="ham" id="pnavHam" aria-label="Menu" aria-expanded="false" onclick="pnavToggle()"><span></span><span></span><span></span></button>
  <nav id="pnavMenu">
    <a href="index.php">Posts</a>
    <a href="editor.php">+ New Post</a>
    <a href="authors.php">Authors</a>
    <a href="ads.php">Ads</a>
    <a href="subscribers.php">Subscribers</a>
    <a href="../index.php" target="_blank">View Blog ↗</a>
    <a href="logout.php">Logout</a>
  </nav>
</div>
<script>
function pnavToggle(){
  var m = document.getElementById('pnavMenu'), h = document.getElementById('pnavHam');
  var open = m.classList.toggle('open');
  h.classList.toggle('open', open);
  h.setAttribute('aria-expanded', open ? 'true' : 'false');
}
</script>
<?php }