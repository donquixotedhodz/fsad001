<?php

class AuditLogger {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Log an action to the audit logs table
     * 
     * @param string $action The action type (CREATE, READ, UPDATE, DELETE)
     * @param string $tableName The name of the table affected
     * @param int $recordId The ID of the affected record
     * @param string $description A description of what happened
     * @param array|null $oldValues The old values before the action (for UPDATE)
     * @param array|null $newValues The new values after the action
     * @param int|null $userId The ID of the user performing the action
     * @param string|null $username The username of the user performing the action
     */
    public function log($action, $tableName, $recordId, $description, $oldValues = null, $newValues = null, $userId = null, $username = null) {
        try {
            // Get IP address
            $ipAddress = $this->getIpAddress();
            
            // Get User Agent
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Get user info from session if not provided
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            if ($username === null && isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
            }
            
            // Convert arrays to JSON
            $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
            $newValuesJson = $newValues ? json_encode($newValues) : null;
            
            // Prepare and execute insert
            $stmt = $this->conn->prepare("
                INSERT INTO audit_logs 
                (user_id, username, action, table_name, record_id, description, old_values, new_values, ip_address, user_agent) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $username,
                $action,
                $tableName,
                $recordId,
                $description,
                $oldValuesJson,
                $newValuesJson,
                $ipAddress,
                $userAgent
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Audit Logger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a CREATE action
     */
    public function logCreate($tableName, $recordId, $description, $newValues = null, $userId = null, $username = null) {
        return $this->log('CREATE', $tableName, $recordId, $description, null, $newValues, $userId, $username);
    }

    /**
     * Log a READ/VIEW action
     */
    public function logRead($tableName, $recordId, $description = 'Record viewed', $userId = null, $username = null) {
        return $this->log('READ', $tableName, $recordId, $description, null, null, $userId, $username);
    }

    /**
     * Log an UPDATE action
     */
    public function logUpdate($tableName, $recordId, $description, $oldValues = null, $newValues = null, $userId = null, $username = null) {
        return $this->log('UPDATE', $tableName, $recordId, $description, $oldValues, $newValues, $userId, $username);
    }

    /**
     * Log a DELETE action
     */
    public function logDelete($tableName, $recordId, $description, $oldValues = null, $userId = null, $username = null) {
        return $this->log('DELETE', $tableName, $recordId, $description, $oldValues, null, $userId, $username);
    }

    /**
     * Get user's IP address
     */
    private function getIpAddress() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Get audit logs with filters
     */
    public function getLogs($filters = []) {
        $query = "SELECT * FROM audit_logs WHERE 1=1";
        $params = [];

        if (isset($filters['action']) && !empty($filters['action'])) {
            $query .= " AND action = ?";
            $params[] = $filters['action'];
        }

        if (isset($filters['table_name']) && !empty($filters['table_name'])) {
            $query .= " AND table_name = ?";
            $params[] = $filters['table_name'];
        }

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $query .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['username']) && !empty($filters['username'])) {
            $query .= " AND username = ?";
            $params[] = $filters['username'];
        }

        if (isset($filters['record_id']) && !empty($filters['record_id'])) {
            $query .= " AND record_id = ?";
            $params[] = $filters['record_id'];
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $query .= " ORDER BY created_at DESC";

        if (isset($filters['limit']) && !empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit log by ID
     */
    public function getLogById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM audit_logs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent logs for a specific user
     */
    public function getRecentLogsForUser($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM audit_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get logs for a specific table
     */
    public function getLogsForTable($tableName, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT * FROM audit_logs 
            WHERE table_name = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$tableName, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of logs by action type
     */
    public function getLogCountByAction() {
        $stmt = $this->conn->prepare("
            SELECT action, COUNT(*) as count 
            FROM audit_logs 
            GROUP BY action
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of logs by table
     */
    public function getLogCountByTable() {
        $stmt = $this->conn->prepare("
            SELECT table_name, COUNT(*) as count 
            FROM audit_logs 
            GROUP BY table_name
            ORDER BY count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get count of logs by user
     */
    public function getLogCountByUser() {
        $stmt = $this->conn->prepare("
            SELECT username, COUNT(*) as count 
            FROM audit_logs 
            WHERE username IS NOT NULL
            GROUP BY username
            ORDER BY count DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
