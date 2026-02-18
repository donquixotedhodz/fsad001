<?php

class ECController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get EC by ID
     */
    public function getEC($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM electric_cooperatives WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get all ECs with pagination
     */
    public function getAllECs($limit = 10, $offset = 0)
    {
        try {
            if ($limit === 0) {
                $stmt = $this->conn->prepare("SELECT * FROM electric_cooperatives ORDER BY name ASC");
                $stmt->execute();
            }
            else {
                $stmt = $this->conn->prepare("SELECT * FROM electric_cooperatives ORDER BY name ASC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get total count of ECs
     */
    public function getTotalCount()
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM electric_cooperatives");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['total'];
        }
        catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Add new EC
     */
    public function addEC($name, $code, $description = '')
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            $stmt = $this->conn->prepare("INSERT INTO electric_cooperatives (name, code, description) VALUES (?, ?, ?)");
            $result = $stmt->execute([$name, $code, $description]);

            if ($result) {
                $newId = $this->conn->lastInsertId();
                // Log the action
                $auditLogger->logCreate(
                    'electric_cooperatives',
                    $newId,
                    "New EC added: $name ($code)",
                ['name' => $name, 'code' => $code, 'description' => $description]
                );
                return ['success' => true, 'message' => 'EC added successfully!'];
            }
            return ['success' => false, 'message' => 'Failed to add EC'];
        }
        catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'message' => 'EC name or code already exists'];
            }
            return ['success' => false, 'message' => 'Error: ' . htmlspecialchars($e->getMessage())];
        }
    }

    /**
     * Update EC
     */
    public function updateEC($id, $name, $code, $description = '')
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Fetch old data
            $oldData = $this->getEC($id);
            if (!$oldData) {
                return ['success' => false, 'message' => 'EC not found'];
            }

            $stmt = $this->conn->prepare("UPDATE electric_cooperatives SET name = ?, code = ?, description = ? WHERE id = ?");
            $result = $stmt->execute([$name, $code, $description, $id]);

            if ($result) {
                // Fetch new data
                $newData = $this->getEC($id);
                // Log the action
                $auditLogger->logUpdate(
                    'electric_cooperatives',
                    $id,
                    "EC updated: $name ($code)",
                    $oldData,
                    $newData
                );
                return ['success' => true, 'message' => 'EC updated successfully!'];
            }
            return ['success' => false, 'message' => 'Failed to update EC'];
        }
        catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'message' => 'EC name or code already exists'];
            }
            return ['success' => false, 'message' => 'Error: ' . htmlspecialchars($e->getMessage())];
        }
    }

    /**
     * Delete EC
     */
    public function deleteEC($id)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Fetch data
            $oldData = $this->getEC($id);
            if (!$oldData) {
                return ['success' => false, 'message' => 'EC not found'];
            }

            $stmt = $this->conn->prepare("DELETE FROM electric_cooperatives WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                // Log the action
                $auditLogger->logDelete(
                    'electric_cooperatives',
                    $id,
                    "EC deleted: {$oldData['name']} ({$oldData['code']})",
                    $oldData
                );
                return ['success' => true, 'message' => 'EC deleted successfully!'];
            }
            return ['success' => false, 'message' => 'Failed to delete EC'];
        }
        catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . htmlspecialchars($e->getMessage())];
        }
    }

    /**
     * Search ECs by name or code
     */
    public function searchECs($searchTerm, $limit = 10, $offset = 0)
    {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            if ($limit === 0) {
                $stmt = $this->conn->prepare("SELECT * FROM electric_cooperatives WHERE name LIKE ? OR code LIKE ? OR description LIKE ? ORDER BY name ASC");
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            }
            else {
                $stmt = $this->conn->prepare("SELECT * FROM electric_cooperatives WHERE name LIKE ? OR code LIKE ? OR description LIKE ? ORDER BY name ASC LIMIT ? OFFSET ?");
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get search count
     */
    public function getSearchCount($searchTerm)
    {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM electric_cooperatives WHERE name LIKE ? OR code LIKE ? OR description LIKE ?");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $result = $stmt->fetch();
            return $result['total'];
        }
        catch (Exception $e) {
            return 0;
        }
    }
}
?>
