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

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$assetId  = (int)($data["assetId"] ?? 0);
$midPrice = (float)($data["midPrice"] ?? 0);

if ($assetId <= 0 || $midPrice <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Hibás assetId vagy midPrice."]);
  exit;
}

// FIX spread ($)
$spread = 0.05;
$half   = $spread / 2.0;

$bid = $midPrice - $half;
$ask = $midPrice + $half;

// extra védelem
if ($bid <= 0 || $ask <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Érvénytelen bid/ask ár."]);
  exit;
}

// 1) Lekérjük az összes NYITOTT pozíció sort
$stmt = $conn->prepare("
  SELECT ID, Quantity, EntryPrice, PositionType
  FROM positions
  WHERE UserID = ? AND AssetID = ? AND IsOpen = 1
  ORDER BY ID ASC
");
$stmt->bind_param("ii", $userId, $assetId);
$stmt->execute();
$stmt->bind_result($id, $qty, $entry, $type);

$rows = [];
while ($stmt->fetch()) {
  $rows[] = [
    "ID" => (int)$id,
    "Quantity" => (float)$qty,
    "EntryPrice" => (float)$entry,
    "PositionType" => (string)$type
  ];
}
$stmt->close();

if (count($rows) === 0) {
  http_response_code(404);
  echo json_encode(["ok" => false, "error" => "Nincs nyitott pozíció ehhez a termékhez."]);
  exit;
}

// 2) Transaction
$conn->begin_transaction();

try {
  $update = $conn->prepare("
    UPDATE positions
    SET CloseTime = NOW(),
        ExitPrice = ?,
        ProfitLoss = ?,
        IsOpen = 0
    WHERE ID = ? AND UserID = ? AND IsOpen = 1
  ");

  $totalPnl = 0.0;
  $totalCashDelta = 0.0;
  $closedCount = 0;

  foreach ($rows as $pos) {
    $positionId = (int)$pos["ID"];
    $q   = (float)$pos["Quantity"];
    $en  = (float)$pos["EntryPrice"];
    $pt  = strtolower(trim((string)$pos["PositionType"])); // buy / sell

    if ($q <= 0 || $en <= 0) continue;

    // ZÁRÓÁR spread szerint:
    // buy zárás = eladás BID-en
    // sell zárás = visszavétel ASK-on
    if ($pt === "buy") {
      $closePrice = $bid;
      $pnl = ($closePrice - $en) * $q;
      $cashDelta = $closePrice * $q;        // eladás -> pénz be
    } elseif ($pt === "sell") {
      $closePrice = $ask;
      $pnl = ($en - $closePrice) * $q;
      $cashDelta = -($closePrice * $q);     // visszavétel -> pénz ki
    } else {
      continue;
    }

    $update->bind_param("ddii", $closePrice, $pnl, $positionId, $userId);
    $update->execute();

    if ($update->affected_rows > 0) {
      $totalPnl += $pnl;
      $totalCashDelta += $cashDelta;
      $closedCount++;
    }
  }

  if ($closedCount <= 0) {
    $conn->rollback();
    http_response_code(409);
    echo json_encode(["ok" => false, "error" => "Nem volt lezárható nyitott pozíció."]);
    exit;
  }

  // 3) Egyenleg frissítése CASHFLOW-val (mert nyitáskor már könyveltél)
  $updBal = $conn->prepare("UPDATE users SET DemoBalance = DemoBalance + ? WHERE ID = ?");
  $updBal->bind_param("di", $totalCashDelta, $userId);
  $updBal->execute();

  $conn->commit();

  echo json_encode([
    "ok" => true,
    "assetId" => $assetId,
    "midPrice" => $midPrice,
    "bid" => $bid,
    "ask" => $ask,
    "closedCount" => $closedCount,
    "totalProfitLoss" => $totalPnl,
    "balanceDelta" => $totalCashDelta,
    "spread" => $spread
  ]);

} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Szerver hiba zárás közben."]);
}
