<?php
session_start();
require __DIR__ . '_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Nincs bejelentkezve."]);
    exit;
}


$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT p.ID, p.AssetID, p.OpenTime, p.Quantity, p.EntryPrice, p.PositionType, p.ProfitLoss
    FROM positions p
    WHERE p.UserID = ? AND p.IsOpen = 1
    ORDER BY p.OpenTime DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);


