<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/session.php";

$username = trim($_POST["username"]);
$password = trim($_POST["password"]);

$stmt = $conn->prepare("SELECT ID, PasswordHash FROM Users WHERE Username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $_SESSION["error"] = "Nincs ilyen felhasználó!";
    header("Location: ../StockMaster/login.php");
    exit;
}

$stmt->bind_result($id, $hash);
$stmt->fetch();

if (!password_verify($password, $hash)) {
    $_SESSION["error"] = "Hibás jelszó!";
    header("Location: ../StockMaster/login.php");
    exit;
}


// success:
$_SESSION["user_id"] = $id;

header("Location: ../StockMaster/index.php");
exit;
?>
