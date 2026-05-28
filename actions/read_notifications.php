<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user = require_login();
$uid = current_user_id();

validate_csrf();

$db = get_db();

$stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$stmt->execute([$uid]);

set_flash('success', 'All notifications marked as read.');
header('Location: ../pages/notifications.php');
