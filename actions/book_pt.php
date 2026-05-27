<?php
declare(strict_types=1);

require_once('../config/session.php');
require_post();

$user = require_role('member');
$uid = current_user_id();

require_once('../config/db.php');
$db = get_db();

$slot_id = (int)($_POST['slot_id'] ?? 0);

if ($slot_id <= 0) {
    set_flash('error', 'Invalid slot.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$stmt = $db->prepare('SELECT id, trainer_id, start_time, end_time, is_booked FROM pt_availability WHERE id = ?');
$stmt->execute([$slot_id]);
$slot = $stmt->fetch();

if (!$slot) {
    set_flash('error', 'Slot not found.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($slot['is_booked']) {
    set_flash('error', 'This slot is already booked.');
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$start_ts = strtotime($slot['start_time']);
$end_ts = strtotime($slot['end_time']);
$duration = (int)round(($end_ts - $start_ts) / 60);

$db->beginTransaction();
try {
    $stmt = $db->prepare('INSERT INTO pt_bookings (trainer_id, member_id, scheduled_at, duration_min, status) VALUES (?, ?, ?, ?, "pending")');
    $stmt->execute([$slot['trainer_id'], $uid, $slot['start_time'], $duration]);
    
    $stmt = $db->prepare('UPDATE pt_availability SET is_booked = 1 WHERE id = ?');
    $stmt->execute([$slot_id]);

    $db->commit();
    set_flash('success', 'PT Session booked successfully!');
} catch (Exception $e) {
    $db->rollBack();
    set_flash('error', 'Failed to book session. Please try again.');
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
