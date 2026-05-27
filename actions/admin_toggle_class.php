<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_classes.php');

$classId = (int)($_POST['class_id'] ?? 0);
if ($classId <= 0) {
    set_flash('error', 'Invalid class selected.');
    header('Location: ../pages/admin_classes.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT is_active FROM classes WHERE id = ?');
$stmt->execute([$classId]);
$class = $stmt->fetch();

if (!$class) {
    set_flash('error', 'Class not found.');
    header('Location: ../pages/admin_classes.php');
    exit;
}

$newState = $class['is_active'] ? 0 : 1;
$db->prepare('UPDATE classes SET is_active = ?, updated_at = datetime("now") WHERE id = ?')
   ->execute([$newState, $classId]);

set_flash('success', $newState ? 'Class reactivated.' : 'Class deactivated.');
header('Location: ../pages/admin_classes.php');
exit;
