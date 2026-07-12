<?php
/** KŪGERIA INSIGHTS — subscribe endpoint (JSON) */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=UTF-8');

function out(bool $ok, string $message): never {
  echo json_encode(['ok' => $ok, 'message' => $message]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request.');

// Honeypot: bots fill the hidden "website" field
if (!empty($_POST['website'])) out(false, 'Blocked.');

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, 'Please enter a valid email address.');

try {
  $q = db()->prepare("INSERT IGNORE INTO subscribers (email) VALUES (?)");
  $q->execute([$email]);

  if ($q->rowCount() === 0) out(true, "You're already subscribed — great taste! 🎉");

  // Welcome email
  $subject = 'Welcome to ' . SITE_NAME;
  $bodyTxt = "Karibu!\n\nYou're now subscribed to Kūgeria Insights. "
           . "You'll get an email whenever a new article is published.\n\n"
           . "Browse everything so far: " . SITE_URL . "/\n\n"
           . "— Isaac Oonge, Kūgeria Ltd\n\n"
           . "Unsubscribe any time with one click:\n" . unsub_link($email);
  @mail($email, $subject, $bodyTxt,
        "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\nReply-To: " . FROM_EMAIL);

  out(true, "Subscribed! Check your inbox for a welcome note. 🎉");
} catch (Throwable $e) {
  out(false, 'Server error — please try again later.');
}