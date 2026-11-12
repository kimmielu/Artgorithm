<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Unauthorized action.');
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request token.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$user = getLoggedInUser();
$db = getDBConnection();

// Invite a collaborator to a repository
if ($action === 'invite_contributor') {
    $repoId = (int)($_POST['repository_id'] ?? 0);
    $inviteeUsername = sanitizeInput($_POST['username'] ?? '');

    // Check if the current user is the owner of the repo
    $repoStmt = $db->prepare("SELECT user_id, name FROM repositories WHERE id = ?");
    $repoStmt->execute([$repoId]);
    $repo = $repoStmt->fetch();

    if (!$repo || $repo['user_id'] != $user['id']) {
        setFlashMessage('error', 'Only repository owners can invite contributors.');
        header("Location: ../repository.php?id=" . $repoId);
        exit;
    }

    // Find the invited user
    $userStmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $userStmt->execute([$inviteeUsername]);
    $invitee = $userStmt->fetch();

    if (!$invitee) {
        setFlashMessage('error', 'User @' . $inviteeUsername . ' not found.');
        header("Location: ../repository.php?id=" . $repoId);
        exit;
    }

    if ($invitee['id'] == $user['id']) {
        setFlashMessage('error', 'You are already the owner of this repository.');
        header("Location: ../repository.php?id=" . $repoId);
        exit;
    }

    try {
        $collabStmt = $db->prepare("INSERT INTO collaborations (repository_id, user_id, status, role) VALUES (?, ?, 'pending', 'contributor')");
        $collabStmt->execute([$repoId, $invitee['id']]);

        // Send Notification to Invitee
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id, message) VALUES (?, ?, 'invitation', ?, ?)");
        $msg = "{$user['username']} invited you to collaborate on '{$repo['name']}'.";
        $notifStmt->execute([$invitee['id'], $user['id'], $repoId, $msg]);

        setFlashMessage('success', "Invitation sent to @{$inviteeUsername}.");
    } catch (PDOException $e) {
        setFlashMessage('error', 'An invitation has already been sent to this user.');
    }

    header("Location: ../repository.php?id=" . $repoId);
    exit;
}

// Respond to an invitation (Accept or Decline)
if ($action === 'respond_invitation') {
    $collabId = (int)($_POST['collaboration_id'] ?? 0);
    $responseStatus = ($_POST['status'] ?? '') === 'accepted' ? 'accepted' : 'declined';

    $collabStmt = $db->prepare("
        SELECT c.*, r.name as repo_name, r.user_id as owner_id 
        FROM collaborations c 
        JOIN repositories r ON c.repository_id = r.id 
        WHERE c.id = ? AND c.user_id = ?
    ");
    $collabStmt->execute([$collabId, $user['id']]);
    $collab = $collabStmt->fetch();

    if ($collab) {
        $updateStmt = $db->prepare("UPDATE collaborations SET status = ? WHERE id = ?");
        $updateStmt->execute([$responseStatus, $collabId]);

        // Send notification back to repo owner
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id, message) VALUES (?, ?, 'invite_response', ?, ?)");
        $msg = "{$user['username']} {$responseStatus} your collaboration invitation for '{$collab['repo_name']}'.";
        $notifStmt->execute([$collab['owner_id'], $user['id'], $collab['repository_id'], $msg]);

        setFlashMessage('success', "Invitation {$responseStatus}.");
    } else {
        setFlashMessage('error', 'Invitation not found or unauthorized.');
    }

    header('Location: ../notifications.php');
    exit;
}