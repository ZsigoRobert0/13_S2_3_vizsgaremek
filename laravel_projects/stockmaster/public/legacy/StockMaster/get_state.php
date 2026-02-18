<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/auth.php";
requireLogin();
require_once __DIR__ . "/db.php";

header("Content-Type: application/json; charset=utf-8");

$userId = (int)($_SESSION["user_id"] ?? 0);

session_write_close();

if ($userId <= 0) {
    echo json_encode(["error" => "Nincs user_id sessionben."]);
    exit;
}

// Egyenleg
$stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$balance = $user ? (float)$user["DemoBalance"] : 0.0;

/*
  Nettó pozíció számítás:
  - buy: +qty
  - sell: -qty
  AvgEntryPrice: nettó irány szerinti súlyozott átlag az entryPrice-okból.
*/
$sql = "
SELECT 
  a.ID AS AssetID,
  a.Symbol,
  a.Name,
  SUM(CASE WHEN p.PositionType='buy' THEN p.Quantity ELSE -p.Quantity END) AS NetQty,
  (
    SUM(
      CASE 
        WHEN p.PositionType='buy' THEN  (p.Quantity * p.EntryPrice)
        ELSE                             (-p.Quantity * p.EntryPrice)
      END
    )
    /
    NULLIF(SUM(CASE WHEN p.PositionType='buy' THEN p.Quantity ELSE -p.Quantity END), 0)
  ) AS AvgEntryPrice
FROM positions p
JOIN assets a ON a.ID = p.AssetID
WHERE p.UserID = ? AND p.IsOpen = 1
GROUP BY a.ID, a.Symbol, a.Name
HAVING ABS(NetQty) > 0.000001
ORDER BY a.Symbol
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$positions = [];
while ($row = $res->fetch_assoc()) {
    $positions[] = [
        "AssetID"       => (int)$row["AssetID"],
        "Symbol"        => $row["Symbol"],
        "Name"          => $row["Name"],
        "Quantity"      => (float)$row["NetQty"],
        "AvgEntryPrice" => $row["AvgEntryPrice"] !== null ? (float)$row["AvgEntryPrice"] : 0.0
    ];
}
$stmt->close();

echo json_encode([
    "balance"   => $balance,
    "positions" => $positions
]);
