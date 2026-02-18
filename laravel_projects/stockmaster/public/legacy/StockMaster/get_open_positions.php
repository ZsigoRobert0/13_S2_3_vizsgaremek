<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['error' => 'Nincs bejelentkezve.'], 401);
}

$conn   = legacy_db();
$userId = currentUserId();

$stmt = $conn->prepare("
    SELECT
        p.ID,
        p.AssetID,
        p.OpenTime,
        p.Quantity,
        p.EntryPrice,
        p.PositionType,
        p.ProfitLoss
    FROM positions p
    WHERE p.UserID = ? AND p.IsOpen = 1
    ORDER BY p.OpenTime DESC
");

if (!$stmt) {
    legacy_json(['error' => 'DB prepare hiba.'], 500);
}

$stmt->bind_param("i", $userId);

if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['error' => 'DB execute hiba.'], 500);
}

$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    
    $rows[] = $row;
}

$stmt->close();

legacy_json($rows);
