<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function getUser(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $conn = legacy_db();

    // FONTOS: user_settings mező neve: user_id (nem UserID)
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

            -- user_settings mezők (új séma)
            us.timezone,
            us.chart_interval,
            us.chart_theme,
            us.chart_limit_initial,
            us.chart_backfill_chunk,
            us.news_limit,
            us.news_per_symbol_limit,
            us.news_portfolio_total_limit,
            us.calendar_limit,
            us.auto_login,
            us.receive_notifications,
            us.data

        FROM users u
        LEFT JOIN user_settings us ON us.user_id = u.ID
        WHERE u.ID = ?
        LIMIT 1
    ");

    if (!$stmt) {
        // debugolható hibaüzenet (ne legyen néma null)
        throw new RuntimeException('DB prepare error (getUser): ' . $conn->error);
    }

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new RuntimeException('DB execute error (getUser): ' . $err);
    }

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    // Ha nincs még settings sora (régi user / seed hiba), csináljunk defaultot automatikusan
    if ($row['chart_interval'] === null) {
        ensureUserSettingsRow($id);
        // újra lekérjük, hogy már legyen joinolt adat
        return getUser($id);
    }

    return $row;
}

/**
 * Biztosítja, hogy legyen user_settings rekord a userhez.
 */
function ensureUserSettingsRow(int $userId): void
{
    $conn = legacy_db();

    $chk = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ? LIMIT 1");
    if (!$chk) return;
    $chk->bind_param('i', $userId);
    if (!$chk->execute()) { $chk->close(); return; }

    $chk->store_result();
    if ($chk->num_rows > 0) {
        $chk->close();
        return;
    }
    $chk->close();

    $ins = $conn->prepare("
        INSERT INTO user_settings
        (user_id, timezone, chart_interval, chart_theme, chart_limit_initial, chart_backfill_chunk,
         news_limit, news_per_symbol_limit, news_portfolio_total_limit, calendar_limit,
         auto_login, receive_notifications, data, created_at, updated_at)
        VALUES
        (?, 'Europe/Budapest', '1m', 'dark', 1500, 1500,
         8, 3, 20, 8,
         0, 1, NULL, NOW(), NOW())
    ");
    if (!$ins) return;
    $ins->bind_param('i', $userId);
    $ins->execute();
    $ins->close();
}