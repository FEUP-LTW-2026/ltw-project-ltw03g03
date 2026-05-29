<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$isAdmin = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
$redirect_on_error = $isAdmin ? '../pages/admin_create_user.php' : '../pages/register.php';

if ($_POST['password'] !== $_POST['password_confirm']) {
    set_flash('error', 'Passwords do not match.');
    header('Location: ' . $redirect_on_error);
    exit;
}

$db = get_db();

$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
    set_flash('error', 'All fields are required.');
    header('Location: ' . $redirect_on_error);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Invalid email address.');
    header('Location: ' . $redirect_on_error);
    exit;
}

// Security guard: only admins can create users with trainer/admin roles
$requested_role = ($isAdmin && isset($_POST['role'])) ? $_POST['role'] : 'member';

// Validate and prepare specialty (only applicable for trainers)
$specialty = null;
if ($requested_role === 'trainer') {
    $allowedSpecialties = ['strength', 'cardio', 'yoga', 'crossfit', 'pilates', 'martial_arts', 'nutrition', ''];
    $specialty = (in_array($_POST['specialty'] ?? '', $allowedSpecialties)) ? ($_POST['specialty'] ?: null) : null;
}

try {
    User::create($db, [
        'first_name' => $firstname,
        'last_name'  => $lastname,
        'email'      => $email,
        'password'   => $password,
        'phone'      => $_POST['phone'] ?? null,
        'dob'        => $_POST['dob']   ?? null,
        'role'       => $requested_role,
        'specialty'  => $specialty,
    ]);
} catch (PDOException $e) {
    $msg = 'Registration failed.';
    $info = $e->errorInfo ?? null;
    $errText = is_array($info) && isset($info[2]) ? $info[2] : $e->getMessage();
    if (stripos($errText, 'unique') !== false || stripos($errText, 'UNIQUE constraint failed') !== false) {
        $msg = 'Email is already taken.';
    } else {
        $msg = 'Email is already taken or invalid input.';
    }
    set_flash('error', $msg);
    header('Location: ' . $redirect_on_error);
    exit;
}

if ($isAdmin) {
    // Admin created the account; redirect back to admin users page without signing out
    set_flash('success', 'User account created successfully.');
    header('Location: ../pages/admin_users.php');
    exit;
} else {
    // Auto-login the newly registered member
    $user = User::getUserWithPassword($db, $email, $password);
    if ($user) {
        $_SESSION['user'] = [
            'id' => $user->id,
            'role' => $user->role,
            'username' => $user->username
        ];
        refresh_session_user();
    }
    header('Location: ../pages/dashboard.php');
    exit;
}
