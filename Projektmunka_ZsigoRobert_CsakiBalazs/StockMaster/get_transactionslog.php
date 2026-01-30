<?php
require_once "auth.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$userId = (int)($_SESSION["UserID"] ?? $_SESSION["user_id"] ?? 0);
if ($userId <= 0) { echo json_encode([]); exit; }

$limit = (int)($_GET["limit"] ?? 100);
if ($limit <= 0) $limit = 100;
if ($limit > 500) $limit = 500;

$stmt = $conn->prepare("
  SELECT ID, Type, Amount, TransactionTime, Description
  FROM transactionslog
  WHERE UserID = ?
  ORDER BY TransactionTime DESC, ID DESC
  LIMIT ?
");
$stmt->bind_param("ii", $userId, $limit);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) {
  $out[] = [
    "id" => (int)$r["ID"],
    "type" => $r["Type"],                 
    "amount" => (float)$r["Amount"],
    "time" => $r["TransactionTime"],
    "description" => $r["Description"]
  ];
}
$stmt->close();

echo json_encode($out);
