<?php
$pageTitle = "Explore Repositories";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDBConnection();
$search = sanitizeInput($_GET['q'] ?? '');
$langFilter = sanitizeInput($_GET['lang'] ?? '');
$sort = sanitizeInput($_GET['sort'] ?? 'updated');

// Base query for public repositories
$sql = "
    SELECT r.*, u.username, u.avatar_url,
           (SELECT COUNT(*) FROM repository_stars WHERE repository_id = r.id) AS star_count,
           (SELECT COUNT(*) FROM comments WHERE repository_id = r.id) AS comment_count
    FROM repositories r
    JOIN users u ON r.user_id = u.id
    WHERE r.is_private = 0
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (r.name LIKE ? OR r.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($langFilter)) {
    $sql .= " AND r.primary_language = ?";
    $params[] = $langFilter;
}

if ($sort === 'stars') {
    $sql .= " ORDER BY star_count DESC, r.updated_at DESC";
} else {
    $sql .= " ORDER BY r.updated_at DESC";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$repositories = $stmt->fetchAll();

// Get list of distinct languages for filter dropdown
$langStmt = $db->query("SELECT DISTINCT primary_language FROM repositories WHERE is_private = 0 AND primary_language IS NOT NULL AND primary_language != '' ORDER BY primary_language ASC");
$languages = $langStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0;">
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; font-weight: 800;">Explore Community Code</h1>
        <p style="color: var(--text-muted);">Discover open-source repositories, learn from other developers, and star projects.</p>
    </div>

    <!-- Search & Filter Controls -->
    <form method="GET" action="explore.php" style="background: var(--bg-card); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; margin-bottom: 2.5rem;">
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search projects by name or description..." style="width: 100%; padding: 0.65rem 1rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-size: 0.95rem;">
        </div>

        <div>
            <select name="lang" style="padding: 0.65rem 1rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-size: 0.95rem;">
                <option value="">All Languages</option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?php echo htmlspecialchars($lang); ?>" <?php echo ($langFilter === $lang) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lang); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <select name="sort" style="padding: 0.65rem 1rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-size: 0.95rem;">
                <option value="updated" <?php echo ($sort === 'updated') ? 'selected' : ''; ?>>Recently Updated</option>
                <option value="stars" <?php echo ($sort === 'stars') ? 'selected' : ''; ?>>Most Starred</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">Filter</button>
        <?php if (!empty($search) || !empty($langFilter) || $sort !== 'updated'): ?>
            <a href="explore.php" class="btn btn-secondary" style="padding: 0.65rem 1rem;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Public Repositories Grid -->
    <?php if (empty($repositories)): ?>
        <div style="background: var(--bg-card); padding: 4rem; text-align: center; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
            <p style="color: var(--text-muted); font-size: 1.1rem;">No public repositories matched your criteria.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.5rem;">
            <?php foreach ($repositories as $repo): ?>
                <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <a href="profile.php?username=<?php echo htmlspecialchars($repo['username']); ?>" style="font-size: 0.85rem; color: var(--accent-violet); font-weight: 600;">
                                @<?php echo htmlspecialchars($repo['username']); ?>
                            </a>
                            <span style="font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 4px; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-muted);">
                                <?php echo htmlspecialchars($repo['primary_language']); ?>
                            </span>
                        </div>

                        <h3 style="margin-bottom: 0.5rem;">
                            <a href="repository.php?id=<?php echo $repo['id']; ?>" style="color: var(--text-main); font-size: 1.2rem; font-weight: 700;">
                                <?php echo htmlspecialchars($repo['name']); ?>
                            </a>
                        </h3>

                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.25rem; line-height: 1.4; min-height: 2.6rem;">
                            <?php echo htmlspecialchars($repo['description'] ?? 'No description provided.'); ?>
                        </p>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-dim); border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <div style="display: flex; gap: 1rem;">
                            <span>⭐ <?php echo $repo['star_count']; ?></span>
                            <span>💬 <?php echo $repo['comment_count']; ?></span>
                        </div>
                        <span>Updated <?php echo date('M j, Y', strtotime($repo['updated_at'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>