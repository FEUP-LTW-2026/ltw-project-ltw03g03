<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$db   = get_db();
$user = User::getUserWithPassword($db, $_POST['email'], $_POST['password']);

if ($user) {
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'         => $user->id,
        'username'   => $user->username,
        'email'      => $user->email,
        'first_name' => $user->firstName,
        'last_name'  => $user->lastName,
        'photo_path' => $user->photoPath,
        'role'       => $user->role,
    ];
    // Keep legacy keys for any code that still reads them
    $_SESSION['id']   = $user->id;
    $_SESSION['name'] = $user->name();
    $_SESSION['role'] = $user->role;

    // Remember-me cookie
    if (!empty($_POST['remember'])) {
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $db->prepare(
            "INSERT INTO remember_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, datetime('now', '+30 days'))"
        )->execute([$user->id, $tokenHash]);
        setcookie('remember_token', $token, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    header('Location: ../pages/dashboard.php');
} else {
    set_flash('error', 'Invalid email or password. Please try again.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
