<?php
// Session bootstrap. Include at the top of every PHP page.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// ── Remember-me auto-login ─────────────────────────────────────
// If no active session but a remember_token cookie is present,
// attempt to restore the session from the stored token.
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $cookieValue = $_COOKIE['remember_token'];
    $tokenHash   = hash('sha256', $cookieValue);

    require_once __DIR__ . '/db.php';
    $db = get_db();

    $stmt = $db->prepare(
        "SELECT rt.user_id, rt.expires_at,
                u.id, u.username, u.email, u.first_name, u.last_name,
                u.photo_path, u.role, u.is_active
         FROM remember_tokens rt
         JOIN users u ON u.id = rt.user_id
         WHERE rt.token_hash = ?
           AND rt.expires_at > datetime('now')
         LIMIT 1"
    );
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();

    if ($row && $row['is_active']) {
        $_SESSION['user'] = [
            'id'         => $row['id'],
            'username'   => $row['username'],
            'email'      => $row['email'],
            'first_name' => $row['first_name'],
            'last_name'  => $row['last_name'],
            'photo_path' => $row['photo_path'],
            'role'       => $row['role'],
        ];
        session_regenerate_id(true);
        
        // Rotate remember-me token
        $db->prepare('DELETE FROM remember_tokens WHERE token_hash = ?')->execute([$tokenHash]);
        $newToken = bin2hex(random_bytes(32));
        $newTokenHash = hash('sha256', $newToken);
        $db->prepare(
            "INSERT INTO remember_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, datetime('now', '+30 days'))"
        )->execute([$row['id'], $newTokenHash]);
        setcookie('remember_token', $newToken, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
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

/**
 * Validate the CSRF token from a POST request.
 */
function validate_csrf(): void {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

/**
 * Ensure the request is a POST and has a valid CSRF token.
 */
function require_post(string $redirect = '../pages/index.php'): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $redirect);
        exit;
    }
    validate_csrf();
}
