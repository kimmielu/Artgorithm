<?php
session_start();
require_once "database.php";

// ---- CONNECT TO DATABASE ----
$database = new Database();
$db = $database->connect(); // ✔ FIXED

$message = "";

// ---- ADD PROJECT ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add'])) {

    $project_name = $_POST['project_name'];
    $description = $_POST['description'];
    $visibility = $_POST['visibility'];
    $user_id = 1; // for now, static user. Later use $_SESSION['user_id']

    $sql = "INSERT INTO projects (user_id, project_name, description, visibility)
            VALUES (:user_id, :project_name, :description, :visibility)";
    $stmt = $db->prepare($sql);

    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':project_name', $project_name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':visibility', $visibility);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success text-center'>Project added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Failed to add project.</div>";
    }
}

// ---- DELETE PROJECT ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
    $project_id = $_POST['project_id'];

    $deleteQuery = "DELETE FROM projects WHERE project_id = :project_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(":project_id", $project_id);

    if ($deleteStmt->execute()) {
        $message = "<div class='alert alert-warning text-center'>Project deleted.</div>";
    }
}

// ---- FETCH PROJECTS ----
$query = "SELECT * FROM projects ORDER BY project_id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Projects</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Artgorithm</a>
    <a href="login.php" class="btn btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container mt-4">
  <h2 class="text-center">Manage Projects</h2>

  <?= $message ?>

  <div class="card p-4 mt-3 shadow-sm mb-4">
    <h4>Add New Project</h4>
    <form method="POST" class="row g-3">
      <div class="col-md-4">
        <input type="text" name="project_name" class="form-control" placeholder="Project name" required>
      </div>
      <div class="col-md-4">
        <input type="text" name="description" class="form-control" placeholder="Short description">
      </div>
      <div class="col-md-4">
        <select name="visibility" class="form-control" required>
          <option value="public">Public</option>
          <option value="private">Private</option>
        </select>
      </div>
      <button type="submit" name="add" class="btn btn-success mt-3">Add Project</button>
    </form>
  </div>

  <div class="card p-4 shadow-sm">
    <h4>All Projects</h4>
    <table class="table table-striped mt-3">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Visibility</th>
          <th>Description</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($projects) > 0): ?>
          <?php foreach ($projects as $p): ?>
          <tr>
            <td><?= $p['project_id'] ?></td>
            <td><?= htmlspecialchars($p['project_name']) ?></td>
            <td><?= htmlspecialchars($p['visibility']) ?></td>
            <td><?= htmlspecialchars($p['description']) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="project_id" value="<?= $p['project_id'] ?>">
                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center">No projects yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>
