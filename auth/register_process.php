<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid security token.');
    header('Location: ../register.php');
    exit;
}

$username = sanitizeInput($_POST['username'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$username || !$email || strlen($password) < 8) {
    setFlashMessage('error', 'Please provide a valid username, email, and a password with at least 8 characters.');
    header('Location: ../register.php');
    exit;
}

$db = getDBConnection();

// Check unique username & email
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    setFlashMessage('error', 'Username or email address is already registered.');
    header('Location: ../register.php');
    exit;
}

// Insert User
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insertStmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
if ($insertStmt->execute([$username, $email, $passwordHash])) {
    $userId = $db->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    setFlashMessage('success', 'Account created successfully! Welcome to The Artgorithm.');
    header('Location: ../dashboard.php');
    exit;
} else {
    setFlashMessage('error', 'An error occurred while creating your account.');
    header('Location: ../register.php');
    exit;
}