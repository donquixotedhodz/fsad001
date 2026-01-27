<?php
require_once __DIR__ . '/config.php';

// Get all documents from database with their file paths
$stmt = $conn->prepare("
    SELECT 
        id,
        ec,
        item,
        file_name,
        file_path,
        created_at
    FROM manap 
    WHERE file_path IS NOT NULL AND file_path != ''
    ORDER BY created_at DESC
");

$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get actual files from uploads directory
$uploads_dir = __DIR__ . '/SOMANAP/uploads';
$actual_files = [];

if (is_dir($uploads_dir)) {
    $files = scandir($uploads_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($uploads_dir . '/' . $file)) {
            $actual_files[] = [
                'filename' => $file,
                'path' => 'SOMANAP/uploads/' . $file,
                'size' => filesize($uploads_dir . '/' . $file),
                'modified' => date('Y-m-d H:i:s', filemtime($uploads_dir . '/' . $file))
            ];
        }
    }
}

// Check for ppe subfolder
$ppe_dir = $uploads_dir . '/ppe';
if (is_dir($ppe_dir)) {
    $files = scandir($ppe_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($ppe_dir . '/' . $file)) {
            $actual_files[] = [
                'filename' => $file,
                'path' => 'SOMANAP/uploads/ppe/' . $file,
                'size' => filesize($ppe_dir . '/' . $file),
                'modified' => date('Y-m-d H:i:s', filemtime($ppe_dir . '/' . $file))
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Locations</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 30px; }
        .section { background: white; padding: 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { color: #0066cc; margin-bottom: 15px; font-size: 1.3em; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f9f9f9; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; font-weight: 600; color: #333; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .path { font-family: 'Courier New', monospace; color: #0066cc; word-break: break-all; }
        .size { text-align: right; color: #666; }
        .info { background: #e8f4f8; padding: 12px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #0066cc; }
        .stat { display: inline-block; margin-right: 30px; }
        .stat-number { font-size: 1.5em; font-weight: bold; color: #0066cc; }
        .stat-label { color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Document Storage Locations</h1>
        
        <div class="section">
            <div class="info">
                <div class="stat">
                    <div class="stat-number"><?php echo count($documents); ?></div>
                    <div class="stat-label">Documents in Database</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo count($actual_files); ?></div>
                    <div class="stat-label">Files in Uploads Folder</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>📂 Documents from Database (with file paths)</h2>
            <?php if (!empty($documents)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>File Name</th>
                            <th>File Path</th>
                            <th>Uploaded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo $doc['id']; ?></td>
                                <td><?php echo htmlspecialchars($doc['file_name']); ?></td>
                                <td class="path"><?php echo htmlspecialchars($doc['file_path']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($doc['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666;">No documents found in database.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>📄 Actual Files in Uploads Folder</h2>
            <?php if (!empty($actual_files)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Full Path</th>
                            <th>Size (MB)</th>
                            <th>Last Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($actual_files as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['filename']); ?></td>
                                <td class="path"><?php echo htmlspecialchars($file['path']); ?></td>
                                <td class="size"><?php echo round($file['size'] / 1024 / 1024, 2); ?></td>
                                <td><?php echo $file['modified']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666;">No files found in uploads folder.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>📍 Upload Directory Information</h2>
            <p><strong>Base Upload Directory:</strong></p>
            <p class="path"><?php echo $uploads_dir; ?></p>
            <p><strong>Relative Web Path:</strong></p>
            <p class="path">SOMANAP/uploads/</p>
            <p><strong>Directory Status:</strong> <?php echo is_dir($uploads_dir) ? '✅ Exists' : '❌ Does not exist'; ?></p>
        </div>
    </div>
</body>
</html>
