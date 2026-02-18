<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve'], 401);
}

$conn = legacy_db();
$userId = currentUserId();

$receive = 1; // default: engedélyezett

$stmt = $conn->prepare("SELECT ReceiveNotifications FROM usersettings WHERE UserID = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $receive = (int)($row['ReceiveNotifications'] ?? 1);
        }
    }

    $stmt->close();
}

if ($receive !== 1) {
    legacy_json(['ok' => false, 'error' => 'Értesítések kikapcsolva'], 403);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = [];
}

$title   = trim((string)($data['title']   ?? ($_POST['title']   ?? '')));
$message = trim((string)($data['message'] ?? ($_POST['message'] ?? '')));

if ($title === '' || $message === '') {
    legacy_json(['ok' => false, 'error' => 'Hiányzó title/message'], 400);
}

// Max hosszak (biztonság)
if (mb_strlen($title) > 255)   $title = mb_substr($title, 0, 255);
if (mb_strlen($message) > 2000) $message = mb_substr($message, 0, 2000);

$stmt = $conn->prepare("
    INSERT INTO notifications (UserID, Title, Message, CreatedAt, IsRead)
    VALUES (?, ?, ?, NOW(), 0)
");

if (!$stmt) {
    legacy_json(['ok' => false, 'error' => 'Prepare failed: ' . $conn->error], 500);
}

$stmt->bind_param('iss', $userId, $title, $message);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    legacy_json(['ok' => false, 'error' => 'DB hiba: ' . $err], 500);
}

$newId = (int)$stmt->insert_id;
$stmt->close();

legacy_json(['ok' => true, 'id' => $newId], 201);
