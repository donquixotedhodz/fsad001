<?php

class AuditLogsController extends MainController
{
    private $auditLogger;

    public function __construct($db)
    {
        parent::__construct($db);
        require_once __DIR__ . '/../helpers/AuditLogger.php';
        $this->auditLogger = new AuditLogger($db);
    }

    public function getLogs($filters = [], $limit = 10, $offset = 0)
    {
        $filters['limit'] = $limit;
        $filters['offset'] = $offset;
        return $this->auditLogger->getLogs($filters);
    }

    public function getLogCount($filters = [])
    {
        return $this->auditLogger->getLogCount($filters);
    }

    public function getLogById($id)
    {
        return $this->auditLogger->getLogById($id);
    }

    public function getAvailableActions()
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableTables()
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableUsers()
    {
        $stmt = $this->conn->prepare("SELECT DISTINCT username FROM audit_logs WHERE username IS NOT NULL ORDER BY username ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
