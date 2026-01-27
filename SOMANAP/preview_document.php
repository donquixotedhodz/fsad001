<?php
/**
 * Document Preview Handler
 * Handles previewing of PDF and other document files
 * Supports streaming PDF files and displaying other file types
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/DocumentController.php';

// Verify user is authenticated
MainController::requireAuth();

// Get document ID from request
$documentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$documentId) {
    http_response_code(400);
    die('Invalid document ID');
}

try {
    // Query to fetch document details from manap table
    $stmt = $conn->prepare("
        SELECT 
            id, 
            file_path, 
            file_name,
            created_at
        FROM manap 
        WHERE id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        http_response_code(404);
        die('Document not found');
    }
    
    // Verify file exists
    $filePath = __DIR__ . '/' . $document['file_path'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found: ' . htmlspecialchars($document['file_name']));
    }
    
    // Get file extension
    $fileExt = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));
    
    // Define allowed preview types
    $previewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    
    // Check if file can be previewed
    if (!in_array($fileExt, $previewableTypes)) {
        http_response_code(400);
        die('File type cannot be previewed: ' . htmlspecialchars($fileExt));
    }
    
    // Set appropriate headers for preview
    $mimeTypes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'bmp'  => 'image/bmp'
    ];
    
    $mimeType = $mimeTypes[$fileExt] ?? 'application/octet-stream';
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for file display (inline - browser will display if possible)
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . basename($document['file_name']) . '"');
    header('Cache-Control: public, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
    
    // Prevent caching for security
    header('Pragma: public');
    
    // Read and output file in chunks for large files
    $chunkSize = 1024 * 1024; // 1MB chunks
    $handle = fopen($filePath, 'rb');
    
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }
        fclose($handle);
    } else {
        http_response_code(500);
        die('Unable to open file for reading');
    }
    
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . htmlspecialchars($e->getMessage()));
}
?>
