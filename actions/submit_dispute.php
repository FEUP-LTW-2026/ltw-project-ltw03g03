<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user = require_login();
$uid = current_user_id();

validate_csrf();

$db = get_db();
$subject = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($subject) || empty($description)) {
    set_flash('error', 'Subject and description are required.');
    header('Location: ../pages/report_issue.php');
    exit;
}

$stmt = $db->prepare('INSERT INTO disputes (reporter_id, subject, description, status) VALUES (?, ?, ?, "open")');
$stmt->execute([$uid, $subject, $description]);

set_flash('success', 'Your report has been submitted successfully.');
header('Location: ../pages/report_issue.php');
