<?php
require_once "database.php";

class Project {
    private $conn;
    private $table_name = "projects";

    public $project_id;
    public $user_id;
    public $project_name;
    public $description;
    public $visibility;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // CREATE
    public function create() {
        $query = "INSERT INTO {$this->table_name} (user_id, project_name, description, visibility)
                  VALUES (:user_id, :project_name, :description, :visibility)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':project_name', $this->project_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':visibility', $this->visibility);
        return $stmt->execute();
    }

    // READ
    public function readAll($user_id) {
        $query = "SELECT * FROM {$this->table_name} WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // UPDATE
    public function update() {
        $query = "UPDATE {$this->table_name} 
                  SET project_name = :project_name, description = :description, visibility = :visibility 
                  WHERE project_id = :project_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_name', $this->project_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':visibility', $this->visibility);
        $stmt->bindParam(':project_id', $this->project_id);
        return $stmt->execute();
    }

    // DELETE
    public function delete() {
        $query = "DELETE FROM {$this->table_name} WHERE project_id = :project_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $this->project_id);
        return $stmt->execute();
    }
}
?>
