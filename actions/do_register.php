<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$db = get_db();

$requested_role = $_POST['role'] ?? 'member';

// Only a logged-in admin can create trainer or admin accounts.
// Anyone else is always registered as a plain member.
$is_admin = isset($_SESSION['id']) && ($_SESSION['role'] ?? '') === 'admin';

if (!$is_admin) {
    $requested_role = 'member';
}

User::create($db, [
    'first_name' => $_POST['firstname'],
    'last_name'  => $_POST['lastname'],
    'email'      => $_POST['email'],
    'password'   => $_POST['password'],
    'phone'      => $_POST['phone']      ?? null,
    'dob'        => $_POST['dob']        ?? null,
    'role'       => $requested_role,
    'specialty'  => $_POST['specialty']  ?? null,
]);

// Only auto-login if this is a self-registration (no one is currently logged in)
if (!isset($_SESSION['id'])) {
    $user = User::getUserWithPassword($db, $_POST['email'], $_POST['password']);

    if ($user) {
        $_SESSION['id']   = $user->id;
        $_SESSION['name'] = $user->name();
        $_SESSION['role'] = $user->role;
    }
}

header('Location: ../pages/dashboard.php');
