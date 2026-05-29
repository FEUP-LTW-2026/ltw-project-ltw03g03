<?php
declare(strict_types=1);

require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/db.php');
require_once(__DIR__ . '/../database/equipment.class.php');

$user = require_role('member'); // Gated for members
$uid = current_user_id();

// Requires GET CSRF validation
validate_csrf_get();

$db = get_db();
$reservationId = (int)($_GET['reservation_id'] ?? 0);

if ($reservationId <= 0) {
    set_flash('error', 'Invalid reservation.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php'));
    exit;
}

// Check if reservation exists, belongs to this member, and is active
$stmt = $db->prepare(
    "SELECT id, status FROM equipment_reservations
     WHERE id = ? AND member_id = ?
     LIMIT 1"
);
$stmt->execute([$reservationId, $uid]);
$reservation = $stmt->fetch();

if (!$reservation) {
    set_flash('error', 'Reservation not found.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php'));
    exit;
}

if ($reservation['status'] !== 'active') {
    set_flash('error', 'Only active reservations can be cancelled.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php'));
    exit;
}

try {
    EquipmentReservation::cancel($db, $reservationId, $uid);
    set_flash('success', 'Equipment reservation cancelled successfully.');
} catch (Exception $e) {
    set_flash('error', 'Failed to cancel reservation.');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php'));
exit;
