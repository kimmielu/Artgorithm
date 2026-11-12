<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getLoggedInUser();
$db = getDBConnection();
$action = $_POST['action'] ?? '';

if ($action === 'save_file') {
    $fileId = (int)($_POST['file_id'] ?? 0);
    $content = $_POST['content'] ?? '';

    // Verify ownership or collaboration access
    $stmt = $db->prepare("
        SELECT f.id, r.user_id, r.id as repo_id 
        FROM repository_files f 
        JOIN repositories r ON f.repository_id = r.id 
        WHERE f.id = ?
    ");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        exit;
    }

    $isOwner = ($file['user_id'] == $user['id']);
    $collabStmt = $db->prepare("SELECT id FROM collaborations WHERE repository_id = ? AND user_id = ? AND status = 'accepted'");
    $collabStmt->execute([$file['repo_id'], $user['id']]);
    $isContributor = (bool)$collabStmt->fetch();

    if (!$isOwner && !$isContributor) {
        echo json_encode(['success' => false, 'message' => 'Permission denied.']);
        exit;
    }

    $updateStmt = $db->prepare("UPDATE repository_files SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$content, $fileId]);

    // Touch repository updated_at timestamp
    $touchRepo = $db->prepare("UPDATE repositories SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $touchRepo->execute([$file['repo_id']]);

    echo json_encode(['success' => true, 'message' => 'File saved successfully.']);
    exit;
}

if ($action === 'create_file') {
    $repoId = (int)($_POST['repository_id'] ?? 0);
    $fileName = sanitizeInput($_POST['file_name'] ?? '');
    
    if (empty($fileName)) {
        setFlashMessage('error', 'File name cannot be empty.');
        header("Location: ../repository.php?id=" . $repoId);
        exit;
    }

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $langMap = [
        'html' => 'html', 'css' => 'css', 'js' => 'javascript',
        'php' => 'php', 'py' => 'python', 'java' => 'java',
        'cpp' => 'cpp', 'sql' => 'sql', 'md' => 'markdown'
    ];
    $lang = $langMap[strtolower($ext)] ?? 'plaintext';

    $stmt = $db->prepare("INSERT INTO repository_files (repository_id, file_path, file_name, content, language) VALUES (?, ?, ?, '', ?)");
    try {
        $stmt->execute([$repoId, $fileName, $fileName, $lang]);
        setFlashMessage('success', 'File created successfully.');
    } catch (PDOException $e) {
        setFlashMessage('error', 'File already exists in this repository.');
    }
    header("Location: ../repository.php?id=" . $repoId);
    exit;
}