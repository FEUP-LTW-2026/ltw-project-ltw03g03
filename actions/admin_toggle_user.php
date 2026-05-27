<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_users.php');

$userId = (int)($_POST['user_id'] ?? 0);
$currentUserId = current_user_id();

if ($userId <= 0) {
    set_flash('error', 'Invalid user selected.');
    header('Location: ../pages/admin_users.php');
    exit;
}

if ($userId === $currentUserId) {
    set_flash('error', 'You cannot deactivate your own account.');
    header('Location: ../pages/admin_users.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT is_active FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'User not found.');
    header('Location: ../pages/admin_users.php');
    exit;
}

$newState = $user['is_active'] ? 0 : 1;
$db->prepare('UPDATE users SET is_active = ?, updated_at = datetime("now") WHERE id = ?')
   ->execute([$newState, $userId]);

set_flash('success', $newState ? 'User reactivated.' : 'User deactivated.');
header('Location: ../pages/admin_users.php');
exit;
