<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once "auth.php";
requireLogin();
require "db.php";

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve."]);
  exit;
}

if (!defined('FINNHUB_API_KEY') || FINNHUB_API_KEY === '' || FINNHUB_API_KEY === 'IDE_MÁSOLDD_BE_A_VALÓDI_FINNHUB_TOKENED') {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Nincs valódi Finnhub token. A db.php-ben állítsd be a FINNHUB_API_KEY-t."]);
  exit;
}

function httpGetJson(string $url): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
  ]);
  $raw = curl_exec($ch);
  $err = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($raw === false || $code >= 400) {
    return ["__error" => $err ?: ("HTTP " . $code), "__code" => $code];
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) return ["__error" => "Nem JSON válasz"];
  return $data;
}

$mode = strtolower((string)($_GET["mode"] ?? "portfolio"));

$defNewsLimit = 8;
$defPerSymbol = 3;
$defPortfolioTotal = 20;

$st = @$conn->prepare("SELECT NewsLimit, NewsPerSymbolLimit, NewsPortfolioTotalLimit FROM usersettings WHERE UserID = ? LIMIT 1");
if ($st) {
  $st->bind_param("i", $userId);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  if ($row) {
    $defNewsLimit = (int)($row["NewsLimit"] ?? $defNewsLimit);
    $defPerSymbol = (int)($row["NewsPerSymbolLimit"] ?? $defPerSymbol);
    $defPortfolioTotal = (int)($row["NewsPortfolioTotalLimit"] ?? $defPortfolioTotal);
  }
  $st->close();
}

$limit = (int)($_GET["limit"] ?? 0);
$perSymbol = (int)($_GET["perSymbol"] ?? 0);

if ($limit <= 0) {
  $limit = ($mode === "general") ? $defNewsLimit : $defPortfolioTotal;
}
if ($perSymbol <= 0) $perSymbol = $defPerSymbol;

if ($limit < 3) $limit = 3;
if ($limit > 60) $limit = 60;

if ($perSymbol < 1) $perSymbol = 1;
if ($perSymbol > 10) $perSymbol = 10;


$generalLimit = (int)($_GET["limit"] ?? 10);         
$portfolioTotalLimit = (int)($_GET["limit"] ?? 20);  
$perSymbolLimit = (int)($_GET["perSymbol"] ?? 3);    

if ($generalLimit < 3) $generalLimit = 3;
if ($generalLimit > 30) $generalLimit = 30;

if ($portfolioTotalLimit < 5) $portfolioTotalLimit = 5;
if ($portfolioTotalLimit > 60) $portfolioTotalLimit = 60;

if ($perSymbolLimit < 1) $perSymbolLimit = 1;
if ($perSymbolLimit > 10) $perSymbolLimit = 10;


$symbols = [];
if ($mode === "portfolio") {
  $stmt = $conn->prepare("
    SELECT DISTINCT a.Symbol
    FROM positions p
    JOIN assets a ON a.ID = p.AssetID
    WHERE p.UserID = ? AND p.IsOpen = 1
    ORDER BY a.Symbol
    LIMIT 10
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $symbols[] = (string)$r["Symbol"];
  $stmt->close();
}

if ($mode !== "general" && count($symbols) === 0) $mode = "general";

$items = [];

if ($mode === "general") {
  $url = "https://finnhub.io/api/v1/news?category=general&token=" . urlencode(FINNHUB_API_KEY);
  $data = httpGetJson($url);

  if (isset($data["__error"])) {
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => "Finnhub hiba (news): " . $data["__error"]]);
    exit;
  }

  foreach (array_slice($data, 0, min(30, $limit)) as $n) {
    $items[] = [
      "headline" => $n["headline"] ?? "",
      "source"   => $n["source"] ?? "",
      "url"      => $n["url"] ?? "",
      "datetime" => $n["datetime"] ?? null,
      "summary"  => $n["summary"] ?? "",
      "symbol"   => null
    ];
  }

  echo json_encode(["ok" => true, "mode" => "general", "items" => $items], JSON_UNESCAPED_UNICODE);
  exit;
}

$to = new DateTime("now");
$from = (clone $to)->modify("-7 days");
$fromStr = $from->format("Y-m-d");
$toStr = $to->format("Y-m-d");

foreach ($symbols as $sym) {
  $url = "https://finnhub.io/api/v1/company-news?symbol=" . urlencode($sym) .
         "&from=" . $fromStr . "&to=" . $toStr .
         "&token=" . urlencode(FINNHUB_API_KEY);

  $data = httpGetJson($url);
  if (isset($data["__error"])) continue;

 foreach (array_slice($data, 0, $perSymbol) as $n) {
    $items[] = [
      "headline" => $n["headline"] ?? "",
      "source"   => $n["source"] ?? "",
      "url"      => $n["url"] ?? "",
      "datetime" => $n["datetime"] ?? null,
      "summary"  => $n["summary"] ?? "",
      "symbol"   => $sym
    ];
  }
}

usort($items, fn($a, $b) => (int)($b["datetime"] ?? 0) <=> (int)($a["datetime"] ?? 0));

echo json_encode([
  "ok" => true,
  "mode" => "portfolio",
  "symbols" => $symbols,
  "items" => array_slice($items, 0, $limit)
], JSON_UNESCAPED_UNICODE);
