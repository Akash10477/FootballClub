<?php
/**
 * Small general-purpose helpers used across the site.
 */

/** Escape output for HTML (prevents XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL of the app, auto-detected from the current script location. */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = (string) config('app.base_url', '');
    if ($configured !== '') {
        return $base = rtrim($configured, '/');
    }
    $root      = str_replace('\\', '/', (string) realpath(ROOT_PATH));
    $scriptDir = str_replace('\\', '/', (string) realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $relative  = trim(substr($scriptDir, strlen($root)), '/');           // '' or 'admin'
    $urlDir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($relative !== '' && str_ends_with($urlDir, '/' . $relative)) {
        $urlDir = substr($urlDir, 0, -strlen($relative) - 1);
    }
    return $base = $urlDir;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = ROOT_PATH . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : 1;      // cache-busting
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function redirect(string $path): never
{
    $target = preg_match('#^(https?:)?//#', $path) || str_starts_with($path, '/') ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/** Only allow redirects back into this site (prevents open-redirects). */
function safe_next(?string $next, string $fallback = 'index.php'): string
{
    if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
        return $next;
    }
    return $fallback;
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- Form helpers ---------- */

function old(string $key, mixed $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function input(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? 'selected' : '';
}

/* ---------- Formatting ---------- */

function money(float|int|string $amount): string
{
    return config('app.currency') . number_format((float) $amount, 0);
}

function fmt_date(?string $dt, string $format = 'D, j M Y'): string
{
    return $dt ? date($format, strtotime($dt)) : '—';
}

function fmt_time(?string $dt): string
{
    return $dt ? date('g:i A', strtotime($dt)) : '';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

function age_from(?string $dob): ?int
{
    return $dob ? (new DateTime($dob))->diff(new DateTime())->y : null;
}

function reference(string $prefix): string
{
    return $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));
}

function product_image(?string $file): string
{
    return $file ? url('uploads/products/' . $file) : asset('img/crest.png');
}

function player_photo(?string $file): ?string
{
    return $file ? url('uploads/players/' . $file) : null;
}

/** Human label + colour class for a match result from FC Scorpio's point of view. */
function match_outcome(array $f): ?array
{
    if ($f['goals_for'] === null || $f['goals_against'] === null) {
        return null;
    }
    return match (true) {
        $f['goals_for'] > $f['goals_against'] => ['W', 'win'],
        $f['goals_for'] < $f['goals_against'] => ['L', 'loss'],
        default                                => ['D', 'draw'],
    };
}

/* ---------- Shopping cart (stored in the session) ---------- */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

function cart_set(int $productId, int $qty): void
{
    if ($qty <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId] = $qty;
    }
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

const FREE_DELIVERY_FROM = 3000;

/** Flat delivery charge, free above a threshold. */
function delivery_fee(float $subtotal): float
{
    return $subtotal >= FREE_DELIVERY_FROM ? 0 : 100;
}

/** Cart lines joined with live product data. */
function cart_lines(): array
{
    $cart = cart();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $products = db_all("SELECT * FROM products WHERE is_active = 1 AND id IN ($placeholders)", $ids);
    $lines = [];
    foreach ($products as $p) {
        $qty = min($cart[$p['id']], (int) $p['stock']);
        $lines[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $qty * (float) $p['price']];
    }
    return $lines;
}

/** Render pagination links. */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page  = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'limit' => $perPage];
}
