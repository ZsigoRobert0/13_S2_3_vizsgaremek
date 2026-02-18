<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . "/auth.php";
requireLogin();
require_once __DIR__ . "/db.php";

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve."]);
  exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$positionId = (int)($data["positionId"] ?? 0);
$exitPrice  = (float)($data["exitPrice"] ?? 0);

if ($positionId <= 0 || $exitPrice <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Hibás positionId vagy exitPrice."]);
  exit;
}

// 1) pozíció lekérés (saját + nyitott)
$stmt = $conn->prepare("
  SELECT ID, UserID, Quantity, EntryPrice, PositionType, IsOpen
  FROM positions
  WHERE ID = ? AND UserID = ?
  LIMIT 1
");
$stmt->bind_param("ii", $positionId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$pos = $res->fetch_assoc();

if (!$pos) {
  http_response_code(404);
  echo json_encode(["ok" => false, "error" => "Pozíció nem található (vagy nem a tiéd)."]);
  exit;
}
if ((int)$pos["IsOpen"] !== 1) {
  http_response_code(409);
  echo json_encode(["ok" => false, "error" => "Ez a pozíció már zárva van."]);
  exit;
}

$qty   = (float)$pos["Quantity"];
$entry = (float)$pos["EntryPrice"];
$type  = strtolower((string)$pos["PositionType"]);

if ($type === "buy") {
  $profitLoss = ($exitPrice - $entry) * $qty;
} elseif ($type === "sell") {
  $profitLoss = ($entry - $exitPrice) * $qty;
} else {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Ismeretlen PositionType."]);
  exit;
}

// 2) zárás update
$upd = $conn->prepare("
  UPDATE positions
  SET CloseTime = NOW(),
      ExitPrice = ?,
      ProfitLoss = ?,
      IsOpen = 0
  WHERE ID = ? AND UserID = ? AND IsOpen = 1
");
$upd->bind_param("ddii", $exitPrice, $profitLoss, $positionId, $userId);
$upd->execute();

echo json_encode([
  "ok" => true,
  "positionId" => $positionId,
  "exitPrice" => $exitPrice,
  "profitLoss" => $profitLoss
]);

refreshState();
