<?php
/** AUTHOR PORTAL — editor: create/edit OWN posts only */
require __DIR__ . '/auth.php';
$me = require_author();

$id   = (int)($_GET['id'] ?? 0);
$msg  = ''; $err = '';
$post = ['id'=>0,'title'=>'','slug'=>'','category'=>'Insights','excerpt'=>'','body'=>'','cover'=>'','status'=>'draft'];

if ($id) {
  // Ownership enforced in the query itself
  $q = db()->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");
  $q->execute([$id, $me['id']]);
  $found = $q->fetch();
  if ($found) $post = $found;
  else { header('Location: index.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  a_csrf_check();
  $post['title']    = trim($_POST['title'] ?? '');
  $post['category'] = trim($_POST['category'] ?? 'Insights');
  $post['excerpt']  = trim($_POST['excerpt'] ?? '');
  $post['body']     = $_POST['body'] ?? '';
  $post['cover']    = trim($_POST['cover'] ?? '');
  $action           = $_POST['action'] ?? 'draft';

  if ($post['title'] === '' || $post['body'] === '' || $post['excerpt'] === '') {
    $err = 'Title, excerpt, and body are all required.';
  } else {
    if (empty($post['slug'])) {
      $base = slugify($post['title']); $slug = $base; $i = 2;
      while (true) {
        $c = db()->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $c->execute([$slug, $post['id']]);
        if (!$c->fetch()) break;
        $slug = $base . '-' . $i++;
      }
      $post['slug'] = $slug;
    }

    $wasPublished   = ($post['status'] ?? '') === 'published';
    $post['status'] = ($action === 'publish') ? 'published' : 'draft';
    $publishedAtSql = ($post['status'] === 'published' && !$wasPublished) ? date('Y-m-d H:i:s') : null;

    if ($post['id']) {
      $sql = "UPDATE posts SET title=?, slug=?, category=?, excerpt=?, body=?, cover=?, status=?"
           . ($publishedAtSql ? ", published_at=?" : "")
           . " WHERE id=? AND author_id=?";           // ownership enforced again
      $vals = [$post['title'],$post['slug'],$post['category'],$post['excerpt'],$post['body'],$post['cover'],$post['status']];
      if ($publishedAtSql) $vals[] = $publishedAtSql;
      $vals[] = $post['id']; $vals[] = $me['id'];
      db()->prepare($sql)->execute($vals);
      $msg = $post['status']==='published' ? 'Post updated and live.' : 'Draft saved.';
    } else {
      db()->prepare("INSERT INTO posts (author_id,title,slug,category,excerpt,body,cover,status,published_at)
                     VALUES (?,?,?,?,?,?,?,?,?)")
         ->execute([$me['id'],$post['title'],$post['slug'],$post['category'],$post['excerpt'],$post['body'],
                    $post['cover'],$post['status'],
                    $post['status']==='published' ? date('Y-m-d H:i:s') : null]);
      $post['id'] = (int)db()->lastInsertId();
      $msg = $post['status']==='published' ? 'Post published! 🎉' : 'Draft saved.';
    }
  }
}

author_head($post['id'] ? 'Edit Post' : 'New Post');
author_topbar($me['name']);
?>
<div class="wrap">
  <h1><?= $post['id'] ? 'Edit Post' : 'New Post' ?></h1>
  <p style="font-size:12.5px;color:#6b7280;margin:-10px 0 16px">Published under your public byline: <strong><?= esc($me['name']) ?> · <?= esc($me['profession']) ?></strong></p>
  <?php if ($msg): ?><div class="msg msg-ok"><?= esc($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-err"><?= esc($err) ?></div><?php endif; ?>

  <form method="post" class="card">
    <input type="hidden" name="csrf" value="<?= a_csrf_token() ?>">

    <label>Title *</label>
    <input type="text" name="title" value="<?= esc($post['title']) ?>" required>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div>
        <label>Category</label>
        <input type="text" name="category" value="<?= esc($post['category']) ?>" placeholder="e.g. Cybersecurity, Fintech">
      </div>
      <div>
        <label>Cover image path <span style="font-weight:400;color:#9ca3af">(upload below, then paste)</span></label>
        <input type="text" name="cover" value="<?= esc($post['cover']) ?>" placeholder="uploads/my-image.jpg">
      </div>
    </div>

    <label>Excerpt * <span style="font-weight:400;color:#9ca3af">(1–2 sentences shown on cards and search results)</span></label>
    <textarea name="excerpt" rows="2" required><?= esc($post['excerpt']) ?></textarea>

    <label>Body (HTML) *</label>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">
      <button type="button" class="btn btn-ghost" onclick="wrap('<h2>','</h2>')">H2</button>
      <button type="button" class="btn btn-ghost" onclick="wrap('<h3>','</h3>')">H3</button>
      <button type="button" class="btn btn-ghost" onclick="wrap('<p>','</p>')">¶</button>
      <button type="button" class="btn btn-ghost" onclick="wrap('<strong>','</strong>')"><b>B</b></button>
      <button type="button" class="btn btn-ghost" onclick="wrap('<em>','</em>')"><i>I</i></button>
      <button type="button" class="btn btn-ghost" onclick="wrap('<ul>\n<li>','</li>\n</ul>')">• List</button>
      <button type="button" class="btn btn-ghost" onclick="wrap('<blockquote>','</blockquote>')">❝ Quote</button>
      <button type="button" class="btn btn-ghost" onclick="insertLink()">🔗 Link</button>
      <label class="btn btn-navy" style="margin:0">📷 Upload Image
        <input type="file" id="imgUp" accept="image/*" style="display:none">
      </label>
      <label class="btn btn-navy" style="margin:0">🎬 Upload Video
        <input type="file" id="vidUp" accept="video/mp4,video/webm" style="display:none">
      </label>
    </div>
    <textarea name="body" id="bodyField" rows="18" placeholder="<p>Write your article here using simple HTML...</p>"><?= esc($post['body']) ?></textarea>
    <div id="upMsg" style="font-size:12.5px;color:#6b7280;margin-top:6px"></div>

    <div style="display:flex;gap:10px;align-items:center;margin-top:22px;flex-wrap:wrap">
      <button class="btn btn-ghost" name="action" value="draft">Save Draft</button>
      <button class="btn" name="action" value="publish">✦ Publish</button>
      <?php if ($post['id'] && $post['status']==='published'): ?>
        <a href="../post.php?slug=<?= esc($post['slug']) ?>" target="_blank" style="font-size:13px;margin-left:auto">View live ↗</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<script>
const body = document.getElementById('bodyField');
function wrap(open, close){
  const s = body.selectionStart, e = body.selectionEnd;
  const sel = body.value.slice(s, e) || 'text here';
  body.setRangeText(open + sel + close, s, e, 'end');
  body.focus();
}
function insertLink(){
  const url = prompt('Link URL (https://...)'); if (!url) return;
  wrap('<a href="' + url + '" target="_blank">', '</a>');
}
async function upload(file, kind){
  const msg = document.getElementById('upMsg');
  msg.textContent = 'Uploading ' + file.name + '...';
  const fd = new FormData();
  fd.append('file', file);
  fd.append('csrf', '<?= a_csrf_token() ?>');
  try {
    const r = await fetch('upload.php', { method:'POST', body: fd });
    const d = await r.json();
    if (!d.ok) { msg.textContent = '❌ ' + d.message; return; }
    const tag = kind === 'video'
      ? '\n<video controls preload="metadata" src="' + d.url + '"></video>\n'
      : '\n<img src="' + d.url + '" alt="" loading="lazy">\n';
    body.setRangeText(tag, body.selectionStart, body.selectionEnd, 'end');
    msg.innerHTML = '✅ Uploaded: <code>' + d.url + '</code>';
  } catch (e) { msg.textContent = '❌ Upload failed.'; }
}
document.getElementById('imgUp').addEventListener('change', e => e.target.files[0] && upload(e.target.files[0], 'image'));
document.getElementById('vidUp').addEventListener('change', e => e.target.files[0] && upload(e.target.files[0], 'video'));
</script>
</body></html>
