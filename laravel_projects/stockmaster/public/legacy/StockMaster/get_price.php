<?php
require_once __DIR__ .'_bootstrap.php';
require_once __DIR__ . '/finnhub_http.php';

header("Content-Type: application/json; charset=utf-8");

$userId = currentUserId();
if ($userId <= 0) {
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve"]);
  exit;
}
session_write_close();

$symbol = strtoupper(trim($_GET["symbol"] ?? ""));
if ($symbol === "") {
  echo json_encode(["ok" => false, "error" => "Hiányzó symbol"]);
  exit;
}

$apiKey = defined("FINNHUB_API_KEY") ? FINNHUB_API_KEY : "";
if ($apiKey === "") {
  echo json_encode(["ok" => false, "error" => "Hiányzik a FINNHUB_API_KEY"]);
  exit;
}

$url = "https://finnhub.io/api/v1/quote?symbol=" . urlencode($symbol) . "&token=" . urlencode($apiKey);
$data = finnhub_get_json($url);

if (!($data["_ok"] ?? false) || !isset($data["c"])) {
  echo json_encode([
    "ok" => false,
    "error" => "Nem sikerült az árfolyam lekérése (API)",
    "symbol" => $symbol,
    "http" => $data["_http"] ?? 0,
    "err" => $data["_err"] ?? "unknown"
  ]);
  exit;
}

echo json_encode([
  "ok" => true,
  "symbol" => $symbol,
  "price" => (float)$data["c"],
  "source" => "api"
]);
