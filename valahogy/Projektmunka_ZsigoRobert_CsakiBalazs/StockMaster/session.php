<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// automatically logout after 24 hours
$timeout = 60 * 60 * 24;

if (isset($_SESSION["LAST_ACTIVITY"]) &&
   (time() - $_SESSION["LAST_ACTIVITY"] > $timeout)) {
    session_unset();
    session_destroy();
}

$_SESSION["LAST_ACTIVITY"] = time();
?>
