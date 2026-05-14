<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../database/connection.db.php');
require_once('../database/user.class.php');

$db   = getDatabaseConnection();
$user = User::getUser($db, $_SESSION['id']);

if ($user) {
    $user->firstName = $_POST['first_name'];
    $user->lastName  = $_POST['last_name'];
    $user->username  = $_POST['username'];
    $user->phone     = $_POST['phone'] ?? null;

    if (!empty($_POST['new_password'])) {
        $user->password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    }

    $user->save($db);

    $_SESSION['name'] = $user->name();
}

header('Location: ../pages/profile.php');
