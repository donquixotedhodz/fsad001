<?php

class FaqController
{
    protected $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getFaqCount($search = '')
    {
        try {
            $query = "SELECT COUNT(*) AS total FROM faq";
            $params = [];

            if (!empty($search)) {
                $query .= " WHERE category LIKE ? OR question LIKE ? OR answer LIKE ?";
                $searchPattern = '%' . $search . '%';
                $params = [$searchPattern, $searchPattern, $searchPattern];
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['total'] ?? 0);
        }
        catch (Exception $e) {
            throw new Exception("Error counting FAQ records: " . $e->getMessage());
        }
    }

    public function getAllFaq($limit = null, $offset = 0, $search = '')
    {
        try {
            $query = "SELECT id, category, question, answer, display_order, is_active, created_at, updated_at FROM faq";
            $params = [];

            if (!empty($search)) {
                $query .= " WHERE category LIKE ? OR question LIKE ? OR answer LIKE ?";
                $searchPattern = '%' . $search . '%';
                $params = [$searchPattern, $searchPattern, $searchPattern];
            }

            $query .= " ORDER BY display_order ASC, id ASC";

            if ($limit !== null && $limit !== 'all') {
                $query .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            throw new Exception("Error fetching FAQ records: " . $e->getMessage());
        }
    }

    public function getFaqById($id)
    {
        try {
            $stmt = $this->conn->prepare("SELECT id, category, question, answer, display_order, is_active FROM faq WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            throw new Exception("Error fetching FAQ record: " . $e->getMessage());
        }
    }

    public function addFaq($category, $question, $answer, $displayOrder = 1, $isActive = 1)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            $stmt = $this->conn->prepare("SELECT id FROM faq WHERE question = ?");
            $stmt->execute([$question]);
            if ($stmt->fetch()) {
                throw new Exception("A FAQ with the same question already exists");
            }

            $insertStmt = $this->conn->prepare("INSERT INTO faq (category, question, answer, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([$category, $question, $answer, $displayOrder, $isActive]);

            $newId = $this->conn->lastInsertId();

            $auditLogger->logCreate(
                'faq',
                $newId,
                "New FAQ added: {$question}",
                [
                    'category' => $category,
                    'question' => $question,
                    'answer' => $answer,
                    'display_order' => (int) $displayOrder,
                    'is_active' => (int) $isActive
                ]
            );

            return $newId;
        }
        catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateFaq($id, $category, $question, $answer, $displayOrder = 1, $isActive = 1)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            $oldData = $this->getFaqById($id);
            if (!$oldData) {
                throw new Exception("FAQ record not found");
            }

            $stmt = $this->conn->prepare("SELECT id FROM faq WHERE question = ? AND id != ?");
            $stmt->execute([$question, $id]);
            if ($stmt->fetch()) {
                throw new Exception("A FAQ with the same question already exists");
            }

            $updateStmt = $this->conn->prepare("UPDATE faq SET category = ?, question = ?, answer = ?, display_order = ?, is_active = ? WHERE id = ?");
            $updateStmt->execute([$category, $question, $answer, $displayOrder, $isActive, $id]);

            $newData = $this->getFaqById($id);

            $auditLogger->logUpdate(
                'faq',
                $id,
                "FAQ updated: {$question}",
                $oldData,
                $newData
            );

            return true;
        }
        catch (Exception $e) {
            throw new Exception("Error updating FAQ: " . $e->getMessage());
        }
    }

    public function deleteFaq($id)
    {
        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            $oldData = $this->getFaqById($id);
            if (!$oldData) {
                throw new Exception("FAQ record not found");
            }

            $stmt = $this->conn->prepare("DELETE FROM faq WHERE id = ?");
            $stmt->execute([$id]);

            $auditLogger->logDelete(
                'faq',
                $id,
                "FAQ deleted: {$oldData['question']}",
                $oldData
            );

            return true;
        }
        catch (Exception $e) {
            throw new Exception("Error deleting FAQ: " . $e->getMessage());
        }
    }
}

?>
