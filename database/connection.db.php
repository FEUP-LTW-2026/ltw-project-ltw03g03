<?php
declare(strict_types=1);

function getDatabaseConnection(): PDO {
    static $db = null;
    if ($db === null) {
        $path = __DIR__ . '/w8.db';
        $db   = new PDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Required every connection in SQLite
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA journal_mode = WAL');
    }
    return $db;
}
