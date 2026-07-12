<?php
/** KŪGERIA INSIGHTS — JSON endpoint: latest published posts.
 *  Used by the Schedule page's success screen to suggest reading. */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=UTF-8');

try {
  $posts = db()->query(
    "SELECT slug, title, category, excerpt FROM posts
     WHERE status = 'published' ORDER BY published_at DESC LIMIT 3"
  )->fetchAll();
  $out = array_map(function ($p) {
    return [
      'title'    => $p['title'],
      'category' => $p['category'],
      'excerpt'  => $p['excerpt'],
      'url'      => 'blog/post.php?slug=' . rawurlencode($p['slug']),
    ];
  }, $posts);
  echo json_encode(['ok' => true, 'posts' => $out]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'posts' => []]);
}
