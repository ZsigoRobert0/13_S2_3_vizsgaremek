<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'no session'], 401);
}

$conn = legacy_db();
$userId = currentUserId();

$limit = (int)($_GET['limit'] ?? 15);
if ($limit <= 0) $limit = 15;
if ($limit > 50) $limit = 50;

$stmt = $conn->prepare("
    SELECT ID, Title, Message, CreatedAt, IsRead
    FROM notifications
    WHERE UserID = ?
    ORDER BY IsRead ASC, CreatedAt DESC, ID DESC
    LIMIT ?
");

if (!$stmt) {
    legacy_json(['ok' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
}

$stmt->bind_param('ii', $userId, $limit);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    legacy_json(['ok' => false, 'error' => 'Execute failed: ' . $err], 500);
}

$res = $stmt->get_result();
$items = [];
$unread = 0;

while ($r = $res->fetch_assoc()) {
    $isRead = (int)($r['IsRead'] ?? 0);
    if ($isRead === 0) $unread++;

    $items[] = [
        'id' => (int)$r['ID'],
        'title' => (string)($r['Title'] ?? ''),
        'message' => (string)($r['Message'] ?? ''),
        'createdAt' => $r['CreatedAt'] ?? null,
        'isRead' => $isRead,
    ];
}

$stmt->close();

legacy_json([
    'ok' => true,
    'unread' => $unread,
    'items' => $items,
]);
