<?php

class DepartmentsController
{
    protected $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get all departments
     */
    public function getAllDepartments($limit = null, $offset = 0)
    {
        try {
            $query = "SELECT id, name, acronym FROM neadept_table ORDER BY name ASC";

            if ($limit && $limit !== 'all') {
                $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            throw new Exception("Error fetching departments: " . $e->getMessage());
        }
    }

    /**
     * Get total count of departments
     */
    public function getDepartmentCount()
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM neadept_table");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        }
        catch (Exception $e) {
            throw new Exception("Error counting departments: " . $e->getMessage());
        }
    }

    /**
     * Get department by ID
     */
    public function getDepartmentById($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT id, name, acronym FROM neadept_table WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            throw new Exception("Error fetching department: " . $e->getMessage());
        }
    }

    /**
     * Add new department
     */
    public function addDepartment($name, $acronym = null)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Check if department already exists
            $stmt = $this->conn->prepare("SELECT id FROM neadept_table WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                throw new Exception("Department already exists");
            }

            $stmt = $this->conn->prepare("INSERT INTO neadept_table (name, acronym) VALUES (?, ?)");
            $stmt->execute([$name, $acronym]);

            $newId = $this->conn->lastInsertId();

            // Log the action
            $auditLogger->logCreate(
                'neadept_table',
                $newId,
                "New department added: $name" . ($acronym ? " ($acronym)" : ""),
            ['name' => $name, 'acronym' => $acronym]
            );

            return $newId;
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Update department
     */
    public function updateDepartment($id, $name, $acronym = null)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Fetch old data
            $oldData = $this->getDepartmentById($id);
            if (!$oldData) {
                throw new Exception("Department not found");
            }

            // Check if name exists for other departments
            $stmt = $this->conn->prepare("SELECT id FROM neadept_table WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                throw new Exception("Department name already exists");
            }

            $stmt = $this->conn->prepare("UPDATE neadept_table SET name = ?, acronym = ? WHERE id = ?");
            $stmt->execute([$name, $acronym, $id]);

            // Fetch new data
            $newData = $this->getDepartmentById($id);

            // Log the action
            $auditLogger->logUpdate(
                'neadept_table',
                $id,
                "Department updated: $name",
                $oldData,
                $newData
            );

            return true;
        }
        catch (Exception $e) {
            throw new Exception("Error updating department: " . $e->getMessage());
        }
    }

    /**
     * Delete department
     */
    public function deleteDepartment($id)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Fetch data
            $oldData = $this->getDepartmentById($id);
            if (!$oldData) {
                throw new Exception("Department not found");
            }

            $stmt = $this->conn->prepare("DELETE FROM neadept_table WHERE id = ?");
            $stmt->execute([$id]);

            // Log the action
            $auditLogger->logDelete(
                'neadept_table',
                $id,
                "Department deleted: {$oldData['name']}",
                $oldData
            );

            return true;
        }
        catch (Exception $e) {
            throw new Exception("Error deleting department: " . $e->getMessage());
        }
    }
}
?>
