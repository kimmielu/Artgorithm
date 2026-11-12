<?php
$pageTitle = "Log In";
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<div class="container" style="max-width: 440px; margin: 4rem auto;">
    <div style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
        <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Welcome back</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Log in to continue building.</p>
        
        <form action="auth/login_process.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">Email or Username</label>
                <input type="text" name="login_identity" required style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted);">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.85rem; margin-top: 0.5rem;">Log In</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted); font-size: 0.9rem;">
            Don't have an account? <a href="register.php">Sign up</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>