<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$username = trim((string)($_POST['username'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    $_SESSION['error'] = 'Minden mező kitöltése kötelező.';
    legacy_redirect('register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Érvénytelen email cím.';
    legacy_redirect('register.php');
}

$conn = legacy_db();

// Check existing user
$check = $conn->prepare("SELECT ID FROM users WHERE Username = ? OR Email = ? LIMIT 1");
if (!$check) {
    $_SESSION['error'] = 'Adatbázis hiba.';
    legacy_redirect('register.php');
}

$check->bind_param('ss', $username, $email);

if (!$check->execute()) {
    $check->close();
    $_SESSION['error'] = 'Adatbázis hiba.';
    legacy_redirect('register.php');
}

$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    $_SESSION['error'] = 'A felhasználónév vagy email már létezik!';
    legacy_redirect('register.php');
}

$check->close();

// Password hash
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert
$stmt = $conn->prepare("
    INSERT INTO users (Username, Email, PasswordHash, DemoBalance)
    VALUES (?, ?, ?, 10000)
");
if (!$stmt) {
    $_SESSION['error'] = 'Adatbázis hiba (prepare).';
    legacy_redirect('register.php');
}

$stmt->bind_param('sss', $username, $email, $hash);

if (!$stmt->execute()) {
    $stmt->close();
    $_SESSION['error'] = 'Hiba történt az adatbázis művelet során!';
    legacy_redirect('register.php');
}

$stmt->close();

legacy_redirect('login.php');
