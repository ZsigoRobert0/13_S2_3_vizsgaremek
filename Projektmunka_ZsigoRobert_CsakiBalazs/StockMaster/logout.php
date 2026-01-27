<?php
require_once "../StockMaster/session.php";
session_destroy();
header("Location: login.php");
exit;
?>
