<?php
require_once "database.php";
require_once "User.php";

// connect to DB
$database = new Database();
$db = $database->connect();

// create User object
$user = new User($db);

// assign data for testing
$user->username = "test_user";
$user->email = "test@example.com";
$user->password_hash = "password123"; // raw password

// run registration
if ($user->register()) {
    echo "✅ Test Passed: User inserted successfully!";
} else {
    echo "❌ Test Failed: Could not insert user.";
}
?>
