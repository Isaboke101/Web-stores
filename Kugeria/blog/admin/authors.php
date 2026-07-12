<?php
/** ADMIN — authors manager: invite guest authors, ban/unban, remove */
require __DIR__ . '/auth.php';
require_admin();

$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();

  // ── Invite a new author ──
  if (isset($_POST['invite'])) {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $n = trim($_POST['name'] ?? '');
    $pr = trim($_POST['profession'] ?? '');
    if (strlen($u) < 3 || strlen($p) < 8 || !$n || !$pr) {
      $err = 'Username (3+), temp password (8+), name, and profession are all required.';
    } else {
      try {
        db()->prepare("INSERT INTO authors (username, pass_hash, name, profession) VALUES (?,?,?,?)")
           ->execute([$u, password_hash($p, PASSWORD_DEFAULT), $n, $pr]);
        $msg = "Author invited. Send them: login page /blog/author/login.php, username \"$u\", and the temp password. Tell them to change it in their Profile page.";
      } catch (Throwable $e) {
        $err = 'Username already taken.';
      }
    }
  }

  // ── Ban / unban (takes effect on their very next request) ──
  if (isset($_POST['ban_id'])) {
    db()->prepare("UPDATE authors SET status = IF(status='banned','active','banned') WHERE id = ?")
       ->execute([(int)$_POST['ban_id']]);
    $msg = 'Author status updated. A banned author is locked out immediately — including mid-session.';
  }

  // ── Remove author (their published posts remain, byline reverts to default) ──
  if (isset($_POST['remove_id'])) {
    $aid = (int)$_POST['remove_id'];
    db()->prepare("UPDATE posts SET author_id = NULL WHERE author_id = ?")->execute([$aid]);
    db()->prepare("DELETE FROM authors WHERE id = ?")->execute([$aid]);
    $msg = 'Author removed. Their posts remain live under the default Kūgeria byline.';
  }
}

$authors = db()->query(
  "SELECT a.*, COUNT(p.id) AS n_posts
   FROM authors a LEFT JOIN posts p ON p.author_id = a.id
   GROUP BY a.id ORDER BY a.created_at DESC")->fetchAll();

admin_head('Authors');
admin_topbar();
?>
<div class="wrap">
  <h1>Guest Authors</h1>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-err"><?= esc($err) ?></div><?php endif; ?>

  <div class="card">
    <strong>Invite an Author</strong>
    <p style="font-size:12.5px;color:#6b7280;margin-top:4px">Creates their account. Share the login link, username, and temp password with them privately — they complete their own profile (photo, LinkedIn, password) after first sign-in.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="invite" value="1">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div><label>Username</label><input type="text" name="username" required minlength="3" autocomplete="off"></div>
        <div><label>Temporary password (8+)</label><input type="text" name="password" required minlength="8" autocomplete="off"></div>
        <div><label>Full name (public)</label><input type="text" name="name" required placeholder="e.g. Wanjiru Kamau"></div>
        <div><label>Profession (public)</label><input type="text" name="profession" required placeholder="e.g. Cybersecurity Analyst"></div>
      </div>
      <button class="btn" style="margin-top:16px">Create Invite</button>
    </form>
  </div>

  <div class="card">
    <strong>All Authors</strong>
    <?php if (!$authors): ?>
      <p style="color:#6b7280;font-size:14px;margin-top:8px">No guest authors yet.</p>
    <?php else: ?>
    <table style="margin-top:10px">
      <tr><th>Author</th><th>Profession</th><th>Posts</th><th>Status</th><th></th></tr>
      <?php foreach ($authors as $a): ?>
      <tr>
        <td data-label="Author">
          <strong><?= esc($a['name']) ?></strong>
          <span style="color:#9ca3af;font-size:12px"> @<?= esc($a['username']) ?></span>
          <?php if ($a['linkedin']): ?> · <a href="<?= esc($a['linkedin']) ?>" target="_blank" style="font-size:12px">in↗</a><?php endif; ?>
        </td>
        <td data-label="Profession" style="font-size:12.5px"><?= esc($a['profession']) ?></td>
        <td data-label="Posts"><?= (int)$a['n_posts'] ?></td>
        <td data-label="Status"><span class="badge <?= $a['status']==='active'?'b-pub':'b-draft' ?>"><?= esc($a['status']) ?></span></td>
        <td style="white-space:nowrap">
          <form method="post" style="display:inline" onsubmit="return confirm('<?= $a['status']==='banned' ? 'Unban this author?' : 'Ban this author? They will be locked out instantly.' ?>')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="ban_id" value="<?= $a['id'] ?>">
            <button style="background:none;border:none;color:#052F5F;font-size:12px;cursor:pointer;font-weight:600"><?= $a['status']==='banned' ? 'Unban' : 'Ban' ?></button>
          </form> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Remove this author account? Their posts stay live under the default byline.')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="remove_id" value="<?= $a['id'] ?>">
            <button style="background:none;border:none;color:#b91c1c;font-size:12px;cursor:pointer;font-weight:600">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>
</body></html>