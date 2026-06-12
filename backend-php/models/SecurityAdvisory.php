<?php

class SecurityAdvisory {
    private $conn;
    private $table_name = "security_advisories";

    public $id;
    public $component_name;
    public $affected_versions;
    public $fixed_version;
    public $severity;
    public $title;
    public $description;
    public $alternative_resources;
    public $status;
    public $resolved_at;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET component_name=:component_name, 
                      affected_versions=:affected_versions, 
                      fixed_version=:fixed_version, 
                      severity=:severity, 
                      title=:title, 
                      description=:description, 
                      alternative_resources=:alternative_resources, 
                      status=:status";
        
        $stmt = $this->conn->prepare($query);

        $this->component_name = htmlspecialchars(strip_tags($this->component_name));
        $this->affected_versions = htmlspecialchars(strip_tags($this->affected_versions));
        $this->fixed_version = htmlspecialchars(strip_tags($this->fixed_version));
        $this->severity = htmlspecialchars(strip_tags($this->severity));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->alternative_resources = $this->alternative_resources;
        $this->status = htmlspecialchars(strip_tags($this->status));

        $stmt->bindParam(":component_name", $this->component_name);
        $stmt->bindParam(":affected_versions", $this->affected_versions);
        $stmt->bindParam(":fixed_version", $this->fixed_version);
        $stmt->bindParam(":severity", $this->severity);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":alternative_resources", $this->alternative_resources);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findAll($status = null, $severity = null) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        $params = [];

        if ($status) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        if ($severity) {
            $query .= " AND severity = :severity";
            $params[':severity'] = $severity;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET component_name=:component_name, 
                      affected_versions=:affected_versions, 
                      fixed_version=:fixed_version, 
                      severity=:severity, 
                      title=:title, 
                      description=:description, 
                      alternative_resources=:alternative_resources
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $this->component_name = htmlspecialchars(strip_tags($this->component_name));
        $this->affected_versions = htmlspecialchars(strip_tags($this->affected_versions));
        $this->fixed_version = htmlspecialchars(strip_tags($this->fixed_version));
        $this->severity = htmlspecialchars(strip_tags($this->severity));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->alternative_resources = $this->alternative_resources;

        $stmt->bindParam(":component_name", $this->component_name);
        $stmt->bindParam(":affected_versions", $this->affected_versions);
        $stmt->bindParam(":fixed_version", $this->fixed_version);
        $stmt->bindParam(":severity", $this->severity);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":alternative_resources", $this->alternative_resources);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status";
        
        if ($status === 'resolved') {
            $query .= ", resolved_at = NOW()";
        } else {
            $query .= ", resolved_at = NULL";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if($stmt->execute()) {
            if($stmt->rowCount() > 0) {
                return true;
            }
        }
        return false;
    }

    public function matchByKeyword($keyword) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE component_name LIKE :keyword 
                     OR title LIKE :keyword 
                     OR description LIKE :keyword
                  ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low'), created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $keyword . "%";
        $stmt->bindParam(":keyword", $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function matchByComponentName($componentName) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE component_name LIKE :componentName
                  ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low'), created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $componentName . "%";
        $stmt->bindParam(":componentName", $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
