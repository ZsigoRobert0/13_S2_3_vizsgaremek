<?php
session_start();
require_once "../StockMaster/auth.php";
requireLogin();
require "db.php";

header("Content-Type: application/json; charset=utf-8");

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Nincs bejelentkezve."]);
    exit;
}

$userId = (int)$_SESSION["user_id"];

$symbol     = $_POST["symbol"]      ?? null;  
$assetName  = $_POST["asset_name"]  ?? null;  
$qty        = isset($_POST["quantity"]) ? (float)$_POST["quantity"] : 0;
$price      = isset($_POST["price"])    ? (float)$_POST["price"]    : 0;
$side       = $_POST["side"]        ?? null;  

if (!$symbol || !$assetName || $qty <= 0 || $price <= 0 || !in_array($side, ["buy","sell"])) {
    http_response_code(400);
    echo json_encode(["error" => "Hibás vagy hiányzó adatok."]);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT ID FROM assets WHERE Symbol = ? LIMIT 1");
    $stmt->bind_param("s", $symbol);
    $stmt->execute();
    $res   = $stmt->get_result();
    $asset = $res->fetch_assoc();

    if ($asset) {
        $assetId = (int)$asset["ID"];
    } else {
        $ins = $conn->prepare("INSERT INTO assets (Symbol, Name, IsTradable) VALUES (?, ?, 1)");
        $ins->bind_param("ss", $symbol, $assetName);
        $ins->execute();
        $assetId = $conn->insert_id;
    }

    $stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ? FOR UPDATE");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user) {
        throw new Exception("Felhasználó nem található.");
    }

    $balance = (float)$user["DemoBalance"];
    $tradeValue = $qty * $price;

    if ($side === "buy") {
        if ($tradeValue > $balance) {
            throw new Exception("Nincs elegendő egyenleg.");
        }
        $newBalance = $balance - $tradeValue;

    } else {
        $q = $conn->prepare("
            SELECT 
                COALESCE(SUM(
                    CASE 
                        WHEN PositionType = 'buy' THEN Quantity 
                        WHEN PositionType = 'sell' THEN -Quantity
                        ELSE 0
                    END
                ),0) AS holding
            FROM positions
            WHERE UserID = ? AND AssetID = ?
        ");
        $q->bind_param("ii", $userId, $assetId);
        $q->execute();
        $holdRes = $q->get_result()->fetch_assoc();
        $holding = (float)$holdRes["holding"];

        if ($qty > $holding) {
            throw new Exception("Nincs ennyi eladható mennyiséged ebből a részvényből.");
        }

        $newBalance = $balance + $tradeValue;
    }

    $stmt = $conn->prepare("
        INSERT INTO positions (UserID, AssetID, OpenTime, Quantity, EntryPrice, PositionType, IsOpen)
        VALUES (?, ?, NOW(), ?, ?, ?, 1)
    ");
    $stmt->bind_param("iidds", $userId, $assetId, $qty, $price, $side);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE users SET DemoBalance = ? WHERE ID = ?");
    $stmt->bind_param("di", $newBalance, $userId);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        "status"     => "ok",
        "newBalance" => $newBalance
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
