<?php
declare(strict_types=1);

require_once('../config/session.php');

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../config/db.php');
require_once('../database/review.class.php');

validate_csrf();

$db = get_db();

Review::create($db, [
    'session_id' => (int)$_POST['session_id'],
    'member_id'  => $_SESSION['id'],
    'rating'     => (int)$_POST['rating'],
    'comment'    => $_POST['comment'] ?? null,
]);

header('Location: ' . $_SERVER['HTTP_REFERER']);
