<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Artgorithm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="alert alert-success">
    <h3>Welcome to Artgorithm 🎨💻</h3>
    <p>You have successfully logged in using 2FA!</p>
  </div>
  <a href="manage_projects.php" class="btn btn-outline-primary mt-3">Manage Projects</a>

</div>
</body>
</html>
