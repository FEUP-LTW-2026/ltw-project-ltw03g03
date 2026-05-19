<?php
declare(strict_types=1);

require_once('../config/session.php');

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$db   = get_db();
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? null;
$user = User::getUser($db, $_SESSION['id']);

if ($user) {
    $user->firstName = $_POST['first_name'];
    $user->lastName  = $_POST['last_name'];
    $user->username  = $_POST['username'];
    $user->phone     = $_POST['phone'] ?? null;

    $user->save($db);

    // Save trainer-specific profile fields
    if ($role === 'trainer') {
        $user->saveTrainerProfile($db, [
            'bio'              => $_POST['bio']              ?? null,
            'specialty'        => $_POST['specialty']        ?? null,
            'certifications'   => $_POST['certifications']   ?? null,
            'years_experience' => $_POST['years_experience'] ?? 0,
        ]);
    }

    // Handle password separately using the dedicated method
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $user->savePassword($db, $_POST['new_password']);
        } else {
            set_flash('error', 'Passwords do not match.');
            header('Location: ../pages/profile.php');
            exit;
        }
    }

    $_SESSION['name'] = $user->name();
    refresh_session_user();
    set_flash('success', 'Profile updated successfully.');
}

header('Location: ../pages/profile.php');
