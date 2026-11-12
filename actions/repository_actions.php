<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlashMessage('error', 'Unauthorized action.');
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('error', 'Invalid request or session token.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$user = getLoggedInUser();
$db = getDBConnection();

if ($action === 'create') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $primaryLanguage = sanitizeInput($_POST['primary_language'] ?? 'Plain Text');
    $isPrivate = isset($_POST['is_private']) && $_POST['is_private'] === '1' ? 1 : 0;

    if (empty($name)) {
        setFlashMessage('error', 'Repository name is required.');
        header('Location: ../create-repository.php');
        exit;
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    try {
        $stmt = $db->prepare("INSERT INTO repositories (user_id, name, slug, description, is_private, primary_language) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $name, $slug, $description, $isPrivate, $primaryLanguage]);
        $repoId = $db->lastInsertId();

        // Seed initial files (index.html, style.css, script.js, README.md) if HTML/CSS/JS project
        if ($primaryLanguage === 'HTML/CSS/JS') {
            $htmlContent = "<!DOCTYPE html>\n<html>\n<head>\n  <link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n  <h1>Welcome to " . htmlspecialchars($name) . "</h1>\n  <script src=\"script.js\"></script>\n</body>\n</html>";
            $cssContent = "body {\n  font-family: sans-serif;\n  background: #111;\n  color: #fff;\n  padding: 2rem;\n}";
            $jsContent = "console.log('App initialization complete.');";
            
            $fileStmt = $db->prepare("INSERT INTO repository_files (repository_id, file_path, file_name, content, language) VALUES (?, ?, ?, ?, ?)");
            $fileStmt->execute([$repoId, 'index.html', 'index.html', $htmlContent, 'html']);
            $fileStmt->execute([$repoId, 'style.css', 'style.css', $cssContent, 'css']);
            $fileStmt->execute([$repoId, 'script.js', 'script.js', $jsContent, 'javascript']);
        } else {
            $readmeContent = "# " . $name . "\n\n" . ($description ?: 'Welcome to the project repository.');
            $fileStmt = $db->prepare("INSERT INTO repository_files (repository_id, file_path, file_name, content, language) VALUES (?, ?, ?, ?, ?)");
            $fileStmt->execute([$repoId, 'README.md', 'README.md', $readmeContent, 'markdown']);
        }

        setFlashMessage('success', 'Repository created successfully!');
        header("Location: ../repository.php?id=" . $repoId);
        exit;

    } catch (PDOException $e) {
        setFlashMessage('error', 'A repository with this name already exists in your account.');
        header('Location: ../create-repository.php');
        exit;
    }
    // Star / Unstar Action
if ($action === 'toggle_star') {
    $repoId = (int)($_POST['repository_id'] ?? 0);
    
    $checkStmt = $db->prepare("SELECT id FROM repository_stars WHERE user_id = ? AND repository_id = ?");
    $checkStmt->execute([$user['id'], $repoId]);
    $star = $checkStmt->fetch();

    if ($star) {
        $delStmt = $db->prepare("DELETE FROM repository_stars WHERE id = ?");
        $delStmt->execute([$star['id']]);
        setFlashMessage('success', 'Unstarred repository.');
    } else {
        $addStmt = $db->prepare("INSERT INTO repository_stars (user_id, repository_id) VALUES (?, ?)");
        $addStmt->execute([$user['id'], $repoId]);

        // Send notification to owner
        $repoStmt = $db->prepare("SELECT user_id FROM repositories WHERE id = ?");
        $repoStmt->execute([$repoId]);
        $repo = $repoStmt->fetch();
        if ($repo && $repo['user_id'] != $user['id']) {
            $notifStmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id, message) VALUES (?, ?, 'star', ?, ?)");
            $msg = "{$user['username']} starred your repository.";
            $notifStmt->execute([$repo['user_id'], $user['id'], $repoId, $msg]);
        }
        setFlashMessage('success', 'Starred repository!');
    }
    header("Location: ../repository.php?id=" . $repoId);
    exit;
}

// Follow / Unfollow User Action
if ($action === 'toggle_follow') {
    $followingId = (int)($_POST['target_user_id'] ?? 0);
    $targetUsername = sanitizeInput($_POST['target_username'] ?? '');

    if ($followingId === $user['id']) {
        setFlashMessage('error', 'You cannot follow yourself.');
        header("Location: ../profile.php?username=" . $targetUsername);
        exit;
    }

    $checkStmt = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $checkStmt->execute([$user['id'], $followingId]);
    $follow = $checkStmt->fetch();

    if ($follow) {
        $delStmt = $db->prepare("DELETE FROM followers WHERE id = ?");
        $delStmt->execute([$follow['id']]);
        setFlashMessage('success', "Unfollowed @{$targetUsername}.");
    } else {
        $addStmt = $db->prepare("INSERT INTO followers (follower_id, following_id) VALUES (?, ?)");
        $addStmt->execute([$user['id'], $followingId]);

        // Notification
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id, message) VALUES (?, ?, 'follow', ?, ?)");
        $msg = "{$user['username']} started following you.";
        $notifStmt->execute([$followingId, $user['id'], $user['id'], $msg]);

        setFlashMessage('success', "Now following @{$targetUsername}.");
    }
    header("Location: ../profile.php?username=" . $targetUsername);
    
    //Update Repository Details
    // Update Repository Details
if ($action === 'update_repo') {
    $repoId = (int)($_POST['repository_id'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $isPrivate = isset($_POST['is_private']) && $_POST['is_private'] === '1' ? 1 : 0;

    $stmt = $db->prepare("UPDATE repositories SET description = ?, is_private = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$description, $isPrivate, $repoId, $user['id']]);

    setFlashMessage('success', 'Repository settings saved.');
    header("Location: ../repository.php?id=" . $repoId);
    exit;
}

// Delete Repository
if ($action === 'delete_repo') {
    $repoId = (int)($_POST['repository_id'] ?? 0);

    $stmt = $db->prepare("DELETE FROM repositories WHERE id = ? AND user_id = ?");
    $stmt->execute([$repoId, $user['id']]);

    if ($stmt->rowCount() > 0) {
        setFlashMessage('success', 'Repository permanently deleted.');
        header('Location: ../dashboard.php');
    } else {
        setFlashMessage('error', 'Unable to delete repository.');
        header("Location: ../repository.php?id=" . $repoId);
    }
    exit;
}
}
}