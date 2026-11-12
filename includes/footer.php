</main>
<footer>
    <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
        <p>&copy; <?php echo date('Y'); ?> The Artgorithm. Crafted for developers.</p>
        <div style="display: flex; gap: 1rem;">
            <a href="explore.php">Explore Repos</a>
            <a href="https://github.com" target="_blank" rel="noopener">GitHub</a>
        </div>
    </div>
</footer>
<script src="assets/js/main.js"></script>
<?php if (isset($extraJs)): ?>
    <script src="assets/js/<?php echo htmlspecialchars($extraJs); ?>"></script>
<?php endif; ?>
</body>
</html>