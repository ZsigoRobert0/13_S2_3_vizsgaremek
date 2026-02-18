<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/finnhub_http.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve'], 401);
}

session_write_close();

$symbol = strtoupper(trim((string)($_GET['symbol'] ?? '')));
if ($symbol === '') {
    legacy_json(['ok' => false, 'error' => 'Hiányzó symbol'], 400);
}

// Bootstrapból jön az env (safe): FINNHUB_API_KEY
$apiKey = (string) legacy_env('FINNHUB_API_KEY', '');
if ($apiKey === '') {
    legacy_json(['ok' => false, 'error' => 'Hiányzik a FINNHUB_API_KEY'], 500);
}

$url  = 'https://finnhub.io/api/v1/quote?symbol=' . urlencode($symbol) . '&token=' . urlencode($apiKey);
$resp = finnhub_get_json($url);

if (!($resp['ok'] ?? false)) {
    legacy_json([
        'ok'     => false,
        'error'  => 'Nem sikerült az árfolyam lekérése (API)',
        'symbol' => $symbol,
        'http'   => (int)($resp['http'] ?? 0),
        'err'    => (string)($resp['error'] ?? 'unknown'),
    ], 502);
}

$data = $resp['data'] ?? [];
$price = $data['c'] ?? null;

if (!is_numeric($price)) {
    legacy_json([
        'ok'     => false,
        'error'  => 'Nem sikerült az árfolyam lekérése (hiányzó adat)',
        'symbol' => $symbol,
        'http'   => (int)($resp['http'] ?? 0),
    ], 502);
}

legacy_json([
    'ok'     => true,
    'symbol' => $symbol,
    'price'  => (float)$price,
    'source' => 'api',
]);
