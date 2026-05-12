<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db   = get_db();

$session_id = (int)($_GET['session_id'] ?? 0);
if (!$session_id) {
    header('Location: ../pages/classes.php');
    exit;
}

// Find the enrollment
$stmt = $db->prepare(
    'SELECT e.id, e.status, c.name, c.capacity, cs.scheduled_at
     FROM enrollments e
     JOIN class_sessions cs ON cs.id = e.session_id
     JOIN classes c ON c.id = cs.class_id
     WHERE e.session_id = ? AND e.member_id = ?'
);
$stmt->execute([$session_id, $user['id']]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    set_flash('error', 'Enrollment not found.');
    header('Location: ../pages/classes.php');
    exit;
}

// Can only cancel future classes
if (strtotime($enrollment['scheduled_at']) <= time()) {
    set_flash('error', 'You cannot cancel a class that has already started.');
    header('Location: ../pages/classes.php');
    exit;
}

$was_enrolled = $enrollment['status'] === 'enrolled';

// Cancel the enrollment
$db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE id = ?")->execute([$enrollment['id']]);

// If this freed a spot, promote the first person on the waitlist
if ($was_enrolled) {
    $stmt = $db->prepare(
        "SELECT id, member_id FROM enrollments
         WHERE session_id = ? AND status = 'waitlist'
         ORDER BY enrolled_at ASC LIMIT 1"
    );
    $stmt->execute([$session_id]);
    $next = $stmt->fetch();

    if ($next) {
        $db->prepare("UPDATE enrollments SET status = 'enrolled' WHERE id = ?")->execute([$next['id']]);

        // Notify the promoted member
        $db->prepare(
            "INSERT INTO notifications (user_id, type, message) VALUES (?, 'waitlist_update', ?)"
        )->execute([
            $next['member_id'],
            'A spot opened up! You have been enrolled in "' . $enrollment['name'] . '" on ' . date('D d M, H:i', strtotime($enrollment['scheduled_at'])) . '.'
        ]);
    }
}

set_flash('success', 'Your enrollment in "' . $enrollment['name'] . '" has been cancelled.');
header('Location: ../pages/classes.php');
exit;
