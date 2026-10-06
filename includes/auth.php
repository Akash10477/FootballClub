<?php
/**
 * Authentication & authorisation helpers.
 * One `users` table with a `role` column ('fan' or 'admin').
 */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = $_SESSION['user_id'] ?? null;
    $user = $id ? db_one('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?', [$id]) : null;
    if (!$user) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? null) === 'admin';
}

function login_user(array $user): void
{
    session_regenerate_id(true);          // prevent session fixation
    $_SESSION['user_id'] = (int) $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Redirect guests to the login page, then bring them back afterwards. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('info', 'Please sign in to continue.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        flash('error', 'That area is for club staff only.');
        redirect('index.php');
    }
    return $user;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('<div style="font-family:system-ui;text-align:center;margin-top:15vh"><h2>Your session expired</h2>'
            . '<p>Please go back, refresh the page and try again.</p></div>');
    }
}
