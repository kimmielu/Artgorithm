<?php
$pageTitle = "Dashboard";
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$user = getLoggedInUser();$db = getDBConnection();

// Fetch owned repositories
$repoStmt =$db->prepare("SELECT * FROM repositories WHERE user_id = ? ORDER BY updated_at DESC");
$repoStmt->execute([$user['id']]);
$userRepos =$repoStmt->fetchAll();

// Fetch contributed repositories
$collabStmt =$db->prepare("
    SELECT r.*, u.username as owner_name 
    FROM collaborations c 
    JOIN repositories r ON c.repository_id = r.id 
    JOIN users u ON r.user_id = u.id 
    WHERE c.user_id = ? AND c.status = 'accepted'
    ORDER BY r.updated_at DESC
");
$collabStmt->execute([$user['id']]);
$contributedRepos =$collabStmt->fetchAll();
?>

<div class="container" style="padding: 2.5rem 0;">
    <!-- Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700;">Welcome back, <?php echo htmlspecialchars($user['username']); ?></h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Manage your code repositories and collaborative projects.</p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="explore.php" class="btn btn-secondary">Explore Code</a>
            <a href="create-repository.php" class="btn btn-primary">+ New Repository</a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Your Repositories</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent-violet); margin-top: 0.5rem;"><?php echo count($userRepos); ?></div>
        </div>
        <div style="background: var(--bg-card); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Collaborations</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent-lime); margin-top: 0.5rem;"><?php echo count($contributedRepos); ?></div>
        </div>
    </div>

    <!-- User Repositories Grid -->
    <h2 style="font-size: 1.4rem; margin-bottom: 1.25rem; font-weight: 600;">Your Projects</h2>
    <?php if (empty($userRepos)): ?>
        <div style="background: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--radius-md); border: 1px dashed var(--border-color); margin-bottom: 3rem;">
            <p style="color: var(--text-muted); margin-bottom: 1rem;">You haven't created any repositories yet.</p>
            <a href="create-repository.php" class="btn btn-primary">Create Your First Project</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <?php foreach ($userRepos as$repo): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                            <a href="repository.php?id=<?php echo $repo['id']; ?>" style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">
                                <?php echo htmlspecialchars($repo['name']); ?>
                            </a>
                            <span style="font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-muted);">
                                <?php echo $repo['is_private'] ? 'Private' : 'Public'; ?>
                            </span>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem; min-height: 2.7rem; line-height: 1.4;">
                            <?php echo htmlspecialchars($repo['description'] ?? 'No description provided.'); ?>
                        </p>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: var(--text-dim); border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <span>Lang: <strong><?php echo htmlspecialchars($repo['primary_language']); ?></strong></span>
                        <span>Updated: <?php echo date('M j, Y', strtotime($repo['updated_at'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Contributed Projects Grid -->
    <?php if (!empty($contributedRepos)): ?>
        <h2 style="font-size: 1.4rem; margin-bottom: 1.25rem; font-weight: 600;">Projects You Contribute To</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
            <?php foreach ($contributedRepos as$cRepo): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
                    <span style="font-size: 0.8rem; color: var(--accent-violet);">@<?php echo htmlspecialchars($cRepo['owner_name']); ?> /</span>
                    <h3 style="margin-bottom: 0.5rem;">
                        <a href="repository.php?id=<?php echo $cRepo['id']; ?>" style="color: var(--text-main);">
                            <?php echo htmlspecialchars($cRepo['name']); ?>
                        </a>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($cRepo['description'] ?? ''); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>