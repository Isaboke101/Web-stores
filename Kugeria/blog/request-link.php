<?php
/** KŪGERIA INSIGHTS — request-link.php
 *  "I've followed before but this device shows Follow" / "I want to
 *  unsubscribe but don't have the email handy" — request a personal
 *  token link be re-sent. No login, no password: control of the inbox
 *  IS the identity proof, same as every password-reset flow works. */
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=UTF-8');

function out(bool $ok, string $message): never {
  echo json_encode(['ok' => $ok, 'message' => $message]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request.');

// Honeypot
if (!empty($_POST['website'])) out(false, 'Blocked.');

$email = strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, 'Please enter a valid email address.');

// Always return the same message whether or not the email is subscribed —
// prevents using this endpoint to check who is/isn't on the list.
$generic = "If that email is following Kūgeria Insights, we've sent a link to manage it.";

try {
  $q = db()->prepare("SELECT 1 FROM subscribers WHERE email = ?");
  $q->execute([$email]);
  if ($q->fetch()) {
    $link = SITE_URL . '/manage.php?email=' . rawurlencode($email) . '&token=' . unsub_token($email);
    $subject = 'Manage Your ' . SITE_NAME . ' Subscription';
    $bodyTxt = "Here's your personal link to check your subscription status, "
             . "sync the Follow button on this device, or unsubscribe:\n\n"
             . $link . "\n\n"
             . "This link is unique to your address — no password needed.\n\n"
             . "— Isaac Oonge, Kūgeria Ltd";

    $bodyHtml = '<p>Here\'s your personal link to check your subscription status, sync the '
              . '<strong>Follow</strong> button on this device, or unsubscribe.</p>'
              . '<p>This link is unique to your address — no password needed.</p>'
              . '<p style="color:#6b7280">— Isaac Oonge, Kūgeria Ltd</p>';

    send_mail($email, $subject, $bodyTxt,
      email_template('Manage Your Subscription', $bodyHtml, 'Open My Link', $link));
  }
  out(true, $generic);
} catch (Throwable $e) {
  out(false, 'Server error — please try again later.');
}
