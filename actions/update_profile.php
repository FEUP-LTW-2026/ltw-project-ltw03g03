<?php
declare(strict_types=1);

require_once('../config/session.php');

$uid = current_user_id();
if (!$uid) die(header('Location: ../pages/sign_in.php'));

require_once('../config/db.php');
require_once('../database/user.class.php');

validate_csrf();

$db   = get_db();
$role = current_user()['role'] ?? null;
$user = User::getUser($db, $uid);

if ($user) {
    $user->firstName = trim($_POST['first_name'] ?? $user->firstName);
    $user->lastName  = trim($_POST['last_name']  ?? $user->lastName);
    $user->phone     = $_POST['phone'] ?? $user->phone;

    // Validate username uniqueness
    $newUsername = trim($_POST['username'] ?? $user->username);
    if ($newUsername !== $user->username) {
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->execute([$newUsername, $uid]);
        if ($stmt->fetch()) {
            set_flash('error', 'That username is already taken.');
            header('Location: ../pages/profile.php');
            exit;
        }
    }
    $user->username = $newUsername;

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (in_array($ext, $allowed) && in_array($mime, $allowed_mimes) && $file['size'] < 5 * 1024 * 1024) {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $new_name = 'user_' . $user->id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
                $user->photoPath = 'uploads/profiles/' . $new_name;
            }
        } else {
            set_flash('error', 'Invalid photo. Use JPG, PNG or WebP under 5 MB.');
            header('Location: ../pages/profile.php');
            exit;
        }
    }

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

    refresh_session_user();
    set_flash('success', 'Profile updated successfully.');
}

header('Location: ../pages/profile.php');
