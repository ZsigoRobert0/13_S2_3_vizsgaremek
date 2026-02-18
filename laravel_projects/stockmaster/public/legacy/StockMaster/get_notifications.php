<?php
require_once "auth.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$userId = (int)($_SESSION["UserID"] ?? $_SESSION["user_id"] ?? 0);
if ($userId <= 0) { echo json_encode(["ok"=>false,"error"=>"no session"]); exit; }

$limit = (int)($_GET["limit"] ?? 15);
if ($limit <= 0) $limit = 15;
if ($limit > 50) $limit = 50;

$stmt = $conn->prepare("
  SELECT ID, Title, Message, CreatedAt, IsRead
  FROM notifications
  WHERE UserID = ?
  ORDER BY IsRead ASC, CreatedAt DESC, ID DESC
  LIMIT ?
");
$stmt->bind_param("ii", $userId, $limit);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$unread = 0;

while ($r = $res->fetch_assoc()) {
  $isRead = (int)$r["IsRead"];
  if ($isRead === 0) $unread++;

  $items[] = [
    "id" => (int)$r["ID"],
    "title" => $r["Title"],
    "message" => $r["Message"],
    "createdAt" => $r["CreatedAt"],
    "isRead" => $isRead
  ];
}
$stmt->close();

echo json_encode(["ok"=>true, "unread"=>$unread, "items"=>$items]);
