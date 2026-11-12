<?php
$pageTitle = "Create Repository";
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 600px; padding: 3rem 0;">
    <div style="background: var(--bg-card); padding: 2.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
        <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Create a new repository</h1>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">A repository contains all project files, code history, and documentation.</p>

        <form action="actions/repository_actions.php" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">Repository Name *</label>
                <input type="text" name="name" required placeholder="e.g. interactive-canvas" style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">Primary Programming Language</label>
                <select name="primary_language" style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md);">
                    <option value="HTML/CSS/JS">HTML / CSS / JavaScript</option>
                    <option value="PHP">PHP</option>
                    <option value="Python">Python</option>
                    <option value="JavaScript">JavaScript</option>
                    <option value="C++">C++</option>
                    <option value="Java">Java</option>
                    <option value="SQL">SQL</option>
                    <option value="Plain Text">Plain Text</option>
                </select>
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">Description (Optional)</label>
                <textarea name="description" rows="3" placeholder="Briefly describe what your project does..." style="width: 100%; padding: 0.75rem; background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-main); border-radius: var(--radius-md); font-family: inherit;"></textarea>
            </div>

            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem;">Visibility</label>
                <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="is_private" value="0" checked>
                        <span><strong>Public</strong> — Anyone can view this project</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="radio" name="is_private" value="1">
                        <span><strong>Private</strong> — Only you and contributors can view</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 0.85rem; margin-top: 1rem;">Create Repository</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>