<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/finnhub_http.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$conn = legacy_db();
$userId = currentUserId();

session_write_close();

// Finnhub kulcs ellenőrzés (a finnhub_http.php is hozzáfűzi, de ha nincs, demo / hiba)
$apiKey = defined('FINNHUB_API_KEY') ? trim((string)FINNHUB_API_KEY) : '';
if ($apiKey === '' || $apiKey === 'CHANGE_ME_FINNHUB_KEY') {
    legacy_json([
        'ok' => false,
        'error' => 'Nincs Finnhub token (FINNHUB_API_KEY). Állítsd be a .env-ben vagy a bootstrapben.'
    ], 500);
}

$mode = strtolower((string)($_GET['mode'] ?? 'portfolio'));
if (!in_array($mode, ['portfolio', 'general'], true)) {
    $mode = 'portfolio';
}

// --- Usersettings default limitek ---
$defNewsLimit = 8;          // general
$defPerSymbol = 3;          // portfolio: tickerenként
$defPortfolioTotal = 20;    // portfolio: összesen

$st = $conn->prepare("SELECT NewsLimit, NewsPerSymbolLimit, NewsPortfolioTotalLimit FROM usersettings WHERE UserID = ? LIMIT 1");
if ($st) {
    $st->bind_param('i', $userId);
    if ($st->execute()) {
        $row = $st->get_result()->fetch_assoc();
        if ($row) {
            $defNewsLimit = (int)($row['NewsLimit'] ?? $defNewsLimit);
            $defPerSymbol = (int)($row['NewsPerSymbolLimit'] ?? $defPerSymbol);
            $defPortfolioTotal = (int)($row['NewsPortfolioTotalLimit'] ?? $defPortfolioTotal);
        }
    }
    $st->close();
}

// GET param felülírja a defaultot
$limit = (int)($_GET['limit'] ?? 0);
$perSymbol = (int)($_GET['perSymbol'] ?? 0);

if ($limit <= 0) {
    $limit = ($mode === 'general') ? $defNewsLimit : $defPortfolioTotal;
}
if ($perSymbol <= 0) {
    $perSymbol = $defPerSymbol;
}

$limit = max(3, min(60, $limit));
$perSymbol = max(1, min(10, $perSymbol));

// --- Portfolio symbols (nyitott pozikból) ---
$symbols = [];
if ($mode === 'portfolio') {
    $stmt = $conn->prepare("
        SELECT DISTINCT a.Symbol
        FROM positions p
        JOIN assets a ON a.ID = p.AssetID
        WHERE p.UserID = ? AND p.IsOpen = 1
        ORDER BY a.Symbol
        LIMIT 10
    ");
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $symbols[] = (string)$r['Symbol'];
            }
        }
        $stmt->close();
    }

    // ha nincs portfolio symbol, fallback general
    if (count($symbols) === 0) {
        $mode = 'general';
        $limit = max(3, min(30, (int)$defNewsLimit)); // generalnál inkább 30-ig
    }
}

// --- General mode ---
if ($mode === 'general') {
    $url = "https://finnhub.io/api/v1/news?category=general";
    $r = finnhub_get_json($url);

    if (!$r['ok']) {
        legacy_json(['ok' => false, 'error' => 'Finnhub hiba (news): ' . ($r['error'] ?? 'unknown')], 502);
    }

    $data = $r['data'];
    if (!is_array($data)) $data = [];

    $items = [];
    foreach (array_slice($data, 0, min(30, $limit)) as $n) {
        $items[] = [
            'headline' => $n['headline'] ?? '',
            'source'   => $n['source'] ?? '',
            'url'      => $n['url'] ?? '',
            'datetime' => $n['datetime'] ?? null,
            'summary'  => $n['summary'] ?? '',
            'symbol'   => null,
        ];
    }

    legacy_json([
        'ok' => true,
        'mode' => 'general',
        'limit' => $limit,
        'items' => $items,
    ]);
}

// --- Portfolio mode: company-news (utolsó 7 nap) ---
$to = new DateTime('now');
$from = (clone $to)->modify('-7 days');
$fromStr = $from->format('Y-m-d');
$toStr = $to->format('Y-m-d');

$items = [];

foreach ($symbols as $sym) {
    $url = "https://finnhub.io/api/v1/company-news?symbol=" . urlencode($sym) .
        "&from=" . urlencode($fromStr) .
        "&to=" . urlencode($toStr);

    $r = finnhub_get_json($url);
    if (!$r['ok']) {
        continue;
    }

    $data = $r['data'];
    if (!is_array($data)) $data = [];

    foreach (array_slice($data, 0, $perSymbol) as $n) {
        $items[] = [
            'headline' => $n['headline'] ?? '',
            'source'   => $n['source'] ?? '',
            'url'      => $n['url'] ?? '',
            'datetime' => $n['datetime'] ?? null,
            'summary'  => $n['summary'] ?? '',
            'symbol'   => $sym,
        ];
    }
}

// idő szerint rendezés
usort($items, fn($a, $b) => (int)($b['datetime'] ?? 0) <=> (int)($a['datetime'] ?? 0));

legacy_json([
    'ok' => true,
    'mode' => 'portfolio',
    'symbols' => $symbols,
    'limit' => $limit,
    'perSymbol' => $perSymbol,
    'items' => array_slice($items, 0, $limit),
]);
