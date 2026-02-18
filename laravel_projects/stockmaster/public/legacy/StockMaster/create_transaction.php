<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    legacy_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$conn = legacy_db();
$userId = currentUserId();

$type = (string)($_POST['type'] ?? '');
$amountRaw = (string)($_POST['amount'] ?? '');
$description = trim((string)($_POST['description'] ?? ''));

// FRONTENDDEL EGYEZTETVE: deposit / withdrawal
if (!in_array($type, ['deposit', 'withdrawal'], true)) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen típus'], 400);
}

if (!is_numeric($amountRaw)) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen összeg'], 400);
}

$amount = (float)$amountRaw;
if ($amount <= 0) {
    legacy_json(['ok' => false, 'error' => 'Érvénytelen összeg'], 400);
}

if ($description === '') {
    $description = ($type === 'deposit') ? 'Befizetés' : 'Kiutalás';
}

$conn->begin_transaction();

try {
    // 1) Aktuális DemoBalance
    $stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $userId);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Execute failed: ' . $err);
    }

    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        throw new RuntimeException('Felhasználó nem található');
    }

    $currentBalance = (float)$row['DemoBalance'];

    // 2) Új balance számítás
    $newBalance = $currentBalance + ($type === 'deposit' ? $amount : -$amount);

    if ($newBalance < 0) {
        throw new RuntimeException('Nincs elég egyenleg');
    }

    // 3) users DemoBalance update
    $stmt = $conn->prepare("UPDATE users SET DemoBalance = ? WHERE ID = ?");
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('di', $newBalance, $userId);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Update failed: ' . $err);
    }

    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO transactionslog (UserID, Type, Amount, TransactionTime, Description)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('isds', $userId, $type, $amount, $description);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Insert failed: ' . $err);
    }

    $stmt->close();

    $conn->commit();

    legacy_json([
        'ok' => true,
        'newBalance' => number_format($newBalance, 2, '.', ''),
    ], 200);

} catch (Throwable $e) {
    $conn->rollback();
    legacy_json(['ok' => false, 'error' => $e->getMessage()], 400);
}
