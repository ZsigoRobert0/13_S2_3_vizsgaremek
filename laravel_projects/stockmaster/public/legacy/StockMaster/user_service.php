<?php
require_once __DIR__ . "/_bootstrap.php";

function getUser(int $id): ?array {
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
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}
