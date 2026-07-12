<?php
/** KŪGERIA INSIGHTS — single post page */
require __DIR__ . '/config.php';

$slug = $_GET['slug'] ?? '';
$q = db()->prepare(
  "SELECT p.*, a.name AS a_name, a.profession AS a_prof, a.linkedin AS a_linkedin,
          a.photo AS a_photo, a.show_photo AS a_show_photo
   FROM posts p LEFT JOIN authors a ON a.id = p.author_id
   WHERE p.slug = ? AND p.status = 'published'");
$q->execute([$slug]);
$post = $q->fetch();

if (!$post) {
  http_response_code(404);
  header('Location: index.php');
  exit;
}

/* Insert the inline ad after the 3rd paragraph of the body */
$body = $post['body'];
$parts = preg_split('/(<\/p>)/i', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
if (count($parts) > 6) {
  $rebuilt = ''; $pCount = 0; $adDone = false;
  foreach ($parts as $chunk) {
    $rebuilt .= $chunk;
    if (!$adDone && strcasecmp($chunk, '</p>') === 0 && ++$pCount === 3) {
      $rebuilt .= render_ad('post_inline');
      $adDone = true;
    }
  }
  $body = $rebuilt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($post['title']) ?> | Kūgeria Ltd</title>
  <meta name="description" content="<?= esc($post['excerpt']) ?>">
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#052F5F">
  <link rel="canonical" href="<?= SITE_URL ?>/post.php?slug=<?= esc($post['slug']) ?>">
  <link rel="alternate" type="application/rss+xml" title="<?= esc(SITE_NAME) ?>" href="<?= SITE_URL ?>/rss.php">
  <!-- Open Graph for sharing -->
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?= esc($post['title']) ?>">
  <meta property="og:description" content="<?= esc($post['excerpt']) ?>">
  <?php if ($post['cover']): ?><meta property="og:image" content="<?= SITE_URL . '/' . esc($post['cover']) ?>"><?php endif; ?>
  <!-- Article structured data (helps Google News/Discover) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": <?= json_encode($post['title']) ?>,
    "description": <?= json_encode($post['excerpt']) ?>,
    "datePublished": <?= json_encode(date('c', strtotime($post['published_at']))) ?>,
    "author": { "@type": "Person", "name": <?= json_encode($post['a_name'] ?: 'Isaac Oonge') ?> },
    "publisher": { "@type": "Organization", "name": "Kūgeria Ltd", "url": <?= json_encode(HOME_URL) ?> }
    <?= $post['cover'] ? ',"image": ' . json_encode(SITE_URL . '/' . $post['cover']) : '' ?>
  }
  </script>
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
      <a href="index.php">← All Insights</a>
      <a href="../schedule.html">Schedule</a>
    </div>
  </div>
</nav>

<article class="article">
  <div class="cat"><?= esc($post['category']) ?></div>
  <h1><?= esc($post['title']) ?></h1>
  <div class="byline">By <?= esc($post['a_name'] ?: 'Isaac Oonge') ?> · <?= esc($post['a_prof'] ?: 'Kūgeria Ltd') ?> · <?= nice_date($post['published_at']) ?></div>

  <?php if ($post['cover']): ?>
  <div class="article-cover"><img src="<?= esc($post['cover']) ?>" alt="<?= esc($post['title']) ?>"></div>
  <?php endif; ?>

  <div class="article-body">
    <?= $body /* trusted admin-authored HTML */ ?>
  </div>

  <!-- ═══ AUTHOR CARD ═══ -->
  <div class="author-card">
    <?php if ($post['a_name']): ?>
      <?php if ($post['a_photo'] && $post['a_show_photo']): ?>
        <img class="author-photo" src="<?= esc($post['a_photo']) ?>" alt="<?= esc($post['a_name']) ?>">
      <?php else: ?>
        <div class="author-initial"><?= esc(initial($post['a_name'])) ?></div>
      <?php endif; ?>
      <div class="author-info">
        <div class="author-label">Written by</div>
        <div class="author-name"><?= esc($post['a_name']) ?></div>
        <div class="author-prof"><?= esc($post['a_prof']) ?></div>
        <?php if ($post['a_linkedin']): ?>
          <a class="author-li" href="<?= esc($post['a_linkedin']) ?>" target="_blank" rel="noopener">in&nbsp; Connect on LinkedIn →</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="author-initial">K</div>
      <div class="author-info">
        <div class="author-label">Written by</div>
        <div class="author-name">Isaac Oonge</div>
        <div class="author-prof">Founder &amp; Developer · Kūgeria Ltd</div>
      </div>
    <?php endif; ?>
  </div>
</article>

<!-- ═══ AD SLOT: below article ═══ -->
<div style="max-width:720px;margin:0 auto;padding:0 20px">
  <?= render_ad('sidebar') ?>
</div>

<!-- ═══ SUBSCRIBE ═══ -->
<div style="padding:0 20px">
  <div class="sub-box">
    <h3>Enjoyed This? Get the <em>Next One</em></h3>
    <p>New articles straight to your inbox — no spam, unsubscribe any time.</p>
    <form class="sub-form" id="subForm">
      <input type="email" id="subEmail" placeholder="you@business.co.ke" required>
      <input type="text" id="subHp" style="display:none" tabindex="-1" autocomplete="off">
      <button type="submit">Subscribe</button>
    </form>
    <div class="sub-msg" id="subMsg"></div>
  </div>
</div>

<footer class="footer">
  © <?= date('Y') ?> Kūgeria Ltd · <a href="../index.html">Home</a> · <a href="index.php">Insights</a> · <a href="../privacy.html">Privacy</a> · <a href="rss.php">RSS</a>
</footer>

<script>
document.getElementById('subForm').addEventListener('submit', async function(e){
  e.preventDefault();
  var msg = document.getElementById('subMsg');
  try {
    var r = await fetch('subscribe.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'email=' + encodeURIComponent(document.getElementById('subEmail').value.trim())
          + '&website=' + encodeURIComponent(document.getElementById('subHp').value)
    });
    var d = await r.json();
    msg.textContent = d.message;
    if (d.ok) document.getElementById('subForm').reset();
  } catch (err) { msg.textContent = 'Something went wrong — please try again.'; }
});
</script>
</body>
</html>
