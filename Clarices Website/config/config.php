<?php
/**
 * config.php — Central configuration loader
 *
 * Reads the .env file from the project root and defines all
 * application constants used throughout the backend. No credentials
 * are hardcoded here — they all come from .env so that swapping
 * from sandbox to live requires only editing that one file.
 *
 * Usage: require_once __DIR__ . '/../config/config.php';
 */

/* ── Parse .env file ─────────────────────────────────────────────
   A lightweight .env parser so we don't need the vlucas/phpdotenv
   Composer package (keeping the stack XAMPP-compatible out of the box).
   Lines starting with # are comments. Empty lines are skipped.
   Format expected: KEY=value (no quotes needed, no shell expansion).
─────────────────────────────────────────────────────────────────── */
// Hide errors from browser output — log them only
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        die('FATAL: .env file not found at ' . $path . '. Copy .env.example and fill in your credentials.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comment lines
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        // Only process lines that contain an = sign
        if (!str_contains($line, '=')) {
            continue;
        }
        // Split on the FIRST = only so values can contain = (e.g. base64)
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Make available via getenv() and $_ENV
        if (!array_key_exists($key, $_ENV)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/* ── Load .env from project root ─────────────────────────────── */
loadEnv(dirname(__DIR__) . '/.env');

/* ── Helper: read env value with a fallback default ─────────────
   Usage: env('DB_HOST', 'localhost')
   Returns the value as a string, or the default if not set.
─────────────────────────────────────────────────────────────────── */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return ($value !== false) ? $value : $default;
}

/* ── Database constants ──────────────────────────────────────── */
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'injili_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

/* ── Application constants ───────────────────────────────────── */
define('APP_URL',  env('APP_URL', 'http://localhost/injili'));
define('APP_ENV',  env('APP_ENV', 'sandbox'));

/* ── M-Pesa Daraja constants ─────────────────────────────────── */
define('MPESA_ENV',             env('MPESA_ENV',             'sandbox'));
define('MPESA_CONSUMER_KEY',    env('MPESA_CONSUMER_KEY',    ''));
define('MPESA_CONSUMER_SECRET', env('MPESA_CONSUMER_SECRET', ''));
define('MPESA_SHORTCODE',       env('MPESA_SHORTCODE',       '174379'));
define('MPESA_PASSKEY',         env('MPESA_PASSKEY',         ''));
define('MPESA_CALLBACK_URL',    env('MPESA_CALLBACK_URL',    ''));

/* ── Email constants ─────────────────────────────────────────── */
define('MAIL_HOST',         env('MAIL_HOST',         'smtp.gmail.com'));
define('MAIL_PORT',         env('MAIL_PORT',         '587'));
define('MAIL_USERNAME',     env('MAIL_USERNAME',     ''));
define('MAIL_PASSWORD',     env('MAIL_PASSWORD',     ''));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', ''));
define('MAIL_FROM_NAME',    env('MAIL_FROM_NAME',    'Injili Apparel'));

/* ── Admin notification targets ─────────────────────────────── */
define('ADMIN_EMAIL', env('ADMIN_EMAIL', ''));
define('ADMIN_PHONE', env('ADMIN_PHONE', ''));

/* ── Africa's Talking SMS constants ──────────────────────────── */
define('AT_API_KEY',   env('AT_API_KEY',   ''));
define('AT_USERNAME',  env('AT_USERNAME',  'sandbox'));
define('AT_SENDER_ID', env('AT_SENDER_ID', 'INJILI'));

/* ── CallMeBot WhatsApp constants ───────────────────────────── */
define('CALLMEBOT_PHONE',   env('CALLMEBOT_PHONE',   ''));
define('CALLMEBOT_API_KEY', env('CALLMEBOT_API_KEY', ''));

/* ── Pickup Mtaani constants ─────────────────────────────────── */
define('PM_USE_MOCK',     env('PM_USE_MOCK',     'true') === 'true');
define('PM_BASE_URL',     env('PM_BASE_URL',     'https://api.pickupmtaani.com/v1'));
define('PM_API_KEY',      env('PM_API_KEY',      ''));
define('PM_SENDER_NAME',  env('PM_SENDER_NAME',  'Injili Apparel'));

/* ── PHP session security settings ──────────────────────────────
   Set before any session_start() call. These are safe defaults
   for an XAMPP development environment.
─────────────────────────────────────────────────────────────────── */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);   // Block JS from reading the session cookie
    ini_set('session.use_strict_mode', 1);  // Reject unrecognised session IDs
    ini_set('session.cookie_samesite', 'Lax');
}
