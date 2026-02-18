<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isLoggedIn()) {
    legacy_json(['ok' => false, 'error' => 'Nincs bejelentkezve.'], 401);
}

$conn   = legacy_db();
$userId = currentUserId();

session_write_close();

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);
if (!is_array($payload)) {
    $payload = [];
}

// ---- Base settings ----
$autoLogin           = (int)($payload['AutoLogin'] ?? 0);
$receiveNotifications = (int)($payload['ReceiveNotifications'] ?? 1);

// Frontend név keveredés miatt: PreferredChartTheme vagy ChartTheme
$chartTheme    = (string)($payload['PreferredChartTheme'] ?? $payload['ChartTheme'] ?? 'dark');
$chartInterval = (string)($payload['PreferredChartInterval'] ?? '1m');

// ---- Limits (new fields) ----
$newsLimit               = (int)($payload['NewsLimit'] ?? 8);
$newsPerSymbolLimit      = (int)($payload['NewsPerSymbolLimit'] ?? 3);
$newsPortfolioTotalLimit = (int)($payload['NewsPortfolioTotalLimit'] ?? 20);
$calendarLimit           = (int)($payload['CalendarLimit'] ?? 8);

// ---- Clamp ----
$autoLogin = $autoLogin ? 1 : 0;
$receiveNotifications = $receiveNotifications ? 1 : 0;

$chartTheme = strtolower(trim($chartTheme));
if (!in_array($chartTheme, ['dark', 'light'], true)) {
    $chartTheme = 'dark';
}

$chartInterval = trim($chartInterval);
if ($chartInterval === '') {
    $chartInterval = '1m';
}

if ($newsLimit < 3) $newsLimit = 3;
if ($newsLimit > 30) $newsLimit = 30;

if ($newsPerSymbolLimit < 1) $newsPerSymbolLimit = 1;
if ($newsPerSymbolLimit > 10) $newsPerSymbolLimit = 10;

if ($newsPortfolioTotalLimit < 5) $newsPortfolioTotalLimit = 5;
if ($newsPortfolioTotalLimit > 60) $newsPortfolioTotalLimit = 60;

if ($calendarLimit < 3) $calendarLimit = 3;
if ($calendarLimit > 60) $calendarLimit = 60;

$chk = $conn->prepare('SELECT 1 FROM usersettings WHERE UserID = ? LIMIT 1');
if (!$chk) {
    legacy_json(['ok' => false, 'error' => 'DB prepare hiba (exists).'], 500);
}
$chk->bind_param('i', $userId);
if (!$chk->execute()) {
    $chk->close();
    legacy_json(['ok' => false, 'error' => 'DB execute hiba (exists).'], 500);
}
$chk->store_result();
$exists = ($chk->num_rows > 0);
$chk->close();

$warning = null;

if ($exists) {
    // UPDATE (extended)
    $stmt = @$conn->prepare("
        UPDATE usersettings
        SET AutoLogin = ?,
            ReceiveNotifications = ?,
            PreferredChartTheme = ?,
            PreferredChartInterval = ?,
            NewsLimit = ?,
            NewsPerSymbolLimit = ?,
            NewsPortfolioTotalLimit = ?,
            CalendarLimit = ?
        WHERE UserID = ?
    ");

    if ($stmt) {
        $stmt->bind_param(
            'iissiiiii',
            $autoLogin,
            $receiveNotifications,
            $chartTheme,
            $chartInterval,
            $newsLimit,
            $newsPerSymbolLimit,
            $newsPortfolioTotalLimit,
            $calendarLimit,
            $userId
        );

        $ok = $stmt->execute();
        $stmt->close();

        legacy_json(['ok' => (bool)$ok]);
    }

    // UPDATE fallback (legacy fields only)
    $stmt2 = $conn->prepare("
        UPDATE usersettings
        SET AutoLogin = ?,
            ReceiveNotifications = ?,
            PreferredChartTheme = ?,
            PreferredChartInterval = ?
        WHERE UserID = ?
    ");
    if (!$stmt2) {
        legacy_json(['ok' => false, 'error' => 'DB prepare hiba (fallback update).'], 500);
    }

    $stmt2->bind_param('iissi', $autoLogin, $receiveNotifications, $chartTheme, $chartInterval, $userId);
    $ok2 = $stmt2->execute();
    $stmt2->close();

    $warning = 'A limit mezők nem mentődtek (hiányoznak az oszlopok). Futtasd le az ALTER TABLE SQL-t.';
    legacy_json(['ok' => (bool)$ok2, 'warning' => $warning]);
}

// INSERT (extended)
$stmt = @$conn->prepare("
    INSERT INTO usersettings
        (UserID, AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval,
         NewsLimit, NewsPerSymbolLimit, NewsPortfolioTotalLimit, CalendarLimit)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if ($stmt) {
    $stmt->bind_param(
        'iiissiiii',
        $userId,
        $autoLogin,
        $receiveNotifications,
        $chartTheme,
        $chartInterval,
        $newsLimit,
        $newsPerSymbolLimit,
        $newsPortfolioTotalLimit,
        $calendarLimit
    );

    $ok = $stmt->execute();
    $stmt->close();

    legacy_json(['ok' => (bool)$ok]);
}

$stmt2 = $conn->prepare("
    INSERT INTO usersettings
        (UserID, AutoLogin, ReceiveNotifications, PreferredChartTheme, PreferredChartInterval)
    VALUES (?, ?, ?, ?, ?)
");
if (!$stmt2) {
    legacy_json(['ok' => false, 'error' => 'DB prepare hiba (fallback insert).'], 500);
}

$stmt2->bind_param('iiiss', $userId, $autoLogin, $receiveNotifications, $chartTheme, $chartInterval);
$ok2 = $stmt2->execute();
$stmt2->close();

$warning = 'A limit mezők nem mentődtek (hiányoznak az oszlopok). Futtasd le az ALTER TABLE SQL-t.';
legacy_json(['ok' => (bool)$ok2, 'warning' => $warning]);
