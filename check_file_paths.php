<?php
require 'config.php';

// Get sample of documents with mismatched paths
$stmt = $conn->prepare("SELECT id, file_name, file_path FROM manap WHERE file_name LIKE '%AKELCO%' OR file_name LIKE 'Appendix%' LIMIT 10");
$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== Documents in Database ===\n";
foreach ($documents as $doc) {
    echo "ID: {$doc['id']}\n";
    echo "  Name: {$doc['file_name']}\n";
    echo "  Path: {$doc['file_path']}\n";
    
    // Check if file exists
    $fullPath = __DIR__ . '/SOMANAP/' . $doc['file_path'];
    $exists = file_exists($fullPath) ? 'EXISTS' : 'MISSING';
    echo "  Full Path: {$fullPath}\n";
    echo "  Status: {$exists}\n\n";
}

// List actual files in uploads
echo "\n=== Actual Files in Uploads Folder ===\n";
$uploadDir = __DIR__ . '/SOMANAP/uploads';
if (is_dir($uploadDir)) {
    $files = array_diff(scandir($uploadDir), ['.', '..']);
    foreach ($files as $file) {
        if (is_file($uploadDir . '/' . $file)) {
            echo $file . "\n";
        }
    }
}
?>
