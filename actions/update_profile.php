<?php
declare(strict_types=1);

require_once('../config/session.php');

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$db   = get_db();
$user = User::getUser($db, $_SESSION['id']);

if ($user) {
    $user->firstName = $_POST['first_name'];
    $user->lastName  = $_POST['last_name'];
    $user->username  = $_POST['username'];
    $user->phone     = $_POST['phone'] ?? null;

    $user->save($db);

    // Handle password separately using the dedicated method
    if (!empty($_POST['new_password'])) {
        $user->savePassword($db, $_POST['new_password']);
    }

    $_SESSION['name'] = $user->name();
}

header('Location: ../pages/profile.php');
