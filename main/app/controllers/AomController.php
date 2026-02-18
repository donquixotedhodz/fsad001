<?php

class AomController extends MainController
{

    /**
     * Get all AOM records with department names
     */
    public function getAllAOM()
    {
        try {
            $sql = "
                SELECT 
                    a.*,
                    d.name as department_name,
                    d.acronym as department_acronym
                FROM aom_table a
                LEFT JOIN neadept_table d ON a.department_id = d.id
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
            $stmt = $this->conn->prepare("
                SELECT *
                FROM aom_table
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
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

            // Validate required fields
            $item = trim($_POST['item'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
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

            // Insert new AOM record
            $stmt = $this->conn->prepare("
                INSERT INTO aom_table (item, date, department_id, title, coa_observation, coa_recommendation, comments_justification)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $item,
                !empty($date) ? $date : null,
                $department_id,
                $title,
                $coa_observation,
                $coa_recommendation,
                $comments_justification
            ]);

            $newId = $this->conn->lastInsertId();

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
                'id' => $newId
            ]);
            exit;

        }
        catch (Exception $e) {
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

            $id = (int)($_POST['id'] ?? 0);
            $item = trim($_POST['item'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
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
                $department_id,
                $title,
                $coa_observation,
                $coa_recommendation,
                $comments_justification,
                $id
            ]);

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

            // Delete record
            $stmt = $this->conn->prepare("DELETE FROM aom_table WHERE id = ?");
            $stmt->execute([$id]);

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
