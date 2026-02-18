<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

legacy_require_login_api();

$conn = legacy_db();
$userId = currentUserId();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen JSON body.'], 400);
}

$assetId  = (int)($data['assetId'] ?? 0);
$midPrice = (float)($data['midPrice'] ?? 0);

if ($assetId <= 0 || $midPrice <= 0) {
    legacy_json(['ok' => false, 'error' => 'Hibás assetId vagy midPrice.'], 400);
}

// FIX spread ($)
$spread = 0.05;
$half   = $spread / 2.0;

$bid = $midPrice - $half;
$ask = $midPrice + $half;

if ($bid <= 0 || $ask <= 0) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen bid/ask ár.'], 400);
}

$stmt = $conn->prepare("
    SELECT ID, Quantity, EntryPrice, PositionType
    FROM positions
    WHERE UserID = ? AND AssetID = ? AND IsOpen = 1
    ORDER BY ID ASC
");

if (!$stmt) {
    legacy_json(['ok' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
}

$stmt->bind_param('ii', $userId, $assetId);

if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['ok' => false, 'error' => 'Execute failed: ' . $stmt->error], 500);
}

$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

if (count($rows) === 0) {
    legacy_json(['ok' => false, 'error' => 'Nincs nyitott pozíció ehhez a termékhez.'], 404);
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

    if (!$update) {
        throw new RuntimeException('Prepare update failed: ' . $conn->error);
    }

    $totalPnl = 0.0;
    $totalCashDelta = 0.0;
    $closedCount = 0;

    foreach ($rows as $pos) {
        $positionId = (int)($pos['ID'] ?? 0);
        $q   = (float)($pos['Quantity'] ?? 0);
        $en  = (float)($pos['EntryPrice'] ?? 0);
        $pt  = strtolower(trim((string)($pos['PositionType'] ?? ''))); // buy / sell

        if ($positionId <= 0 || $q <= 0 || $en <= 0) {
            continue;
        }

        // ZÁRÓÁR spread szerint:
        // buy zárás = eladás BID-en
        // sell zárás = visszavétel ASK-on
        if ($pt === 'buy') {
            $closePrice = $bid;
            $pnl = ($closePrice - $en) * $q;
            $cashDelta = $closePrice * $q;        // eladás -> pénz be
        } elseif ($pt === 'sell') {
            $closePrice = $ask;
            $pnl = ($en - $closePrice) * $q;
            $cashDelta = -($closePrice * $q);     // visszavétel -> pénz ki
        } else {
            continue;
        }

        $update->bind_param('ddii', $closePrice, $pnl, $positionId, $userId);

        if (!$update->execute()) {
            throw new RuntimeException('Update execute failed: ' . $update->error);
        }

        if ($update->affected_rows > 0) {
            $totalPnl += $pnl;
            $totalCashDelta += $cashDelta;
            $closedCount++;
        }
    }

    $update->close();

    if ($closedCount <= 0) {
        $conn->rollback();
        legacy_json(['ok' => false, 'error' => 'Nem volt lezárható nyitott pozíció.'], 409);
    }

    $updBal = $conn->prepare("UPDATE users SET DemoBalance = DemoBalance + ? WHERE ID = ?");

    if (!$updBal) {
        throw new RuntimeException('Prepare balance update failed: ' . $conn->error);
    }

    $updBal->bind_param('di', $totalCashDelta, $userId);

    if (!$updBal->execute()) {
        throw new RuntimeException('Balance execute failed: ' . $updBal->error);
    }

    $updBal->close();

    $conn->commit();

    legacy_json([
        'ok' => true,
        'assetId' => $assetId,
        'midPrice' => $midPrice,
        'bid' => $bid,
        'ask' => $ask,
        'closedCount' => $closedCount,
        'totalProfitLoss' => $totalPnl,
        'balanceDelta' => $totalCashDelta,
        'spread' => $spread,
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    legacy_json(['ok' => false, 'error' => 'Szerver hiba zárás közben.'], 500);
}
