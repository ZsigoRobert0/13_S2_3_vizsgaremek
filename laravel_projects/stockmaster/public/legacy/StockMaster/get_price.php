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

$apiKey = (string) legacy_env('FINNHUB_API_KEY', '');
if ($apiKey === '') {
    legacy_json(['ok' => false, 'error' => 'Hiányzik a FINNHUB_API_KEY'], 500);
}

/**
 * Debug log (ha nem kell, később kikapcsoljuk)
 */
function _ingest_debug(string $msg): void {
    $file = __DIR__ . '/_ingest_debug.log';
    @file_put_contents($file, date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL, FILE_APPEND);
}

/**
 * OPTIONAL: tick ingest a Laravel felé, hogy a candles tábla real-time teljen.
 * Csak akkor fut, ha a kliens kéri: ?ingest=1
 * Fire-and-forget (nem törheti el a get_price választ!)
 *
 * FONTOS: cURL hiány esetén fallback file_get_contents().
 */
function _try_ingest_tick(string $symbol, float $price): void
{
    $ingestUrl = (string) legacy_env('LARAVEL_TICK_INGEST_URL', 'http://127.0.0.1:8000/api/tick/ingest');
    if ($ingestUrl === '') return;

    $payloadArr = [
        'symbol' => $symbol,
        'ts'     => time(),
        'price'  => $price,
        'source' => 'legacy',
    ];

    $payload = json_encode($payloadArr, JSON_UNESCAPED_SLASHES);
    if ($payload === false) return;

    // --- 1) cURL (ha van) ---
    if (function_exists('curl_init')) {
        $ch = @curl_init($ingestUrl);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER    => true,
                CURLOPT_POST              => true,
                CURLOPT_HTTPHEADER        => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS        => $payload,
                CURLOPT_TIMEOUT_MS        => 1200,
                CURLOPT_CONNECTTIMEOUT_MS => 500,
            ]);

            $resp = @curl_exec($ch);
            $err  = @curl_error($ch);
            $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
            @curl_close($ch);

            _ingest_debug("cURL ingest symbol={$symbol} price={$price} http={$code} err=" . ($err ?: 'none'));
            return;
        }
        _ingest_debug("cURL ingest FAILED to init, fallback to file_get_contents");
    }

    // --- 2) fallback: file_get_contents ---
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => $payload,
            'timeout'       => 1.2,
            'ignore_errors' => true, // hogy body-t is vissza tudjon adni 4xx/5xx esetén
        ],
    ]);

    $resp = @file_get_contents($ingestUrl, false, $ctx);

    // status code kiszedése a response headerből
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\d\.\d\s+(\d+)#', $h, $m)) {
                $code = (int)$m[1];
                break;
            }
        }
    }

    _ingest_debug("FGC ingest symbol={$symbol} price={$price} http={$code} resp=" . (is_string($resp) ? substr($resp, 0, 140) : 'null'));
}

// ---- FINNHUB QUOTE ----
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

$data  = $resp['data'] ?? [];
$price = $data['c'] ?? null;
$bid   = $data['b'] ?? null;
$ask   = $data['a'] ?? null;

if (!is_numeric($price)) {
    legacy_json([
        'ok'     => false,
        'error'  => 'Nem sikerült az árfolyam lekérése (hiányzó adat)',
        'symbol' => $symbol,
        'http'   => (int)($resp['http'] ?? 0),
    ], 502);
}

$priceF = (float)$price;

// ✅ ingest a JSON válasz előtt (mert legacy_json() exit-el)
if (isset($_GET['ingest']) && (string)$_GET['ingest'] === '1') {
    _try_ingest_tick($symbol, $priceF);
}

legacy_json([
    'ok'     => true,
    'symbol' => $symbol,
    'price'  => $priceF,
    'bid'    => is_numeric($bid) ? (float)$bid : null,
    'ask'    => is_numeric($ask) ? (float)$ask : null,
    'source' => 'api',
]);