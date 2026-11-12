<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid security token.');
    header('Location: ../login.php');
    exit;
}

$identity = sanitizeInput($_POST['login_identity'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($identity) || empty($password)) {
    setFlashMessage('error', 'Please enter your login credentials.');
    header('Location: ../login.php');
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
$stmt->execute([$identity, $identity]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];

    setFlashMessage('success', 'Logged in successfully.');
    header('Location: ../dashboard.php');
    exit;
} else {
    setFlashMessage('error', 'Invalid login credentials.');
    header('Location: ../login.php');
    exit;
}