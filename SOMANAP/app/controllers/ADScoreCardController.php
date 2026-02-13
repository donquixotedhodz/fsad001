<?php

class ADScoreCardController extends MainController {
    
    /**
     * Get all audit decision scorecards
     */
    public function getAllADS() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    id,
                    audit_report,
                    adsyear,
                    scope,
                    bac_date,
                    bac_reso,
                    boa_date,
                    boa_reso,
                    remarks,
                    created_at,
                    updated_at
                FROM ads
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            error_log("Error fetching ADS: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single ADS record by ID
     */
    public function getADSById($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    id,
                    audit_report,
                    adsyear,
                    scope,
                    bac_date,
                    bac_reso,
                    boa_date,
                    boa_reso,
                    remarks,
                    created_at,
                    updated_at
                FROM ads
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            error_log("Error fetching ADS by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add new audit decision scorecard record
     */
    public function addADS() {
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
            $audit_report = trim($_POST['audit_report'] ?? '');
            $adsyear = trim($_POST['adsyear'] ?? '');
            $scope = trim($_POST['scope'] ?? '');
            $bac_date = trim($_POST['bac_date'] ?? '');
            $bac_reso = trim($_POST['bac_reso'] ?? '');
            $boa_date = trim($_POST['boa_date'] ?? '');
            $boa_reso = trim($_POST['boa_reso'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');

            if (empty($audit_report)) {
                echo json_encode(['success' => false, 'message' => 'Audit Report is required']);
                exit;
            }

            // Insert new ADS record
            $stmt = $this->conn->prepare("
                INSERT INTO ads (audit_report, adsyear, scope, bac_date, bac_reso, boa_date, boa_reso, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $audit_report,
                !empty($adsyear) ? $adsyear : null,
                $scope,
                !empty($bac_date) ? $bac_date : null,
                $bac_reso,
                !empty($boa_date) ? $boa_date : null,
                $boa_reso,
                $remarks
            ]);

            $newId = $this->conn->lastInsertId();

            // Log the action
            $auditLogger->log(
                'add_ads',
                'Audit Decision Scorecard added: ' . $audit_report,
                'ads',
                $newId
            );

            echo json_encode([
                'success' => true,
                'message' => 'Audit Decision Scorecard added successfully',
                'id' => $newId
            ]);
            exit;

        } catch(Exception $e) {
            error_log("Error adding ADS: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error adding record: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Update ADS record
     */
    public function updateADS() {
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
            $audit_report = trim($_POST['audit_report'] ?? '');
            $adsyear = trim($_POST['adsyear'] ?? '');
            $scope = trim($_POST['scope'] ?? '');
            $bac_date = trim($_POST['bac_date'] ?? '');
            $bac_reso = trim($_POST['bac_reso'] ?? '');
            $boa_date = trim($_POST['boa_date'] ?? '');
            $boa_reso = trim($_POST['boa_reso'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
                exit;
            }

            if (empty($audit_report)) {
                echo json_encode(['success' => false, 'message' => 'Audit Report is required']);
                exit;
            }

            // Update ADS record
            $stmt = $this->conn->prepare("
                UPDATE ads 
                SET 
                    audit_report = ?,
                    adsyear = ?,
                    scope = ?,
                    bac_date = ?,
                    bac_reso = ?,
                    boa_date = ?,
                    boa_reso = ?,
                    remarks = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $audit_report,
                !empty($adsyear) ? $adsyear : null,
                $scope,
                !empty($bac_date) ? $bac_date : null,
                $bac_reso,
                !empty($boa_date) ? $boa_date : null,
                $boa_reso,
                $remarks,
                $id
            ]);

            // Log the action
            $auditLogger->log(
                'edit_ads',
                'Audit Decision Scorecard updated: ' . $audit_report,
                'ads',
                $id
            );

            echo json_encode([
                'success' => true,
                'message' => 'Audit Scorecard updated successfully'
            ]);
            exit;

        } catch(Exception $e) {
            error_log("Error updating ADS: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Delete ADS record
     */
    public function deleteADS() {
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
            $getStmt = $this->conn->prepare("SELECT audit_report FROM ads WHERE id = ?");
            $getStmt->execute([$id]);
            $record = $getStmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                exit;
            }

            // Delete record
            $stmt = $this->conn->prepare("DELETE FROM ads WHERE id = ?");
            $stmt->execute([$id]);

            // Log the action
            $auditLogger->log(
                'delete_ads',
                'Audit Decision Scorecard deleted: ' . $record['audit_report'],
                'ads',
                $id
            );

            echo json_encode([
                'success' => true,
                'message' => 'Audit Decision Scorecard deleted successfully'
            ]);
            exit;

        } catch(Exception $e) {
            error_log("Error deleting ADS: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error deleting record: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Get ADS record as JSON for editing
     */
    public function getADSJSON() {
        ob_clean();
        header('Content-Type: application/json');

        try {
            $id = (int)($_GET['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
                exit;
            }

            $record = $this->getADSById($id);

            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'data' => $record
            ]);
            exit;

        } catch(Exception $e) {
            error_log("Error fetching ADS JSON: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error fetching record']);
            exit;
        }
    }
}
?>
