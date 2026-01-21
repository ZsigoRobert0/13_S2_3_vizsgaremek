<?php
$host = "localhost";
$user = "root";
$pass = "mysql";
$dbname = "stockmasters";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
