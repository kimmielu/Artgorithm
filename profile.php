<?php
$pageTitle = "Developer Profile";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$username = sanitizeInput($_GET['username'] ?? '');
$currentUser = getLoggedInUser();

if (empty($username) && $currentUser) {
    $username = $currentUser['username'];
}

$db = getDBConnection();
$userStmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$userStmt->execute([$username]);
$profileUser = $userStmt->fetch();

if (!$profileUser) {
    die("Developer profile not found.");
}

// Fetch stats
$followersCount = $db->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
$followersCount->execute([$profileUser['id']]);
$numFollowers = $followersCount->fetchColumn();

$followingCount = $db->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
$followingCount->execute([$profileUser['id']]);
$numFollowing = $followingCount->fetchColumn();

// Is current logged in user following this profile?
$isFollowing = false;
if ($currentUser && $currentUser['id'] != $profileUser['id']) {
    $followCheck = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $followCheck->execute([$currentUser['id'], $profileUser['id']]);
    $isFollowing = (bool)$followCheck->fetch();
}

// Fetch public repositories of this developer
$reposStmt = $db->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) FROM repository_stars WHERE repository_id = r.id) AS star_count
    FROM repositories r 
    WHERE r.user_id = ? AND (r.is_private = 0 OR r.user_id = ?)
    ORDER BY r.updated_at DESC
");
$reposStmt->execute([$profileUser['id'], $currentUser['id'] ?? 0]);
$userRepos = $reposStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 3rem 0;">
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2.5rem;">
        
        <!-- Left Sidebar: Profile Details -->
        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; text-align: center;">
                <div style="width: 90px; height: 90px; border-radius: 50%; background: var(--accent-violet); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; color: #fff; margin: 0 auto 1.25rem;">
                    <?php echo strtoupper(substr($profileUser['username'], 0, 1)); ?>
                </div>
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($profileUser['username']); ?></h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; margin-bottom: 1.25rem;"><?php echo htmlspecialchars($profileUser['email']); ?></p>

                <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 1.5rem; text-align: left; background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <?php echo htmlspecialchars($profileUser['bio'] ?? 'No bio provided yet.'); ?>
                </p>

                <div style="display: flex; justify-content: space-around; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem;">
                    <div>
                        <strong style="display: block; font-size: 1.2rem; color: var(--accent-lime);"><?php echo $numFollowers; ?></strong> Followers
                    </div>
                    <div>
                        <strong style="display: block; font-size: 1.2rem; color: var(--accent-violet);"><?php echo $numFollowing; ?></strong> Following
                    </div>
                </div>

                <?php if ($currentUser && $currentUser['id'] !== $profileUser['id']): ?>
                    <form action="actions/repository_actions.php" method="POST">
                        <input type="hidden" name="action" value="toggle_follow">
                        <input type="hidden" name="target_user_id" value="<?php echo $profileUser['id']; ?>">
                        <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($profileUser['username']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn <?php echo $isFollowing ? 'btn-secondary' : 'btn-primary'; ?>" style="width: 100%;">
                            <?php echo $isFollowing ? 'Unfollow' : '+ Follow Developer'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Main Content: Public Repositories -->
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Repositories (<?php echo count($userRepos); ?>)</h2>
            <?php if (empty($userRepos)): ?>
                <div style="background: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
                    <p style="color: var(--text-muted);">This user has not published any repositories yet.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($userRepos as $repo): ?>
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem;">
                                    <a href="repository.php?id=<?php echo $repo['id']; ?>" style="color: var(--text-main);">
                                        <?php echo htmlspecialchars($repo['name']); ?>
                                    </a>
                                </h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($repo['description'] ?? ''); ?>
                                </p>
                                <span style="font-size: 0.8rem; color: var(--accent-lime);">
                                    <?php echo htmlspecialchars($repo['primary_language']); ?>
                                </span>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">
                                ⭐ <?php echo $repo['star_count']; ?> stars
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?><?php
$pageTitle = "Developer Profile";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$username = sanitizeInput($_GET['username'] ?? '');
$currentUser = getLoggedInUser();

if (empty($username) && $currentUser) {
    $username = $currentUser['username'];
}

$db = getDBConnection();
$userStmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$userStmt->execute([$username]);
$profileUser = $userStmt->fetch();

if (!$profileUser) {
    die("Developer profile not found.");
}

// Fetch stats
$followersCount = $db->prepare("SELECT COUNT(*) FROM followers WHERE following_id = ?");
$followersCount->execute([$profileUser['id']]);
$numFollowers = $followersCount->fetchColumn();

$followingCount = $db->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
$followingCount->execute([$profileUser['id']]);
$numFollowing = $followingCount->fetchColumn();

// Is current logged in user following this profile?
$isFollowing = false;
if ($currentUser && $currentUser['id'] != $profileUser['id']) {
    $followCheck = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
    $followCheck->execute([$currentUser['id'], $profileUser['id']]);
    $isFollowing = (bool)$followCheck->fetch();
}

// Fetch public repositories of this developer
$reposStmt = $db->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) FROM repository_stars WHERE repository_id = r.id) AS star_count
    FROM repositories r 
    WHERE r.user_id = ? AND (r.is_private = 0 OR r.user_id = ?)
    ORDER BY r.updated_at DESC
