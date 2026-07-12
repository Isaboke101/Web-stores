<?php
/** KŪGERIA INSIGHTS — public blog hub */
require __DIR__ . '/config.php';

$posts = db()->query(
  "SELECT p.slug, p.title, p.category, p.excerpt, p.cover, p.published_at,
          a.name AS a_name
   FROM posts p LEFT JOIN authors a ON a.id = p.author_id
   WHERE p.status = 'published'
   ORDER BY p.published_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Insights — Web, AI &amp; Digital Growth in Kenya | Kūgeria Ltd</title>
  <meta name="description" content="Practical guides on web development, M-Pesa integration, AI, and digital growth for Kenyan businesses — from Kūgeria Ltd in Nairobi.">
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#052F5F">
  <link rel="canonical" href="<?= SITE_URL ?>/">
  <link rel="alternate" type="application/rss+xml" title="<?= esc(SITE_NAME) ?>" href="<?= SITE_URL ?>/rss.php">
  <link rel="icon" href="../images/logo_small.svg" type="image/svg+xml">
  <link rel="icon" type="image/png" href="../images/logo_small.png">
  <link rel="apple-touch-icon" href="../images/logo_small.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a href="../index.html"><img src="../images/logo.svg" alt="Kūgeria Ltd"></a>
    <div class="nav-links">
      <a href="../index.html">Home</a>
      <a href="../schedule.html">Schedule</a>
      <a href="rss.php" class="rss" title="Subscribe via RSS">RSS ⚡</a>
    </div>
  </div>
</nav>

<div class="hub-hero">
  <div class="hub-label">Kūgeria Insights</div>
  <h1>Ideas Worth <em>Building On</em></h1>
  <p class="hub-sub">Practical guides on web development, M-Pesa integration, AI, and digital growth — written for Kenyan business owners, not developers.</p>
</div>

<div class="wrap">

  <!-- ═══ AD SLOT: hub top banner ═══ -->
  <?= render_ad('hub_top') ?>

  <?php if (!$posts): ?>
    <p style="text-align:center;color:var(--gray-500);padding:40px 0">First articles coming soon — subscribe below so you don't miss them.</p>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($posts as $p): ?>
    <a href="post.php?slug=<?= esc($p['slug']) ?>" class="post-card">
      <div class="post-cover">
        <?php if ($p['cover']): ?><img src="<?= esc($p['cover']) ?>" alt="<?= esc($p['title']) ?>" loading="lazy">
        <?php else: ?>✦<?php endif; ?>
      </div>
      <div class="post-body">
        <div class="post-cat"><?= esc($p['category']) ?></div>
        <div class="post-title"><?= esc($p['title']) ?></div>
        <p class="post-desc"><?= esc($p['excerpt']) ?></p>
        <div class="post-meta">
          <span><?= esc($p['a_name'] ?: 'Isaac Oonge') ?> · <?= nice_date($p['published_at']) ?></span>
          <span class="post-read">Read →</span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ═══ SUBSCRIBE ═══ -->
  <div class="sub-box" id="subscribe">
    <h3>Never Miss an <em>Insight</em></h3>
    <p>New articles delivered to your inbox. No spam — unsubscribe any time.</p>
    <form class="sub-form" id="subForm">
      <input type="email" id="subEmail" placeholder="you@business.co.ke" required>
      <input type="text" id="subHp" style="display:none" tabindex="-1" autocomplete="off">
      <button type="submit">Subscribe</button>
    </form>
    <div class="sub-msg" id="subMsg"></div>
  </div>

</div>

<footer class="footer">
  © <?= date('Y') ?> Kūgeria Ltd · <a href="../index.html">Home</a> · <a href="../schedule.html">Schedule</a> · <a href="../privacy.html">Privacy</a> · <a href="rss.php">RSS Feed</a>
</footer>

<script>
document.getElementById('subForm').addEventListener('submit', async function(e){
  e.preventDefault();
  var msg = document.getElementById('subMsg');
  var email = document.getElementById('subEmail').value.trim();
  var hp = document.getElementById('subHp').value;
  msg.textContent = 'Subscribing...';
  try {
    var r = await fetch('subscribe.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'email=' + encodeURIComponent(email) + '&website=' + encodeURIComponent(hp)
    });
    var d = await r.json();
    msg.textContent = d.message;
    if (d.ok) document.getElementById('subForm').reset();
  } catch (err) {
    msg.textContent = 'Something went wrong — please try again.';
  }
});
</script>
</body>
</html>