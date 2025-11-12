<?php
require_once "User.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = new User();
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];
    $user->password_hash = $_POST['password'];

    if ($user->register()) {
        $message = "<div class='alert alert-success'>Registration successful! You can now log in.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error: Email already exists or registration failed.</div>";
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
  <div class="card shadow p-4 mx-auto" style="max-width:400px;">
    <h3 class="text-center mb-3">Create Account</h3>
    <?= $message ?>
    <form method="POST">
      <div class="mb-3">
        <label>Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" minlength="6" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="mt-3 text-center">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</div>
</body>
</html>