");
$reposStmt->execute([$profileUser['id'], $currentUser['id'] ?? 0]);
$userRepos = $reposStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 3rem 0;">
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2.5rem;">
        
        <!-- Left Sidebar: Profile Details -->
        <div>
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; text-align: center;">
                <div style="width: 90px; height: 90px; border-radius: 50%; background: var(--accent-violet); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; color: #fff; margin: 0 auto 1.25rem;">
                    <?php echo strtoupper(substr($profileUser['username'], 0, 1)); ?>
                </div>
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($profileUser['username']); ?></h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem; margin-bottom: 1.25rem;"><?php echo htmlspecialchars($profileUser['email']); ?></p>

                <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 1.5rem; text-align: left; background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <?php echo htmlspecialchars($profileUser['bio'] ?? 'No bio provided yet.'); ?>
                </p>

                <div style="display: flex; justify-content: space-around; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem;">
                    <div>
                        <strong style="display: block; font-size: 1.2rem; color: var(--accent-lime);"><?php echo $numFollowers; ?></strong> Followers
                    </div>
                    <div>
                        <strong style="display: block; font-size: 1.2rem; color: var(--accent-violet);"><?php echo $numFollowing; ?></strong> Following
                    </div>
                </div>

                <?php if ($currentUser && $currentUser['id'] !== $profileUser['id']): ?>
                    <form action="actions/repository_actions.php" method="POST">
                        <input type="hidden" name="action" value="toggle_follow">
                        <input type="hidden" name="target_user_id" value="<?php echo $profileUser['id']; ?>">
                        <input type="hidden" name="target_username" value="<?php echo htmlspecialchars($profileUser['username']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn <?php echo $isFollowing ? 'btn-secondary' : 'btn-primary'; ?>" style="width: 100%;">
                            <?php echo $isFollowing ? 'Unfollow' : '+ Follow Developer'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Main Content: Public Repositories -->
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Repositories (<?php echo count($userRepos); ?>)</h2>
            <?php if (empty($userRepos)): ?>
                <div style="background: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
                    <p style="color: var(--text-muted);">This user has not published any repositories yet.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($userRepos as $repo): ?>
                        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem;">
                                    <a href="repository.php?id=<?php echo $repo['id']; ?>" style="color: var(--text-main);">
                                        <?php echo htmlspecialchars($repo['name']); ?>
                                    </a>
                                </h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($repo['description'] ?? ''); ?>
                                </p>
                                <span style="font-size: 0.8rem; color: var(--accent-lime);">
                                    <?php echo htmlspecialchars($repo['primary_language']); ?>
                                </span>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-muted);">
                                ⭐ <?php echo $repo['star_count']; ?> stars
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>