<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

require_admin();
require_post('../pages/admin_equipment.php');

$equipmentId = (int)($_POST['equipment_id'] ?? 0);
if ($equipmentId <= 0) {
    set_flash('error', 'Invalid equipment selected.');
    header('Location: ../pages/admin_equipment.php');
    exit;
}

$db = get_db();
$db->prepare('DELETE FROM equipment WHERE id = ?')->execute([$equipmentId]);
set_flash('success', 'Equipment type removed.');
header('Location: ../pages/admin_equipment.php');
exit;
