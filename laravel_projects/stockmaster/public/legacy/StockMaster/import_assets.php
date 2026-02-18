<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/finnhub_http.php';

set_time_limit(120);
ini_set('memory_limit', '512M');

$conn = legacy_db();

$apiKey = (string) legacy_env('FINNHUB_API_KEY', '');
if ($apiKey === '') {
    exit('Hiányzik a FINNHUB_API_KEY az .env fájlban.');
}

// USA részvények lekérése (NASDAQ + NYSE + AMEX)
$url  = 'https://finnhub.io/api/v1/stock/symbol?exchange=US&token=' . urlencode($apiKey);
$resp = finnhub_get_json($url);

if (!($resp['ok'] ?? false)) {
    exit('Finnhub API hiba. HTTP: ' . (int)($resp['http'] ?? 0));
}

$data = $resp['data'] ?? [];

if (!is_array($data)) {
    exit('API hiba: nem érvényes JSON érkezett.');
}

// Duplikáció elkerülés 
$stmt = $conn->prepare("
    INSERT INTO assets (Symbol, Name, IsTradable)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE
        Name = VALUES(Name)
");

if (!$stmt) {
    exit('Prepare hiba: ' . $conn->error);
}

$inserted = 0;
$maxRows  = 300; // első körben limitált import

foreach ($data as $stock) {

    if ($inserted >= $maxRows) {
        break;
    }

    $symbol = strtoupper(trim((string)($stock['symbol'] ?? '')));
    $name   = trim((string)($stock['description'] ?? ''));

    // Szűrések
    if ($symbol === '' || $name === '') continue;
    if (strpos($symbol, '-') !== false) continue;

    $stmt->bind_param('ss', $symbol, $name);
    $stmt->execute();

    $inserted++;
}

$stmt->close();

echo "Sikeresen importálva / frissítve: {$inserted} részvény az assets táblába.";
