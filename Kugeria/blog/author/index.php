<?php
/** AUTHOR PORTAL — dashboard: my posts only */
require __DIR__ . '/auth.php';
$me = require_author();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  a_csrf_check();
  // Authors can delete ONLY their own posts
  db()->prepare("DELETE FROM posts WHERE id = ? AND author_id = ?")
     ->execute([(int)$_POST['delete_id'], $me['id']]);
  $msg = 'Post deleted.';
}

$q = db()->prepare("SELECT id, slug, title, category, status, published_at, created_at
                    FROM posts WHERE author_id = ?
                    ORDER BY COALESCE(published_at, created_at) DESC");
$q->execute([$me['id']]);
$posts = $q->fetchAll();

author_head('My Posts');
author_topbar($me['name']);
?>
<div class="wrap">
  <h1>My Posts</h1>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <strong><?= count($posts) ?> post(s)</strong>
      <a href="editor.php" class="btn">+ New Post</a>
    </div>
    <?php if (!$posts): ?>
      <p style="color:#6b7280;font-size:14px">You haven't written anything yet — your first post awaits!</p>
    <?php else: ?>
    <table>
      <tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th></th></tr>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td data-label="Title"><a href="editor.php?id=<?= $p['id'] ?>"><?= esc($p['title']) ?></a></td>
        <td data-label="Category"><?= esc($p['category']) ?></td>
        <td data-label="Status"><span class="badge <?= $p['status']==='published'?'b-pub':'b-draft' ?>"><?= esc($p['status']) ?></span></td>
        <td data-label="Date"><?= nice_date($p['published_at'] ?: $p['created_at']) ?></td>
        <td style="white-space:nowrap">
          <?php if ($p['status']==='published'): ?>
            <a href="../post.php?slug=<?= esc($p['slug']) ?>" target="_blank" style="font-size:12px">View ↗</a> ·
          <?php endif; ?>
          <a href="editor.php?id=<?= $p['id'] ?>" style="font-size:12px">Edit</a> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this post permanently?')">
            <input type="hidden" name="csrf" value="<?= a_csrf_token() ?>">
            <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
            <button style="background:none;border:none;color:#b91c1c;font-size:12px;cursor:pointer;font-weight:600;padding:0">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body></html>