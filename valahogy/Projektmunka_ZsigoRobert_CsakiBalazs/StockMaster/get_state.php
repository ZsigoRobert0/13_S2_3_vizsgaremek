<?php
session_start();
require_once "../StockMaster/auth.php";
requireLogin();
require "db.php";

header("Content-Type: application/json; charset=utf-8");

$userId = (int)$_SESSION["user_id"];

// Egyenleg
$stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$balance = $user ? (float)$user["DemoBalance"] : 0.0;

// AGGREGÁLT NYITOTT POZÍCIÓK
$sql = "
    SELECT 
        a.Symbol,
        a.Name,
        SUM(
            CASE 
                WHEN p.PositionType = 'buy' THEN p.Quantity
                WHEN p.PositionType = 'sell' THEN -p.Quantity
                ELSE 0
            END
        ) AS Quantity,
        -- súlyozott átlag belépő ár csak a BUY-okból
        CASE 
            WHEN SUM(CASE WHEN p.PositionType = 'buy' THEN p.Quantity ELSE 0 END) > 0
            THEN 
                SUM(
                    CASE 
                        WHEN p.PositionType = 'buy' THEN p.Quantity * p.EntryPrice
                        ELSE 0
                    END
                ) 
                / 
                SUM(CASE WHEN p.PositionType = 'buy' THEN p.Quantity ELSE 0 END)
            ELSE 0
        END AS AvgEntryPrice
    FROM positions p
    JOIN assets a ON p.AssetID = a.ID
    WHERE p.UserID = ?
    GROUP BY a.ID, a.Symbol, a.Name
    HAVING Quantity > 0
    ORDER BY a.Symbol
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$positions = [];
while ($row = $res->fetch_assoc()) {
    $positions[] = [
        "Symbol"        => $row["Symbol"],
        "Name"          => $row["Name"],
        "Quantity"      => (float)$row["Quantity"],
        "AvgEntryPrice" => (float)$row["AvgEntryPrice"]
    ];
}

echo json_encode([
    "balance"   => $balance,
    "positions" => $positions
]);
