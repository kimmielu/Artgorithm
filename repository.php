<?php
$pageTitle = "Repository Workspace";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$repoId = (int)($_GET['id'] ?? 0);
$db = getDBConnection();

// Load Repo Info & Owner Details
$stmt = $db->prepare("SELECT r.*, u.username FROM repositories r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$repoId]);
$repo = $stmt->fetch();

if (!$repo) {
    setFlashMessage('error', 'Repository not found.');
    header('Location: explore.php');
    exit;
}

// Access Control for Private Repositories
$user = getLoggedInUser();
$isOwner = $user && ($user['id'] == $repo['user_id']);
$isContributor = false;

if ($user && !$isOwner) {
    $collabCheck = $db->prepare("SELECT id FROM collaborations WHERE repository_id = ? AND user_id = ? AND status = 'accepted'");
    $collabCheck->execute([$repoId, $user['id']]);
    $isContributor = (bool)$collabCheck->fetch();
}

if ($repo['is_private'] && !$isOwner && !$isContributor) {
    setFlashMessage('error', 'This repository is private.');
    header('Location: explore.php');
    exit;
}

// Load Files
$filesStmt = $db->prepare("SELECT * FROM repository_files WHERE repository_id = ? ORDER BY file_name ASC");
$filesStmt->execute([$repoId]);
$files = $filesStmt->fetchAll();

$activeFileId = (int)($_GET['file_id'] ?? ($files[0]['id'] ?? 0));
$activeFile = null;

foreach ($files as $f) {
    if ($f['id'] == $activeFileId) {
        $activeFile = $f;
        break;
    }
}

// Fetch Star Info
$starCountStmt = $db->prepare("SELECT COUNT(*) FROM repository_stars WHERE repository_id = ?");
$starCountStmt->execute([$repoId]);
$starCount = $starCountStmt->fetchColumn();

$hasStarred = false;
if ($user) {
    $userStarCheck = $db->prepare("SELECT id FROM repository_stars WHERE user_id = ? AND repository_id = ?");
    $userStarCheck->execute([$user['id'], $repoId]);
    $hasStarred = (bool)$userStarCheck->fetch();
}

// Fetch Comments
$commentsStmt = $db->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repository_id = ? 
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$repoId]);
$comments = $commentsStmt->fetchAll();

