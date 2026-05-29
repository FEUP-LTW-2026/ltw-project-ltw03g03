<?php
declare(strict_types=1);

require_once('../config/session.php');

// Logout must be a POST with a valid CSRF token to prevent CSRF forced-logout.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/dashboard.php');
    exit;
}
validate_csrf();

// Clear remember-me cookie and DB token if present
if (isset($_COOKIE['remember_token'])) {
    $tokenHash = hash('sha256', $_COOKIE['remember_token']);
    require_once('../config/db.php');
    $db = get_db();
    $db->prepare('DELETE FROM remember_tokens WHERE token_hash = ?')->execute([$tokenHash]);
    setcookie('remember_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

session_destroy();

header('Location: ../pages/sign_in.php');
exit;
