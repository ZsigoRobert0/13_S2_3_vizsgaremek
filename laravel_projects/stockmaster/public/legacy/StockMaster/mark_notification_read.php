<?php
require_once __DIR__ . 'auth.php';
require_once __DIR__ .  'db.php';

header("Content-Type: application/json; charset=utf-8");

$userId = (int)($_SESSION["UserID"] ?? $_SESSION["user_id"] ?? 0);
if ($userId <= 0) { echo json_encode(["ok"=>false,"error"=>"no session"]); exit; }

$id = (int)($_POST["id"] ?? 0);
if ($id <= 0) { echo json_encode(["ok"=>false,"error"=>"bad id"]); exit; }

$stmt = $conn->prepare("UPDATE notifications SET IsRead = 1 WHERE ID = ? AND UserID = ?");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

echo json_encode(["ok"=>true, "updated"=>$affected]);
