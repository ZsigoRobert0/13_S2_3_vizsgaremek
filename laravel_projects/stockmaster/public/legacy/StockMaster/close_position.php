<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$conn = legacy_db();
$userId = currentUserId();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen JSON body.'], 400);
}

$positionId = (int)($data['positionId'] ?? 0);
$exitPrice  = (float)($data['exitPrice'] ?? 0);

if ($positionId <= 0 || $exitPrice <= 0) {
    legacy_json(['ok' => false, 'error' => 'Hibás positionId vagy exitPrice.'], 400);
}

// 1) pozíció lekérés (saját + nyitott)
$stmt = $conn->prepare("
    SELECT ID, UserID, Quantity, EntryPrice, PositionType, IsOpen
    FROM positions
    WHERE ID = ? AND UserID = ?
    LIMIT 1
");

if (!$stmt) {
    legacy_json(['ok' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
}

$stmt->bind_param('ii', $positionId, $userId);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    legacy_json(['ok' => false, 'error' => 'Execute failed: ' . $err], 500);
}

$res = $stmt->get_result();
$pos = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$pos) {
    legacy_json(['ok' => false, 'error' => 'Pozíció nem található (vagy nem a tiéd).'], 404);
}

if ((int)$pos['IsOpen'] !== 1) {
    legacy_json(['ok' => false, 'error' => 'Ez a pozíció már zárva van.'], 409);
}

$qty   = (float)$pos['Quantity'];
$entry = (float)$pos['EntryPrice'];
$type  = strtolower(trim((string)$pos['PositionType']));

if ($qty <= 0 || $entry <= 0) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen Quantity vagy EntryPrice.'], 400);
}

if ($type === 'buy') {
    $profitLoss = ($exitPrice - $entry) * $qty;
} elseif ($type === 'sell') {
    $profitLoss = ($entry - $exitPrice) * $qty;
} else {
    legacy_json(['ok' => false, 'error' => 'Ismeretlen PositionType.'], 500);
}

// 2) zárás update (transaction)
$upd = $conn->prepare("
    UPDATE positions
    SET CloseTime = NOW(),
        ExitPrice = ?,
        ProfitLoss = ?,
        IsOpen = 0
    WHERE ID = ? AND UserID = ? AND IsOpen = 1
");

if (!$upd) {
    legacy_json(['ok' => false, 'error' => 'Prepare update failed: ' . $conn->error], 500);
}

$upd->bind_param('ddii', $exitPrice, $profitLoss, $positionId, $userId);

if (!$upd->execute()) {
    $err = $upd->error;
    $upd->close();
    legacy_json(['ok' => false, 'error' => 'Update execute failed: ' . $err], 500);
}

$affected = $upd->affected_rows;
$upd->close();

if ($affected <= 0) {
    legacy_json(['ok' => false, 'error' => 'Nem sikerült lezárni (lehet már zárva / nem a tiéd).'], 409);
}

legacy_json([
    'ok' => true,
    'positionId' => $positionId,
    'exitPrice' => $exitPrice,
    'profitLoss' => $profitLoss
]);
