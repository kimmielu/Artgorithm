<?php
session_start();
require_once "database.php";
require_once "User.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // connect to DB
    $database = new Database();
    $db = $database->connect();

    // user model
    $user = new User($db);

    $entered_code = $_POST['code'];
    $email = $_SESSION['email'];

    // verify using the correct method
    $result = $user->verify2FA($email, $entered_code);

    if ($result->rowCount() > 0) {
        echo "<script>
                alert('Verification successful!');
                window.location='dashboard.php';
              </script>";
        exit;
    } else {
        $message = "<div class='alert alert-danger'>Incorrect verification code.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Code</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4 mx-auto" style="max-width:400px;">
        <h3 class="text-center mb-3">Enter Verification Code</h3>
        <?= $message ?>
        <form method="POST">
            <div class="mb-3">
                <label>Verification Code</label>
                <input type="text" name="code" class="form-control" maxlength="6" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Verify</button>
        </form>
    </div>
</div>
</body>
</html>
