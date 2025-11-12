<?php
// Enable detailed error reporting (useful for debugging during development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database {
    // Database connection details
    private $host = "127.0.0.1";          // localhost
    private $port = "3307";               // your MariaDB port
    private $db_name = "artgorithm_db";   // your database name
    private $username = "root";           // your MariaDB username
    private $password = "1234";           // your MariaDB password
    public $conn;

    // Function to establish connection
    public function connect() {
        $this->conn = null;

        try {
            // Create new PDO connection
            $this->conn = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}",
                $this->username,
                $this->password
            );

            // Set PDO attributes
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✅ Connection successful!";

        } catch (PDOException $e) {
            echo "❌ Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
