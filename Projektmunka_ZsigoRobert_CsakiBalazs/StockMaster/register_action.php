<?php
require_once "../StockMaster/db.php";
require_once "../StockMaster/session.php";

$username = trim($_POST["username"]);
$email    = trim($_POST["email"]);
$pass     = password_hash($_POST["password"], PASSWORD_BCRYPT);

$stmt = $conn->prepare("SELECT ID FROM Users WHERE Username=? OR Email=?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION["error"] = "A felhasználónév vagy email már foglalt!";
    header("Location: ../StockMaster/register.php");
    exit;
}

$stmt = $conn->prepare("
INSERT INTO Users (Username, Email, PasswordHash, RegistrationDate, DemoBalance, RealBalance)
VALUES (?, ?, ?, NOW(), 10000, 0)
");
$stmt->bind_param("sss", $username, $email, $pass);

if ($stmt->execute()) {
    $newID = $stmt->insert_id;

    $stmt2 = $conn->prepare("
    INSERT INTO UserSettings (UserID, AutoLogin, ReceiveNotifications, PreferredChartInterval, ChartTheme)
    VALUES (?, 0, 1, '1h', 'dark')
    ");
    $stmt2->bind_param("i", $newID);
    $stmt2->execute();

    header("Location: ../public/login.php");
} else {
    $_SESSION["error"] = "Hiba történt!";
    header("Location: ../public/register.php");
}
?>
