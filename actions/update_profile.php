<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$user = require_login();
$db   = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/profile.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name']  ?? '');
$username   = trim($_POST['username']   ?? '');
$phone      = trim($_POST['phone']      ?? '');
$password   = $_POST['new_password']    ?? '';
$confirm    = $_POST['confirm_password']?? '';

$errors = [];
if (!$first_name) $errors[] = 'First name is required.';
if (!$last_name)  $errors[] = 'Last name is required.';
if (!$username)   $errors[] = 'Username is required.';

// Check username uniqueness (excluding current user)
if ($username) {
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $stmt->execute([$username, $user['id']]);
    if ($stmt->fetch()) $errors[] = 'That username is already taken.';
}

// Password change (optional)
$new_hash = null;
if ($password) {
    if (strlen($password) < 8) $errors[] = 'New password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$errors) $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Profile photo upload
$photo_path = $user['photo_path'];
if (!empty($_FILES['photo']['tmp_name'])) {
    $original_name = $_FILES['photo']['name'];

    // Reject filenames with path traversal sequences (req 18.4)
    $traversal_patterns = ['../', './', '%2e%2e', '%2e', '%2f', "\0"];
    $lower_name = strtolower($original_name);
    $has_traversal = false;
    foreach ($traversal_patterns as $pattern) {
        if (strpos($lower_name, strtolower($pattern)) !== false) {
            $has_traversal = true;
            break;
        }
    }
    // Also check for raw null bytes in the original name
    if (strpos($original_name, "\0") !== false) {
        $has_traversal = true;
    }

    if ($has_traversal) {
        $errors[] = 'Invalid filename.';
    } else {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $mime    = mime_content_type($_FILES['photo']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Photo must be a JPEG, PNG, WebP, or GIF.';
        } elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Photo must be under 3 MB.';
        } else {
            $ext      = pathinfo($original_name, PATHINFO_EXTENSION);
            $filename = 'user_' . $user['id'] . '_' . time() . '.' . $ext;
            $dest     = __DIR__ . '/../uploads/photos/' . $filename;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photo_path = 'uploads/photos/' . $filename;
            }
        }
    }
}

if ($errors) {
    set_flash('error', implode(' ', $errors));
    header('Location: ../pages/profile.php');
    exit;
}

// Build update query dynamically
if ($new_hash) {
    $db->prepare(
        'UPDATE users SET first_name=?, last_name=?, username=?, phone=?, photo_path=?, password_hash=? WHERE id=?'
    )->execute([$first_name, $last_name, $username, $phone ?: null, $photo_path, $new_hash, $user['id']]);
} else {
    $db->prepare(
        'UPDATE users SET first_name=?, last_name=?, username=?, phone=?, photo_path=? WHERE id=?'
    )->execute([$first_name, $last_name, $username, $phone ?: null, $photo_path, $user['id']]);
}

// Trainer-specific profile fields
if ($user['role'] === 'trainer') {
    $bio       = trim($_POST['bio']            ?? '');
    $specialty = trim($_POST['specialty']      ?? '');
    $certs     = trim($_POST['certifications'] ?? '');
    $exp       = (int) ($_POST['years_experience'] ?? 0);

    $trainer_errors = [];
    if (mb_strlen($bio, 'UTF-8') > 500) {
        $trainer_errors[] = 'Bio must be 500 characters or fewer.';
    }
    if (mb_strlen($specialty, 'UTF-8') > 100) {
        $trainer_errors[] = 'Specialty must be 100 characters or fewer.';
    }
    if (mb_strlen($certs, 'UTF-8') > 300) {
        $trainer_errors[] = 'Certifications must be 300 characters or fewer.';
    }
    if ($exp < 0 || $exp > 60) {
        $trainer_errors[] = 'Years of experience must be between 0 and 60.';
    }

    if ($trainer_errors) {
        set_flash('error', implode(' ', $trainer_errors));
        header('Location: ../pages/profile.php');
        exit;
    }

    $db->prepare(
        'INSERT OR REPLACE INTO trainer_profiles (user_id, bio, specialty, certifications, years_experience)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$user['id'], $bio ?: null, $specialty ?: null, $certs ?: null, $exp]);
}

refresh_session_user();
set_flash('success', 'Profile updated successfully.');
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
header('Location: ../pages/profile.php');
exit;
