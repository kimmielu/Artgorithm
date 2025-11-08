<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/Database.php';

// Simple home page + DB test
echo "<h3>Artgorithm API</h3>";

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "<p>✅ Database connection OK.</p>";
} else {
    echo "<p>❌ Database connection failed.</p>";
}
