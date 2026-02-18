<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function getUser(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $conn = legacy_db();

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

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}
