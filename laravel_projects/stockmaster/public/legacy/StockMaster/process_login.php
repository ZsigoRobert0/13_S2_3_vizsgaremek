<?php
session_start();
require __DIR__ . 'db.php';

$username = $_POST["username"];
$password = $_POST["password"];

$stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows == 0){
    $_SESSION["error"] = "Hibás felhasználónév!";
    header("Location: login.php");
    exit;
}

$stmt->bind_result($id, $hash);
$stmt->fetch();

if(password_verify($password, $hash)){
    $_SESSION["user_id"] = $id;
    header("Location: index.php");   //Belépés után főoldal
    exit;
} else {
    $_SESSION["error"] = "Hibás jelszó!";
    header("Location: login.php");
    exit;
}
