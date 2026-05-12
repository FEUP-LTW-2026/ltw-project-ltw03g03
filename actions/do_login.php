<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/sign_in.php');
    exit;
}

$db = get_db();

$identifier = trim($_POST['email']    ?? '');   // email or username
$password   = $_POST['password']      ?? '';
$remember   = !empty($_POST['remember']);

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

if (!$user || !password_verify($password, $user['password_hash'])) {
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
$_SESSION['user'] = $user;
session_regenerate_id(true);

// ── Remember-me cookie ─────────────────────────────────────────
if ($remember) {
    $token      = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expires    = date('Y-m-d H:i:s', strtotime('+30 days'));
    $db->prepare(
        'INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
    )->execute([$user['id'], $token_hash, $expires]);
    setcookie('remember_token', $token, [
        'expires'  => strtotime('+30 days'),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

set_flash('success', 'Welcome back, ' . htmlspecialchars($user['first_name']) . '!');
header('Location: ../pages/dashboard.php');
exit;
