<?php

class DocumentController extends MainController {
    
    /**
     * Handle document upload
     */
    public function uploadDocument() {
        // Clear any previous output
        ob_clean();
        
        // Set JSON header FIRST before any processing
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            // Initialize audit logger
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            // Validate required fields
            $ec = $_POST['ec'] ?? '';
            $department = $_POST['department'] ?? '';
            $team = $_POST['team'] ?? '';
            $items = $_POST['items'] ?? [];
            $recommending_approvals_list = $_POST['recommending_approvals_list'] ?? [];
            $approving_authority_list = $_POST['approving_authority_list'] ?? [];
            $control_points = $_POST['control_points'] ?? [];

            if (empty($ec) || empty($items)) {
                echo json_encode(['success' => false, 'message' => 'EC and at least one Item are required']);
                exit;
            }

            // Ensure arrays
            if (!is_array($items)) {
                $items = [$items];
            }
            if (!is_array($recommending_approvals_list)) {
                $recommending_approvals_list = [$recommending_approvals_list];
            }
            if (!is_array($approving_authority_list)) {
                $approving_authority_list = [$approving_authority_list];
            }
            if (!is_array($control_points)) {
                $control_points = [$control_points];
            }

            // Insert new items if provided and don't exist
            foreach ($items as $item) {
                if (!empty($item)) {
                    try {
                        $checkStmt = $this->conn->prepare("SELECT id FROM items WHERE name = ?");
                        $checkStmt->execute([$item]);
                        if ($checkStmt->rowCount() === 0) {
                            $insertStmt = $this->conn->prepare("INSERT INTO items (name, description) VALUES (?, ?)");
                            $insertStmt->execute([$item, 'Added via document upload']);
                        }
                    } catch(Exception $e) {
                        // Continue even if insert fails - item might already exist or table might have constraints
                    }
                }
            }

            // Insert new recommending approvals if provided and don't exist
            foreach ($recommending_approvals_list as $approval) {
                if (!empty($approval)) {
                    try {
                        $checkStmt = $this->conn->prepare("SELECT id FROM recommending_approvals WHERE name = ?");
                        $checkStmt->execute([$approval]);
                        if ($checkStmt->rowCount() === 0) {
                            $insertStmt = $this->conn->prepare("INSERT INTO recommending_approvals (name, description) VALUES (?, ?)");
                            $insertStmt->execute([$approval, 'Added via document upload']);
                        }
                    } catch(Exception $e) {
                        // Continue even if insert fails
                    }
                }
            }

            // Insert new approving authorities if provided and don't exist
            foreach ($approving_authority_list as $authority) {
                if (!empty($authority)) {
                    try {
                        $checkStmt = $this->conn->prepare("SELECT id FROM approving_authority WHERE name = ?");
                        $checkStmt->execute([$authority]);
                        if ($checkStmt->rowCount() === 0) {
                            $insertStmt = $this->conn->prepare("INSERT INTO approving_authority (name, description) VALUES (?, ?)");
                            $insertStmt->execute([$authority, 'Added via document upload']);
                        }
                    } catch(Exception $e) {
                        // Continue even if insert fails
                    }
                }
            }

            // Insert new departments if provided and don't exist
            if (!empty($department)) {
                $deptItems = array_filter(array_map('trim', explode("\n", $department)));
                foreach ($deptItems as $dept) {
                    // Remove numbering if exists (e.g., "1. Operations" -> "Operations")
                    $dept = preg_replace('/^\d+\.\s+/', '', $dept);
                    if (!empty($dept)) {
                        try {
                            $checkStmt = $this->conn->prepare("SELECT id FROM departments WHERE name = ?");
                            $checkStmt->execute([$dept]);
                            if ($checkStmt->rowCount() === 0) {
                                $insertStmt = $this->conn->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                                $insertStmt->execute([$dept, 'Added via document upload']);
                            }
                        } catch(Exception $e) {
                            // Continue even if insert fails
                        }
                    }
                }
            }

            // Insert new teams if provided and don't exist
            if (!empty($team)) {
                $teamItems = array_filter(array_map('trim', explode("\n", $team)));
                foreach ($teamItems as $t) {
                    // Remove numbering if exists (e.g., "1. Team A" -> "Team A")
                    $t = preg_replace('/^\d+\.\s+/', '', $t);
                    if (!empty($t)) {
                        try {
                            $checkStmt = $this->conn->prepare("SELECT id FROM teams WHERE name = ?");
                            $checkStmt->execute([$t]);
                            if ($checkStmt->rowCount() === 0) {
                                $insertStmt = $this->conn->prepare("INSERT INTO teams (name, description) VALUES (?, ?)");
                                $insertStmt->execute([$t, 'Added via document upload']);
                            }
                        } catch(Exception $e) {
                            // Continue even if insert fails
                        }
                    }
                }
            }

            // Check if files are uploaded
            if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
                echo json_encode(['success' => false, 'message' => 'No files uploaded']);
                exit;
            }

