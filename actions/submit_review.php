<?php
declare(strict_types=1);

require_once('../config/session.php');

$user = require_role('member');

require_once('../config/db.php');
require_once('../database/review.class.php');

validate_csrf();

$db = get_db();
$session_id = (int)($_POST['session_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));
$redirect = $_POST['redirect'] ?? '../pages/profile.php';
if (!in_array($redirect, ['../pages/profile.php', '../pages/my_reviews.php'], true)) {
    $redirect = '../pages/profile.php';
}

if ($rating < 1 || $rating > 5) {
    set_flash('error', 'Please choose a rating between 1 and 5.');
    header('Location: ' . $redirect);
    exit;
}

$stmt = $db->prepare(
    'SELECT 1
     FROM enrollments
     WHERE session_id = ?
       AND member_id = ?
       AND status = "attended"
     LIMIT 1'
);
$stmt->execute([$session_id, $user['id']]);
if (!$stmt->fetchColumn()) {
    set_flash('error', 'You can only review classes you have attended.');
    header('Location: ' . $redirect);
    exit;
}

Review::create($db, [
    'session_id' => $session_id,
    'member_id'  => $user['id'],
    'rating'     => $rating,
    'comment'    => $comment !== '' ? $comment : null,
]);

set_flash('success', 'Thanks for reviewing the class.');
header('Location: ' . $redirect);
