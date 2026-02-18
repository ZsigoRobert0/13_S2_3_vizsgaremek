<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$conn   = legacy_db();
$userId = currentUserId();

session_write_close();

$stmt = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE UserID = ? AND IsRead = 0");
if (!$stmt) {
    legacy_json(['ok' => false, 'error' => 'DB prepare hiba.'], 500);
}

$stmt->bind_param('i', $userId);

if (!$stmt->execute()) {
    $stmt->close();
    legacy_json(['ok' => false, 'error' => 'DB execute hiba.'], 500);
}

$affected = (int)$stmt->affected_rows;
$stmt->close();

legacy_json(['ok' => true, 'updated' => $affected]);
