<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$conn   = legacy_db();
$userId = currentUserId();

session_write_close();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    legacy_json(['ok' => false, 'error' => 'Hibás id.'], 400);
}

$stmt = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE ID = ? AND UserID = ? LIMIT 1");
if (!$stmt) {
    legacy_json(['ok' => false, 'error' => 'DB prepare hiba.'], 500);
}

$stmt->bind_param('ii', $id, $userId);

if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['ok' => false, 'error' => 'DB execute hiba.'], 500);
}

$affected = (int)$stmt->affected_rows;
$stmt->close();

legacy_json(['ok' => true, 'updated' => $affected]);
