<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'You must be logged in to comment.');
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request session.');
    header('Location: ../explore.php');
    exit;
}

$action = $_POST['action'] ?? '';
$user = getLoggedInUser();
$db = getDBConnection();

if ($action === 'add_comment') {
    $repoId = (int)($_POST['repository_id'] ?? 0);
    $commentText = sanitizeInput($_POST['comment_text'] ?? '');

    if ($repoId <= 0 || empty($commentText)) {
        setFlashMessage('error', 'Comment content cannot be empty.');
        header("Location: ../repository.php?id=" . $repoId);
        exit;
    }

    // Verify repo visibility
    $repoStmt = $db->prepare("SELECT user_id, is_private FROM repositories WHERE id = ?");
    $repoStmt->execute([$repoId]);
    $repo = $repoStmt->fetch();

    if (!$repo || ($repo['is_private'] && $repo['user_id'] != $user['id'])) {
        setFlashMessage('error', 'Permission denied.');
        header("Location: ../explore.php");
        exit;
    }

    $stmt = $db->prepare("INSERT INTO comments (repository_id, user_id, comment_text) VALUES (?, ?, ?)");
    $stmt->execute([$repoId, $user['id'], $commentText]);

    // Send Notification to Repo Owner (if owner is not the commenter)
    if ($repo['user_id'] != $user['id']) {
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id, message) VALUES (?, ?, 'comment', ?, ?)");
        $msg = "{$user['username']} commented on your repository.";
        $notifStmt->execute([$repo['user_id'], $user['id'], $repoId, $msg]);
    }

    setFlashMessage('success', 'Comment posted successfully.');
    header("Location: ../repository.php?id=" . $repoId);
    exit;
}

if ($action === 'delete_comment') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $repoId = (int)($_POST['repository_id'] ?? 0);

    $stmt = $db->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if ($comment && $comment['user_id'] == $user['id']) {
        $delStmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $delStmt->execute([$commentId]);
        setFlashMessage('success', 'Comment deleted.');
    } else {
        setFlashMessage('error', 'Cannot delete this comment.');
    }

    header("Location: ../repository.php?id=" . $repoId);
    exit;
}