<?php
/**
 * Loaded at the top of every page. Sets up config, session, DB, auth and CSRF protection.
 */
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
$GLOBALS['config'] = require ROOT_PATH . '/config/config.php';

date_default_timezone_set(config('app.timezone'));
ini_set('display_errors', config('app.debug') ? '1' : '0');
error_reporting(E_ALL);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/icons.php';

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_name('fcscorpio_session');
session_start();

// Basic security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Every POST request must carry a valid CSRF token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

/** Read a config value using dot notation, e.g. config('db.host'). */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}
