<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isLoggedIn()) {
    legacy_json(['error' => 'Nincs bejelentkezve.'], 401);
}

$conn   = legacy_db();
$userId = currentUserId();

session_write_close();

// Egyenleg (legacy: users.DemoBalance)
$stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ? LIMIT 1");
if (!$stmt) {
    legacy_json(['error' => 'DB prepare hiba (balance).'], 500);
}
$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['error' => 'DB execute hiba (balance).'], 500);
}
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$balance = $user ? (float)($user['DemoBalance'] ?? 0.0) : 0.0;

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
if (!$stmt) {
    legacy_json(['error' => 'DB prepare hiba (positions).'], 500);
}
$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['error' => 'DB execute hiba (positions).'], 500);
}

$res = $stmt->get_result();

$positions = [];
while ($row = $res->fetch_assoc()) {
    $positions[] = [
        'AssetID'       => (int)$row['AssetID'],
        'Symbol'        => (string)$row['Symbol'],
        'Name'          => (string)$row['Name'],
        'Quantity'      => (float)$row['NetQty'],
        'AvgEntryPrice' => $row['AvgEntryPrice'] !== null ? (float)$row['AvgEntryPrice'] : 0.0,
    ];
}
$stmt->close();

legacy_json([
    'balance'   => $balance,
    'positions' => $positions,
]);
