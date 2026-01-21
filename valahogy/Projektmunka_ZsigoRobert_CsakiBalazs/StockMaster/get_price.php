<?php
// get_price.php
require "db.php";

header("Content-Type: application/json; charset=utf-8");

$symbol = $_GET["symbol"] ?? null;
if (!$symbol) {
    echo json_encode(["error" => "Hiányzó symbol."]);
    exit;
}

$apiKey = "d4si64pr01qvsjbhte00d4si64pr01qvsjbhte0g";

// árfolyam lekérés
$url = "https://finnhub.io/api/v1/quote?symbol=" . urlencode($symbol) . "&token=" . $apiKey;
$json = file_get_contents($url);

if ($json === false) {
    echo json_encode(["error" => "Nem sikerült az árfolyam lekérése."]);
    exit;
}

$data = json_decode($json, true);

if (!is_array($data) || !isset($data["c"])) {
    echo json_encode(["error" => "Nem érvényes választ adott az API."]);
    exit;
}

echo json_encode([
    "symbol" => $symbol,
    "price"  => $data["c"]   
]);
