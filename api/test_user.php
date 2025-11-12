<?php
require_once "User.php";

$user = new User();
$user->username = "test_user";
$user->email = "test@example.com";
$user->password_hash = "password123";

if ($user->register()) {
    echo "✅ User inserted successfully!";
} else {
    echo "❌ Failed to insert user.";
}
?>
