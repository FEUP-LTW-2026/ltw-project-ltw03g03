<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db   = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/profile.php');
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name']  ?? '');
$username   = trim($_POST['username']   ?? '');
$phone      = trim($_POST['phone']      ?? '');
$password   = $_POST['new_password']    ?? '';
$confirm    = $_POST['confirm_password']?? '';

$errors = [];
if (!$first_name) $errors[] = 'First name is required.';
if (!$last_name)  $errors[] = 'Last name is required.';
if (!$username)   $errors[] = 'Username is required.';

// Check username uniqueness (excluding current user)
if ($username) {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $stmt->execute([$username, $user['id']]);
    if ($stmt->fetch()) $errors[] = 'That username is already taken.';
}

// Password change (optional)
$new_hash = null;
if ($password) {
    if (strlen($password) < 8) $errors[] = 'New password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$errors) $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Profile photo upload
$photo_path = $user['photo_path'];
if (!empty($_FILES['photo']['tmp_name'])) {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $mime    = mime_content_type($_FILES['photo']['tmp_name']);
    if (!in_array($mime, $allowed)) {
        $errors[] = 'Photo must be a JPEG, PNG, WebP, or GIF.';
    } elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
        $errors[] = 'Photo must be under 3 MB.';
    } else {
        $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
        $dest     = __DIR__ . '/../uploads/photos/' . $filename;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $photo_path = 'uploads/photos/' . $filename;
        }
    }
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    header('Location: ../pages/profile.php');
    exit;
}

// Build update query dynamically
if ($new_hash) {
    $db->prepare(
        'UPDATE users SET first_name=?, last_name=?, username=?, phone=?, photo_path=?, password_hash=? WHERE id=?'
    )->execute([$first_name, $last_name, $username, $phone ?: null, $photo_path, $new_hash, $user['id']]);
} else {
    $db->prepare(
        'UPDATE users SET first_name=?, last_name=?, username=?, phone=?, photo_path=? WHERE id=?'
    )->execute([$first_name, $last_name, $username, $phone ?: null, $photo_path, $user['id']]);
}

// Trainer-specific profile fields
if ($user['role'] === 'trainer') {
    $bio       = trim($_POST['bio']            ?? '');
    $specialty = trim($_POST['specialty']      ?? '');
    $certs     = trim($_POST['certifications'] ?? '');
    $exp       = (int) ($_POST['years_experience'] ?? 0);
    $db->prepare(
        'INSERT INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE bio=VALUES(bio), specialty=VALUES(specialty),
           certifications=VALUES(certifications), years_experience=VALUES(years_experience)'
    )->execute([$user['id'], $bio, $specialty, $certs, $exp]);
}

refresh_session_user();
set_flash('success', 'Profile updated successfully.');
header('Location: ../pages/profile.php');
exit;
