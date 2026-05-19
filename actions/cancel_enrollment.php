<?php
declare(strict_types=1);

require_once('../config/session.php');

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../config/db.php');
require_once('../database/enrollment.class.php');

validate_csrf_get();

$db         = get_db();
$session_id = (int)$_GET['session_id'];

Enrollment::cancel($db, $_SESSION['id'], $session_id);

header('Location: ' . $_SERVER['HTTP_REFERER']);