            // Get uploaded files
            $files = $_FILES['files'];
            $uploadedCount = 0;
            $totalFiles = count($files['name']);

            // Create uploads directory if it doesn't exist
            $uploads_dir = __DIR__ . '/../../uploads';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }

            // Allowed file types
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.ms-excel', 
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                             'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                             'image/jpeg', 'image/png', 'image/gif'];

            // Process each file
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($files['error'][$i] != 0) {
                    continue;
                }

                $file_name = basename($files['name'][$i]);
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];
                $file_tmp = $files['tmp_name'][$i];

                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    continue;
                }

                // Validate file size (max 50MB)
                if ($file_size > 50 * 1024 * 1024) {
                    continue;
                }

                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_filename = uniqid() . '_' . time() . '_' . $i . '.' . $file_extension;
                $file_path = $uploads_dir . '/' . $unique_filename;

                if (!move_uploaded_file($file_tmp, $file_path)) {
                    continue;
                }

                // Store relative path for database
                $relative_path = 'uploads/' . $unique_filename;

                // Concatenate all control points into a numbered list format (with line breaks)
                $filtered_cp = array_filter($control_points);
                $numbered_cp = [];
                $count = 1;
                foreach ($filtered_cp as $cp) {
                    $numbered_cp[] = $count . '. ' . $cp;
                    $count++;
                }
                $all_control_points = implode("\n", $numbered_cp);

                // Create entries with parallel mapping: item[i] with approval[i] and authority[i]
                // All items get the same control points and file
                $maxCount = max(count($items), count($recommending_approvals_list), count($approving_authority_list));
                
                if ($maxCount == 0) $maxCount = 1; // Ensure at least one record if items exist
                
                for ($idx = 0; $idx < $maxCount; $idx++) {
                    $item = isset($items[$idx]) ? $items[$idx] : (isset($items[0]) ? $items[0] : '');
                    $rec_approval = isset($recommending_approvals_list[$idx]) ? $recommending_approvals_list[$idx] : '';
                    $app_authority = isset($approving_authority_list[$idx]) ? $approving_authority_list[$idx] : '';

                    // Insert record with parallel mapping
                    $stmt = $this->conn->prepare("
                        INSERT INTO manap (ec, item, department, team, recommending_approvals, approving_authority, control_point, file_path, file_name)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $ec,
                        $item,
                        $department,
                        $team,
                        $rec_approval,
                        $app_authority,
                        $all_control_points,
                        $relative_path,
                        $file_name
                    ]);

                    if ($result) {
                        $lastId = $this->conn->lastInsertId();
                        $uploadedCount++;
                        
                        // Create comprehensive description
                        $itemNames = implode(', ', array_filter($items));
                        $approvalNames = implode(', ', array_filter($recommending_approvals_list));
                        $authorityNames = implode(', ', array_filter($approving_authority_list));
                        
                        $description = "Document uploaded: '{$file_name}' | ";
                        $description .= "Electric Cooperative: {$ec} | ";
                        $description .= "Items: {$itemNames} | ";
                        if (!empty($approvalNames)) {
                            $description .= "Recommending Approvals: {$approvalNames} | ";
                        }
                        if (!empty($authorityNames)) {
                            $description .= "Approving Authority: {$authorityNames} | ";
                        }
                        $description .= "File: {$file_name}";
                        
                        // Log the document creation with full details
                        $documentData = [
                            'ec' => $ec,
                            'item' => $item,
                            'recommending_approvals' => $rec_approval,
                            'approving_authority' => $app_authority,
                            'file_name' => $file_name,
                            'file_size' => $file_size,
                            'items_list' => $items,
                            'approvals_list' => $recommending_approvals_list,
                            'authority_list' => $approving_authority_list
                        ];
                        $auditLogger->logCreate('manap', $lastId, $description, $documentData);
                    }
                }
            }

            if ($uploadedCount > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => $uploadedCount . ' document(s) uploaded successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload documents']);
            }

        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Handle document deletion
     */
    public function deleteDocument() {
        // Clear any previous output
        ob_clean();
        
        // Set JSON header FIRST
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        // Check role authorization - only administrator and superadmin can delete
        if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'administrator' && $_SESSION['role'] !== 'superadmin')) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete documents']);
            exit;
        }

        try {
            // Initialize audit logger
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                echo json_encode(['success' => false, 'message' => 'Document ID is required']);
                exit;
            }

            // Get document info first
            $stmt = $this->conn->prepare("SELECT * FROM manap WHERE id = ?");
            $stmt->execute([$data['id']]);
            $document = $stmt->fetch();

            if (!$document) {
                echo json_encode(['success' => false, 'message' => 'Document not found']);
                exit;
            }

            // Delete file if it exists
            if (!empty($document['file_path'])) {
                $file_path = __DIR__ . '/../../' . $document['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Delete from database
            $stmt = $this->conn->prepare("DELETE FROM manap WHERE id = ?");
            $result = $stmt->execute([$data['id']]);

            if ($result) {
                // Create comprehensive description
                $description = "Document deleted: '{$document['file_name']}' | ";
                $description .= "Electric Cooperative: {$document['ec']} | ";
                $description .= "Item: {$document['item']} | ";
                if (!empty($document['recommending_approvals'])) {
                    $description .= "Recommending Approvals: {$document['recommending_approvals']} | ";
                }
                if (!empty($document['approving_authority'])) {
                    $description .= "Approving Authority: {$document['approving_authority']} | ";
                }
                $description .= "Created at: {$document['created_at']}";
                
                // Log the document deletion with complete details
                $documentData = [
                    'id' => $document['id'],
                    'ec' => $document['ec'],
                    'item' => $document['item'],
                    'file_name' => $document['file_name'],
                    'file_path' => $document['file_path'],
                    'recommending_approvals' => $document['recommending_approvals'],
                    'approving_authority' => $document['approving_authority'],
                    'control_point' => $document['control_point'],
                    'created_at' => $document['created_at']
                ];
                $auditLogger->logDelete('manap', $data['id'], $description, $documentData);
                
                echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete document']);
            }

        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Edit document
     */
    public function editDocument() {
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        // Check role authorization
        if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'administrator' && $_SESSION['role'] !== 'superadmin')) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to edit documents']);
            exit;
        }

        try {
            require_once __DIR__ . '/../helpers/AuditLogger.php';
            $auditLogger = new AuditLogger($this->conn);

            $docId = $_POST['doc_id'] ?? '';
            $ec = $_POST['ec'] ?? '';
            $items = $_POST['items'] ?? '';
            $recommending_approvals = $_POST['recommending_approvals'] ?? '';
            $approving_authority = $_POST['approving_authority'] ?? '';
            $control_point = $_POST['control_point'] ?? '';
            $department = $_POST['department'] ?? '';
            $team = $_POST['team'] ?? '';

            if (empty($docId) || empty($ec) || empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
                exit;
            }

            // Get old document data for audit
            $stmt = $this->conn->prepare("SELECT * FROM manap WHERE id = ?");
            $stmt->execute([$docId]);
            $oldDocument = $stmt->fetch();

            if (!$oldDocument) {
                echo json_encode(['success' => false, 'message' => 'Document not found']);
                exit;
            }

            // Auto-save departments if provided
            if (!empty($department)) {
                $deptItems = array_filter(array_map('trim', explode("\n", $department)));
                foreach ($deptItems as $dept) {
                    $dept = preg_replace('/^\d+\.\s+/', '', $dept);
                    if (!empty($dept)) {
                        try {
                            $checkStmt = $this->conn->prepare("SELECT id FROM departments WHERE name = ?");
                            $checkStmt->execute([$dept]);
                            if ($checkStmt->rowCount() === 0) {
                                $insertStmt = $this->conn->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
                                $insertStmt->execute([$dept, 'Added via document edit']);
                            }
                        } catch(Exception $e) {}
                    }
                }
            }

            // Auto-save teams if provided
            if (!empty($team)) {
                $teamItems = array_filter(array_map('trim', explode("\n", $team)));
                foreach ($teamItems as $t) {
                    $t = preg_replace('/^\d+\.\s+/', '', $t);
                    if (!empty($t)) {
                        try {
                            $checkStmt = $this->conn->prepare("SELECT id FROM teams WHERE name = ?");
                            $checkStmt->execute([$t]);
                            if ($checkStmt->rowCount() === 0) {
                                $insertStmt = $this->conn->prepare("INSERT INTO teams (name, description) VALUES (?, ?)");
                                $insertStmt->execute([$t, 'Added via document edit']);
                            }
                        } catch(Exception $e) {}
                    }
                }
            }

            // Handle file upload if provided
            $newFilePath = null;
            if (!empty($_FILES['edit_file']['tmp_name'])) {
                // Delete old file if it exists
                if (!empty($oldDocument['file_path'])) {
                    $oldFile = __DIR__ . '/../../' . $oldDocument['file_path'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Upload new file
                $uploadDir = __DIR__ . '/../../uploads/';
                $fileName = basename($_FILES['edit_file']['name']);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Allow specific file types
                $allowedExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'ppt', 'pptx'];
                if (!in_array($fileExt, $allowedExts)) {
                    echo json_encode(['success' => false, 'message' => 'File type not allowed']);
                    exit;
                }

                // Check file size (50MB max)
                if ($_FILES['edit_file']['size'] > 50 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File size exceeds 50MB limit']);
                    exit;
                }

                // Generate unique filename
                $uniqueFileName = uniqid() . '_' . time() . '_0.' . $fileExt;
                $uploadPath = $uploadDir . $uniqueFileName;
                $relativePath = 'uploads/' . $uniqueFileName;

                if (move_uploaded_file($_FILES['edit_file']['tmp_name'], $uploadPath)) {
                    $newFilePath = $relativePath;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
                    exit;
                }
            }

            // Update document
            $updateFields = [
                'ec' => $ec,
                'item' => $items,
                'department' => $department,
                'team' => $team,
                'recommending_approvals' => $recommending_approvals,
                'approving_authority' => $approving_authority,
                'control_point' => $control_point
            ];

            // Add file path to update if new file uploaded
            if ($newFilePath) {
                $updateFields['file_path'] = $newFilePath;
                // Update filename if available
                if (!empty($_FILES['edit_file']['name'])) {
                    $updateFields['file_name'] = $_FILES['edit_file']['name'];
                }
            }

            // Build SQL query dynamically
            $setClauses = [];
            $values = [];
            foreach ($updateFields as $field => $value) {
                $setClauses[] = "$field = ?";
                $values[] = $value;
            }
            $values[] = $docId; // Add ID for WHERE clause

            $stmt = $this->conn->prepare("
                UPDATE manap SET " . implode(', ', $setClauses) . "
                WHERE id = ?
            ");

            $result = $stmt->execute($values);

            if ($result) {
                // Create audit log
                $description = "Document updated: '{$oldDocument['file_name']}' | ";
                $description .= "EC: {$oldDocument['ec']} → {$ec} | ";
                $description .= "Item: {$oldDocument['item']} → {$items}";

                $documentData = [
                    'id' => $docId,
                    'old_ec' => $oldDocument['ec'],
                    'new_ec' => $ec,
                    'old_item' => $oldDocument['item'],
                    'new_item' => $items,
                    'old_department' => $oldDocument['department'],
                    'new_department' => $department,
                    'old_team' => $oldDocument['team'],
                    'new_team' => $team,
                    'file_name' => $oldDocument['file_name']
                ];
                $auditLogger->logUpdate('manap', $docId, $description, $documentData);

                echo json_encode(['success' => true, 'message' => 'Document updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update document']);
            }

        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get all documents
     */
    public function getAllDocuments() {
        $stmt = $this->conn->prepare("SELECT * FROM manap ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
