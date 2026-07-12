<?php
/**
 * KŪGERIA BLOG CMS — config.php
 * Shared configuration + database connection + helpers.
 * UPDATE THE FOUR DB_ CONSTANTS with your cPanel MySQL details.
 */

// ── DATABASE — from cPanel → MySQL Databases ──────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');

// ── SITE SETTINGS ─────────────────────────────────────────────
define('SITE_NAME',  'Kūgeria Insights');
define('SITE_URL',   'https://www.kugeria.co.ke/blog'); // no trailing slash
define('HOME_URL',   'https://www.kugeria.co.ke');
define('FROM_EMAIL', 'hello@kugeria.co.ke');            // subscriber emails sent from
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('UNSUB_SECRET', 'CHANGE_THIS_TO_A_LONG_RANDOM_STRING'); // any random 30+ chars; keep it constant once live

// ── DB CONNECTION (PDO) ───────────────────────────────────────
function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
  }
  return $pdo;
}

// ── HELPERS ───────────────────────────────────────────────────
function esc(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function slugify(string $t): string {
  $t = strtolower(trim($t));
  $t = preg_replace('/[^a-z0-9]+/', '-', $t);
  return trim(preg_replace('/-+/', '-', $t), '-');
}

/** Fetch one active ad for a slot, or null. Rotates randomly if several. */
function get_ad(string $slot): ?array {
  $q = db()->prepare(
    "SELECT * FROM ads WHERE slot = ? AND active = 1
       AND (starts IS NULL OR starts <= CURDATE())
       AND (ends   IS NULL OR ends   >= CURDATE())
     ORDER BY RAND() LIMIT 1");
  $q->execute([$slot]);
  $ad = $q->fetch();
  return $ad ?: null;
}

/** Render an ad slot: real ad if booked, branded placeholder if not. */
function render_ad(string $slot): string {
  $ad = get_ad($slot);
  if ($ad) {
    return '<a class="ad-unit" href="' . esc($ad['link']) . '" target="_blank" rel="noopener sponsored">'
         . '<img src="' . esc($ad['image']) . '" alt="' . esc($ad['advertiser']) . '" loading="lazy">'
         . '<span class="ad-tag">Sponsored</span></a>';
  }
  return '<a class="ad-unit ad-empty" href="' . HOME_URL . '/contact.html">'
       . '<span class="ad-empty-title">Your Ad Here</span>'
       . '<span class="ad-empty-sub">Reach Kenyan business owners — advertise with Kūgeria Insights</span></a>';
}

/** Signed unsubscribe token so only the real recipient can unsubscribe. */
function unsub_token(string $email): string {
  return substr(hash_hmac('sha256', strtolower(trim($email)), UNSUB_SECRET), 0, 32);
}

/** Full one-click unsubscribe URL for an email. */
function unsub_link(string $email): string {
  return SITE_URL . '/unsubscribe.php?email=' . rawurlencode(strtolower(trim($email)))
       . '&token=' . unsub_token($email);
}

/** First character of a name, UTF-8 safe, works without mbstring. */
function initial(string $name): string {
  if (function_exists('mb_substr')) return mb_substr(trim($name), 0, 1);
  return preg_match('/./u', trim($name), $m) ? $m[0] : strtoupper(substr(trim($name), 0, 1));
}

/** Format a date nicely. */
function nice_date(?string $d): string {
  return $d ? date('j M Y', strtotime($d)) : '';
}