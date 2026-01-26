<?php
// Connect to database
try {
    $conn = new PDO('mysql:host=localhost;dbname=neafsad', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get last 5 records
    $stmt = $conn->query("SELECT id, ec, item, recommending_approvals, approving_authority, control_point, file_name FROM manap ORDER BY id DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Last 5 Documents Uploaded</h2>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr><th>ID</th><th>EC</th><th>Item</th><th>Recommending Approvals</th><th>Approving Authority</th><th>Control Points</th><th>File</th></tr>";
    
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>" . $record['id'] . "</td>";
        echo "<td>" . htmlspecialchars($record['ec']) . "</td>";
        echo "<td>" . htmlspecialchars($record['item']) . "</td>";
        echo "<td>" . htmlspecialchars($record['recommending_approvals'] ?? 'EMPTY') . "</td>";
        echo "<td>" . htmlspecialchars($record['approving_authority'] ?? 'EMPTY') . "</td>";
        echo "<td>" . htmlspecialchars(substr($record['control_point'], 0, 50) . '...') . "</td>";
        echo "<td>" . htmlspecialchars($record['file_name']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
