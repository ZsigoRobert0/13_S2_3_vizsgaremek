<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$username = trim((string)($_POST['username'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$plainPwd = (string)($_POST['password'] ?? '');

if ($username === '' || $email === '' || $plainPwd === '') {
    $_SESSION['error'] = 'Minden mező kitöltése kötelező!';
    legacy_redirect('register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Érvénytelen email cím!';
    legacy_redirect('register.php');
}

$conn = legacy_db();
$conn->begin_transaction();

try {
    // 1) Username / Email ellenőrzés
    $stmt = $conn->prepare("SELECT ID FROM users WHERE Username = ? OR Email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('DB prepare hiba (check user): ' . $conn->error);
    }

    $stmt->bind_param('ss', $username, $email);

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('DB execute hiba (check user).');
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $_SESSION['error'] = 'A felhasználónév vagy email már foglalt!';
        $conn->rollback();
        legacy_redirect('register.php');
    }

    $stmt->close();

    // 2) Jelszó hash
    $hash = password_hash($plainPwd, PASSWORD_DEFAULT);

    // 3) User beszúrás (legacy séma szerint)
    $stmt = $conn->prepare("
        INSERT INTO users
        (Username, Email, PasswordHash, RegistrationDate, DemoBalance, RealBalance)
        VALUES (?, ?, ?, NOW(), 10000, 0)
    ");
    if (!$stmt) {
        throw new Exception('DB prepare hiba (insert user): ' . $conn->error);
    }

    $stmt->bind_param('sss', $username, $email, $hash);

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('DB execute hiba (insert user): ' . $stmt->error);
    }

    $newID = (int)$conn->insert_id;
    $stmt->close();

    if ($newID <= 0) {
        throw new Exception('Nem sikerült új UserID-t generálni.');
    }

    // 4) Default user_settings (ÚJ)
    $stmt2 = $conn->prepare("
        INSERT INTO user_settings
        (user_id, timezone, chart_interval, chart_theme, chart_limit_initial, chart_backfill_chunk,
         news_limit, news_per_symbol_limit, news_portfolio_total_limit, calendar_limit,
         auto_login, receive_notifications, data, created_at, updated_at)
        VALUES
        (?, 'Europe/Budapest', '1m', 'dark', 1500, 1500,
         8, 3, 20, 8,
         0, 1, NULL, NOW(), NOW())
    ");
    if (!$stmt2) {
        throw new Exception('DB prepare hiba (user_settings): ' . $conn->error);
    }

    $stmt2->bind_param('i', $newID);

    if (!$stmt2->execute()) {
        $stmt2->close();
        throw new Exception('DB execute hiba (user_settings): ' . $stmt2->error);
    }

    $stmt2->close();

    $conn->commit();
    legacy_redirect('login.php');

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Hiba történt: ' . $e->getMessage();
    legacy_redirect('register.php');
}