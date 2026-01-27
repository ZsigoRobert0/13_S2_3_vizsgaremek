<?php
require_once __DIR__ . "/session.php";

function isLoggedIn(): bool {
  return isset($_SESSION["user_id"]) && (int)$_SESSION["user_id"] > 0;
}

function requireLogin(): void {
  if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
  }
}
