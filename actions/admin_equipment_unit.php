<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_equipment.php');

$action = $_POST['action'] ?? 'update';
$equipmentId = (int)($_POST['equipment_id'] ?? 0);
$unitId = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
$status = trim($_POST['status'] ?? 'available');
$unitLabel = trim($_POST['unit_label'] ?? '');
$validStatuses = ['available','maintenance','retired'];
$status = in_array($status, $validStatuses, true) ? $status : 'available';

$db = get_db();

if ($action === 'add') {
    if ($equipmentId <= 0) {
        set_flash('error', 'Invalid equipment selected.');
        header('Location: ../pages/admin_equipment.php');
        exit;
    }

    $stmt = $db->prepare(
        'INSERT INTO equipment_units (equipment_id, unit_label, status) VALUES (?, ?, ?)'
    );
    $stmt->execute([$equipmentId, $unitLabel ?: null, $status]);
    set_flash('success', 'Equipment unit added.');
} elseif ($action === 'delete') {
    if ($unitId <= 0) {
        set_flash('error', 'Invalid unit selected.');
        header('Location: ../pages/admin_equipment.php?edit_equipment=' . $equipmentId);
        exit;
    }
    $db->prepare('DELETE FROM equipment_units WHERE id = ?')->execute([$unitId]);
    set_flash('success', 'Equipment unit removed.');
} else {
    if ($unitId <= 0) {
        set_flash('error', 'Invalid unit selected.');
        header('Location: ../pages/admin_equipment.php?edit_equipment=' . $equipmentId);
        exit;
    }
    $stmt = $db->prepare('UPDATE equipment_units SET status = ?, unit_label = ? WHERE id = ?');
    $stmt->execute([$status, $unitLabel ?: null, $unitId]);
    set_flash('success', 'Equipment unit updated.');
}

header('Location: ../pages/admin_equipment.php?edit_equipment=' . $equipmentId);
exit;
