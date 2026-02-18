<?php

if (session_status() === PHP_SESSION_NONE) {
    
    session_start();
}

function currentUserId(): int {
    return (int)($_SESSION["user_id"] ?? $_SESSION["UserID"] ?? 0);
}
