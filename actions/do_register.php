<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php');
    exit;
}

$db = get_db();

// ── Sanitise & validate input ──────────────────────────────────
$first_name = trim($_POST['firstname'] ?? '');
$last_name  = trim($_POST['lastname']  ?? '');
$dob        = trim($_POST['dob']       ?? '');
$phone      = trim($_POST['phone']     ?? '');
$email      = strtolower(trim($_POST['email']    ?? ''));
$password   = $_POST['password']         ?? '';
$confirm    = $_POST['password_confirm'] ?? '';
$role       = $_POST['role']             ?? 'member';
$terms      = $_POST['terms']            ?? '';
$specialty  = trim($_POST['specialty']   ?? '');

$errors = [];

if (!$first_name) $errors[] = 'First name is required.';
if (!$last_name)  $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm) $errors[] = 'Passwords do not match.';
if (!in_array($role, ['member','trainer','admin'])) $errors[] = 'Invalid role.';
if (!$terms) $errors[] = 'You must accept the Terms of Service.';

// Check duplicate email
if (!$errors) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'An account with that email already exists.';
}

// Build username from name
$base_username = strtolower(preg_replace('/[^a-z0-9]/i', '', $first_name . $last_name));
$username = $base_username;
$suffix = 1;
while (true) {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if (!$stmt->fetch()) break;
    $username = $base_username . $suffix++;
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    header('Location: ../pages/register.php');
    exit;
}

// ── Insert user ────────────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $db->prepare(
    'INSERT INTO users (username, email, password_hash, first_name, last_name, phone, date_of_birth, role)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $username,
    $email,
    $hash,
    $first_name,
    $last_name,
    $phone ?: null,
    $dob   ?: null,
    $role,
]);
$user_id = (int) $db->lastInsertId();

// ── Create role-specific profile ───────────────────────────────
if ($role === 'member') {
    $db->prepare('INSERT INTO member_profiles (user_id) VALUES (?)')->execute([$user_id]);
}
if ($role === 'trainer') {
    $db->prepare(
        'INSERT INTO trainer_profiles (user_id, specialty) VALUES (?, ?)'
    )->execute([$user_id, $specialty ?: null]);
}

// ── Auto-login after registration ─────────────────────────────
$stmt = $db->prepare('SELECT id, username, email, first_name, last_name, photo_path, role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$_SESSION['user'] = $stmt->fetch();

set_flash('success', 'Welcome to W8, ' . htmlspecialchars($first_name) . '!');
header('Location: ../pages/dashboard.php');
exit;
