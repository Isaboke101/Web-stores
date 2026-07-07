<?php
/**
 * db.php — PDO database connection singleton
 *
 * Returns a single shared PDO instance for the entire request.
 * Calling getDB() multiple times is safe — it will always return
 * the same connection object rather than opening a new one.
 *
 * ALL queries throughout the app must use PDO prepared statements.
 * No raw string interpolation into SQL is permitted.
 *
 * Usage:
 *   require_once __DIR__ . '/../config/db.php';
 *   $db = getDB();
 *   $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
 *   $stmt->execute([':email' => $email]);
 */

require_once __DIR__ . '/config.php';

/**
 * Returns the shared PDO connection.
 * Creates it on first call, reuses it on subsequent calls.
 *
 * @return PDO
 * @throws RuntimeException if the connection fails
 */
function getDB(): PDO
{
    /* Static variable persists between calls within the same request.
       This is PHP's lightweight alternative to a Singleton class. */
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );

    $options = [
        /* Throw PDOException on errors instead of silently failing.
           This means we always get a clear error message to log. */
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

        /* Return rows as associative arrays by default.
           Keeps result handling consistent across the codebase. */
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        /* Disable emulated prepared statements.
           This makes PDO use real native MySQL prepared statements,
           which gives proper type-safe parameterised queries. */
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        /* Log the real error internally but never expose DB details
           to the browser — that would be a security leak. */
        error_log('[DB ERROR] ' . $e->getMessage());
        throw new RuntimeException('Database connection failed. Check your .env credentials.');
    }

    return $pdo;
}
