<?php
/**
 * admin/auth_check.php — Admin session guard
 *
 * Included at the top of every admin page.
 * Redirects to login.php if no valid admin session exists.
 *
 * Usage: require_once __DIR__ . '/auth_check.php';
 *        (before any output)
 */

session_name('injili_admin');
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ' . rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/login.php');
    exit;
}

/* Expose the admin's name for use in page templates */
$adminName = $_SESSION['admin_name'] ?? 'Admin';
