<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$db   = get_db();
$user = User::getUserWithPassword($db, $_POST['email'], $_POST['password']);

if ($user) {
    $_SESSION['id']   = $user->id;
    $_SESSION['name'] = $user->name();
    $_SESSION['role'] = $user->role;
    header('Location: ../pages/dashboard.php');
} else {
    set_flash('error', 'Invalid email or password. Please try again.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
