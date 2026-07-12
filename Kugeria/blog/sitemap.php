<?php
/** KŪGERIA INSIGHTS — dynamic sitemap: hub + every published post.
 *  Referenced from robots.txt; updates itself on every publish. */
require __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=UTF-8');

$posts = db()->query(
  "SELECT slug, published_at FROM posts
   WHERE status = 'published' ORDER BY published_at DESC"
)->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= SITE_URL ?>/</loc>
    <?php if ($posts): ?><lastmod><?= date('Y-m-d', strtotime($posts[0]['published_at'])) ?></lastmod><?php endif; ?>
    <priority>0.8</priority>
  </url>
<?php foreach ($posts as $p): ?>
  <url>
    <loc><?= SITE_URL ?>/post.php?slug=<?= rawurlencode($p['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($p['published_at'])) ?></lastmod>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>
</urlset>
