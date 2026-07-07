<?php
/**
 * api/auth/register.php — Customer registration endpoint
 *
 * POST /api/auth/register.php
 * Body (JSON): { first_name, last_name, email, phone, password }
 * Returns: { success, message, user? }
 *
 * Creates a new customer account, starts a session, and returns
 * the user profile for the frontend state.
 */

require_once __DIR__ . '/../../config/db.php';

/* Only accept POST requests */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

/* Start a customer session (separate from admin session) */
session_name('injili_customer');
session_start();

/* Read and decode the JSON body sent by the frontend */
$body = json_decode(file_get_contents('php://input'), true);

$firstName = trim($body['first_name'] ?? '');
$lastName  = trim($body['last_name']  ?? '');
$email     = strtolower(trim($body['email']    ?? ''));
$phone     = trim($body['phone']    ?? '');
$password  = $body['password'] ?? '';

/* ── Input validation ──────────────────────────────────────────── */
if (!$firstName || !$lastName || !$email || !$phone || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

/* ── Check for duplicate email ─────────────────────────────────── */
$db   = getDB();
$stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists']);
    exit;
}

/* ── Create the account ────────────────────────────────────────── */
/* Hash the password using bcrypt — never store plain text */
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$insert = $db->prepare(
    'INSERT INTO users (first_name, last_name, email, phone, password)
     VALUES (:fname, :lname, :email, :phone, :pass)'
);
$insert->execute([
    ':fname' => $firstName,
    ':lname' => $lastName,
    ':email' => $email,
    ':phone' => $phone,
    ':pass'  => $hashedPassword,
]);

$userId = $db->lastInsertId();

/* ── Start the session ─────────────────────────────────────────── */
$_SESSION['user_id']    = $userId;
$_SESSION['user_email'] = $email;
$_SESSION['user_name']  = $firstName . ' ' . $lastName;

echo json_encode([
    'success' => true,
    'message' => 'Account created successfully',
    'user'    => [
        'id'        => $userId,
        'name'      => $firstName . ' ' . $lastName,
        'email'     => $email,
        'phone'     => $phone,
        'initials'  => strtoupper($firstName[0] . $lastName[0]),
    ],
]);
