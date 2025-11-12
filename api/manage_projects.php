<?php
session_start();
require_once "Project.php";

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$project = new Project();
$message = "";

// CREATE
if (isset($_POST['create'])) {
    $project->user_id = 1; // for demo, use logged-in user_id if stored in session
    $project->project_name = $_POST['project_name'];
    $project->description = $_POST['description'];
    $project->visibility = $_POST['visibility'];
    $project->create();
    $message = "<div class='alert alert-success'>Project created successfully!</div>";
}

// UPDATE
if (isset($_POST['update'])) {
    $project->project_id = $_POST['project_id'];
    $project->project_name = $_POST['project_name'];
    $project->description = $_POST['description'];
    $project->visibility = $_POST['visibility'];
    $project->update();
    $message = "<div class='alert alert-info'>Project updated successfully!</div>";
}

// DELETE
if (isset($_POST['delete'])) {
    $project->project_id = $_POST['project_id'];
    $project->delete();
    $message = "<div class='alert alert-danger'>Project deleted successfully!</div>";
}

// READ
$projects = $project->readAll(1); // use $_SESSION['user_id'] when implemented
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Projects - Artgorithm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="mb-4">Manage Your Projects</h3>
  <?= $message ?>

  <!-- Create Project Form -->
  <form method="POST" class="card p-3 mb-4 shadow-sm">
    <h5>Create New Project</h5>
    <input type="text" name="project_name" class="form-control mb-2" placeholder="Project Name" required>
    <textarea name="description" class="form-control mb-2" placeholder="Project Description"></textarea>
    <select name="visibility" class="form-select mb-2">
      <option value="public">Public</option>
      <option value="private">Private</option>
    </select>
    <button type="submit" name="create" class="btn btn-success w-100">Add Project</button>
  </form>

  <!-- List of Projects -->
  <div class="card p-3 shadow-sm">
    <h5>Your Projects</h5>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Name</th>
          <th>Description</th>
          <th>Visibility</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $projects->fetch(PDO::FETCH_ASSOC)) : ?>
        <tr>
          <form method="POST">
            <input type="hidden" name="project_id" value="<?= $row['project_id'] ?>">
            <td><input type="text" name="project_name" value="<?= htmlspecialchars($row['project_name']) ?>" class="form-control"></td>
            <td><input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>" class="form-control"></td>
            <td>
              <select name="visibility" class="form-select">
                <option value="public" <?= $row['visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $row['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
              </select>
            </td>
            <td>
              <button type="submit" name="update" class="btn btn-sm btn-primary">Update</button>
              <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
            </td>
          </form>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
