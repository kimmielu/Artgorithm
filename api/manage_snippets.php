<?php
require_once "database.php";

$database = new Database();
$conn = $database->connect();

$message = "";

// --- ADD SNIPPET ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add"])) {

    $title = $_POST["title"];
    $description = $_POST["description"];
    $language = $_POST["language"];
    $code = $_POST["code"];
    $user_id = 1; // TEMP: you will replace this with session user later

    $query = "INSERT INTO snippets (user_id, title, description, language, code)
              VALUES (:user_id, :title, :description, :language, :code)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":language", $language);
    $stmt->bindParam(":code", $code);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success text-center'>Snippet added successfully!</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Failed to add snippet.</div>";
    }
}

// --- DELETE SNIPPET ---
if (isset($_POST["delete"])) {
    $id = $_POST["snippet_id"];

    $query = "DELETE FROM snippets WHERE snippet_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();

    $message = "<div class='alert alert-warning text-center'>Snippet deleted.</div>";
}

// --- FETCH SNIPPETS ---
$query = "SELECT * FROM snippets ORDER BY snippet_id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$snippets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Snippets | Artgorithm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">Artgorithm</a>
        <a href="login.php" class="btn btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container">
    <h2 class="text-center mb-4">Manage Code Snippets</h2>

    <?= $message ?>

    <!-- ADD SNIPPET FORM -->
    <div class="card p-4 shadow mb-4">
        <h4>Add New Snippet</h4>
        <form method="POST" class="row g-3">

            <div class="col-md-6">
                <input type="text" name="title" placeholder="Snippet title" class="form-control" required>
            </div>

            <div class="col-md-6">
                <input type="text" name="language" placeholder="Language (PHP, JS, C++)" class="form-control" required>
            </div>

            <div class="col-md-12">
                <textarea name="description" rows="2" class="form-control" placeholder="Short description"></textarea>
            </div>

            <div class="col-md-12">
                <textarea name="code" rows="5" class="form-control" placeholder="Enter your code here..." required></textarea>
            </div>

            <div class="text-center">
                <button name="add" class="btn btn-success px-4 mt-3">Add Snippet</button>
            </div>
        </form>
    </div>

    <!-- SNIPPETS TABLE -->
    <div class="card shadow p-4">
        <h4>All Snippets</h4>

        <table class="table table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Language</th>
                    <th>Description</th>
                    <th>Code</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($snippets) > 0): ?>
                    <?php foreach ($snippets as $s): ?>
                        <tr>
                            <td><?= $s["snippet_id"] ?></td>
                            <td><?= htmlspecialchars($s["title"]) ?></td>
                            <td><?= htmlspecialchars($s["language"]) ?></td>
                            <td><?= htmlspecialchars($s["description"]) ?></td>
                            <td><pre style="white-space: pre-wrap;"><?= htmlspecialchars($s["code"]) ?></pre></td>

                            <td>
                                <form method="POST">
                                    <input type="hidden" name="snippet_id" value="<?= $s['snippet_id'] ?>">
                                    <button name="delete" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No snippets available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

</body>
</html>
