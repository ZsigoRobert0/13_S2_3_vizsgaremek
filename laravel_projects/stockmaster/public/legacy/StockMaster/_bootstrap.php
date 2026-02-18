<?php

// ---------- Paths ----------
define('LEGACY_DIR', __DIR__);
define('PROJECT_ROOT', realpath(__DIR__ . '/../../..')); // stockmaster/ (Laravel root)

if (!PROJECT_ROOT) {
    http_response_code(500);
    echo "Legacy bootstrap error: PROJECT_ROOT not found.";
    exit;
}


$autoload = PROJECT_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;

    if (class_exists(\Dotenv\Dotenv::class)) {
        try {
            
            \Dotenv\Dotenv::createImmutable(PROJECT_ROOT)->safeLoad();
        } catch (\Throwable $e) {
        }
    }
}

// ---------- Default timezone ----------
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Budapest');
}

// ---------- Session ----------
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

if (!defined('FINNHUB_API_KEY')) {
    $k = getenv('FINNHUB_API_KEY') ?: ($_ENV['FINNHUB_API_KEY'] ?? null);
    define('FINNHUB_API_KEY', $k ?: 'CHANGE_ME_FINNHUB_KEY');
}

function legacy_env(string $key, $default = null) {
    $v = getenv($key);
    if ($v !== false) return $v;
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    return $default;
}

function legacy_base_url(): string {
    return '/legacy/StockMaster';
}

function legacy_redirect(string $path): void {
    if (preg_match('~^https?://~i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    // ha "/..." akkor abszolút web path
    if (str_starts_with($path, '/')) {
        header('Location: ' . $path);
        exit;
    }

    // különben legacy gyökérhöz képest
    header('Location: ' . legacy_base_url() . '/' . ltrim($path, '/'));
    exit;
}

function legacy_json($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- DB (mysqli singleton) ----------
function legacy_db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    $host = legacy_env('DB_HOST', '127.0.0.1');
    $user = legacy_env('DB_USERNAME', 'root');
    $pass = legacy_env('DB_PASSWORD', '');
    $db   = legacy_env('DB_DATABASE', 'stockmasters');
    $port = (int) legacy_env('DB_PORT', 3306);

    $conn = @new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        legacy_json(['ok' => false, 'error' => 'DB connection failed: ' . $conn->connect_error], 500);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

// ---------- Auth helpers (legacy session-alapon) ----------
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        legacy_redirect('login.php');
    }
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function legacy_require_login_api(): void {
    if (!isLoggedIn()) {
        legacy_json(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
}
