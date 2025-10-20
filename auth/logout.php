<?php
date_default_timezone_set('Asia/Muscat');
include_once "../includes/config.php";
include_once "../logs/log_action.php";

if (isset($_SESSION['user_id'])) {
    logAction($pdo, $_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], $_SESSION['username'], null, null, null);
}

session_unset();
session_destroy();
header("Location: /Healthcare_Dashboard/auth/login.php");
exit;
?>