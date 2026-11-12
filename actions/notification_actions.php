<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid token.');
    header('Location: ../notifications.php');
    exit;
}

$action = $_POST['action'] ?? '';
$user = getLoggedInUser();
$db = getDBConnection();

if ($action === 'mark_read') {
    $notifId = (int)($_POST['notification_id'] ?? 0);
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $user['id']]);
    setFlashMessage('success', 'Notification marked as read.');
}

if ($action === 'mark_all_read') {
    $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    setFlashMessage('success', 'All notifications marked as read.');
}

header('Location: ../notifications.php');
exit;