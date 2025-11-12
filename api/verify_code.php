<?php
session_start();
require_once "User.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $entered = $_POST['code'];
    $email = $_SESSION['email'];

    $user = new User();
    $stmt = $user->verify2FA($email, $entered);

    if ($stmt->rowCount() > 0) {
        // Success — clear code and go to dashboard
        $_SESSION['logged_in'] = true;
        echo "<script>alert('✅ 2FA verified successfully!'); window.location='dashboard.php';</script>";
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Incorrect code. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>Verify Code</title>
  <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
  <div class='card shadow p-4 mx-auto' style='max-width:400px;'>
    <h3 class='text-center mb-3'>Two-Factor Verification</h3>
    <?= $message ?>
    <form method='POST'>
      <div class='mb-3'>
        <label>Enter 6-Digit Code</label>
        <input type='text' name='code' maxlength='6' class='form-control' required>
      </div>
      <button type='submit' class='btn btn-success w-100'>Verify</button>
    </form>
  </div>
</div>
</body>
</html>
