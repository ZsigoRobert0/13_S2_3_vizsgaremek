<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json([], 401); 
}

$conn   = legacy_db();
$userId = currentUserId();

session_write_close();

$limit = (int)($_GET['limit'] ?? 100);
if ($limit <= 0) {
    $limit = 100;
}
if ($limit > 500) {
    $limit = 500;
}

$stmt = $conn->prepare("
    SELECT ID, Type, Amount, TransactionTime, Description
    FROM transactionslog
    WHERE UserID = ?
    ORDER BY TransactionTime DESC, ID DESC
    LIMIT ?
");

if (!$stmt) {
    legacy_json(['error' => 'DB prepare hiba.'], 500);
}

$stmt->bind_param('ii', $userId, $limit);

if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['error' => 'DB execute hiba.'], 500);
}

$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
    $out[] = [
        'id'          => (int)$r['ID'],
        'type'        => (string)$r['Type'], // deposit / withdrawal
        'amount'      => (float)$r['Amount'],
        'time'        => (string)$r['TransactionTime'],
        'description' => (string)($r['Description'] ?? ''),
    ];
}

$stmt->close();

legacy_json($out);
