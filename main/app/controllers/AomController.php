<?php

class AomController extends MainController
{

    private function ensureAomDepartmentsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS aom_departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aom_id INT NOT NULL,
                department_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_aom_department (aom_id, department_id),
                INDEX idx_aom_id (aom_id),
                INDEX idx_department_id (department_id),
                CONSTRAINT fk_aom_departments_aom FOREIGN KEY (aom_id) REFERENCES aom_table(id) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_aom_departments_department FOREIGN KEY (department_id) REFERENCES neadept_table(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        $this->conn->exec($sql);
    }

    private function normalizeDepartmentIds($departmentIdsRaw, $fallbackDepartmentId = null)
    {
        $departmentIds = [];

        if (is_array($departmentIdsRaw)) {
            foreach ($departmentIdsRaw as $deptIdRaw) {
                $deptId = (int)$deptIdRaw;
                if ($deptId > 0) {
                    $departmentIds[] = $deptId;
                }
            }
        }

        $departmentIds = array_values(array_unique($departmentIds));

        if (empty($departmentIds) && !empty($fallbackDepartmentId) && (int)$fallbackDepartmentId > 0) {
            $departmentIds[] = (int)$fallbackDepartmentId;
        }

        return $departmentIds;
    }

    private function syncAomDepartments($aomId, $departmentIds)
    {
        $deleteStmt = $this->conn->prepare("DELETE FROM aom_departments WHERE aom_id = ?");
        $deleteStmt->execute([$aomId]);

        if (empty($departmentIds)) {
            return;
        }

        $insertStmt = $this->conn->prepare("INSERT INTO aom_departments (aom_id, department_id) VALUES (?, ?)");
        foreach ($departmentIds as $departmentId) {
            $insertStmt->execute([$aomId, $departmentId]);
        }
    }

