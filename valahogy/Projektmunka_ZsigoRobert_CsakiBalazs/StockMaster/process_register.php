<?php
session_start();
require "db.php";

$username = $_POST["username"];
$email    = $_POST["email"];
$pass     = password_hash($_POST["password"], PASSWORD_DEFAULT);

// Check existing user
$check = $conn->prepare("SELECT ID FROM users WHERE Username=? OR Email=?");
$check->bind_param("ss", $username, $email);
$check->execute();
$check->store_result();

if($check->num_rows > 0){
    $_SESSION["error"] = "A felhasználónév vagy email már létezik!";
    header("Location: register.php");
    exit;
}

// Insert - HASZNÁLD A HELYES MEZŐNEVEKET
$stmt = $conn->prepare("INSERT INTO users (Username, Email, PasswordHash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $pass);

if($stmt->execute()){
    header("Location: login.php");
} else {
    $_SESSION["error"] = "Hiba történt az adatbázis művelet során!";
    header("Location: register.php");
}