// Fetch Collaborators (for owner management & display)
$collabListStmt = $db->prepare("
    SELECT c.*, u.username 
    FROM collaborations c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repository_id = ?
");
$collabListStmt->execute([$repoId]);
$collaborators = $collabListStmt->fetchAll();

$pageTitle = htmlspecialchars($repo['name']) . " — Repository";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Header Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
        <div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                <a href="profile.php?username=<?php echo htmlspecialchars($repo['username']); ?>">@<?php echo htmlspecialchars($repo['username']); ?></a> /
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 0.2rem;">
                <?php echo htmlspecialchars($repo['name']); ?>
                <span style="font-size: 0.75rem; vertical-align: middle; padding: 0.2rem 0.6rem; border-radius: 4px; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--accent-lime); margin-left: 0.5rem;">
                    <?php echo $repo['is_private'] ? 'Private' : 'Public'; ?>
                </span>
            </h1>
            <?php if (!empty($repo['description'])): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.4rem;">
                    <?php echo htmlspecialchars($repo['description']); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <!-- Star Toggle Button -->
            <?php if ($user): ?>
                <form action="actions/repository_actions.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="toggle_star">
                    <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn <?php echo $hasStarred ? 'btn-lime' : 'btn-secondary'; ?>" style="font-size: 0.9rem;">
                        ⭐ <?php echo $hasStarred ? 'Starred' : 'Star'; ?> (<?php echo $starCount; ?>)
                    </button>
                </form>
            <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.9rem; padding: 0.5rem 0.75rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    ⭐ <?php echo $starCount; ?> stars
                </span>
            <?php endif; ?>

            <!-- Save File Button -->
            <?php if (($isOwner || $isContributor) && $activeFile): ?>
                <button id="saveCodeBtn" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                    💾 Save Changes
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- IDE Workspace Layout -->
    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 1.5rem; min-height: 550px;">
        
        <!-- File Navigation Panel -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                    <span style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted);">Files</span>
                </div>
                
                <?php if (empty($files)): ?>
                    <p style="color: var(--text-dim); font-size: 0.85rem;">No files yet.</p>
                <?php else: ?>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.4rem;">
                        <?php foreach ($files as $file): ?>
                            <li>
                                <a href="repository.php?id=<?php echo $repoId; ?>&file_id=<?php echo $file['id']; ?>" 
                                   style="display: block; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.9rem; font-family: var(--font-mono); color: <?php echo ($activeFile && $activeFile['id'] == $file['id']) ? 'var(--accent-lime)' : 'var(--text-muted)'; ?>; background: <?php echo ($activeFile && $activeFile['id'] == $file['id']) ? 'var(--bg-surface)' : 'transparent'; ?>;">
                                    📄 <?php echo htmlspecialchars($file['file_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ($isOwner || $isContributor): ?>
                <form action="actions/file_actions.php" method="POST" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <input type="hidden" name="action" value="create_file">
                    <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                    <input type="text" name="file_name" placeholder="newfile.js" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 0.5rem;">
                    <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.8rem; padding: 0.4rem;">+ New File</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Editor & Live Preview Panel -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php if ($activeFile): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
                    <!-- Editor Control Header -->
                    <div style="background: var(--bg-surface); padding: 0.6rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
                        <span>Editing: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($activeFile['file_name']); ?></strong></span>
                        <span>Language: <?php echo htmlspecialchars($activeFile['language']); ?></span>
                    </div>

                    <!-- Code Textarea -->
                    <textarea id="codeEditor" style="width: 100%; height: 350px; background: #0b0d10; color: #7dd3fc; font-family: var(--font-mono); font-size: 0.95rem; padding: 1rem; border: none; outline: none; resize: vertical;" <?php echo (!$isOwner && !$isContributor) ? 'readonly' : ''; ?>><?php echo htmlspecialchars($activeFile['content'] ?? ''); ?></textarea>
                </div>

                <!-- Sandboxed Live Preview (for HTML/CSS/JS) -->
                <?php if (in_array(strtolower($activeFile['language']), ['html', 'css', 'javascript'])): ?>
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem;">
                        <h3 style="font-size: 0.9rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem;">Live Preview Frame</h3>
                        <iframe id="previewFrame" sandbox="allow-scripts" style="width: 100%; height: 220px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></iframe>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 4rem; text-align: center; color: var(--text-muted);">
                    No files found in this repository. Create a file using the sidebar to begin coding.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Collaborator Management Panel (Owner Only) -->
    <?php if ($isOwner): ?>
        <div style="margin-top: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Manage Collaborators</h3>
            
            <!-- Invite Form -->
            <form action="actions/collaboration_actions.php" method="POST" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="hidden" name="action" value="invite_contributor">
                <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="text" name="username" required placeholder="Enter developer username..." style="flex: 1; padding: 0.6rem 0.85rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-sm); font-size: 0.9rem;">
                <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;">+ Invite Contributor</button>
            </form>

            <!-- Collaborators List -->
            <?php if (!empty($collaborators)): ?>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($collaborators as $collab): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 0.85rem;">
                            <span>
                                <a href="profile.php?username=<?php echo htmlspecialchars($collab['username']); ?>" style="color: var(--accent-violet); font-weight: 600;">
                                    @<?php echo htmlspecialchars($collab['username']); ?>
                                </a>
                            </span>
                            <span style="color: <?php echo $collab['status'] === 'accepted' ? 'var(--accent-lime)' : 'var(--text-muted)'; ?>;">
                                Status: <strong><?php echo ucfirst($collab['status']); ?></strong>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-dim); font-size: 0.85rem;">No collaborators invited yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Discussion & Comments Block -->
    <div style="margin-top: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <h3 style="font-size: 1.2rem;">Discussion & Feedback (<?php echo count($comments); ?>)</h3>
        </div>

        <!-- Add Comment Form -->
        <?php if ($user): ?>
            <form action="actions/comment_actions.php" method="POST" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <textarea name="comment_text" rows="3" required placeholder="Leave constructive feedback or ask a question..." style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit; margin-bottom: 0.75rem;"></textarea>
                <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;">Post Comment</button>
            </form>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                <a href="login.php">Log in</a> to join the discussion and leave feedback.
            </p>
        <?php endif; ?>

        <!-- Comment List -->
        <?php if (empty($comments)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center;">No comments yet. Be the first to share feedback!</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($comments as $comment): ?>
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.85rem;">
                            <a href="profile.php?username=<?php echo htmlspecialchars($comment['username']); ?>" style="font-weight: 700; color: var(--accent-violet);">
                                @<?php echo htmlspecialchars($comment['username']); ?>
                            </a>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="color: var(--text-dim);"><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></span>
                                <?php if ($user && $user['id'] == $comment['user_id']): ?>
                                    <form action="actions/comment_actions.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.8rem;">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.4;"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('codeEditor');
    const saveBtn = document.getElementById('saveCodeBtn');
    const previewFrame = document.getElementById('previewFrame');

    function updatePreview() {
        if (!previewFrame || !editor) return;
        const frameDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write(editor.value);
        frameDoc.close();
    }

    if (editor && previewFrame) {
        updatePreview();
        editor.addEventListener('input', updatePreview);
    }

    if (saveBtn && editor) {
        saveBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('action', 'save_file');
            formData.append('file_id', '<?php echo $activeFileId; ?>');
            formData.append('content', editor.value);

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const response = await fetch('actions/file_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    saveBtn.textContent = '✓ Saved!';
                    setTimeout(() => { saveBtn.textContent = '💾 Save Changes'; saveBtn.disabled = false; }, 2000);
                } else {
                    alert(res.message || 'Error saving file.');
                    saveBtn.disabled = false;
                }
            } catch (err) {
                alert('Server network error while saving.');
                saveBtn.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?><?php
$pageTitle = "Repository Workspace";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$repoId = (int)($_GET['id'] ?? 0);
$db = getDBConnection();

// Load Repo Info & Owner Details
$stmt = $db->prepare("SELECT r.*, u.username FROM repositories r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$repoId]);
$repo = $stmt->fetch();

if (!$repo) {
    setFlashMessage('error', 'Repository not found.');
    header('Location: explore.php');
    exit;
}

// Access Control for Private Repositories
$user = getLoggedInUser();
$isOwner = $user && ($user['id'] == $repo['user_id']);
$isContributor = false;

if ($user && !$isOwner) {
    $collabCheck = $db->prepare("SELECT id FROM collaborations WHERE repository_id = ? AND user_id = ? AND status = 'accepted'");
    $collabCheck->execute([$repoId, $user['id']]);
    $isContributor = (bool)$collabCheck->fetch();
}

if ($repo['is_private'] && !$isOwner && !$isContributor) {
    setFlashMessage('error', 'This repository is private.');
    header('Location: explore.php');
    exit;
}

// Load Files
$filesStmt = $db->prepare("SELECT * FROM repository_files WHERE repository_id = ? ORDER BY file_name ASC");
$filesStmt->execute([$repoId]);
$files = $filesStmt->fetchAll();

$activeFileId = (int)($_GET['file_id'] ?? ($files[0]['id'] ?? 0));
$activeFile = null;

foreach ($files as $f) {
    if ($f['id'] == $activeFileId) {
        $activeFile = $f;
        break;
    }
}

// Fetch Star Info
$starCountStmt = $db->prepare("SELECT COUNT(*) FROM repository_stars WHERE repository_id = ?");
$starCountStmt->execute([$repoId]);
$starCount = $starCountStmt->fetchColumn();

$hasStarred = false;
if ($user) {
    $userStarCheck = $db->prepare("SELECT id FROM repository_stars WHERE user_id = ? AND repository_id = ?");
    $userStarCheck->execute([$user['id'], $repoId]);
    $hasStarred = (bool)$userStarCheck->fetch();
}

// Fetch Comments
$commentsStmt = $db->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repository_id = ? 
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$repoId]);
$comments = $commentsStmt->fetchAll();

// Fetch Collaborators (for owner management & display)
$collabListStmt = $db->prepare("
    SELECT c.*, u.username 
    FROM collaborations c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repository_id = ?
");
$collabListStmt->execute([$repoId]);
$collaborators = $collabListStmt->fetchAll();

$pageTitle = htmlspecialchars($repo['name']) . " — Repository";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Header Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
        <div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                <a href="profile.php?username=<?php echo htmlspecialchars($repo['username']); ?>">@<?php echo htmlspecialchars($repo['username']); ?></a> /
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 0.2rem;">
                <?php echo htmlspecialchars($repo['name']); ?>
                <span style="font-size: 0.75rem; vertical-align: middle; padding: 0.2rem 0.6rem; border-radius: 4px; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--accent-lime); margin-left: 0.5rem;">
                    <?php echo $repo['is_private'] ? 'Private' : 'Public'; ?>
                </span>
            </h1>
            <?php if (!empty($repo['description'])): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.4rem;">
                    <?php echo htmlspecialchars($repo['description']); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <!-- Star Toggle Button -->
            <?php if ($user): ?>
                <form action="actions/repository_actions.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="toggle_star">
                    <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn <?php echo $hasStarred ? 'btn-lime' : 'btn-secondary'; ?>" style="font-size: 0.9rem;">
                        ⭐ <?php echo $hasStarred ? 'Starred' : 'Star'; ?> (<?php echo $starCount; ?>)
                    </button>
                </form>
            <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.9rem; padding: 0.5rem 0.75rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    ⭐ <?php echo $starCount; ?> stars
                </span>
            <?php endif; ?>

            <!-- Save File Button -->
            <?php if (($isOwner || $isContributor) && $activeFile): ?>
                <button id="saveCodeBtn" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                    💾 Save Changes
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- IDE Workspace Layout -->
    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 1.5rem; min-height: 550px;">
        
        <!-- File Navigation Panel -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                    <span style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted);">Files</span>
                </div>
                
                <?php if (empty($files)): ?>
                    <p style="color: var(--text-dim); font-size: 0.85rem;">No files yet.</p>
                <?php else: ?>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.4rem;">
                        <?php foreach ($files as $file): ?>
                            <li>
                                <a href="repository.php?id=<?php echo $repoId; ?>&file_id=<?php echo $file['id']; ?>" 
                                   style="display: block; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.9rem; font-family: var(--font-mono); color: <?php echo ($activeFile && $activeFile['id'] == $file['id']) ? 'var(--accent-lime)' : 'var(--text-muted)'; ?>; background: <?php echo ($activeFile && $activeFile['id'] == $file['id']) ? 'var(--bg-surface)' : 'transparent'; ?>;">
                                    📄 <?php echo htmlspecialchars($file['file_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ($isOwner || $isContributor): ?>
                <form action="actions/file_actions.php" method="POST" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <input type="hidden" name="action" value="create_file">
                    <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                    <input type="text" name="file_name" placeholder="newfile.js" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 0.5rem;">
                    <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.8rem; padding: 0.4rem;">+ New File</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Editor & Live Preview Panel -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php if ($activeFile): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
                    <!-- Editor Control Header -->
                    <div style="background: var(--bg-surface); padding: 0.6rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
                        <span>Editing: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($activeFile['file_name']); ?></strong></span>
                        <span>Language: <?php echo htmlspecialchars($activeFile['language']); ?></span>
                    </div>

                    <!-- Code Textarea -->
                    <textarea id="codeEditor" style="width: 100%; height: 350px; background: #0b0d10; color: #7dd3fc; font-family: var(--font-mono); font-size: 0.95rem; padding: 1rem; border: none; outline: none; resize: vertical;" <?php echo (!$isOwner && !$isContributor) ? 'readonly' : ''; ?>><?php echo htmlspecialchars($activeFile['content'] ?? ''); ?></textarea>
                </div>

                <!-- Sandboxed Live Preview (for HTML/CSS/JS) -->
                <?php if (in_array(strtolower($activeFile['language']), ['html', 'css', 'javascript'])): ?>
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem;">
                        <h3 style="font-size: 0.9rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem;">Live Preview Frame</h3>
                        <iframe id="previewFrame" sandbox="allow-scripts" style="width: 100%; height: 220px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></iframe>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 4rem; text-align: center; color: var(--text-muted);">
                    No files found in this repository. Create a file using the sidebar to begin coding.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Collaborator Management Panel (Owner Only) -->
    <?php if ($isOwner): ?>
        <div style="margin-top: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Manage Collaborators</h3>
            
            <!-- Invite Form -->
            <form action="actions/collaboration_actions.php" method="POST" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="hidden" name="action" value="invite_contributor">
                <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="text" name="username" required placeholder="Enter developer username..." style="flex: 1; padding: 0.6rem 0.85rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-sm); font-size: 0.9rem;">
                <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;">+ Invite Contributor</button>
            </form>

            <!-- Collaborators List -->
            <?php if (!empty($collaborators)): ?>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($collaborators as $collab): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 0.85rem;">
                            <span>
                                <a href="profile.php?username=<?php echo htmlspecialchars($collab['username']); ?>" style="color: var(--accent-violet); font-weight: 600;">
                                    @<?php echo htmlspecialchars($collab['username']); ?>
                                </a>
                            </span>
                            <span style="color: <?php echo $collab['status'] === 'accepted' ? 'var(--accent-lime)' : 'var(--text-muted)'; ?>;">
                                Status: <strong><?php echo ucfirst($collab['status']); ?></strong>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-dim); font-size: 0.85rem;">No collaborators invited yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Discussion & Comments Block -->
    <div style="margin-top: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <h3 style="font-size: 1.2rem;">Discussion & Feedback (<?php echo count($comments); ?>)</h3>
        </div>

        <!-- Add Comment Form -->
        <?php if ($user): ?>
            <form action="actions/comment_actions.php" method="POST" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <textarea name="comment_text" rows="3" required placeholder="Leave constructive feedback or ask a question..." style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit; margin-bottom: 0.75rem;"></textarea>
                <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;">Post Comment</button>
            </form>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                <a href="login.php">Log in</a> to join the discussion and leave feedback.
            </p>
        <?php endif; ?>

        <!-- Comment List -->
        <?php if (empty($comments)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center;">No comments yet. Be the first to share feedback!</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($comments as $comment): ?>
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.85rem;">
                            <a href="profile.php?username=<?php echo htmlspecialchars($comment['username']); ?>" style="font-weight: 700; color: var(--accent-violet);">
                                @<?php echo htmlspecialchars($comment['username']); ?>
                            </a>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="color: var(--text-dim);"><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></span>
                                <?php if ($user && $user['id'] == $comment['user_id']): ?>
                                    <form action="actions/comment_actions.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.8rem;">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.4;"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('codeEditor');
    const saveBtn = document.getElementById('saveCodeBtn');
    const previewFrame = document.getElementById('previewFrame');

    function updatePreview() {
        if (!previewFrame || !editor) return;
        const frameDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write(editor.value);
        frameDoc.close();
    }

    if (editor && previewFrame) {
        updatePreview();
        editor.addEventListener('input', updatePreview);
    }

    if (saveBtn && editor) {
        saveBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('action', 'save_file');
            formData.append('file_id', '<?php echo $activeFileId; ?>');
            formData.append('content', editor.value);

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const response = await fetch('actions/file_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    saveBtn.textContent = '✓ Saved!';
                    setTimeout(() => { saveBtn.textContent = '💾 Save Changes'; saveBtn.disabled = false; }, 2000);
                } else {
                    alert(res.message || 'Error saving file.');
                    saveBtn.disabled = false;
                }
            } catch (err) {
                alert('Server network error while saving.');
                saveBtn.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?><?php
$pageTitle = "Repository Workspace";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$repoId = (int)($_GET['id'] ?? 0);
$db = getDBConnection();

// Load Repo Info & Owner Details
$stmt = $db->prepare("SELECT r.*, u.username FROM repositories r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$repoId]);
$repo = $stmt->fetch();

if (!$repo) {
    setFlashMessage('error', 'Repository not found.');
    header('Location: explore.php');
    exit;
}

// Access Control for Private Repositories
$user = getLoggedInUser();
$isOwner = $user && ($user['id'] == $repo['user_id']);
$isContributor = false;

if ($user && !$isOwner) {
    $collabCheck = $db->prepare("SELECT id FROM collaborations WHERE repository_id = ? AND user_id = ? AND status = 'accepted'");
    $collabCheck->execute([$repoId, $user['id']]);
    $isContributor = (bool)$collabCheck->fetch();
}

if ($repo['is_private'] && !$isOwner && !$isContributor) {
    setFlashMessage('error', 'This repository is private.');
    header('Location: explore.php');
    exit;
}

// Load Files
$filesStmt = $db->prepare("SELECT * FROM repository_files WHERE repository_id = ? ORDER BY file_name ASC");
$filesStmt->execute([$repoId]);
$files = $filesStmt->fetchAll();

$activeFileId = (int)($_GET['file_id'] ?? ($files[0]['id'] ?? 0));
$activeFile = null;

foreach ($files as $f) {
    if ($f['id'] == $activeFileId) {
        $activeFile = $f;
        break;
    }
}

// Fetch Star Info
$starCountStmt = $db->prepare("SELECT COUNT(*) FROM repository_stars WHERE repository_id = ?");
$starCountStmt->execute([$repoId]);
$starCount = $starCountStmt->fetchColumn();

$hasStarred = false;
if ($user) {
    $userStarCheck = $db->prepare("SELECT id FROM repository_stars WHERE user_id = ? AND repository_id = ?");
    $userStarCheck->execute([$user['id'], $repoId]);
    $hasStarred = (bool)$userStarCheck->fetch();
}

// Fetch Comments
$commentsStmt = $db->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repository_id = ? 
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$repoId]);
$comments = $commentsStmt->fetchAll();

// Fetch Collaborators (for owner management & display)
$collabListStmt = $db->prepare("
    SELECT c.*, u.username 
    FROM collaborations c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.repository_id = ?
");
$collabListStmt->execute([$repoId]);
$collaborators = $collabListStmt->fetchAll();

$pageTitle = htmlspecialchars($repo['name']) . " — Repository";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Header Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; background: var(--bg-card); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
        <div>
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                <a href="profile.php?username=<?php echo htmlspecialchars($repo['username']); ?>">@<?php echo htmlspecialchars($repo['username']); ?></a> /
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 700; color: var(--text-main); margin-top: 0.2rem;">
                <?php echo htmlspecialchars($repo['name']); ?>
                <span style="font-size: 0.75rem; vertical-align: middle; padding: 0.2rem 0.6rem; border-radius: 4px; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--accent-lime); margin-left: 0.5rem;">
                    <?php echo $repo['is_private'] ? 'Private' : 'Public'; ?>
                </span>
            </h1>
            <?php if (!empty($repo['description'])): ?>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.4rem;">
                    <?php echo htmlspecialchars($repo['description']); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <!-- Star Toggle Button -->
            <?php if ($user): ?>
                <form action="actions/repository_actions.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="toggle_star">
                    <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn <?php echo $hasStarred ? 'btn-lime' : 'btn-secondary'; ?>" style="font-size: 0.9rem;">
                        ⭐ <?php echo $hasStarred ? 'Starred' : 'Star'; ?> (<?php echo $starCount; ?>)
                    </button>
                </form>
            <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.9rem; padding: 0.5rem 0.75rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    ⭐ <?php echo $starCount; ?> stars
                </span>
            <?php endif; ?>

            <!-- Save File Button -->
            <?php if (($isOwner || $isContributor) && $activeFile): ?>
                <button id="saveCodeBtn" class="btn btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
                    💾 Save Changes
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- IDE Workspace Layout -->
    <div style="display: grid; grid-template-columns: 240px 1fr; gap: 1.5rem; min-height: 550px;">
        
        <!-- File Navigation Panel -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color);">
                    <span style="font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted);">Files</span>
                </div>
                
                <?php if (empty($files)): ?>
                    <p style="color: var(--text-dim); font-size: 0.85rem;">No files yet.</p>
                <?php else: ?>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.4rem;">
                        <?php foreach ($files as $file): ?>
                            <li>
                                <a href="repository.php?id=<?php echo $repoId; ?>&file_id=<?php echo $file['id']; ?>" 
                                   style="display: block; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.9rem; font-family: var(--font-mono); color: <?php echo ($activeFile && $activeFile['id'] == $file['id']) ? 'var(--accent-lime)' : 'var(--text-muted)'; ?>; background: <?php echo ($activeFile && $activeFile['id'] == $file['id']) ? 'var(--bg-surface)' : 'transparent'; ?>;">
                                    📄 <?php echo htmlspecialchars($file['file_name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ($isOwner || $isContributor): ?>
                <form action="actions/file_actions.php" method="POST" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <input type="hidden" name="action" value="create_file">
                    <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                    <input type="text" name="file_name" placeholder="newfile.js" required style="width: 100%; padding: 0.5rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 0.5rem;">
                    <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.8rem; padding: 0.4rem;">+ New File</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Editor & Live Preview Panel -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php if ($activeFile): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden;">
                    <!-- Editor Control Header -->
                    <div style="background: var(--bg-surface); padding: 0.6rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted);">
                        <span>Editing: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($activeFile['file_name']); ?></strong></span>
                        <span>Language: <?php echo htmlspecialchars($activeFile['language']); ?></span>
                    </div>

                    <!-- Code Textarea -->
                    <textarea id="codeEditor" style="width: 100%; height: 350px; background: #0b0d10; color: #7dd3fc; font-family: var(--font-mono); font-size: 0.95rem; padding: 1rem; border: none; outline: none; resize: vertical;" <?php echo (!$isOwner && !$isContributor) ? 'readonly' : ''; ?>><?php echo htmlspecialchars($activeFile['content'] ?? ''); ?></textarea>
                </div>

                <!-- Sandboxed Live Preview (for HTML/CSS/JS) -->
                <?php if (in_array(strtolower($activeFile['language']), ['html', 'css', 'javascript'])): ?>
                    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem;">
                        <h3 style="font-size: 0.9rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem;">Live Preview Frame</h3>
                        <iframe id="previewFrame" sandbox="allow-scripts" style="width: 100%; height: 220px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-sm);"></iframe>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 4rem; text-align: center; color: var(--text-muted);">
                    No files found in this repository. Create a file using the sidebar to begin coding.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Collaborator Management Panel (Owner Only) -->
    <?php if ($isOwner): ?>
        <div style="margin-top: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Manage Collaborators</h3>
            
            <!-- Invite Form -->
            <form action="actions/collaboration_actions.php" method="POST" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <input type="hidden" name="action" value="invite_contributor">
                <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="text" name="username" required placeholder="Enter developer username..." style="flex: 1; padding: 0.6rem 0.85rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-sm); font-size: 0.9rem;">
                <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;">+ Invite Contributor</button>
            </form>

            <!-- Collaborators List -->
            <?php if (!empty($collaborators)): ?>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($collaborators as $collab): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-size: 0.85rem;">
                            <span>
                                <a href="profile.php?username=<?php echo htmlspecialchars($collab['username']); ?>" style="color: var(--accent-violet); font-weight: 600;">
                                    @<?php echo htmlspecialchars($collab['username']); ?>
                                </a>
                            </span>
                            <span style="color: <?php echo $collab['status'] === 'accepted' ? 'var(--accent-lime)' : 'var(--text-muted)'; ?>;">
                                Status: <strong><?php echo ucfirst($collab['status']); ?></strong>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-dim); font-size: 0.85rem;">No collaborators invited yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Discussion & Comments Block -->
    <div style="margin-top: 2rem; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <h3 style="font-size: 1.2rem;">Discussion & Feedback (<?php echo count($comments); ?>)</h3>
        </div>

        <!-- Add Comment Form -->
        <?php if ($user): ?>
            <form action="actions/comment_actions.php" method="POST" style="margin-bottom: 2rem;">
                <input type="hidden" name="action" value="add_comment">
                <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <textarea name="comment_text" rows="3" required placeholder="Leave constructive feedback or ask a question..." style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit; margin-bottom: 0.75rem;"></textarea>
                <button type="submit" class="btn btn-primary" style="font-size: 0.85rem;">Post Comment</button>
            </form>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                <a href="login.php">Log in</a> to join the discussion and leave feedback.
            </p>
        <?php endif; ?>

        <!-- Comment List -->
        <?php if (empty($comments)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center;">No comments yet. Be the first to share feedback!</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($comments as $comment): ?>
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.85rem;">
                            <a href="profile.php?username=<?php echo htmlspecialchars($comment['username']); ?>" style="font-weight: 700; color: var(--accent-violet);">
                                @<?php echo htmlspecialchars($comment['username']); ?>
                            </a>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span style="color: var(--text-dim);"><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></span>
                                <?php if ($user && $user['id'] == $comment['user_id']): ?>
                                    <form action="actions/comment_actions.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="repository_id" value="<?php echo $repoId; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.8rem;">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.4;"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('codeEditor');
    const saveBtn = document.getElementById('saveCodeBtn');
    const previewFrame = document.getElementById('previewFrame');

    function updatePreview() {
        if (!previewFrame || !editor) return;
        const frameDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write(editor.value);
        frameDoc.close();
    }

    if (editor && previewFrame) {
        updatePreview();
        editor.addEventListener('input', updatePreview);
    }

    if (saveBtn && editor) {
        saveBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('action', 'save_file');
            formData.append('file_id', '<?php echo $activeFileId; ?>');
            formData.append('content', editor.value);

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const response = await fetch('actions/file_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    saveBtn.textContent = '✓ Saved!';
                    setTimeout(() => { saveBtn.textContent = '💾 Save Changes'; saveBtn.disabled = false; }, 2000);
                } else {
                    alert(res.message || 'Error saving file.');
                    saveBtn.disabled = false;
                }
            } catch (err) {
                alert('Server network error while saving.');
                saveBtn.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>