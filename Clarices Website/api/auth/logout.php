<?php
/**
 * api/auth/logout.php — Customer sign-out endpoint
 * POST /api/auth/logout.php
 * Returns: { success, message }
 */

header('Content-Type: application/json');
session_name('injili_customer');
session_start();
/* Destroy all session data and the session cookie */
$_SESSION = [];
session_destroy();
echo json_encode(['success' => true, 'message' => 'Signed out']);
