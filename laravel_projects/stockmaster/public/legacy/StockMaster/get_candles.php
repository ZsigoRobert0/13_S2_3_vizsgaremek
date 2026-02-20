<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/finnhub_http.php';

/**
 * get_candles.php (Legacy stable + debug-friendly)
 *
 * Példa:
 *  /legacy/StockMaster/get_candles.php?symbol=AAPL&tf=5m
 *  /legacy/StockMaster/get_candles.php?symbol=AAPL&tf=1d&bars=200
 *
 * Auth:
 *  JSON endpoint -> 401 JSON, nincs redirect.
 */

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Unauthenticated'], 401);
}

function tf_to_resolution(string $tf): string
{
    // Finnhub: 1,5,15,30,60,D,W,M
    return match ($tf) {
        '1m'  => '1',
        '5m'  => '5',
        '15m' => '15',
        '30m' => '30',
        '1h'  => '60',
        '1d'  => 'D',
        default => '5',
    };
}

function seconds_per_bar(string $tf): int
{
    return match ($tf) {
        '1m'  => 60,
        '5m'  => 300,
        '15m' => 900,
        '30m' => 1800,
        '1h'  => 3600,
        '1d'  => 86400,
        default => 300,
    };
}

function default_bars(string $tf): int
{
    return match ($tf) {
        '1m'  => 300,
        '5m'  => 300,
        '15m' => 300,
        '30m' => 300,
        '1h'  => 300,
        '1d'  => 365,
        default => 300,
    };
}

function cache_dir(): string
{
    $dir = __DIR__ . '/_cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function cache_get(string $key, int $ttlSeconds): ?array
{
    $path = cache_dir() . '/' . $key . '.json';
    if (!is_file($path)) return null;

    $age = time() - (int)@filemtime($path);
    if ($age > $ttlSeconds) return null;

    $raw = @file_get_contents($path);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function cache_set(string $key, array $payload): void
{
    $path = cache_dir() . '/' . $key . '.json';
    @file_put_contents($path, json_encode($payload));
}

// --- INPUT ---
$symbol = strtoupper(trim((string)($_GET['symbol'] ?? '')));
$tf     = strtolower(trim((string)($_GET['tf'] ?? '5m')));

if ($symbol === '' || !preg_match('/^[A-Z0-9\.\-\_]{1,20}$/', $symbol)) {
    legacy_json(['ok' => false, 'error' => 'Invalid symbol'], 422);
}

$resolution = tf_to_resolution($tf);

$bars = (int)($_GET['bars'] ?? default_bars($tf));
if ($bars < 50) $bars = 50;
if ($bars > 1000) $bars = 1000;

$now  = time();
$from = isset($_GET['from']) ? (int)$_GET['from'] : ($now - seconds_per_bar($tf) * $bars);
$to   = isset($_GET['to'])   ? (int)$_GET['to']   : $now;

if ($from <= 0 || $to <= 0 || $from >= $to) {
    legacy_json([
        'ok' => false,
        'error' => 'Invalid time range',
        'debug' => ['from' => $from, 'to' => $to]
    ], 422);
}

// --- CACHE: csak OK választ cache-eljünk (hibát NEM!) ---
$cacheKey = 'candles_' . sha1($symbol . '|' . $resolution . '|' . $from . '|' . $to);
$cached = cache_get($cacheKey, 15);
if (is_array($cached) && ($cached['ok'] ?? false) === true) {
    legacy_json($cached, 200);
}

// --- FINNHUB CALL ---
$url = 'https://finnhub.io/api/v1/stock/candle'
    . '?symbol=' . rawurlencode($symbol)
    . '&resolution=' . rawurlencode($resolution)
    . '&from=' . $from
    . '&to=' . $to;

$fh = finnhub_get_json($url);

// --- FINNHUB ERROR (KIÍRJUK A RAW-T!) ---
if (!($fh['ok'] ?? false)) {
    $http = (int)($fh['http'] ?? 500);

    // finnhub_http.php-tól függően lehet raw/body/debug mező:
    $raw = (string)($fh['raw'] ?? ($fh['body'] ?? ''));
    $dbg = $fh['debug'] ?? null;

    legacy_json([
        'ok' => false,
        'error' => (string)($fh['error'] ?? 'Finnhub error'),
        'http' => $http,
        'raw' => mb_substr(trim($raw), 0, 2000),
        'debug' => $dbg,
        'request' => [
            'symbol' => $symbol,
            'tf' => $tf,
            'resolution' => $resolution,
            'from' => $from,
            'to' => $to,
            'url' => $url, // token nincs benne (finnhub_http hozzáfűzi)
        ],
    ], ($http >= 400 && $http <= 599) ? $http : 500);
}

// --- VALIDATE ---
$data = $fh['data'] ?? null;
if (!is_array($data)) {
    legacy_json(['ok' => false, 'error' => 'Invalid Finnhub response'], 502);
}

// Finnhub candle: { c,h,l,o,t,v, s:"ok"/"no_data" }
$status = (string)($data['s'] ?? '');
if ($status !== 'ok') {
    $payload = [
        'ok' => true,
        'symbol' => $symbol,
        'tf' => $tf,
        'resolution' => $resolution,
        'from' => $from,
        'to' => $to,
        'candles' => [],
        'note' => ($status === 'no_data') ? 'no_data' : ('status:' . $status),
    ];
    cache_set($cacheKey, $payload);
    legacy_json($payload, 200);
}

$t = $data['t'] ?? [];
$o = $data['o'] ?? [];
$h = $data['h'] ?? [];
$l = $data['l'] ?? [];
$c = $data['c'] ?? [];

if (!is_array($t) || !is_array($o) || !is_array($h) || !is_array($l) || !is_array($c)) {
    legacy_json([
        'ok' => false,
        'error' => 'Malformed candle arrays',
        'debug' => [
            't' => is_array($t) ? count($t) : gettype($t),
            'o' => is_array($o) ? count($o) : gettype($o),
            'h' => is_array($h) ? count($h) : gettype($h),
            'l' => is_array($l) ? count($l) : gettype($l),
            'c' => is_array($c) ? count($c) : gettype($c),
        ],
    ], 502);
}

$count = min(count($t), count($o), count($h), count($l), count($c));
$candles = [];

for ($i = 0; $i < $count; $i++) {
    $ts = (int)$t[$i];
    if ($ts <= 0) continue;

    $candles[] = [
        'time'  => $ts,            // unix seconds
        'open'  => (float)$o[$i],
        'high'  => (float)$h[$i],
        'low'   => (float)$l[$i],
        'close' => (float)$c[$i],
    ];
}

$payload = [
    'ok' => true,
    'symbol' => $symbol,
    'tf' => $tf,
    'resolution' => $resolution,
    'from' => $from,
    'to' => $to,
    'candles' => $candles,
];

// csak sikerest cache-elünk
cache_set($cacheKey, $payload);

legacy_json($payload, 200);