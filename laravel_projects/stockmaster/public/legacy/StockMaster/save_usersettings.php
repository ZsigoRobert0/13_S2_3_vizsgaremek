<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$userId = currentUserId();
session_write_close();

function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    return $scheme . '://' . $host;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) $payload = [];

$autoLogin = !empty($payload['AutoLogin']) ? 1 : 0;
$receiveNotifications = !empty($payload['ReceiveNotifications']) ? 1 : 0;

$chartTheme = strtolower(trim((string)($payload['PreferredChartTheme'] ?? 'dark')));
if (!in_array($chartTheme, ['dark','light'], true)) $chartTheme = 'dark';

$chartInterval = trim((string)($payload['PreferredChartInterval'] ?? '1m'));
if ($chartInterval === '') $chartInterval = '1m';

$newsLimit = (int)($payload['NewsLimit'] ?? 8);
$newsPerSymbolLimit = (int)($payload['NewsPerSymbolLimit'] ?? 3);
$newsPortfolioTotalLimit = (int)($payload['NewsPortfolioTotalLimit'] ?? 20);
$calendarLimit = (int)($payload['CalendarLimit'] ?? 8);

$newsLimit = max(3, min(30, $newsLimit));
$newsPerSymbolLimit = max(1, min(10, $newsPerSymbolLimit));
$newsPortfolioTotalLimit = max(5, min(60, $newsPortfolioTotalLimit));
$calendarLimit = max(3, min(60, $calendarLimit));

$apiUrl = baseUrl() . "/api/settings";

$body = json_encode([
    'user_id' => (int)$userId,
    'chart_interval' => $chartInterval,
    'chart_theme' => $chartTheme,
    'news_limit' => $newsLimit,
    'news_per_symbol_limit' => $newsPerSymbolLimit,
    'news_portfolio_total_limit' => $newsPortfolioTotalLimit,
    'calendar_limit' => $calendarLimit,
    'auto_login' => $autoLogin,
    'receive_notifications' => $receiveNotifications,
]);

$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => $body,
        'timeout' => 3,
    ],
]);

$json = @file_get_contents($apiUrl, false, $ctx);
if (!$json) {
    legacy_json(['ok' => false, 'error' => 'API hívás sikertelen (/api/settings).'], 500);
}

$data = json_decode($json, true);
if (!is_array($data) || empty($data['ok'])) {
    legacy_json(['ok' => false, 'error' => $data['error'] ?? 'API mentés hiba.'], 500);
}

legacy_json(['ok' => true]);