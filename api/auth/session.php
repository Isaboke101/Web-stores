<?php
/**
 * api/auth/session.php — Current session state
 *
 * GET /api/auth/session.php
 * Returns: { loggedIn, user? }
 *
 * Called by the frontend on page load to restore auth state.
 * If a customer is signed in, returns their profile so the
 * UI can show their name, pre-fill checkout fields, etc.
 */

header('Content-Type: application/json');
session_name('injili_customer');
session_start();

if (!empty($_SESSION['user_id'])) {
    /* Session is active — return the user profile */
    require_once __DIR__ . '/../../config/db.php';
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, first_name, last_name, email, phone, created_at FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode([
            'loggedIn' => true,
            'user'     => [
                'id'        => $user['id'],
                'name'      => $user['first_name'] . ' ' . $user['last_name'],
                'email'     => $user['email'],
                'phone'     => $user['phone'],
                'initials'  => strtoupper($user['first_name'][0] . $user['last_name'][0]),
                'since'     => date('F Y', strtotime($user['created_at'])),
            ],
        ]);
        exit;
    }
}

/* No session — return guest state */
echo json_encode(['loggedIn' => false, 'user' => null]);
