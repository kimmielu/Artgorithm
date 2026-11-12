<?php
$pageTitle = "The Artgorithm — Modern Collaborative Platform";
require_once __DIR__ . '/includes/header.php';
?>
<section style="padding: 5rem 0; text-align: center;">
    <div class="container" style="max-width: 800px;">
        <span style="display: inline-block; padding: 0.35rem 1rem; background: var(--bg-card); border: 1px solid var(--border-color); color: var(--accent-lime); border-radius: 50px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;">
            Built for Modern Developers
        </span>
        <h1 style="font-size: 3.2rem; font-weight: 800; line-height: 1.2; margin-bottom: 1.5rem;">
            Where Code meets <span style="color: var(--accent-violet);">Craftsmanship</span>.
        </h1>
        <p style="font-size: 1.2rem; color: var(--text-muted); margin-bottom: 2.5rem;">
            A dark editorial platform designed for developers to showcase repositories, receive asynchronous feedback, and collaborate seamlessly.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 1rem;">Get Started Free</a>
                <a href="explore.php" class="btn btn-secondary" style="padding: 0.85rem 2rem; font-size: 1rem;">Explore Repos</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 1rem;">Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>