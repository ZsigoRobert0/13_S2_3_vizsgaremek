<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$username = trim((string)($_POST['username'] ?? ''));
$password = trim((string)($_POST['password'] ?? ''));

if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Hiányzó felhasználónév vagy jelszó.';
    legacy_redirect('login.php');
}

$conn = legacy_db();

$stmt = $conn->prepare("SELECT ID, PasswordHash FROM users WHERE Username = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['error'] = 'Adatbázis hiba (prepare).';
    legacy_redirect('login.php');
}

$stmt->bind_param('s', $username);

if (!$stmt->execute()) {
    $stmt->close();
    $_SESSION['error'] = 'Adatbázis hiba (execute).';
    legacy_redirect('login.php');
}

$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    $_SESSION['error'] = 'Hibás felhasználónév vagy jelszó.';
    legacy_redirect('login.php');
}

$userId = (int)($row['ID'] ?? 0);
$hash   = (string)($row['PasswordHash'] ?? '');

if ($userId <= 0 || $hash === '') {
    $_SESSION['error'] = 'Hibás felhasználónév vagy jelszó.';
    legacy_redirect('login.php');
}

if (!password_verify($password, $hash)) {
    $_SESSION['error'] = 'Hibás felhasználónév vagy jelszó.';
    legacy_redirect('login.php');
}

$_SESSION['user_id'] = $userId;

session_regenerate_id(true);

legacy_redirect('index.php');
