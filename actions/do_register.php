<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

if ($_POST['password'] !== $_POST['password_confirm']) {
    set_flash('error', 'Passwords do not match.');
    header('Location: ../pages/register.php');
    exit;
}

$db = get_db();


$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
    set_flash('error', 'All fields are required.');
    header('Location: ../pages/register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Invalid email address.');
    header('Location: ../pages/register.php');
    exit;
}

try {
    User::create($db, [
        'first_name' => $firstname,
        'last_name'  => $lastname,
        'email'      => $email,
        'password'   => $password,
        'phone'      => $_POST['phone'] ?? null,
        'dob'        => $_POST['dob']   ?? null,
        'role'       => 'member',
    ]);
} catch (PDOException $e) {
    $msg = 'Registration failed.';
    $info = $e->errorInfo ?? null;
    // SQLite constraint violation code is 19; MySQL uses 1062 for duplicates.
    $errText = is_array($info) && isset($info[2]) ? $info[2] : $e->getMessage();
    if (stripos($errText, 'unique') !== false || stripos($errText, 'UNIQUE constraint failed') !== false || stripos($errText, '1062') !== false) {
        $msg = 'Email is already taken.';
    } else {
        $msg = 'Email is already taken or invalid input.';
    }
    set_flash('error', $msg);
    header('Location: ../pages/register.php');
    exit;
}

// Auto-login the newly registered member
$user = User::getUserWithPassword($db, $email, $password);

if ($user) {
    $_SESSION['user'] = [
        'id'       => $user->id,
        'role'     => $user->role,
        'username' => $user->username
    ];
    refresh_session_user();
}

header('Location: ../pages/dashboard.php');
