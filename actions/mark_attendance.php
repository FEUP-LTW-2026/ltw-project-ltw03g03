<?php
declare(strict_types=1);

require_once('../config/session.php');

$trainer = require_role('trainer');
$trainer_id = current_user_id();

require_once('../config/db.php');

validate_csrf_get();

$db = get_db();
$session_id = (int)($_GET['session_id'] ?? 0);
$member_id = (int)($_GET['member_id'] ?? 0);

$stmt = $db->prepare(
    'SELECT e.id
     FROM enrollments e
     JOIN class_sessions cs ON cs.id = e.session_id
     JOIN classes c ON c.id = cs.class_id
     WHERE e.session_id = ?
       AND e.member_id = ?
       AND e.status = "enrolled"
       AND c.trainer_id = ?
     LIMIT 1'
);
$stmt->execute([$session_id, $member_id, $trainer_id]);
$enrollment_id = $stmt->fetchColumn();

if (!$enrollment_id) {
    set_flash('error', 'Could not mark attendance for that member.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/my_roster.php'));
    exit;
}

$stmt = $db->prepare('UPDATE enrollments SET status = "attended" WHERE id = ?');
$stmt->execute([$enrollment_id]);

set_flash('success', 'Attendance marked.');
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/my_roster.php'));
