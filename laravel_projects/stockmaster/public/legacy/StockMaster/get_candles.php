<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/finnhub_http.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Unauthenticated'], 401);
}

function tf_to_resolution(string $tf): string
{
    // Finnhub resolutions: 1, 5, 15, 30, 60, D, W, M
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

function default_bars_for_tf(string $tf): int
{
    return match ($tf) {
        '1m'  => 300, // ~5 óra
        '5m'  => 300, // ~25 óra
        '15m' => 300, // ~3 nap
        '30m' => 300, // ~6 nap
        '1h'  => 300, // ~12.5 nap
        '1d'  => 365, // ~1 év
        default => 300,
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

$symbol = strtoupper(trim((string)($_GET['symbol'] ?? '')));
$tf     = strtolower(trim((string)($_GET['tf'] ?? '5m')));

if ($symbol === '' || !preg_match('/^[A-Z0-9\.\-\_]{1,20}$/', $symbol)) {
    legacy_json(['ok' => false, 'error' => 'Invalid symbol'], 422);
}

$resolution = tf_to_resolution($tf);

// from/to opcionális: ha nincs, default ablakot adunk
$now  = time();
$bars = (int)($_GET['bars'] ?? default_bars_for_tf($tf));
if ($bars < 50)  $bars = 50;
if ($bars > 1000) $bars = 1000;

$from = isset($_GET['from']) ? (int)$_GET['from'] : ($now - seconds_per_bar($tf) * $bars);
$to   = isset($_GET['to'])   ? (int)$_GET['to']   : $now;

if ($from <= 0 || $to <= 0 || $from >= $to) {
    legacy_json(['ok' => false, 'error' => 'Invalid time range'], 422);
}

// Cache kulcs (15s): symbol+tf+range (a range miatt fix a key)
$cacheKey = 'candles_' . sha1($symbol . '|' . $resolution . '|' . $from . '|' . $to);
$cached = cache_get($cacheKey, 15);
if (is_array($cached)) {
    legacy_json($cached, 200);
}

$url = 'https://finnhub.io/api/v1/stock/candle'
    . '?symbol=' . rawurlencode($symbol)
    . '&resolution=' . rawurlencode($resolution)
    . '&from=' . $from
    . '&to=' . $to;

$fh = finnhub_get_json($url);

if (!($fh['ok'] ?? false)) {
    $http = (int)($fh['http'] ?? 500);
    $err  = (string)($fh['error'] ?? 'Finnhub error');
    $payload = ['ok' => false, 'error' => $err, 'http' => $http];
    cache_set($cacheKey, $payload); 
    legacy_json($payload, $http >= 400 && $http <= 599 ? $http : 500);
}

$data = $fh['data'] ?? null;
if (!is_array($data)) {
    $payload = ['ok' => false, 'error' => 'Invalid Finnhub response'];
    cache_set($cacheKey, $payload);
    legacy_json($payload, 502);
}

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
        'note' => ($status === 'no_data') ? 'no_data' : 'unknown_status',
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
    $payload = ['ok' => false, 'error' => 'Malformed candle arrays'];
    cache_set($cacheKey, $payload);
    legacy_json($payload, 502);
}

$count = min(count($t), count($o), count($h), count($l), count($c));
$candles = [];

for ($i = 0; $i < $count; $i++) {
    $ts = (int)$t[$i];
    if ($ts <= 0) continue;

    $candles[] = [
        'time'  => $ts,
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

cache_set($cacheKey, $payload);
legacy_json($payload, 200);
