<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once "auth.php";
requireLogin();
require "db.php";

/**
 * db.php-ban:
 * define('FINNHUB_API_KEY', 'IDE_A_TOKEN');
 * (csak a token string, nem URL!)
 */

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve."]);
  exit;
}

// session lock minimalizálás
session_write_close();

$apiKey = defined('FINNHUB_API_KEY') ? trim((string)FINNHUB_API_KEY) : "";

// 1) Időablak (alap: ma -> +14 nap)
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d', strtotime('+14 days'));

// egyszerű validálás
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
  http_response_code(400);
  echo json_encode([
    "ok" => false,
    "error" => "Hibás dátum formátum. (YYYY-MM-DD kell)",
    "from" => $from,
    "to" => $to
  ]);
  exit;
}

function httpGetJson(string $url, int &$httpCode = 0): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false, // localhost fejlesztéshez ok
  ]);
  $raw = curl_exec($ch);
  $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  if ($raw === false) {
    return ["_error" => "cURL hiba: " . $err, "_http" => $httpCode];
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    return ["_error" => "Nem JSON válasz: " . mb_substr((string)$raw, 0, 180), "_http" => $httpCode];
  }
  $data["_http"] = $httpCode;
  return $data;
}

function demoCalendar(string $from, string $to): array {
  return [
    "ok" => true,
    "mode" => "demo",
    "from" => $from,
    "to" => $to,
    "source" => "demo",
    "items" => [
      ["date" => $from, "country" => "US", "event" => "CPI (demo)", "impact" => "high", "actual" => null, "forecast" => null, "previous" => null],
      ["date" => date('Y-m-d', strtotime($from.' +3 days')), "country" => "US", "event" => "FOMC Minutes (demo)", "impact" => "medium", "actual" => null, "forecast" => null, "previous" => null],
      ["date" => date('Y-m-d', strtotime($from.' +7 days')), "country" => "US", "event" => "GDP (demo)", "impact" => "high", "actual" => null, "forecast" => null, "previous" => null],
    ]
  ];
}

/* --------------------------------------------------------------------------
   ✅ ÚJ: LIMIT logika (GET param + usersettings default)
   - Ha van ?limit=..., az nyer
   - Ha nincs, akkor usersettings.CalendarLimit (ha létezik oszlop)
   - Ha az oszlop még nincs, default = 8
-------------------------------------------------------------------------- */

$defCalendarLimit = 8;

// próbáljuk beolvasni usersettingsből (ha az oszlop megvan)
$st = @$conn->prepare("SELECT CalendarLimit FROM usersettings WHERE UserID = ? LIMIT 1");
if ($st) {
  $st->bind_param("i", $userId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if ($row) $defCalendarLimit = (int)($row["CalendarLimit"] ?? $defCalendarLimit);
  $st->close();
}

// GET felülírja
$limit = (int)($_GET["limit"] ?? $defCalendarLimit);
if ($limit < 3) $limit = 3;
if ($limit > 60) $limit = 60;

/* -------------------------------------------------------------------------- */

// 2) Ha nincs kulcs, demo (UI ne dobjon hibát)
if ($apiKey === "" || str_contains($apiKey, "token=") || str_contains($apiKey, "http")) {
  echo json_encode([
    "ok" => true,
    "mode" => "demo",
    "from" => $from,
    "to" => $to,
    "source" => "demo",
    "warning" => "FINNHUB_API_KEY nincs jól beállítva. db.php-ban csak a token legyen (pl. 'abc123'), ne URL.",
    "items" => demoCalendar($from, $to)["items"]
  ]);
  exit;
}

// 3) ECONOMIC calendar
$economicUrl = "https://finnhub.io/api/v1/calendar/economic?from=" . urlencode($from) . "&to=" . urlencode($to) . "&token=" . urlencode($apiKey);
$code = 0;
$econ = httpGetJson($economicUrl, $code);

$econError = $econ["_error"] ?? ($econ["error"] ?? null);

// 3/a) Ha ECONOMIC sikerült
if ($code === 200 && !$econError) {
  $events = $econ["economicCalendar"] ?? [];
  if (!is_array($events)) $events = [];

  $out = [];
  foreach (array_slice($events, 0, $limit) as $e) {  // ✅ itt limitálunk
    $out[] = [
      "date" => $e["date"] ?? "",
      "country" => $e["country"] ?? "",
      "event" => $e["event"] ?? "",
      "impact" => $e["impact"] ?? "",
      "actual" => $e["actual"] ?? null,
      "forecast" => $e["forecast"] ?? null,
      "previous" => $e["previous"] ?? null,
    ];
  }

  echo json_encode([
    "ok" => true,
    "mode" => "economic",
    "from" => $from,
    "to" => $to,
    "source" => "finnhub",
    "limit" => $limit,
    "items" => $out
  ]);
  exit;
}

// 4) Fallback: EARNINGS calendar
$earningsUrl = "https://finnhub.io/api/v1/calendar/earnings?from=" . urlencode($from) . "&to=" . urlencode($to) . "&token=" . urlencode($apiKey);
$code2 = 0;
$earn = httpGetJson($earningsUrl, $code2);
$earnError = $earn["_error"] ?? ($earn["error"] ?? null);

if ($code2 === 200 && !$earnError) {
  $items = $earn["earningsCalendar"] ?? [];
  if (!is_array($items)) $items = [];

  $out = [];

  foreach (array_slice($items, 0, $limit) as $e) { // ✅ itt is limitálunk
    $symbol = (string)($e["symbol"] ?? "");
    $date   = (string)($e["date"] ?? ($e["earningsDate"] ?? ""));
    $time   = (string)($e["time"] ?? "");
    $quarter= (string)($e["quarter"] ?? "");

    $epsA   = $e["epsActual"] ?? ($e["actual"] ?? null);
    $epsE   = $e["epsEstimate"] ?? ($e["estimate"] ?? null);
    $epsS   = $e["epsSurprise"] ?? null;
    $epsSP  = $e["epsSurprisePercent"] ?? null;

    $revA   = $e["revenueActual"] ?? null;
    $revE   = $e["revenueEstimate"] ?? null;

    $yearAgo= $e["yearAgo"] ?? null;

    $out[] = [
      "date" => $date,
      "type" => "earnings",
      "symbol" => $symbol,
      "title" => trim($symbol . " earnings"),
      "time" => $time,
      "quarter" => $quarter,

      "epsActual" => $epsA,
      "epsEstimate" => $epsE,
      "epsSurprise" => $epsS,
      "epsSurprisePercent" => $epsSP,

      "revenueActual" => $revA,
      "revenueEstimate" => $revE,

      "yearAgo" => $yearAgo,
    ];
  }

  echo json_encode([
    "ok" => true,
    "mode" => "earnings",
    "from" => $from,
    "to" => $to,
    "source" => "finnhub",
    "limit" => $limit,
    "items" => $out,
    "note" => "Economic calendar tiltva volt vagy hibázott, earnings fallback ment."
  ]);
  exit;
}

// 5) Ha mindkettő fail -> DEMO + diagnosztika
echo json_encode([
  "ok" => true,
  "mode" => "demo",
  "from" => $from,
  "to" => $to,
  "source" => "demo",
  "limit" => $limit,
  "warning" => "Finnhub calendar nem elérhető ezzel a kulccsal/csomaggal. UI demo módban fut.",
  "debug" => [
    "economic_http" => $code,
    "economic_error" => $econError,
    "earnings_http" => $code2,
    "earnings_error" => $earnError,
  ],
  "items" => demoCalendar($from, $to)["items"]
]);
