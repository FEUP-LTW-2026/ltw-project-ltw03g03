<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['id'])) die(header('Location: ../pages/sign_in.php'));

require_once('../database/connection.db.php');
require_once('../database/review.class.php');

$db = getDatabaseConnection();

Review::create($db, [
    'session_id' => (int)$_POST['session_id'],
    'member_id'  => $_SESSION['id'],
    'rating'     => (int)$_POST['rating'],
    'comment'    => $_POST['comment'] ?? null,
]);

header('Location: ' . $_SERVER['HTTP_REFERER']);
