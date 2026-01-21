<?php
require "db.php";

set_time_limit(120);
ini_set('memory_limit', '512M');

$apiKey = "d4si64pr01qvsjbhte00d4si64pr01qvsjbhte0g";

// USA részvények (NASDAQ+NYSE+AMEX) lekérése
$url = "https://finnhub.io/api/v1/stock/symbol?exchange=US&token=$apiKey";
$json = file_get_contents($url);

if ($json === false) {
    die("Nem sikerült lekérni az API-t (file_get_contents).");
}

$data = json_decode($json, true);

if (!is_array($data)) {
    die("API hiba: nem érvényes JSON érkezett.");
}

// Prepare statement az INSERT-hez
$stmt = $conn->prepare("INSERT INTO assets (Symbol, Name, IsTradable) VALUES (?, ?, 1)");

if (!$stmt) {
    die("Prepare hiba: " . $conn->error);
}

$inserted = 0;
$maxRows  = 300; // ELSŐ KÖRBEN CSAK 300 RÉSZVÉNYT SZÚRUNK BE

foreach ($data as $stock) {

    if ($inserted >= $maxRows) {
        break; // megállunk 300 db után
    }

    $symbol = $stock["symbol"] ?? "";
    $name   = $stock["description"] ?? "";

    // szűrés pár hülyeség ellen
    if ($symbol === "" || $name === "") continue;
    if (strpos($symbol, "-") !== false) continue; // pl. furcsa típusok kizárása

    $stmt->bind_param("ss", $symbol, $name);
    $stmt->execute();

    $inserted++;
}

echo "Sikeresen importálva: $inserted részvény az assets táblába.";
