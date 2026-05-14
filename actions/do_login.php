<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

require_post('../pages/sign_in.php');

$db = get_db();

$identifier = trim($_POST['email']    ?? '');   // email or username
$password   = $_POST['password']      ?? '';
// Support both 'remember_me' (spec field name) and 'remember' (form field name)
$remember   = !empty($_POST['remember_me']) || !empty($_POST['remember']);

if (!$identifier || !$password) {
    set_flash('error', 'Please enter your email and password.');
    header('Location: ../pages/sign_in.php');
    exit;
}

// Support login by email OR username
$stmt = $db->prepare(
    'SELECT id, username, email, password_hash, first_name, last_name, photo_path, role, is_active
     FROM users WHERE email = ? OR username = ? LIMIT 1'
);
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user) {
    // Dummy verify to mitigate timing attacks for username enumeration
    password_verify('', '$2y$10$dummy......................');
    set_flash('error', 'Invalid credentials. Please try again.');
    header('Location: ../pages/sign_in.php');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    set_flash('error', 'Invalid credentials. Please try again.');
    header('Location: ../pages/sign_in.php');
    exit;
}

if (!$user['is_active']) {
    set_flash('error', 'Your account has been deactivated. Please contact the gym.');
    header('Location: ../pages/sign_in.php');
    exit;
}

// ── Start session ──────────────────────────────────────────────
unset($user['password_hash'], $user['is_active']);
session_regenerate_id(true);
$_SESSION['user'] = $user;

// ── Remember-me cookie ─────────────────────────────────────────
if ($remember) {
    $token      = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $db->prepare(
        "INSERT INTO remember_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, datetime('now', '+30 days'))"
    )->execute([$user['id'], $token_hash]);
    setcookie('remember_token', $token, [
        'expires'  => time() + (30 * 24 * 60 * 60),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

set_flash('success', 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!');
header('Location: ../pages/dashboard.php');
exit;
