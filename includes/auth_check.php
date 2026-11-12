<?php
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Please log in to access this page.');
    header('Location: login.php');
    exit;
}