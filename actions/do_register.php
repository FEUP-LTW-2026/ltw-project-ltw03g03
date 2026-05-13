<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$db = get_db();

$first_name = trim($_POST['firstname'] ?? '');
$last_name  = trim($_POST['lastname']  ?? '');
$dob        = trim($_POST['dob']       ?? '');
$phone      = trim($_POST['phone']     ?? '');
$username   = strtolower(trim($_POST['username'] ?? ''));
$email      = strtolower(trim($_POST['email']    ?? ''));
$password   = $_POST['password']         ?? '';
$confirm    = $_POST['password_confirm'] ?? '';
$role       = $_POST['role']             ?? 'member';
$terms      = $_POST['terms']            ?? '';
$specialty  = trim($_POST['specialty']   ?? '');

$errors = [];

if (!$first_name) $errors[] = 'First name is required.';
if (!$last_name)  $errors[] = 'Last name is required.';
if (!$username)   $errors[] = 'Username is required.';
if ($username && !preg_match('/^[a-z0-9_]{3,30}$/', $username))
    $errors[] = 'Username must be 3–30 characters (lowercase letters, numbers, underscores).';
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

// Check duplicate username
if (!$errors) {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) $errors[] = 'That username is already taken.';
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
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
header('Location: ../pages/dashboard.php');
exit;
