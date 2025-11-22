<?php
require_once "database.php";
require_once "User.php";

// connect to DB
$database = new Database();
$db = $database->connect();

// create User object
$user = new User($db);

// generate UNIQUE username & email for every test run
$rand = rand(1000, 99999);

$user->username = "test_user_" . $rand;
$user->email = "test" . $rand . "@example.com";
$user->password_hash = "password123"; // raw password

// run registration
if ($user->register()) {
    echo "✔️ Connection successful!<br>";
    echo "🎉 Test Passed: User inserted successfully!<br><br>";
    echo "<strong>Inserted Username:</strong> {$user->username}<br>";
    echo "<strong>Inserted Email:</strong> {$user->email}<br>";
} else {
    echo "✔️ Connection successful!<br>";
    echo "❌ Test Failed: Could not insert user.<br>";
}
?>
