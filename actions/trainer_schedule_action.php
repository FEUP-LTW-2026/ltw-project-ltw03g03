<?php
declare(strict_types=1);

require_once('../config/session.php');
require_once('../config/db.php');

$trainer = require_trainer();
require_post('../pages/my_schedule.php');

$db = get_db();
$trainerId = current_user_id();
$action = $_POST['action'] ?? '';

function redirect_schedule() {
    header('Location: ../pages/my_schedule.php');
    exit;
}

try {
    if ($action === 'create_class_session') {
        $classId = (int)($_POST['class_id'] ?? 0);
        $scheduledAtRaw = trim($_POST['scheduled_at'] ?? '');
        $timestamp = strtotime($scheduledAtRaw);

        if ($classId <= 0 || !$timestamp) {
            set_flash('error', 'Choose a class and a valid date/time.');
            redirect_schedule();
        }

        $scheduledAt = date('Y-m-d H:i:00', $timestamp);
        if (strtotime($scheduledAt) <= time()) {
            set_flash('error', 'New sessions must be scheduled in the future.');
            redirect_schedule();
        }

        $stmt = $db->prepare(
            'SELECT id FROM classes
             WHERE id = ? AND trainer_id = ? AND is_active = 1'
        );
        $stmt->execute([$classId, $trainerId]);
        if (!$stmt->fetch()) {
            set_flash('error', 'You can only schedule classes assigned to you.');
            redirect_schedule();
        }

        $stmt = $db->prepare(
            'SELECT id, status FROM class_sessions
             WHERE class_id = ? AND scheduled_at = ?
             LIMIT 1'
        );
        $stmt->execute([$classId, $scheduledAt]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'cancelled') {
                $db->prepare("UPDATE class_sessions SET status = 'scheduled' WHERE id = ?")
                   ->execute([$existing['id']]);
                set_flash('success', 'Class session restored.');
            } else {
                set_flash('error', 'That class already has a session at this time.');
            }
            redirect_schedule();
        }

        $db->prepare(
            "INSERT INTO class_sessions (class_id, scheduled_at, status)
             VALUES (?, ?, 'scheduled')"
        )->execute([$classId, $scheduledAt]);

        set_flash('success', 'Class session scheduled.');
        redirect_schedule();
    }

    if ($action === 'create_pt_slot') {
        $startRaw = trim($_POST['start_time'] ?? '');
        $endRaw = trim($_POST['end_time'] ?? '');
        $startTimestamp = strtotime($startRaw);
        $endTimestamp = strtotime($endRaw);

        if (!$startTimestamp || !$endTimestamp) {
            set_flash('error', 'Choose a valid PT start and end time.');
            redirect_schedule();
        }

        $startTime = date('Y-m-d H:i:00', $startTimestamp);
        $endTime = date('Y-m-d H:i:00', $endTimestamp);
        $durationMinutes = (int)(($endTimestamp - $startTimestamp) / 60);

        if ($startTimestamp <= time()) {
            set_flash('error', 'PT slots must start in the future.');
            redirect_schedule();
        }

        if ($durationMinutes < 30 || $durationMinutes > 180) {
            set_flash('error', 'PT slots must be between 30 and 180 minutes.');
            redirect_schedule();
        }

        $stmt = $db->prepare(
            'SELECT id FROM pt_availability
             WHERE trainer_id = ?
               AND start_time < ?
               AND end_time > ?
             LIMIT 1'
        );
        $stmt->execute([$trainerId, $endTime, $startTime]);
        if ($stmt->fetch()) {
            set_flash('error', 'That PT slot overlaps another availability slot.');
            redirect_schedule();
        }

        $db->prepare(
            'INSERT INTO pt_availability (trainer_id, start_time, end_time, is_booked)
             VALUES (?, ?, ?, 0)'
        )->execute([$trainerId, $startTime, $endTime]);

        set_flash('success', 'PT availability added.');
        redirect_schedule();
    }

    if ($action === 'cancel_pt_slot') {
        $slotId = (int)($_POST['slot_id'] ?? 0);

        $stmt = $db->prepare(
            'SELECT id, is_booked
             FROM pt_availability
             WHERE id = ? AND trainer_id = ?'
        );
        $stmt->execute([$slotId, $trainerId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            set_flash('error', 'PT slot not found.');
            redirect_schedule();
        }

        if ((int)$slot['is_booked'] === 1) {
            set_flash('error', 'Booked PT slots must be cancelled from the booking card.');
            redirect_schedule();
        }

        $db->prepare('DELETE FROM pt_availability WHERE id = ?')->execute([$slotId]);

        set_flash('success', 'PT availability removed.');
        redirect_schedule();
    }

    if ($action === 'cancel_class_session' || $action === 'complete_class_session') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $status = $action === 'cancel_class_session' ? 'cancelled' : 'completed';

        $stmt = $db->prepare(
            'SELECT cs.id, cs.scheduled_at, cs.status
             FROM class_sessions cs
             JOIN classes c ON c.id = cs.class_id
             WHERE cs.id = ? AND c.trainer_id = ?'
        );
        $stmt->execute([$sessionId, $trainerId]);
        $session = $stmt->fetch();
        if (!$session) {
            set_flash('error', 'Session not found.');
            redirect_schedule();
        }

        if ($session['status'] !== 'scheduled') {
            set_flash('error', 'Only scheduled class sessions can be updated.');
            redirect_schedule();
        }

        if ($status === 'completed' && strtotime($session['scheduled_at']) > time()) {
            set_flash('error', 'You can only complete a class after its scheduled time.');
            redirect_schedule();
        }

        $db->beginTransaction();
        $db->prepare('UPDATE class_sessions SET status = ? WHERE id = ?')
           ->execute([$status, $sessionId]);

        if ($status === 'completed') {
            $db->prepare(
                "UPDATE enrollments
                 SET status = 'attended'
                 WHERE session_id = ? AND status = 'enrolled'"
            )->execute([$sessionId]);
        }
        $db->commit();

        set_flash('success', $status === 'cancelled' ? 'Class session cancelled.' : 'Class session completed.');
        redirect_schedule();
    }

    if ($action === 'update_pt_status') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $allowed = ['confirmed', 'cancelled', 'completed'];

        if (!in_array($newStatus, $allowed, true)) {
            set_flash('error', 'Invalid booking status.');
            redirect_schedule();
        }

        $stmt = $db->prepare(
            'SELECT id, scheduled_at, status
             FROM pt_bookings
             WHERE id = ? AND trainer_id = ?'
        );
        $stmt->execute([$bookingId, $trainerId]);
        $booking = $stmt->fetch();
        if (!$booking) {
            set_flash('error', 'PT booking not found.');
            redirect_schedule();
        }

        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
        ];
        if (!in_array($newStatus, $validTransitions[$booking['status']] ?? [], true)) {
            set_flash('error', 'This PT booking can no longer be updated that way.');
            redirect_schedule();
        }

        if ($newStatus === 'completed' && strtotime($booking['scheduled_at']) > time()) {
            set_flash('error', 'You can only complete a PT session after its scheduled time.');
            redirect_schedule();
        }

        $db->beginTransaction();
        $db->prepare('UPDATE pt_bookings SET status = ? WHERE id = ?')
           ->execute([$newStatus, $bookingId]);

        if ($newStatus === 'cancelled') {
            $db->prepare(
                'UPDATE pt_availability
                 SET is_booked = 0
                 WHERE trainer_id = ? AND start_time = ?'
            )->execute([$trainerId, $booking['scheduled_at']]);
        }
        $db->commit();

        set_flash('success', 'PT booking updated.');
        redirect_schedule();
    }

    set_flash('error', 'Unknown schedule action.');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    set_flash('error', 'Unable to update schedule.');
}

redirect_schedule();
