<?php
session_start();

// OPTIONAL: block access if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Artgorithm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <span class="navbar-brand fw-bold">Artgorithm Dashboard</span>
    <a href="login.php" class="btn btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container mt-5">

    <h2 class="text-center mb-4">Welcome to Your Dashboard</h2>

    <div class="row g-4">

        <!-- MANAGE PROJECTS -->
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h4>Manage Projects</h4>
                <p>Create, update and delete your coding projects.</p>
                <a href="manage_projects.php" class="btn btn-primary">Open Projects</a>
            </div>
        </div>

        <!-- MANAGE SNIPPETS -->
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h4>Manage Code Snippets</h4>
                <p>Store small useful code snippets for reuse.</p>
                <a href="manage_snippets.php" class="btn btn-primary">Open Snippets</a>
            </div>
        </div>

        <!-- FUTURE FEATURES -->
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h4>Manage Files (Coming Soon)</h4>
                <p>Upload and attach files to your projects.</p>
                <button class="btn btn-secondary" disabled>Feature Locked</button>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h4>Manage Collaborators (Coming Soon)</h4>
                <p>Add teammates to your projects.</p>
                <button class="btn btn-secondary" disabled>Feature Locked</button>
            </div>
        </div>

    </div>
</div>

</body>
</html>
