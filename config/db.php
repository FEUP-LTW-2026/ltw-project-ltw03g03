<?php
// ── config/db.php ──────────────────────────────────────────────
// PDO database connection singleton.
// Adjust DB_HOST / DB_NAME / DB_USER / DB_PASS for your environment.

define('DB_HOST', 'localhost');
define('DB_NAME', 'w8_gym');
define('DB_USER', 'root');          // change to your DB user
define('DB_PASS', '');              // change to your DB password
define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log this and show a generic error page
            error_log('DB connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Database unavailable.']));
        }
    }
    return $pdo;
}
