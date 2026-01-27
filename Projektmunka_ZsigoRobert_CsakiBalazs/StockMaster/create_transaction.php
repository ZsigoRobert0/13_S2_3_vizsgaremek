<?php
require_once "auth.php";
require_once "db.php";

header("Content-Type: application/json; charset=utf-8");

$userId = (int)($_SESSION["UserID"] ?? $_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve"]);
  exit;
}

$type = $_POST["type"] ?? "";
$amountRaw = $_POST["amount"] ?? "";
$description = trim($_POST["description"] ?? "");

if (!in_array($type, ["deposit", "withdrawal"], true)) {
  echo json_encode(["ok" => false, "error" => "Érvénytelen típus"]);
  exit;
}

if (!is_numeric($amountRaw)) {
  echo json_encode(["ok" => false, "error" => "Érvénytelen összeg"]);
  exit;
}

$amount = (float)$amountRaw;
if ($amount <= 0) {
  echo json_encode(["ok" => false, "error" => "Érvénytelen összeg"]);
  exit;
}

if ($description === "") {
  $description = ($type === "deposit") ? "Befizetés" : "Kiutalás";
}

$conn->begin_transaction();

try {
  $stmt = $conn->prepare("SELECT DemoBalance FROM users WHERE ID = ? LIMIT 1");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) {
    throw new Exception("Felhasználó nem található");
  }

  $currentBalance = (float)$row["DemoBalance"];

  $newBalance = $currentBalance + ($type === "deposit" ? $amount : -$amount);

  if ($newBalance < 0) {
    throw new Exception("Nincs elég egyenleg");
  }

  $stmt = $conn->prepare("UPDATE users SET DemoBalance = ? WHERE ID = ?");
  $stmt->bind_param("di", $newBalance, $userId);
  $stmt->execute();
  $stmt->close();

  $stmt = $conn->prepare("
    INSERT INTO transactionslog (UserID, Type, Amount, TransactionTime, Description)
    VALUES (?, ?, ?, NOW(), ?)
  ");
  $stmt->bind_param("isds", $userId, $type, $amount, $description);
  $stmt->execute();
  $stmt->close();

  $conn->commit();

  echo json_encode([
    "ok" => true,
    "newBalance" => number_format($newBalance, 2, ".", ""),
  ]);

} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
