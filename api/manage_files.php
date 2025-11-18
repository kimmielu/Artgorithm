<?php
require_once "database.php";

$database = new Database();
$db = $database->connect();   // Correct DB connection

if (!$db) {
    die("❌ Database connection failed.");
}

$message = "";

// ------------------------
//  FILE UPLOAD HANDLER
// ------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload'])) {

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $title = $_POST['title'];
        $description = $_POST['description'];

        $fileName = basename($_FILES['file']['name']);
        $fileTmp = $_FILES['file']['tmp_name'];

        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // create folder if missing
        }

        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($fileTmp, $targetPath)) {

            $query = "INSERT INTO files (title, description, filename) 
                      VALUES (:title, :description, :filename)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":filename", $fileName);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success text-center'>File uploaded successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger text-center'>Failed to save file to database.</div>";
            }

        } else {
            $message = "<div class='alert alert-danger text-center'>Failed to upload file.</div>";
        }

    } else {
        $message = "<div class='alert alert-warning text-center'>Please choose a valid file.</div>";
    }
}


// ------------------------
//  DELETE HANDLER
// ------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {

    $file_id = $_POST['id'];

    // get file name
    $getFile = $db->prepare("SELECT filename FROM files WHERE file_id = :file_id");
    $getFile->bindParam(":file_id", $file_id);
    $getFile->execute();
    $file = $getFile->fetch(PDO::FETCH_ASSOC);

    if ($file) {
        $filePath = "uploads/" . $file['filename'];

        // delete DB record
        $delete = $db->prepare("DELETE FROM files WHERE file_id = :file_id");
        $delete->bindParam(":file_id", $file_id);

        if ($delete->execute()) {

            // delete file from folder
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $message = "<div class='alert alert-warning text-center'>File deleted successfully.</div>";

        } else {
            $message = "<div class='alert alert-danger text-center'>Failed to delete file from database.</div>";
        }

    }
}


// ------------------------
//  FETCH FILES
// ------------------------
$query = "SELECT * FROM files ORDER BY file_id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Files | Artgorithm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #f8f9fa; }
    .container { max-width: 900px; }
  </style>
</head>

<body>

<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">Artgorithm</a>
    <a href="login.php" class="btn btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container">
  <h2 class="text-center mb-4">Manage Files</h2>

  <?= $message ?>

  <!-- Upload Form -->
  <div class="card shadow-sm p-4 mb-4">
    <h4>Upload New File</h4>

    <form method="POST" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-4">
        <input type="text" name="title" class="form-control" placeholder="File title" required>
      </div>

      <div class="col-md-4">
        <input type="text" name="description" class="form-control" placeholder="Short description" required>
      </div>

      <div class="col-md-4">
        <input type="file" name="file" class="form-control" required>
      </div>

      <div class="text-center">
        <button type="submit" name="upload" class="btn btn-success mt-3">Upload File</button>
      </div>
    </form>
  </div>

  <!-- Files Table -->
  <div class="card shadow-sm p-4">
    <h4>Uploaded Files</h4>

    <table class="table table-striped mt-3">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Description</th>
          <th>Filename</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php if (count($files) > 0): ?>
          <?php foreach ($files as $file): ?>
          <tr>
            <td><?= $file['file_id'] ?></td>
            <td><?= htmlspecialchars($file['title']) ?></td>
            <td><?= htmlspecialchars($file['description']) ?></td>
            <td>
              <a href="uploads/<?= $file['filename'] ?>" target="_blank">
                <?= $file['filename'] ?>
              </a>
            </td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= $file['file_id'] ?>">
                <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center">No files uploaded yet.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div>
</div>

</body>
</html>
