<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$admin = require_admin();
$admin_id = current_user_id();

validate_csrf();

$db = get_db();
$dispute_id = (int)($_POST['dispute_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$admin_reply = trim($_POST['admin_reply'] ?? '');

$valid_statuses = ['open', 'in_review', 'resolved', 'closed'];

if (!in_array($status, $valid_statuses, true)) {
    set_flash('error', 'Invalid status.');
    header('Location: ../pages/admin_disputes.php?id=' . $dispute_id);
    exit;
}

$stmt = $db->prepare('UPDATE disputes SET status = ?, admin_reply = ?, resolved_by = ? WHERE id = ?');
$stmt->execute([$status, $admin_reply, $admin_id, $dispute_id]);

set_flash('success', 'Dispute updated successfully.');
header('Location: ../pages/admin_disputes.php?id=' . $dispute_id);
