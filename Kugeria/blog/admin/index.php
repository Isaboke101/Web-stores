<?php
/** ADMIN — dashboard: post list + quick stats */
require __DIR__ . '/auth.php';
require_admin();

$msg = '';
// Delete post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  csrf_check();
  $q = db()->prepare("DELETE FROM posts WHERE id = ?");
  $q->execute([(int)$_POST['delete_id']]);
  $msg = 'Post deleted.';
}

$posts = db()->query("SELECT p.id, p.slug, p.title, p.category, p.status, p.published_at, p.created_at,
                             a.name AS a_name
                      FROM posts p LEFT JOIN authors a ON a.id = p.author_id
                      ORDER BY COALESCE(p.published_at, p.created_at) DESC")->fetchAll();
$nPub  = db()->query("SELECT COUNT(*) c FROM posts WHERE status='published'")->fetch()['c'];
$nDrf  = db()->query("SELECT COUNT(*) c FROM posts WHERE status='draft'")->fetch()['c'];
$nSub  = db()->query("SELECT COUNT(*) c FROM subscribers")->fetch()['c'];
$nAds  = db()->query("SELECT COUNT(*) c FROM ads WHERE active=1")->fetch()['c'];

admin_head('Posts');
admin_topbar();
?>
<div class="wrap">
  <h1>Dashboard</h1>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>

  <div class="stats">
    <div class="stat"><div class="n"><?= $nPub ?></div><div class="l">Published</div></div>
    <div class="stat"><div class="n"><?= $nDrf ?></div><div class="l">Drafts</div></div>
    <div class="stat"><div class="n"><?= $nSub ?></div><div class="l">Subscribers</div></div>
    <div class="stat"><div class="n"><?= $nAds ?></div><div class="l">Active Ads</div></div>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <strong>All Posts</strong>
      <a href="editor.php" class="btn">+ New Post</a>
    </div>
    <?php if (!$posts): ?>
      <p style="color:#6b7280;font-size:14px">No posts yet — write your first one!</p>
    <?php else: ?>
    <table>
      <tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Date</th><th></th></tr>
      <?php foreach ($posts as $p): ?>
      <tr>
        <td data-label="Title"><a href="editor.php?id=<?= $p['id'] ?>"><?= esc($p['title']) ?></a></td>
        <td data-label="Author" style="font-size:12.5px"><?= esc($p['a_name'] ?: 'Isaac (you)') ?></td>
        <td data-label="Category"><?= esc($p['category']) ?></td>
        <td data-label="Status"><span class="badge <?= $p['status']==='published'?'b-pub':'b-draft' ?>"><?= esc($p['status']) ?></span></td>
        <td data-label="Date"><?= nice_date($p['published_at'] ?: $p['created_at']) ?></td>
        <td style="white-space:nowrap">
          <?php if ($p['status']==='published'): ?>
            <a href="../post.php?slug=<?= esc($p['slug']) ?>" target="_blank" style="font-size:12px">View ↗</a> ·
          <?php endif; ?>
          <a href="editor.php?id=<?= $p['id'] ?>" style="font-size:12px">Edit</a> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this post permanently?')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
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
</body>
</html>