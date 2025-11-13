<?php
require_once "database.php";

class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $email;
    public $password_hash;
    public $two_factor_code;
    public $role = "customer";

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE user
    public function register() {
        $query = "INSERT INTO {$this->table_name} 
                (username, email, password_hash, role)
                VALUES (:username, :email, :password_hash, :role)";
                
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":role", $this->role);

        return $stmt->execute();
    }

    // GET user by email
    public function getByEmail($email) {
        $query = "SELECT * FROM {$this->table_name} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt;
    }

    // UPDATE 2FA CODE
    public function set2FACode($email, $code) {
        $query = "UPDATE {$this->table_name} 
                  SET two_factor_code = :code 
                  WHERE email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->bindParam(":email", $email);
        return $stmt->execute();
    }

    // VERIFY 2FA CODE
    public function verify2FA($email, $code) {
        $query = "SELECT * FROM {$this->table_name} 
                  WHERE email = :email AND two_factor_code = :code 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":code", $code);
        $stmt->execute();
        return $stmt;
    }
}
?>
