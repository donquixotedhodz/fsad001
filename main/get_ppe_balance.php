<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();

try {
    // Fetch the latest balance from PPE table
    $stmt = $conn->prepare("SELECT balance FROM ppe ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $balance = $lastRecord ? floatval($lastRecord['balance']) : 0;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'balance' => $balance
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
