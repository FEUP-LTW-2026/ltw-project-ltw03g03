<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db   = get_db();

if ($user['role'] !== 'member') {
    set_flash('error', 'Only members can enroll in classes.');
    header('Location: ../pages/classes.php');
    exit;
}

$session_id = (int)($_GET['session_id'] ?? 0);
if (!$session_id) {
    header('Location: ../pages/classes.php');
    exit;
}

// Verify session exists and is upcoming
$stmt = $db->prepare(
    'SELECT cs.id, cs.scheduled_at, c.capacity, c.name
     FROM class_sessions cs JOIN classes c ON c.id = cs.class_id
     WHERE cs.id = ? AND cs.scheduled_at >= NOW() AND cs.status = "scheduled"'
);
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    set_flash('error', 'Class session not found or no longer available.');
    header('Location: ../pages/classes.php');
    exit;
}

// Check if already enrolled / waitlisted
$stmt = $db->prepare('SELECT id, status FROM enrollments WHERE session_id = ? AND member_id = ?');
$stmt->execute([$session_id, $user['id']]);
$existing = $stmt->fetch();

if ($existing) {
    $msg = $existing['status'] === 'waitlist'
        ? 'You are already on the waitlist for this class.'
        : 'You are already enrolled in this class.';
    set_flash('error', $msg);
    header('Location: ../pages/classes.php');
    exit;
}

// Count current enrollments
$stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE session_id = ? AND status = 'enrolled'");
$stmt->execute([$session_id]);
$enrolled_count = (int)$stmt->fetchColumn();

$is_full = $enrolled_count >= (int)$session['capacity'];
$status  = $is_full ? 'waitlist' : 'enrolled';

// Insert enrollment
$db->prepare(
    'INSERT INTO enrollments (session_id, member_id, status) VALUES (?, ?, ?)'
)->execute([$session_id, $user['id'], $status]);

if ($status === 'waitlist') {
    set_flash('success', 'Class is full — you have been added to the waitlist for "' . $session['name'] . '".');
} else {
    set_flash('success', 'You are enrolled in "' . $session['name'] . '" on ' . date('D d M, H:i', strtotime($session['scheduled_at'])) . '!');
}

header('Location: ../pages/classes.php');
exit;
