<?php
// Session bootstrap. Include at the top of every PHP page.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,        // set true when using HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Auth helpers ───────────────────────────────────────────────

/**
 * Return the currently logged-in user array or null.
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Require the user to be logged in. Redirect to sign-in if not.
 */
function require_login(string $redirect = '../pages/sign_in.php'): array {
    $user = current_user();
    if (!$user) {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}

/**
 * Require a specific role. Redirect to dashboard if wrong role.
 */
function require_role(string $role, string $redirect = '../pages/dashboard.php'): array {
    $user = require_login();
    if ($user['role'] !== $role) {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}

/**
 * Require the user to be an admin.
 */
function require_admin(): array {
    return require_role('admin');
}

/**
 * Require the user to be a trainer.
 */
function require_trainer(): array {
    return require_role('trainer');
}

/**
 * Check if user has a specific role (without redirecting).
 */
function user_has_role(string $role): bool {
    $user = current_user();
    return $user && $user['role'] === $role;
}

/**
 * Refresh the session user from the DB (call after profile edits).
 */
function refresh_session_user(): void {
    $user = current_user();
    if (!$user) return;
    require_once __DIR__ . '/db.php';
    $db  = get_db();
    $stmt = $db->prepare('SELECT id, username, email, first_name, last_name, photo_path, role FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $fresh = $stmt->fetch();
    if ($fresh) {
        $_SESSION['user'] = $fresh;
    }
}

/**
 * Flash message helpers.
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
