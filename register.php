<?php
$pageTitle = "Sign Up";
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<div class="container" style="max-width: 480px; margin: 4rem auto;">
    <div style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
        <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Create your account</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Join The Artgorithm to build, share, and collaborate.</p>
        
        <form action="auth/register_process.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">Username</label>
                <input type="text" name="username" required style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">Password</label>
                <input type="password" name="password" required minlength="8" style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.85rem; margin-top: 0.5rem;">Create Account</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>