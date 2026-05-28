<?php
declare(strict_types=1);

require_once('../config/session.php');

$user = require_role('member'); // Guard so only members can cancel
$uid = current_user_id();

require_once('../config/db.php');
require_once('../database/enrollment.class.php');

validate_csrf_get();

$db         = get_db();
$session_id = (int)$_GET['session_id'];

$stmt = $db->prepare(
    'SELECT e.status
     FROM enrollments e
     JOIN class_sessions cs ON cs.id = e.session_id
     WHERE e.session_id = ?
       AND e.member_id = ?
       AND e.status IN ("enrolled", "waitlist")
       AND cs.status = "scheduled"
       AND cs.scheduled_at >= datetime("now")
     LIMIT 1'
);
$stmt->execute([$session_id, $uid]);
if (!$stmt->fetchColumn()) {
    set_flash('error', 'There is no upcoming enrollment to cancel for that class.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/classes.php'));
    exit;
}

Enrollment::cancel($db, $uid, $session_id);
set_flash('success', 'Your class enrollment has been cancelled.');

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/classes.php'));
