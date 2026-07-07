<?php
/**
 * api/auth/login.php — Customer sign-in endpoint
 *
 * POST /api/auth/login.php
 * Body (JSON): { email, password }
 * Returns: { success, message, user? }
 */

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

session_name('injili_customer');
session_start();

$body     = json_decode(file_get_contents('php://input'), true);
$email    = strtolower(trim($body['email']    ?? ''));
$password = $body['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

/* Fetch the user row — only the fields needed for verification */
$db   = getDB();
$stmt = $db->prepare(
    'SELECT id, first_name, last_name, email, phone, password FROM users WHERE email = :email'
);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

/* Verify the submitted password against the stored bcrypt hash */
if (!$user || !password_verify($password, $user['password'])) {
    /* Return a generic error — don't reveal whether the email exists */
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

/* ── Regenerate session ID on login to prevent session fixation ── */
session_regenerate_id(true);

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];

echo json_encode([
    'success' => true,
    'message' => 'Signed in successfully',
    'user'    => [
        'id'       => $user['id'],
        'name'     => $user['first_name'] . ' ' . $user['last_name'],
        'email'    => $user['email'],
        'phone'    => $user['phone'],
        'initials' => strtoupper($user['first_name'][0] . $user['last_name'][0]),
    ],
]);
