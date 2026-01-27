<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once "auth.php";
requireLogin();
require_once "db.php";

$userId = (int)($_SESSION["user_id"] ?? 0);
if ($userId <= 0) {
  http_response_code(401);
  echo json_encode(["ok" => false, "error" => "Nincs bejelentkezve."]);
  exit;
}

session_write_close();

$raw = file_get_contents("php://input");
$payload = json_decode($raw ?: "{}", true);
if (!is_array($payload)) $payload = [];

$autoLogin = (int)($payload["AutoLogin"] ?? 0);
$receiveNotifications = (int)($payload["ReceiveNotifications"] ?? 1);
$chartTheme = (string)($payload["PreferredChartTheme"] ?? "dark");
$chartInterval = (string)($payload["PreferredChartInterval"] ?? "1m");

$newsLimit = (int)($payload["NewsLimit"] ?? 8);
$newsPerSymbolLimit = (int)($payload["NewsPerSymbolLimit"] ?? 3);
$newsPortfolioTotalLimit = (int)($payload["NewsPortfolioTotalLimit"] ?? 20);
$calendarLimit = (int)($payload["CalendarLimit"] ?? 8);

if ($newsLimit < 3) $newsLimit = 3;
if ($newsLimit > 30) $newsLimit = 30;

if ($newsPerSymbolLimit < 1) $newsPerSymbolLimit = 1;
if ($newsPerSymbolLimit > 10) $newsPerSymbolLimit = 10;

if ($newsPortfolioTotalLimit < 5) $newsPortfolioTotalLimit = 5;
if ($newsPortfolioTotalLimit > 60) $newsPortfolioTotalLimit = 60;

if ($calendarLimit < 3) $calendarLimit = 3;
if ($calendarLimit > 60) $calendarLimit = 60;

$exists = false;
$chk = $conn->prepare("SELECT 1 FROM usersettings WHERE UserID = ? LIMIT 1");
$chk->bind_param("i", $userId);
$chk->execute();
$chk->store_result();
$exists = ($chk->num_rows > 0);
$chk->close();

if ($exists) {
  $stmt = @$conn->prepare("
    UPDATE usersettings
    SET AutoLogin = ?, ReceiveNotifications = ?, PreferredChartTheme = ?, PreferredChartInterval = ?,
        NewsLimit = ?, NewsPerSymbolLimit = ?, NewsPortfolioTotalLimit = ?, CalendarLimit = ?
    WHERE UserID = ?
  ");

  if ($stmt) {
    $stmt->bind_param(
      "iissiiiii",
      $autoLogin, $receiveNotifications, $chartTheme, $chartInterval,
      $newsLimit, $newsPerSymbolLimit, $newsPortfolioTotalLimit, $calendarLimit,
      $userId
    );
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(["ok" => (bool)$ok]);
    exit;
  }

  $stmt2 = $conn->prepare("
    UPDATE usersettings
    SET AutoLogin = ?, ReceiveNotifications = ?, PreferredChartTheme = ?, PreferredChartInterval = ?
    WHERE UserID = ?
  ");
  $stmt2->bind_param("iissi", $autoLogin, $receiveNotifications, $chartTheme, $chartInterval, $userId);
  $ok2 = $stmt2->execute();
  $stmt2->close();

  echo json_encode([
    "ok" => (bool)$ok2,
    "warning" => "A limit mezők nem mentődtek (hiányoznak az oszlopok). Futtasd le az ALTER TABLE SQL-t."
  ]);
  exit;

} else {
  $stmt = @$conn->prepare("
    INSERT INTO usersettings
      (UserID, AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval,
       NewsLimit, NewsPerSymbolLimit, NewsPortfolioTotalLimit, CalendarLimit)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  if ($stmt) {
    $stmt->bind_param(
      "iiissi iii",
      $userId, $autoLogin, $receiveNotifications, $chartTheme, $chartInterval,
      $newsLimit, $newsPerSymbolLimit, $newsPortfolioTotalLimit, $calendarLimit
    );
  }
}

$stmt = @$conn->prepare("
  INSERT INTO usersettings
    (UserID, AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval,
     NewsLimit, NewsPerSymbolLimit, NewsPortfolioTotalLimit, CalendarLimit)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if ($stmt) {
  $stmt->bind_param(
    "iiissiiii",
    $userId, $autoLogin, $receiveNotifications, $chartTheme, $chartInterval,
    $newsLimit, $newsPerSymbolLimit, $newsPortfolioTotalLimit, $calendarLimit
  );
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(["ok" => (bool)$ok]);
  exit;
}

$stmt2 = $conn->prepare("
  INSERT INTO usersettings (UserID, AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval)
  VALUES (?, ?, ?, ?, ?)
");
$stmt2->bind_param("iiiss", $userId, $autoLogin, $receiveNotifications, $chartTheme, $chartInterval);
$ok2 = $stmt2->execute();
$stmt2->close();

echo json_encode([
  "ok" => (bool)$ok2,
  "warning" => "A limit mezők nem mentődtek (hiányoznak az oszlopok). Futtasd le az ALTER TABLE SQL-t."
]);
