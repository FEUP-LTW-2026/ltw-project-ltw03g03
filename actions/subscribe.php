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

// Activate or replace the member's current plan.
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+1 month'));

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        'INSERT INTO member_profiles (user_id, plan_id, plan_start, plan_end)
         VALUES (?, ?, ?, ?)
         ON CONFLICT(user_id) DO UPDATE SET
           plan_id = excluded.plan_id,
           plan_start = excluded.plan_start,
           plan_end = excluded.plan_end'
    );
    $stmt->execute([$uid, $plan_id, $start_date, $end_date]);

    $db->commit();
    set_flash('success', "Your {$plan['name']} membership is now active.");
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    set_flash('error', 'Unable to activate membership plan. Please try again.');
}

header('Location: ../pages/plans.php');
exit;
