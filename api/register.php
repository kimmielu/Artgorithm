<?php
require_once "database.php";
require_once "User.php";

session_start();
$message = "";

// Only handle POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // simple server-side sanitization
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // basic validation
    if ($username === '' || $email === '' || $password === '') {
        $message = "<div class='alert alert-danger'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert alert-danger'>Please enter a valid email address.</div>";
    } elseif (strlen($password) < 6) {
        $message = "<div class='alert alert-danger'>Password must be at least 6 characters.</div>";
    } else {
        // connect to db
        $database = new Database();
        $db = $database->connect();

        if (!$db) {
            $message = "<div class='alert alert-danger'>Database connection failed.</div>";
        } else {
            try {
                // --- check if username or email already exists ---
                $checkSql = "SELECT username, email FROM users WHERE username = :username OR email = :email LIMIT 1";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->bindParam(':username', $username);
                $checkStmt->bindParam(':email', $email);
                $checkStmt->execute();
                $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    if ($existing['username'] === $username && $existing['email'] === $email) {
                        $message = "<div class='alert alert-danger'>Both username and email are already taken.</div>";
                    } elseif ($existing['username'] === $username) {
                        $message = "<div class='alert alert-danger'>Username is already taken. Choose another.</div>";
                    } else {
                        $message = "<div class='alert alert-danger'>Email is already registered. Try logging in or use a different email.</div>";
                    }
                } else {
                    // create User model and register
                    $user = new User($db);
                    $user->username = $username;
                    $user->email = $email;
                    $user->password_hash = $password; // model will hash
                    $user->role = "customer";

                    $ok = $user->register();

                    if ($ok) {
                        $message = "<div class='alert alert-success'>Registration successful! You can now <a href='login.php'>log in</a>.</div>";
                    } else {
                        // register() returns false on duplicate or false insert
                        $message = "<div class='alert alert-danger'>Registration failed — username or email may already exist.</div>";
                    }
                }

            } catch (PDOException $e) {
                // Log error in production; show friendly message here
                $message = "<div class='alert alert-danger'>An internal error occurred. Please try again later.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Artgorithm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card shadow p-4 mx-auto" style="max-width:420px;">
    <h3 class="text-center mb-3">Create Account</h3>

    <?= $message ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" minlength="6" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>

    <p class="mt-3 text-center">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</div>
</body>
</html>
