<?php
$pageTitle = "Notifications Hub";
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$user = getLoggedInUser();
$db = getDBConnection();

// Fetch activity notifications
$notifStmt = $db->prepare("
    SELECT n.*, u.username as sender_username 
    FROM notifications n 
    JOIN users u ON n.sender_id = u.id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC
");
$notifStmt->execute([$user['id']]);
$notifications = $notifStmt->fetchAll();

// Fetch pending collaborator invitations
$invitesStmt = $db->prepare("
    SELECT c.id as collab_id, r.id as repo_id, r.name as repo_name, u.username as owner_username 
    FROM collaborations c 
    JOIN repositories r ON c.repository_id = r.id 
    JOIN users u ON r.user_id = u.id 
    WHERE c.user_id = ? AND c.status = 'pending'
");
$invitesStmt->execute([$user['id']]);
$pendingInvites = $invitesStmt->fetchAll();
?>

<div class="container" style="max-width: 800px; padding: 2.5rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 800;">Notifications & Activity</h1>
        <?php if (!empty($notifications)): ?>
            <form action="actions/notification_actions.php" method="POST">
                <input type="hidden" name="action" value="mark_all_read">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem;">Mark All as Read</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Pending Collaborator Invitations Section -->
    <?php if (!empty($pendingInvites)): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--accent-violet); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.1rem; color: var(--accent-lime); margin-bottom: 1rem;">📬 Pending Collaboration Invitations</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($pendingInvites as $invite): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                        <div>
                            <strong>@<?php echo htmlspecialchars($invite['owner_username']); ?></strong> invited you to contribute to 
                            <a href="repository.php?id=<?php echo $invite['repo_id']; ?>" style="color: var(--accent-violet); font-weight: 700;">
                                <?php echo htmlspecialchars($invite['repo_name']); ?>
                            </a>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <form action="actions/collaboration_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="respond_invitation">
                                <input type="hidden" name="collaboration_id" value="<?php echo $invite['collab_id']; ?>">
                                <input type="hidden" name="status" value="accepted">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Accept</button>
                            </form>
                            <form action="actions/collaboration_actions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="respond_invitation">
                                <input type="hidden" name="collaboration_id" value="<?php echo $invite['collab_id']; ?>">
                                <input type="hidden" name="status" value="declined">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Decline</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- General Activity Notifications List -->
    <?php if (empty($notifications)): ?>
        <div style="background: var(--bg-card); padding: 3rem; text-align: center; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
            <p style="color: var(--text-muted);">You have no recent notifications.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($notifications as $n): ?>
                <div style="background: var(--bg-card); border: 1px solid <?php echo $n['is_read'] ? 'var(--border-color)' : 'var(--accent-violet)'; ?>; border-radius: var(--radius-md); padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 1.2rem;">
                            <?php 
                            switch($n['type']) {
                                case 'star': echo '⭐'; break;
                                case 'comment': echo '💬'; break;
                                case 'follow': echo '👤'; break;
                                case 'invitation': echo '✉️'; break;
                                default: echo '🔔'; break;
                            }
                            ?>
                        </span>
                        <div>
                            <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($n['message']); ?></p>
                            <span style="color: var(--text-dim); font-size: 0.8rem;"><?php echo date('M j, Y g:i a', strtotime($n['created_at'])); ?></span>
                        </div>
                    </div>

                    <?php if (!$n['is_read']): ?>
                        <form action="actions/notification_actions.php" method="POST">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notification_id" value="<?php echo $n['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <button type="submit" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Mark Read</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>