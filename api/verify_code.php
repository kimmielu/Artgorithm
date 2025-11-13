<?php
session_start();
require_once "database.php";
require_once "User.php";

$message = "";

// check if user came from login
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $database = new Database();
    $db = $database->connect();

    $user = new User($db);

    $email = $_SESSION['email'];
    $entered_code = $_POST['code'];

    // verify
    if ($user->verify2FACode($email, $entered_code)) {

        // login success
        $_SESSION['logged_in'] = true;

        echo "<script>
                alert('Verification successful! Logging you in...');
                window.location='dashboard.php';
             </script>";
        exit;

    } else {
        $message = "<div class='alert alert-danger'>Incorrect code. Try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Code</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow p-4 mx-auto" style="max-width:400px;">
    <h3 class="text-center mb-3">Enter Verification Code</h3>
    <?= $message ?>
    <form method="POST">
      <div class="mb-3">
        <label>Verification Code</label>
        <input type="number" name="code" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Verify</button>
    </form>
  </div>
</div>

</body>
</html>
