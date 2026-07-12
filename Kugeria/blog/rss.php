<?php
/** KŪGERIA INSIGHTS — RSS 2.0 feed
 *  Powers: feed readers, Chrome's "Follow" feature, and Google
 *  Publisher Center (Google News) once submitted there. */
require __DIR__ . '/config.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$posts = db()->query(
  "SELECT p.slug, p.title, p.excerpt, p.body, p.published_at, a.name AS a_name
   FROM posts p LEFT JOIN authors a ON a.id = p.author_id
   WHERE p.status = 'published'
   ORDER BY p.published_at DESC LIMIT 20"
)->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
  <title><?= esc(SITE_NAME) ?></title>
  <link><?= SITE_URL ?>/</link>
  <atom:link href="<?= SITE_URL ?>/rss.php" rel="self" type="application/rss+xml"/>
  <description>Practical guides on web development, M-Pesa integration, AI, and digital growth for Kenyan businesses.</description>
  <language>en-ke</language>
  <lastBuildDate><?= $posts ? date(DATE_RSS, strtotime($posts[0]['published_at'])) : date(DATE_RSS) ?></lastBuildDate>
<?php foreach ($posts as $p):
  $url = SITE_URL . '/post.php?slug=' . rawurlencode($p['slug']);
?>
  <item>
    <title><?= esc($p['title']) ?></title>
    <link><?= esc($url) ?></link>
    <guid isPermaLink="true"><?= esc($url) ?></guid>
    <pubDate><?= date(DATE_RSS, strtotime($p['published_at'])) ?></pubDate>
    <description><?= esc($p['excerpt']) ?></description>
    <dc:creator><?= esc($p['a_name'] ?: 'Isaac Oonge') ?></dc:creator>
    <content:encoded><![CDATA[<?= $p['body'] ?>]]></content:encoded>
  </item>
<?php endforeach; ?>
</channel>
</rss>
