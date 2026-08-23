<?php
/**
 * KŪGERIA BLOG CMS — config.php
 * Shared configuration + database connection + helpers.
 * UPDATE THE FOUR DB_ CONSTANTS with your cPanel MySQL details.
 */

// ── DATABASE — from cPanel → MySQL Databases ──────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'kugeriac_blog');
define('DB_USER', 'kugeriac_bloguser');
define('DB_PASS', 'Mukui@2026#');

// ── SITE SETTINGS ─────────────────────────────────────────────
define('SITE_NAME',  'Kūgeria Insights');
define('SITE_URL',   'https://www.kugeria.co.ke/blog'); // no trailing slash
define('HOME_URL',   'https://www.kugeria.co.ke');
define('FROM_EMAIL', 'hello@kugeria.co.ke');            // subscriber emails sent from
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('UNSUB_SECRET', 'kg7x9Qm2Lp4Rw8vNb3Yt6Zc1Hf5Jd0Ks'); // any random 30+ chars; keep it constant once live

// ── SMTP (authenticated sending via the real hello@kugeria.co.ke mailbox) ──
// Fixes the "Track Delivery: success but nothing ever arrives" problem —
// receiving servers trust authenticated mailbox sends far more than raw
// server-originated mail(), even with SPF/DKIM/DMARC all valid.
define('SMTP_HOST', 'mail.kugeria.co.ke');   // usually mail.yourdomain — check cPanel > Email Accounts > Connect Devices if unsure
define('SMTP_PORT', 465);                    // 465 = SSL (recommended), 587 = STARTTLS
define('SMTP_SECURE', 'ssl');                // 'ssl' for port 465, 'tls' for port 587
define('SMTP_USER', 'hello@kugeria.co.ke');  // the real mailbox login
define('SMTP_PASS', 'Mukui@2026#');                // the real mailbox password

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

// ── PHPMailer (vendored, no Composer needed) ───────────────────
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Send an email via authenticated SMTP through the real mailbox.
 * Pass $htmlBody for a polished branded email (logo, colors, button);
 * omit it to send plain text only (used for e.g. simple admin alerts).
 * $bodyText is always sent too, as the plain-text fallback every HTML
 * email needs for accessibility and spam-filter friendliness.
 * Returns true on success, false on failure (never throws — callers
 * already treat email as best-effort and shouldn't break on failure).
 */
function send_mail(string $to, string $subject, string $bodyText, ?string $htmlBody = null): bool {
  $mail = new PHPMailer(true);
  try {
    $mail->CharSet = 'UTF-8';   // ← fixes "Kūgeria" rendering as "KÅ«geria"
    $mail->Encoding = 'base64';

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(FROM_EMAIL, SITE_NAME);
    $mail->addAddress($to);
    $mail->addReplyTo(FROM_EMAIL, SITE_NAME);
    $mail->Subject = $subject;

    if ($htmlBody !== null) {
      // Embed the logo as an inline attachment (cid) rather than a remote
      // URL — many mail clients (Outlook, Gmail on some settings) block
      // remote images by default, but inline embeds always render.
      $logoPath = dirname(__DIR__) . '/images/logo_small.png';
      if (file_exists($logoPath)) {
        $mail->addEmbeddedImage($logoPath, 'kugerialogo', 'logo_small.png');
      }
      $mail->isHTML(true);
      $mail->Body    = $htmlBody;
      $mail->AltBody = $bodyText;
    } else {
      $mail->isHTML(false);
      $mail->Body = $bodyText;
    }

    return $mail->send();
  } catch (PHPMailerException $e) {
    error_log('send_mail failed to ' . $to . ': ' . $mail->ErrorInfo);
    return false;
  }
}

/**
 * Wrap content in a polished, branded HTML email shell — logo, brand
 * colors, optional call-to-action button, and a consistent footer.
 * Use with send_mail()'s $htmlBody parameter.
 */
function email_template(string $heading, string $bodyHtml, ?string $ctaText = null, ?string $ctaUrl = null, ?string $footerNote = null): string {
  $cta = '';
  if ($ctaText && $ctaUrl) {
    $cta = '<tr><td align="center" style="padding:8px 0 6px">'
         . '<a href="' . esc($ctaUrl) . '" style="display:inline-block;background:#C5A059;color:#212121;'
         . 'text-decoration:none;font-weight:700;font-size:14px;padding:13px 30px;border-radius:8px;'
         . 'font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">' . esc($ctaText) . '</a>'
         . '</td></tr>';
  }
  $footer = $footerNote
    ? '<p style="font-size:11.5px;color:#9ca3af;line-height:1.6;margin:18px 0 0">' . $footerNote . '</p>'
    : '';

  return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
  . '<body style="margin:0;padding:0;background:#FAFAFA;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">'
  . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FAFAFA;padding:32px 16px">'
  . '<tr><td align="center">'
  . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb">'

  // ── Header band with logo ──
  . '<tr><td style="background:#052F5F;padding:26px 30px;text-align:left">'
  . '<img src="cid:kugerialogo" alt="Kūgeria Ltd" width="34" height="34" style="vertical-align:middle;border-radius:50%">'
  . '<span style="color:#ffffff;font-size:15px;font-weight:700;vertical-align:middle;margin-left:10px;letter-spacing:.02em">Kūgeria Insights</span>'
  . '</td></tr>'

  // ── Body ──
  . '<tr><td style="padding:34px 30px 10px">'
  . '<h1 style="font-family:Georgia,\'Times New Roman\',serif;font-size:24px;color:#052F5F;margin:0 0 16px;line-height:1.25">' . esc($heading) . '</h1>'
  . '<div style="font-size:14.5px;color:#3d3d3d;line-height:1.7">' . $bodyHtml . '</div>'
  . '</td></tr>'

  . '<tr><td style="padding:10px 30px 30px">'
  . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $cta . '</table>'
  . '</td></tr>'

  // ── Footer ──
  . '<tr><td style="background:#f7f7f8;padding:20px 30px;border-top:1px solid #e5e7eb">'
  . '<p style="font-size:11.5px;color:#9ca3af;margin:0">© ' . date('Y') . ' Kūgeria Ltd · Nairobi, Kenya</p>'
  . $footer
  . '</td></tr>'

  . '</table>'
  . '</td></tr>'
  . '</table>'
  . '</body></html>';
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
