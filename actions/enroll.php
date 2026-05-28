<?php
declare(strict_types=1);

require_once('../config/session.php');

$user = require_role('member'); // Guard so only members can enroll
$uid = current_user_id();

require_once('../config/db.php');
require_once('../database/enrollment.class.php');

validate_csrf_get();

$db         = get_db();
$session_id = (int)$_GET['session_id'];

$stmt = $db->prepare(
    'SELECT cs.id
     FROM class_sessions cs
     JOIN classes c ON c.id = cs.class_id
     WHERE cs.id = ?
       AND cs.status = "scheduled"
       AND c.is_active = 1
       AND cs.scheduled_at >= datetime("now")
     LIMIT 1'
);
$stmt->execute([$session_id]);
if (!$stmt->fetchColumn()) {
    set_flash('error', 'That class is no longer available for enrollment.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/classes.php'));
    exit;
}

$stmt = $db->prepare(
    'SELECT status FROM enrollments
     WHERE session_id = ? AND member_id = ?
     LIMIT 1'
);
$stmt->execute([$session_id, $uid]);
$current_status = $stmt->fetchColumn();
if ($current_status === 'enrolled' || $current_status === 'waitlist') {
    set_flash('info', 'You are already signed up for that class.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/classes.php'));
    exit;
}

Enrollment::enroll($db, $uid, $session_id);
set_flash('success', 'Your class booking has been updated.');

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/classes.php'));
