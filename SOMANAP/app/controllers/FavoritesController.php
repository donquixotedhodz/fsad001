<?php

class FavoritesController extends MainController {
    
    /**
     * Toggle document favorite status
     */
    public function toggleFavorite() {
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            $documentId = $_POST['document_id'] ?? '';

            if (empty($userId) || empty($documentId)) {
                echo json_encode(['success' => false, 'message' => 'User ID and Document ID are required']);
                exit;
            }

            // Check if favorite already exists
            $stmt = $this->conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND document_id = ?");
            $stmt->execute([$userId, $documentId]);

            if ($stmt->rowCount() > 0) {
                // Remove from favorites
                $deleteStmt = $this->conn->prepare("DELETE FROM favorites WHERE user_id = ? AND document_id = ?");
                $result = $deleteStmt->execute([$userId, $documentId]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Removed from favorites', 'isFavorite' => false]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
                }
            } else {
                // Add to favorites
                $insertStmt = $this->conn->prepare("INSERT INTO favorites (user_id, document_id, created_at) VALUES (?, ?, NOW())");
                $result = $insertStmt->execute([$userId, $documentId]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Added to favorites', 'isFavorite' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add to favorites']);
                }
            }

        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if document is favorite
     */
    public function isFavorite($documentId) {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (empty($userId) || empty($documentId)) {
            return false;
        }

        $stmt = $this->conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND document_id = ?");
        $stmt->execute([$userId, $documentId]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all favorited documents for the current user
     */
    public function getFavoritedDocuments() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (empty($userId)) {
            return [];
        }

        $stmt = $this->conn->prepare("
            SELECT m.* FROM manap m
            INNER JOIN favorites f ON m.id = f.document_id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Remove favorite
     */
    public function removeFavorite() {
        ob_clean();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            // Read JSON data from request body
            $data = json_decode(file_get_contents('php://input'), true);
            $documentId = $data['id'] ?? '';

            if (empty($userId) || empty($documentId)) {
                echo json_encode(['success' => false, 'message' => 'User ID and Document ID are required']);
                exit;
            }

            $stmt = $this->conn->prepare("DELETE FROM favorites WHERE user_id = ? AND document_id = ?");
            $result = $stmt->execute([$userId, $documentId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites']);
            }

        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}

?>
