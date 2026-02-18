<?php
require_once "auth.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

// Bejelentkezett user
$userId = (int)($_SESSION["UserID"] ?? $_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve"]);
  exit;
}

// User settings: ReceiveNotifications (ha nálad nincs, akkor ez a blokk kihagyható)
$receive = 1;
$stmt = $conn->prepare("SELECT ReceiveNotifications FROM usersettings WHERE UserID = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  $receive = (int)$row["ReceiveNotifications"];
}
$stmt->close();

if ($receive !== 1) {
  echo json_encode(["ok" => false, "error" => "Értesítések kikapcsolva"]);
  exit;
}

// Input (POST JSON vagy form POST is ok)
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$title = trim(($data["title"] ?? $_POST["title"] ?? "") . "");
$message = trim(($data["message"] ?? $_POST["message"] ?? "") . "");

if ($title === "" || $message === "") {
  echo json_encode(["ok" => false, "error" => "Hiányzó title/message"]);
  exit;
}

// Max hosszak (biztonság)
if (mb_strlen($title) > 255) $title = mb_substr($title, 0, 255);
if (mb_strlen($message) > 2000) $message = mb_substr($message, 0, 2000);

try {
  $stmt = $conn->prepare("
    INSERT INTO notifications (UserID, Title, Message, CreatedAt, IsRead)
    VALUES (?, ?, ?, NOW(), 0)
  ");
  $stmt->bind_param("iss", $userId, $title, $message);
  $stmt->execute();
  $newId = $stmt->insert_id;
  $stmt->close();

  echo json_encode(["ok" => true, "id" => (int)$newId]);

} catch (Exception $e) {
  echo json_encode(["ok" => false, "error" => "DB hiba"]);
}
