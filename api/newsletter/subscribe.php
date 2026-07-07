<?php
/**
 * api/newsletter/subscribe.php — Newsletter sign-up
 * POST /api/newsletter/subscribe.php
 * Body: { email }
 * Returns: { success, message }
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$body  = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($body['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}
$db   = getDB();
$stmt = $db->prepare(
    'INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:email)'
);
$stmt->execute([':email' => $email]);
echo json_encode(['success' => true, 'message' => 'You\'re on the list!']);
