<?php
session_start();

// If user is logged in → go to dashboard
if (isset($_SESSION['email'])) {
    header("Location: dashboard.php");
    exit;
}

// Otherwise → go to login page
header("Location: login.php");
exit;
?>
