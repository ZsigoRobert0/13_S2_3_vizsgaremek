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

// 1) Időablak (alap: ma -> +14 nap)
$from = (string)($_GET['from'] ?? date('Y-m-d'));
$to   = (string)($_GET['to']   ?? date('Y-m-d', strtotime('+14 days')));

// egyszerű validálás
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    legacy_json([
        'ok' => false,
        'error' => 'Hibás dátum formátum. (YYYY-MM-DD kell)',
        'from' => $from,
        'to' => $to
    ], 400);
}

// 2) LIMIT logika (GET param + usersettings default)
$defCalendarLimit = 8;

$st = $conn->prepare("SELECT CalendarLimit FROM usersettings WHERE UserID = ? LIMIT 1");
if ($st) {
    $st->bind_param('i', $userId);
    if ($st->execute()) {
        $row = $st->get_result()->fetch_assoc();
        if ($row) $defCalendarLimit = (int)($row['CalendarLimit'] ?? $defCalendarLimit);
    }
    $st->close();
}

$limit = (int)($_GET['limit'] ?? $defCalendarLimit);
if ($limit < 3) $limit = 3;
if ($limit > 60) $limit = 60;

// 3) Demo calendar fallback
function demoCalendar(string $from, string $to): array {
    return [
        [
            'date' => $from,
            'country' => 'US',
            'event' => 'CPI (demo)',
            'impact' => 'high',
            'actual' => null,
            'forecast' => null,
            'previous' => null
        ],
        [
            'date' => date('Y-m-d', strtotime($from . ' +3 days')),
            'country' => 'US',
            'event' => 'FOMC Minutes (demo)',
            'impact' => 'medium',
            'actual' => null,
            'forecast' => null,
            'previous' => null
        ],
        [
            'date' => date('Y-m-d', strtotime($from . ' +7 days')),
            'country' => 'US',
            'event' => 'GDP (demo)',
            'impact' => 'high',
            'actual' => null,
            'forecast' => null,
            'previous' => null
        ],
    ];
}

// 4) Ha nincs API kulcs -> demo
$apiKey = defined('FINNHUB_API_KEY') ? trim((string)FINNHUB_API_KEY) : '';
if ($apiKey === '' || $apiKey === 'CHANGE_ME_FINNHUB_KEY') {
    legacy_json([
        'ok' => true,
        'mode' => 'demo',
        'from' => $from,
        'to' => $to,
        'source' => 'demo',
        'limit' => $limit,
        'warning' => 'FINNHUB_API_KEY nincs beállítva, demo módban fut.',
        'items' => array_slice(demoCalendar($from, $to), 0, $limit),
    ]);
}

// 5) ECONOMIC calendar (Finnhub)
$econUrl = "https://finnhub.io/api/v1/calendar/economic?from=" . urlencode($from) . "&to=" . urlencode($to);
$econ = finnhub_get_json($econUrl);

if ($econ['ok'] === true) {
    $payload = $econ['data'];
    $events = $payload['economicCalendar'] ?? [];
    if (!is_array($events)) $events = [];

    $out = [];
    foreach (array_slice($events, 0, $limit) as $e) {
        $out[] = [
            'date' => $e['date'] ?? '',
            'country' => $e['country'] ?? '',
            'event' => $e['event'] ?? '',
            'impact' => $e['impact'] ?? '',
            'actual' => $e['actual'] ?? null,
            'forecast' => $e['forecast'] ?? null,
            'previous' => $e['previous'] ?? null,
        ];
    }

    legacy_json([
        'ok' => true,
        'mode' => 'economic',
        'from' => $from,
        'to' => $to,
        'source' => 'finnhub',
        'limit' => $limit,
        'items' => $out,
    ]);
}

// 6) Fallback: EARNINGS calendar
$earnUrl = "https://finnhub.io/api/v1/calendar/earnings?from=" . urlencode($from) . "&to=" . urlencode($to);
$earn = finnhub_get_json($earnUrl);

if ($earn['ok'] === true) {
    $payload = $earn['data'];
    $items = $payload['earningsCalendar'] ?? [];
    if (!is_array($items)) $items = [];

    $out = [];
    foreach (array_slice($items, 0, $limit) as $e) {
        $symbol  = (string)($e['symbol'] ?? '');
        $date    = (string)($e['date'] ?? ($e['earningsDate'] ?? ''));
        $time    = (string)($e['time'] ?? '');
        $quarter = (string)($e['quarter'] ?? '');

        $epsA  = $e['epsActual'] ?? ($e['actual'] ?? null);
        $epsE  = $e['epsEstimate'] ?? ($e['estimate'] ?? null);
        $epsS  = $e['epsSurprise'] ?? null;
        $epsSP = $e['epsSurprisePercent'] ?? null;

        $revA  = $e['revenueActual'] ?? null;
        $revE  = $e['revenueEstimate'] ?? null;

        $yearAgo = $e['yearAgo'] ?? null;

        $out[] = [
            'date' => $date,
            'type' => 'earnings',
            'symbol' => $symbol,
            'title' => trim($symbol . ' earnings'),
            'time' => $time,
            'quarter' => $quarter,

            'epsActual' => $epsA,
            'epsEstimate' => $epsE,
            'epsSurprise' => $epsS,
            'epsSurprisePercent' => $epsSP,

            'revenueActual' => $revA,
            'revenueEstimate' => $revE,

            'yearAgo' => $yearAgo,
        ];
    }

    legacy_json([
        'ok' => true,
        'mode' => 'earnings',
        'from' => $from,
        'to' => $to,
        'source' => 'finnhub',
        'limit' => $limit,
        'items' => $out,
        'note' => 'Economic calendar hibázott/tiltott, earnings fallback ment.',
    ]);
}

// 7) Ha mindkettő fail -> DEMO + diagnosztika
legacy_json([
    'ok' => true,
    'mode' => 'demo',
    'from' => $from,
    'to' => $to,
    'source' => 'demo',
    'limit' => $limit,
    'warning' => 'Finnhub calendar nem elérhető ezzel a kulccsal/csomaggal. UI demo módban fut.',
    'debug' => [
        'economic' => $econ,
        'earnings' => $earn,
    ],
    'items' => array_slice(demoCalendar($from, $to), 0, $limit),
]);
