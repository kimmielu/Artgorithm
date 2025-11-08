<?php
class Database {
    private $host = "127.0.0.1";     // or "localhost"
    private $db   = "artgorithm";    // your DB name in HeidiSQL
    private $user = "root";          // default XAMPP user
    private $pass = "1234";              // default XAMPP password (empty unless you set one)
    private $charset = "utf8mb4";
    private $port = 3307;            // if you changed MySQL port, update here

    private $pdo = null;

    public function getConnection(): ?PDO {
        if ($this->pdo) return $this->pdo;

        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            return $this->pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            // In production, log this instead of echoing
            echo "DB connection failed.";
            return null;
        }
    }
}
