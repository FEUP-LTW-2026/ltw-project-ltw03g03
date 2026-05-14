<?php
declare(strict_types=1);

session_start();

require_once('../database/connection.db.php');
require_once('../database/user.class.php');

$db = getDatabaseConnection();

User::create($db, [
    'first_name' => $_POST['firstname'],
    'last_name'  => $_POST['lastname'],
    'email'      => $_POST['email'],
    'password'   => $_POST['password'],
    'phone'      => $_POST['phone']      ?? null,
    'dob'        => $_POST['dob']        ?? null,
    'role'       => $_POST['role']       ?? 'member',
    'specialty'  => $_POST['specialty']  ?? null,
]);

$user = User::getUserWithPassword($db, $_POST['email'], $_POST['password']);

if ($user) {
    $_SESSION['id']   = $user->id;
    $_SESSION['name'] = $user->name();
    $_SESSION['role'] = $user->role;
}

header('Location: ../pages/dashboard.php');
