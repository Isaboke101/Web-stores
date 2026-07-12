<?php
/** AUTHOR PORTAL — media upload for post bodies. Returns JSON {ok, url|message}. */
require __DIR__ . '/auth.php';
$me = require_author();
header('Content-Type: application/json; charset=UTF-8');

function jout(bool $ok, array $extra = []): never {
  echo json_encode(['ok' => $ok] + $extra); exit;
}

a_csrf_check();
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  jout(false, ['message' => 'No file received or upload error (file may exceed the server size limit).']);
}

$f = $_FILES['file'];
$allowed = [
  'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
  'image/gif'  => 'gif', 'video/mp4' => 'mp4', 'video/webm' => 'webm',
];
$mime = mime_content_type($f['tmp_name']);
if (!isset($allowed[$mime])) jout(false, ['message' => 'File type not allowed. Use JPG, PNG, WEBP, GIF, MP4, or WEBM.']);
if ($f['size'] > 50 * 1024 * 1024) jout(false, ['message' => 'File too large (max 50MB). For big videos, upload to YouTube and embed instead.']);

$ext  = $allowed[$mime];
$name = date('Ymd-His') . '-a' . $me['id'] . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) {
  jout(false, ['message' => 'Could not save file — check uploads/ folder permissions (755).']);
}
jout(true, ['url' => UPLOAD_URL . $name]);
