# The Artgorithm

A collaborative code-sharing and discovery platform built for developers and programming students. Create repositories, organize multi-file projects, preview HTML/CSS/JS live in a sandboxed frame, star projects, follow developers, and collaborate asynchronously.

## Features
- **Authentication & Security:** Session-based auth with `password_hash()`, CSRF token verification on state-changing requests, prepared PDO statements, and XSS output escaping.
- **Repository Management:** Create public or private repositories, manage files, and control collaborator permissions.
- **Interactive Code Workspace:** File navigation sidebar, code editor, persistent AJAX saving, and a sandboxed `<iframe>` live preview for web projects.
- **Community & Discovery:** Search and filter public repositories by keyword, language, or popularity; star repositories; comment on code; follow developers.
- **Collaboration & Notifications:** Invite registered developers to contribute to repositories, accept/decline invitations, and receive real-time activity notifications.

## Tech Stack
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Backend:** PHP 8+ (PDO)
- **Database:** MySQL / MariaDB

## Local Installation (Windows / XAMPP)
1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Place the `the-artgorithm` project folder inside `C:\xampp\htdocs\`.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`), go to the **Import** tab, and import `database/schema.sql`. This creates the `artgorithm_db` database and all 8 tables.
4. Verify your database credentials in `config/database.php` (default XAMPP credentials are user `root` with an empty password `''`).
5. Visit `http://localhost/the-artgorithm/` in your browser.

## Future Improvements
- Version history and commit diffs
- Syntax highlighting with Prism.js or CodeMirror
- Folder hierarchy navigation inside the workspace sidebar