<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db   = get_db();

if ($user['role'] !== 'member' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/classes.php');
    exit;
}

require_post('../pages/classes.php');

$session_id = (int)($_POST['session_id'] ?? 0);
$rating     = (int)($_POST['rating']     ?? 0);
$comment    = trim($_POST['comment']     ?? '');

if (!$session_id || $rating < 1 || $rating > 5) {
    set_flash('error', 'Invalid review data.');
    header('Location: ../pages/classes.php');
    exit;
}

// Verify the member attended this session
$stmt = $db->prepare(
    "SELECT id FROM enrollments WHERE session_id = ? AND member_id = ? AND status = 'attended'"
);
$stmt->execute([$session_id, $user['id']]);
if (!$stmt->fetch()) {
    set_flash('error', 'You can only review classes you have attended.');
    header('Location: ../pages/classes.php');
    exit;
}

// Insert or update review
$stmt = $db->prepare("SELECT id FROM class_reviews WHERE session_id = ? AND member_id = ?");
$stmt->execute([$session_id, $user['id']]);
$is_update = (bool)$stmt->fetch();

$db->prepare(
    'INSERT OR REPLACE INTO class_reviews (session_id, member_id, rating, comment)
     VALUES (?, ?, ?, ?)'
)->execute([$session_id, $user['id'], $rating, $comment ?: null]);

set_flash('success', $is_update ? 'Your review has been updated.' : 'Your review has been submitted. Thank you!');
header('Location: ../pages/profile.php');
exit;
