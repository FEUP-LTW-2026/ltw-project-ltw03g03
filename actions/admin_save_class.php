<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_classes.php');

$classId = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? 'other');
$level = trim($_POST['level'] ?? 'all');
$capacity = max(1, (int)($_POST['capacity'] ?? 20));
$trainerId = isset($_POST['trainer_id']) && $_POST['trainer_id'] !== '' ? (int)$_POST['trainer_id'] : null;
$description = trim($_POST['description'] ?? '');

$validTypes = ['powerlifting','hiit','crossfit','yoga','other'];
$validLevels = ['all','beginner','intermediate','advanced'];
$type = in_array($type, $validTypes, true) ? $type : 'other';
$level = in_array($level, $validLevels, true) ? $level : 'all';

if ($name === '') {
    set_flash('error', 'A class name is required.');
    header('Location: ../pages/admin_classes.php');
    exit;
}

$db = get_db();

try {
    if ($classId > 0) {
        $stmt = $db->prepare(
            'UPDATE classes
             SET name = ?, type = ?, level = ?, capacity = ?, trainer_id = ?, description = ?, updated_at = datetime("now")
             WHERE id = ?'
        );
        $stmt->execute([$name, $type, $level, $capacity, $trainerId, $description ?: null, $classId]);
        set_flash('success', 'Class updated successfully.');
    } else {
        $stmt = $db->prepare(
            'INSERT INTO classes (name, type, level, capacity, trainer_id, description)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $type, $level, $capacity, $trainerId, $description ?: null]);
        set_flash('success', 'Class created successfully.');
    }
} catch (PDOException $e) {
    set_flash('error', 'Unable to save class.');
}

header('Location: ../pages/admin_classes.php');
exit;
