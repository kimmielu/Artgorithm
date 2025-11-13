<?php
session_start();
require_once "database.php";
require_once "User.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // connect to db
    $database = new Database();
    $db = $database->connect();

    // user model
    $user = new User($db);

    // check if email exists
    $stmt = $user->getByEmail($_POST['email']);

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($_POST['password'], $row['password_hash'])) {

            // generate 6-digit code
            $code = rand(100000, 999999);

            // save 2FA code
            $user->set2FACode($_POST['email'], $code);

            // save session
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['2fa_code'] = $code;

            // redirect to 2FA page
            echo "<script>
                    alert('Your verification code is: $code');
                    window.location='verify_code.php';
                 </script>";
            exit;

        } else {
            $message = "<div class='alert alert-danger'>Incorrect password.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Email not found.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Login - Artgorithm</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
  <div class='card shadow p-4 mx-auto' style='max-width:400px;'>
    <h3 class='text-center mb-3'>Login</h3>
    <?= $message ?>
    <form method='POST'>
      <div class='mb-3'>
        <label>Email</label>
        <input type='email' name='email' class='form-control' required>
      </div>
      <div class='mb-3'>
        <label>Password</label>
        <input type='password' name='password' class='form-control' required>
      </div>
      <button type='submit' class='btn btn-primary w-100'>Login</button>
    </form>
    <p class='mt-3 text-center'>No account? <a href='register.php'>Register</a></p>
  </div>
</div>
</body>
</html>
