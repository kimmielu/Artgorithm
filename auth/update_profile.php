<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid security token.');
    header('Location: ../settings.php');
    exit;
}

$user = getLoggedInUser();
$db = getDBConnection();
$action = $_POST['action'] ?? '';

if ($action === 'update_info') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $bio = sanitizeInput($_POST['bio'] ?? '');

    if (!$email) {
        setFlashMessage('error', 'Please enter a valid email address.');
        header('Location: ../settings.php');
        exit;
    }

    // Ensure email isn't taken by another user
    $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->execute([$email, $user['id']]);
    if ($check->fetch()) {
        setFlashMessage('error', 'That email address is already in use.');
        header('Location: ../settings.php');
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET email = ?, bio = ? WHERE id = ?");
    $stmt->execute([$email, $bio, $user['id']]);
    $_SESSION['email'] = $email;

    setFlashMessage('success', 'Profile updated successfully.');
    header('Location: ../settings.php');
    exit;
}

if ($action === 'update_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (strlen($newPassword) < 8) {
        setFlashMessage('error', 'New password must be at least 8 characters.');
        header('Location: ../settings.php');
        exit;
    }

    $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        setFlashMessage('error', 'Current password is incorrect.');
        header('Location: ../settings.php');
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $update->execute([$newHash, $user['id']]);

    setFlashMessage('success', 'Password updated successfully.');
    header('Location: ../settings.php');
    exit;
}