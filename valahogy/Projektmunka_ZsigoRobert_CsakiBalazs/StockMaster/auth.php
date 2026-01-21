<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/session.php";

function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function getUser($id) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            u.ID,
            u.Username,
            u.Email,
            u.PasswordHash,
            u.RegistrationDate,
            u.IsLoggedIn,
            u.PreferredTheme,
            u.NotificationsEnabled,
            u.DemoBalance,
            u.RealBalance,
            us.ReceiveNotifications
        FROM users u
        LEFT JOIN usersettings us ON us.UserID = u.ID
        WHERE u.ID = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

?>