    /**
     * Get all AOM records with department names
     */
    public function getAllAOM()
    {
        try {
            $this->ensureAomDepartmentsTable();
            $sql = "
                SELECT 
                    a.*,
                    COALESCE(da.department_names, d_fallback.name) as department_name,
                    COALESCE(da.department_acronyms, d_fallback.acronym) as department_acronym,
                    COALESCE(da.department_ids_csv, IF(a.department_id IS NOT NULL, CAST(a.department_id AS CHAR), '')) as department_ids_csv
                FROM aom_table a
                LEFT JOIN (
                    SELECT
                        ad.aom_id,
                        GROUP_CONCAT(DISTINCT d.id ORDER BY d.name SEPARATOR ',') as department_ids_csv,
                        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') as department_names,
                        GROUP_CONCAT(DISTINCT d.acronym ORDER BY d.name SEPARATOR ', ') as department_acronyms
                    FROM aom_departments ad
                    INNER JOIN neadept_table d ON ad.department_id = d.id
                    GROUP BY ad.aom_id
                ) da ON da.aom_id = a.id
                LEFT JOIN neadept_table d_fallback ON a.department_id = d_fallback.id
                ORDER BY a.date DESC, a.id DESC
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            error_log("Error fetching AOM records: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single AOM record by ID
     */
    public function getAOMById($id)
    {
        try {
            $this->ensureAomDepartmentsTable();
            $stmt = $this->conn->prepare("
                SELECT
                    a.*,
                    COALESCE(da.department_names, d_fallback.name) as department_name,
                    COALESCE(da.department_acronyms, d_fallback.acronym) as department_acronym,
                    COALESCE(da.department_ids_csv, IF(a.department_id IS NOT NULL, CAST(a.department_id AS CHAR), '')) as department_ids_csv
                FROM aom_table
                a
                LEFT JOIN (
                    SELECT
                        ad.aom_id,
                        GROUP_CONCAT(DISTINCT d.id ORDER BY d.name SEPARATOR ',') as department_ids_csv,
                        GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') as department_names,
                        GROUP_CONCAT(DISTINCT d.acronym ORDER BY d.name SEPARATOR ', ') as department_acronyms
                    FROM aom_departments ad
                    INNER JOIN neadept_table d ON ad.department_id = d.id
                    GROUP BY ad.aom_id
                ) da ON da.aom_id = a.id
                LEFT JOIN neadept_table d_fallback ON a.department_id = d_fallback.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return null;
            }

            $departmentIdsCsv = trim($record['department_ids_csv'] ?? '');
            $record['department_ids'] = $departmentIdsCsv !== ''
                ? array_values(array_filter(array_map('intval', explode(',', $departmentIdsCsv))))
                : [];

            return $record;
        }
        catch (Exception $e) {
            error_log("Error fetching AOM by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add new AOM record
     */
    public function addAOM()
    {
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);
            $this->ensureAomDepartmentsTable();

            // Validate required fields
            $item = trim($_POST['item'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $department_ids_raw = $_POST['department_ids'] ?? [];
            $title = trim($_POST['title'] ?? '');
            $coa_observation = trim($_POST['coa_observation'] ?? '');

            // Handle bulk recommendations/justifications
            $recs_raw = $_POST['coa_recommendation'] ?? [];
            $justs_raw = $_POST['comments_justification'] ?? [];

            if (is_array($recs_raw) || is_array($justs_raw)) {
                $recs = is_array($recs_raw) ? $recs_raw : [$recs_raw];
                $justs = is_array($justs_raw) ? $justs_raw : [$justs_raw];
                $maxLen = max(count($recs), count($justs));

                $finalRecsArr = [];
                $finalJustsArr = [];
                $hasAnyData = false;
                $lastIndexWithData = -1;

                for ($i = 0; $i < $maxLen; $i++) {
                    $r = isset($recs[$i]) ? trim($recs[$i]) : '';
                    $j = isset($justs[$i]) ? trim($justs[$i]) : '';
                    $finalRecsArr[$i] = $r;
                    $finalJustsArr[$i] = $j;
                    if ($r !== '' || $j !== '') {
                        $hasAnyData = true;
                        $lastIndexWithData = $i;
                    }
                }

                if (!$hasAnyData) {
                    $coa_recommendation = null;
                    $comments_justification = null;
                }
                else {
                    // Keep the sequence by slicing up to the last non-empty row
                    $finalRecsArr = array_slice($finalRecsArr, 0, $lastIndexWithData + 1);
                    $finalJustsArr = array_slice($finalJustsArr, 0, $lastIndexWithData + 1);
                    $coa_recommendation = json_encode($finalRecsArr);
                    $comments_justification = json_encode($finalJustsArr);
                }
            }
            else {
                $coa_recommendation = !empty(trim($recs_raw)) ? trim($recs_raw) : null;
                $comments_justification = !empty(trim($justs_raw)) ? trim($justs_raw) : null;
            }

            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }

            $departmentIds = $this->normalizeDepartmentIds($department_ids_raw, $department_id);
            $primaryDepartmentId = !empty($departmentIds) ? $departmentIds[0] : null;

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                INSERT INTO aom_table (item, date, department_id, title, coa_observation, coa_recommendation, comments_justification)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $item,
                !empty($date) ? $date : null,
                $primaryDepartmentId,
                $title,
                $coa_observation,
                $coa_recommendation,
                $comments_justification
            ]);

            $newId = $this->conn->lastInsertId();
            $this->syncAomDepartments($newId, $departmentIds);

            $this->conn->commit();

            // Log the action
            $auditLogger->log(
                'CREATE',
                'aom_table',
                $newId,
                'AOM record added: ' . $title
            );

            echo json_encode([
                'success' => true,
                'message' => 'AOM record added successfully',
                'id' => $newId,
                'department_count' => count($departmentIds)
            ]);
            exit;

        }
        catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error adding AOM: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error adding record: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Update AOM record
     */
    public function updateAOM()
    {
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);
            $this->ensureAomDepartmentsTable();

            $id = (int)($_POST['id'] ?? 0);
            $item = trim($_POST['item'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            $department_ids_raw = $_POST['department_ids'] ?? [];
            $departmentIds = $this->normalizeDepartmentIds($department_ids_raw, $department_id);
            $primaryDepartmentId = !empty($departmentIds) ? $departmentIds[0] : null;
            $title = trim($_POST['title'] ?? '');
            $coa_observation = trim($_POST['coa_observation'] ?? '');

            // Handle bulk recommendations/justifications
            $recs_raw = $_POST['coa_recommendation'] ?? [];
            $justs_raw = $_POST['comments_justification'] ?? [];

            if (is_array($recs_raw) || is_array($justs_raw)) {
                $recs = is_array($recs_raw) ? $recs_raw : [$recs_raw];
                $justs = is_array($justs_raw) ? $justs_raw : [$justs_raw];
                $maxLen = max(count($recs), count($justs));

                $finalRecsArr = [];
                $finalJustsArr = [];
                $hasAnyData = false;
                $lastIndexWithData = -1;

                for ($i = 0; $i < $maxLen; $i++) {
                    $r = isset($recs[$i]) ? trim($recs[$i]) : '';
                    $j = isset($justs[$i]) ? trim($justs[$i]) : '';
                    $finalRecsArr[$i] = $r;
                    $finalJustsArr[$i] = $j;
                    if ($r !== '' || $j !== '') {
                        $hasAnyData = true;
                        $lastIndexWithData = $i;
                    }
                }

                if (!$hasAnyData) {
                    $coa_recommendation = null;
                    $comments_justification = null;
                }
                else {
                    // Keep the sequence by slicing up to the last non-empty row
                    $finalRecsArr = array_slice($finalRecsArr, 0, $lastIndexWithData + 1);
                    $finalJustsArr = array_slice($finalJustsArr, 0, $lastIndexWithData + 1);
                    $coa_recommendation = json_encode($finalRecsArr);
                    $comments_justification = json_encode($finalJustsArr);
                }
            }
            else {
                $coa_recommendation = !empty(trim($recs_raw)) ? trim($recs_raw) : null;
                $comments_justification = !empty(trim($justs_raw)) ? trim($justs_raw) : null;
            }

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
                exit;
            }

            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit;
            }

            $this->conn->beginTransaction();

            // Update AOM record
            $stmt = $this->conn->prepare("
                UPDATE aom_table 
                SET 
                    item = ?,
                    date = ?,
                    department_id = ?,
                    title = ?,
                    coa_observation = ?,
                    coa_recommendation = ?,
                    comments_justification = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $item,
                !empty($date) ? $date : null,
                $primaryDepartmentId,
                $title,
                $coa_observation,
                $coa_recommendation,
                $comments_justification,
                $id
            ]);

            $this->syncAomDepartments($id, $departmentIds);
            $this->conn->commit();

            // Log the action
            $auditLogger->log(
                'UPDATE',
                'aom_table',
                $id,
                'AOM record updated: ' . $title
            );

            echo json_encode([
                'success' => true,
                'message' => 'AOM record updated successfully'
            ]);
            exit;

        }
        catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error updating AOM: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Delete AOM record
     */
    public function deleteAOM()
    {
        ob_clean();
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            $this->ensureAomDepartmentsTable();

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
                exit;
            }

            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Get record details for logging
            $getStmt = $this->conn->prepare("SELECT title FROM aom_table WHERE id = ?");
            $getStmt->execute([$id]);
            $record = $getStmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                exit;
            }

            $this->conn->beginTransaction();

            // Delete record
            $stmt = $this->conn->prepare("DELETE FROM aom_table WHERE id = ?");
            $stmt->execute([$id]);

            $this->conn->commit();

            // Log the action
            $auditLogger->log(
                'DELETE',
                'aom_table',
                $id,
                'AOM record deleted: ' . $record['title']
            );

            echo json_encode([
                'success' => true,
                'message' => 'AOM record deleted successfully'
            ]);
            exit;

        }
        catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error deleting AOM: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting record: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Get AOM record as JSON for editing
     */
    public function getAOMJSON()
    {
        ob_clean();
        header('Content-Type: application/json');

        try {
            $id = (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
                exit;
            }

            $record = $this->getAOMById($id);

            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $record
            ]);
            exit;

        }
        catch (Exception $e) {
            error_log("Error fetching AOM JSON: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error fetching record']);
            exit;
        }
    }
}
?>
