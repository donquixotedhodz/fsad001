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
                        INSERT INTO manap (ec, item, recommending_approvals, approving_authority, control_point, file_path, file_name)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");

                    $result = $stmt->execute([
                        $ec,
                        $item,
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
     * Get all documents
     */
    public function getAllDocuments() {
        $stmt = $this->conn->prepare("SELECT * FROM manap ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
