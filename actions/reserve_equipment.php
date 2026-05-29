<?php
declare(strict_types=1);

require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../database/equipment.class.php');

$user = require_role('member'); // Gated for members
$uid = current_user_id();

// Requires POST and valid CSRF token
require_post('../pages/equipment.php');

$db = get_db();
$equipmentId = (int)($_POST['equipment_id'] ?? 0);
$reservedFromRaw = trim($_POST['reserved_from'] ?? '');
$reservedToRaw = trim($_POST['reserved_to'] ?? '');

function redirect_reserve(int $eqId) {
    header('Location: ../pages/reserve_equipment.php' . ($eqId > 0 ? '?equipment_id=' . $eqId : ''));
    exit;
}

if ($equipmentId <= 0) {
    set_flash('error', 'Select a valid equipment type.');
    redirect_reserve(0);
}

$startTimestamp = strtotime($reservedFromRaw);
$endTimestamp = strtotime($reservedToRaw);

if (!$startTimestamp || !$endTimestamp) {
    set_flash('error', 'Enter valid dates and times.');
    redirect_reserve($equipmentId);
}

// Convert back to standard SQLite datetime strings
$from = date('Y-m-d H:i:00', $startTimestamp);
$to = date('Y-m-d H:i:00', $endTimestamp);
$durationMinutes = (int)round(($endTimestamp - $startTimestamp) / 60);

// Validate future start time
if ($startTimestamp <= time()) {
    set_flash('error', 'Reservations must start in the future.');
    redirect_reserve($equipmentId);
}

// Validate end time is after start time
if ($endTimestamp <= $startTimestamp) {
    set_flash('error', 'End time must be after the start time.');
    redirect_reserve($equipmentId);
}

// Validate reasonable duration (e.g. 15 mins to 2 hours)
if ($durationMinutes < 15 || $durationMinutes > 120) {
    set_flash('error', 'Reservations must be between 15 minutes and 2 hours long.');
    redirect_reserve($equipmentId);
}

// Check if the user already has an active overlapping equipment reservation
$stmt = $db->prepare(
    "SELECT id FROM equipment_reservations
     WHERE member_id = ?
       AND status = 'active'
       AND reserved_from < ?
       AND reserved_to > ?
     LIMIT 1"
);
$stmt->execute([$uid, $to, $from]);
if ($stmt->fetch()) {
    set_flash('error', 'You already have an active reservation that overlaps this time slot.');
    redirect_reserve($equipmentId);
}

// Find available units of this equipment type
$availableUnits = Equipment::getAvailableUnits($db, $equipmentId, $from, $to);

if (empty($availableUnits)) {
    set_flash('error', 'No units of this equipment type are available for the selected slot.');
    redirect_reserve($equipmentId);
}

// Allocate the first available physical unit
$unitId = (int)$availableUnits[0]['id'];
$unitLabel = $availableUnits[0]['unit_label'] ?? 'Unit';

try {
    EquipmentReservation::create($db, $uid, $unitId, $from, $to);
    set_flash('success', "Successfully reserved " . htmlspecialchars($unitLabel) . "!");
} catch (Exception $e) {
    set_flash('error', 'Failed to save reservation. Please try again.');
}

redirect_reserve($equipmentId);
