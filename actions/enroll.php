<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../database/connection.db.php');
require_once('../database/enrollment.class.php');

$db         = getDatabaseConnection();
$session_id = (int)$_GET['session_id'];

Enrollment::enroll($db, $_SESSION['id'], $session_id);

header('Location: ' . $_SERVER['HTTP_REFERER']);
