<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$conn   = legacy_db();
$userId = currentUserId();

session_write_close();

$symbol    = strtoupper(trim((string)($_POST['symbol'] ?? '')));      // pl. AAPL
$assetName = trim((string)($_POST['asset_name'] ?? ''));             // pl. Apple Inc.
$qty       = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0.0;
$price     = isset($_POST['price'])    ? (float)$_POST['price']    : 0.0;
$side      = strtolower(trim((string)($_POST['side'] ?? '')));        // buy / sell

if ($symbol === '' || $assetName === '' || $qty <= 0 || $price <= 0 || !in_array($side, ['buy', 'sell'], true)) {
    legacy_json(['ok' => false, 'error' => 'Hibás vagy hiányzó adatok.'], 400);
}

$conn->begin_transaction();

try {
    // 1) AssetID lekérése / létrehozása
    $assetId = 0;

    $stmt = $conn->prepare("SELECT ID FROM assets WHERE Symbol = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('DB prepare hiba (assets select).');
    }
    $stmt->bind_param('s', $symbol);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('DB execute hiba (assets select).');
    }
    $res = $stmt->get_result();
    $asset = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($asset) {
        $assetId = (int)$asset['ID'];
    } else {
        $ins = $conn->prepare("INSERT INTO assets (Symbol, Name, IsTradable) VALUES (?, ?, 1)");
        if (!$ins) {
            throw new Exception('DB prepare hiba (assets insert).');
        }
        $ins->bind_param('ss', $symbol, $assetName);
        if (!$ins->execute()) {
            $ins->close();
            throw new Exception('DB execute hiba (assets insert).');
        }
        $assetId = (int)$conn->insert_id;
        $ins->close();
    }

    if ($assetId <= 0) {
        throw new Exception('Nem sikerült AssetID-t meghatározni.');
    }

    // 2) Egyenleg lekérése (FOR UPDATE)
    $stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ? FOR UPDATE");
    if (!$stmt) {
        throw new Exception('DB prepare hiba (balance).');
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('DB execute hiba (balance).');
    }
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception('Felhasználó nem található.');
    }

    $balance    = (float)($user['DemoBalance'] ?? 0.0);
    $tradeValue = $qty * $price;

    // 3) Buy / Sell logika
    if ($side === 'buy') {
        if ($tradeValue > $balance) {
            throw new Exception('Nincs elegendő egyenleg.');
        }
        $newBalance = $balance - $tradeValue;
    } else {
        // Sell: ellenőrizzük a nettó holdingot (csak nyitott pozíciók)
        $q = $conn->prepare("
            SELECT COALESCE(SUM(
                CASE
                    WHEN PositionType = 'buy'  THEN Quantity
                    WHEN PositionType = 'sell' THEN -Quantity
                    ELSE 0
                END
            ), 0) AS holding
            FROM positions
            WHERE UserID = ? AND AssetID = ? AND IsOpen = 1
        ");
        if (!$q) {
            throw new Exception('DB prepare hiba (holding).');
        }
        $q->bind_param('ii', $userId, $assetId);
        if (!$q->execute()) {
            $q->close();
            throw new Exception('DB execute hiba (holding).');
        }
        $holdRes = $q->get_result()->fetch_assoc();
        $q->close();

        $holding = (float)($holdRes['holding'] ?? 0.0);

        if ($qty > $holding) {
            throw new Exception('Nincs ennyi eladható mennyiséged ebből a részvényből.');
        }

        $newBalance = $balance + $tradeValue;
    }

    // 4) Pozíció rögzítése
    $stmt = $conn->prepare("
        INSERT INTO positions (UserID, AssetID, OpenTime, Quantity, EntryPrice, PositionType, IsOpen)
        VALUES (?, ?, NOW(), ?, ?, ?, 1)
    ");
    if (!$stmt) {
        throw new Exception('DB prepare hiba (positions insert).');
    }
    $stmt->bind_param('iidds', $userId, $assetId, $qty, $price, $side);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('DB execute hiba (positions insert).');
    }
    $stmt->close();

    // 5) Egyenleg frissítés
    $stmt = $conn->prepare("UPDATE users SET DemoBalance = ? WHERE ID = ?");
    if (!$stmt) {
        throw new Exception('DB prepare hiba (balance update).');
    }
    $stmt->bind_param('di', $newBalance, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('DB execute hiba (balance update).');
    }
    $stmt->close();

    $conn->commit();

    legacy_json([
        'ok'         => true,
        'status'     => 'ok',        
        'newBalance' => $newBalance,
        'assetId'    => $assetId,
        'symbol'     => $symbol,
        'side'       => $side,
        'quantity'   => $qty,
        'price'      => $price,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    legacy_json(['ok' => false, 'error' => $e->getMessage()], 500);
}
