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

    public function __construct($db) {
        $this->conn = $db;
    }

    /* CREATE PROJECT */
    public function create() {
        $query = "INSERT INTO {$this->table_name}
                  (user_id, project_name, description, visibility)
                  VALUES (:user_id, :project_name, :description, :visibility)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":project_name", $this->project_name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":visibility", $this->visibility);

        return $stmt->execute();
    }

    /* READ ALL PROJECTS */
    public function getAll() {
        $query = "SELECT * FROM {$this->table_name} ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /* DELETE */
    public function delete($id) {
        $query = "DELETE FROM {$this->table_name} WHERE project_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}
?>
