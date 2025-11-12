<?php
require_once "database.php";

$db = new Database();
$conn = $db->connect();

if ($conn) {
    echo "<br>Database connection is active!";
} else {
    echo "<br>Connection failed.";
}
?>
