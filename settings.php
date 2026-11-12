<?php
$pageTitle = "Account Settings";
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$user = getLoggedInUser();
$db = getDBConnection();

$stmt = $db->prepare("SELECT username, email, bio FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();
?>

<div class="container" style="max-width: 640px; padding: 3rem 0;">
    <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Account Settings</h1>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Manage your developer profile and security preferences.</p>

    <!-- Profile Details Form -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 2rem;">
        <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Public Profile</h2>
        <form action="auth/update_profile.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <input type="hidden" name="action" value="update_info">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div>
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: var(--text-muted);">Username (cannot be changed)</label>
                <input type="text" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled style="width: 100%; padding: 0.75rem; background: var(--bg-dark); border: 1px solid var(--border-color); color: var(--text-dim); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: var(--text-muted);">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: var(--text-muted);">Developer Bio</label>
                <textarea name="bio" rows="4" maxlength="500" placeholder="Tell the community what you're building..." style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit;"><?php echo htmlspecialchars($userData['bio'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="align-self: flex-start;">Save Profile Changes</button>
        </form>
    </div>

    <!-- Change Password Form -->
    <div style="background: var(--bg-card); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
        <h2 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Change Password</h2>
        <form action="auth/update_profile.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <input type="hidden" name="action" value="update_password">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div>
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: var(--text-muted);">Current Password</label>
                <input type="password" name="current_password" required style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.4rem; font-size: 0.9rem; color: var(--text-muted);">New Password (min. 8 characters)</label>
                <input type="password" name="new_password" required minlength="8" style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <button type="submit" class="btn btn-secondary" style="align-self: flex-start;">Update Password</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>