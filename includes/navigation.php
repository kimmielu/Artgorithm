<?php
require_once __DIR__ . '/functions.php';
$user = getLoggedInUser();
?>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="brand-logo">
            The Artgorithm<span class="dot">.</span>
        </a>
        <ul class="nav-links">
            <li><a href="explore.php">Explore</a></li>
            <?php if ($user): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="create-repository.php">New Project</a></li>
                <li><a href="notifications.php">Notifications</a></li>
                <li><a href="profile.php"><?php echo htmlspecialchars($user['username']); ?></a></li>
                <li><a href="auth/logout.php" class="btn btn-secondary">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Log In</a></li>
                <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>