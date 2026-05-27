<?php
declare(strict_types=1);

require_once('../config/session.php');

$user = require_role('member'); // Guard so only members can cancel
$uid = current_user_id();

require_once('../config/db.php');

validate_csrf_get();

$db         = get_db();
$booking_id = (int)$_GET['booking_id'];

// Verify the booking belongs to the current user and get booking details
$stmt = $db->prepare('SELECT id, trainer_id, scheduled_at FROM pt_bookings WHERE id = ? AND member_id = ?');
$stmt->execute([$booking_id, $uid]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('error', 'Booking not found.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$db->beginTransaction();
try {
    // Cancel the booking
    $stmt = $db->prepare('UPDATE pt_bookings SET status = "cancelled" WHERE id = ?');
    $stmt->execute([$booking_id]);

    // Make the availability slot bookable again
    $stmt = $db->prepare('UPDATE pt_availability SET is_booked = 0 WHERE trainer_id = ? AND start_time = ?');
    $stmt->execute([$booking['trainer_id'], $booking['scheduled_at']]);

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    set_flash('error', 'Failed to cancel session. Please try again.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

set_flash('success', 'PT Session cancelled successfully!');
header('Location: ' . $_SERVER['HTTP_REFERER']);
