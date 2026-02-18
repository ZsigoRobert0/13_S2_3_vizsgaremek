<?php
require_once "auth.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$userId = (int)($_SESSION["UserID"] ?? $_SESSION["user_id"] ?? 0);
if ($userId <= 0) { echo json_encode(["ok"=>false]); exit; }

$stmt = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE UserID = ? AND IsRead = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

echo json_encode(["ok"=>true, "updated"=>$affected]);
