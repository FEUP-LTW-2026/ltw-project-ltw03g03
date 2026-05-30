<?php
// PDO database connection singleton (SQLite).

define('DB_PATH', __DIR__ . '/../database/database.db');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Ensure the directory for the database file exists
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dsn = 'sqlite:' . DB_PATH;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, null, null, $options);
            // Enable WAL journal mode for better concurrent read performance
            $pdo->exec('PRAGMA journal_mode = WAL');
            // Enable foreign key enforcement (off by default in SQLite)
            $pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            // In production, log this and show a generic error page
            error_log('DB connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Database unavailable.']));
        }
    }
    return $pdo;
}
