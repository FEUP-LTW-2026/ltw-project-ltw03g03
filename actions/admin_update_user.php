<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_users.php');

$userId = (int)($_POST['user_id'] ?? 0);
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = in_array($_POST['role'] ?? '', ['member', 'trainer', 'admin']) ? $_POST['role'] : 'member';
$specialty = null;

if ($role === 'trainer') {
    $allowedSpecialties = ['strength', 'cardio', 'yoga', 'crossfit', 'pilates', 'martial_arts', 'nutrition', ''];
    $specialty = (in_array($_POST['specialty'] ?? '', $allowedSpecialties)) ? ($_POST['specialty'] ?: null) : null;
}

if ($userId <= 0 || $firstName === '' || $lastName === '' || $email === '') {
    set_flash('error', 'All fields are required.');
    header('Location: ../pages/admin_users.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'A valid email address is required.');
    header('Location: ../pages/admin_users.php?edit_user=' . $userId);
    exit;
}

$db = get_db();

try {
    $stmt = $db->prepare(
        'UPDATE users
         SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, updated_at = datetime("now")
         WHERE id = ?'
    );
    $stmt->execute([$firstName, $lastName, strtolower($email), $phone ?: null, $role, $userId]);

    if ($role === 'trainer') {
        $stmt = $db->prepare(
            'INSERT OR IGNORE INTO trainer_profiles (user_id, specialty) VALUES (?, NULL)'
        );
        $stmt->execute([$userId]);
        
        $stmt = $db->prepare(
            'UPDATE trainer_profiles SET specialty = ? WHERE user_id = ?'
        );
        $stmt->execute([$specialty, $userId]);
    }

    set_flash('success', 'User account updated successfully.');
} catch (PDOException $e) {
    $message = 'Unable to update user account.';
    $info = $e->errorInfo ?? null;
    $errText = is_array($info) && isset($info[2]) ? $info[2] : $e->getMessage();
    if (stripos($errText, 'unique') !== false || stripos($errText, 'UNIQUE constraint failed') !== false) {
        $message = 'That email is already in use.';
    }
    set_flash('error', $message);
    header('Location: ../pages/admin_edit_user.php?id=' . $userId);
    exit;
}

header('Location: ../pages/admin_users.php');
exit;
