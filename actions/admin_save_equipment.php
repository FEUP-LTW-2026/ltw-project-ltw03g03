<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_equipment.php');

$equipmentId = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? 'other');
$notes = trim($_POST['notes'] ?? '');
$validCategories = ['cardio','weights','machines','other'];
$category = in_array($category, $validCategories, true) ? $category : 'other';

if ($name === '') {
    set_flash('error', 'Equipment name is required.');
    header('Location: ../pages/admin_equipment.php');
    exit;
}

$db = get_db();

try {
    if ($equipmentId > 0) {
        $stmt = $db->prepare(
            'UPDATE equipment SET name = ?, category = ?, notes = ?, updated_at = datetime("now") WHERE id = ?'
        );
        $stmt->execute([$name, $category, $notes ?: null, $equipmentId]);
        set_flash('success', 'Equipment updated successfully.');
    } else {
        $stmt = $db->prepare(
            'INSERT INTO equipment (name, category, notes) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $category, $notes ?: null]);
        set_flash('success', 'Equipment type added successfully.');
    }
} catch (PDOException $e) {
    set_flash('error', 'Unable to save equipment.');
}

header('Location: ../pages/admin_equipment.php');
exit;
