<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$user = require_role('member'); // Only members can subscribe
$uid = current_user_id();

validate_csrf();

$db = get_db();
$plan_id = (int)($_POST['plan_id'] ?? 0);

// Validate plan exists
$stmt = $db->prepare('SELECT id, name FROM membership_plans WHERE id = ? AND is_active = 1');
$stmt->execute([$plan_id]);
$plan = $stmt->fetch();

if (!$plan) {
    set_flash('error', 'Invalid membership plan selected.');
    header('Location: ../pages/plans.php');
    exit;
}

// Update or insert member_profiles
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+1 month'));

$stmt = $db->prepare('SELECT user_id FROM member_profiles WHERE user_id = ?');
$stmt->execute([$uid]);
$profile = $stmt->fetch();

if ($profile) {
    $db->prepare('UPDATE member_profiles SET plan_id = ?, plan_start = ?, plan_end = ? WHERE user_id = ?')
       ->execute([$plan_id, $start_date, $end_date, $uid]);
} else {
    $db->prepare('INSERT INTO member_profiles (user_id, plan_id, plan_start, plan_end) VALUES (?, ?, ?, ?)')
       ->execute([$uid, $plan_id, $start_date, $end_date]);
}

set_flash('success', "Successfully subscribed to the {$plan['name']} plan.");
header('Location: ../pages/dashboard.php');
