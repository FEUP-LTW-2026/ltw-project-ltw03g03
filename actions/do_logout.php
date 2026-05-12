<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

// Remove remember-me token from DB if it exists
if (isset($_COOKIE['remember_token'])) {
    $db         = get_db();
    $token_hash = hash('sha256', $_COOKIE['remember_token']);
    $db->prepare('DELETE FROM remember_tokens WHERE token_hash = ?')->execute([$token_hash]);
    setcookie('remember_token', '', time() - 3600, '/');
}

$_SESSION = [];
session_destroy();

header('Location: ../pages/sign_in.php');
exit;
