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

Enrollment::enroll($db, $uid, $session_id);

header('Location: ' . $_SERVER['HTTP_REFERER']);
