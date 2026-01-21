<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Nincs bejelentkezve.";
    exit;
}

$userId = (int)$_SESSION['user_id'];

$positionId = isset($_POST['position_id']) ? (int)$_POST['position_id'] : 0;
$exitPrice  = isset($_POST['exit_price']) ? (float)$_POST['exit_price'] : 0;

if ($positionId <= 0 || $exitPrice <= 0) {
    http_response_code(400);
    echo "Hiányzó vagy hibás adatok.";
    exit;
}

$conn->begin_transaction();

try {
    // 1) Pozíció lekérése (csak a sajátod + nyitott)
    $stmt = $conn->prepare("
        SELECT Quantity, EntryPrice, PositionType
        FROM positions
        WHERE ID = ? AND UserID = ? AND IsOpen = 1
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $positionId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $pos = $res->fetch_assoc();

    if (!$pos) {
        throw new Exception("Pozíció nem található vagy már zárt.");
    }

    $quantity    = (float)$pos['Quantity'];
    $entryPrice  = (float)$pos['EntryPrice'];
    $positionType = $pos['PositionType'];

    // 2) Profit/Loss számítás
    if ($positionType === 'long') {
        $profitLoss = ($exitPrice - $entryPrice) * $quantity;
    } else { // short
        $profitLoss = ($entryPrice - $exitPrice) * $quantity;
    }

    // 3) Pozíció frissítése (lezárás)
    $upd = $conn->prepare("
        UPDATE positions
        SET CloseTime = NOW(),
            ExitPrice = ?,
            IsOpen = 0,
            ProfitLoss = ?
        WHERE ID = ? AND UserID = ?
    ");
    $upd->bind_param("ddii", $exitPrice, $profitLoss, $positionId, $userId);
    $upd->execute();

    // 4) DemoBalance frissítése – visszaadjuk a margin-t + P/L-t (ez csak példa)
    // Itt most egyszerűbben csak a P/L-t írjuk jóvá.
    $upd2 = $conn->prepare("
        UPDATE users
        SET DemoBalance = DemoBalance + ?
        WHERE ID = ?
    ");
    $upd2->bind_param("di", $profitLoss, $userId);
    $upd2->execute();

    $conn->commit();
    echo "OK";
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Hiba: " . $e->getMessage();
}
