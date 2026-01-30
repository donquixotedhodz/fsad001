<?php
require_once 'config.php';

try {
    echo "=== SAMPLE DATA FROM MANAP ===\n";
    $stmt = $conn->prepare('SELECT m.id, m.ec, m.file_name FROM manap m LIMIT 20');
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        echo "Doc ID: {$row['id']}, EC: {$row['ec']}\n";
    }
    
    echo "\n=== COUNT BY EC NAME (raw from manap) ===\n";
    $stmt2 = $conn->prepare('SELECT m.ec, COUNT(*) as count FROM manap m GROUP BY m.ec ORDER BY count DESC');
    $stmt2->execute();
    $counts = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($counts as $row) {
        echo "EC: {$row['ec']}, Count: {$row['count']}\n";
    }
    
    echo "\n=== ELECTRIC COOPERATIVES IN DB ===\n";
    $stmt3 = $conn->prepare('SELECT code, name FROM electric_cooperatives LIMIT 30');
    $stmt3->execute();
    $ecs = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ecs as $row) {
        echo "Code: {$row['code']}, Name: {$row['name']}\n";
    }
    
    echo "\n=== CURRENT QUERY RESULT (INNER JOIN) ===\n";
    $stmt4 = $conn->prepare("SELECT ec.code as ec, COUNT(*) as count FROM manap m INNER JOIN electric_cooperatives ec ON m.ec = ec.name GROUP BY ec.code ORDER BY count DESC");
    $stmt4->execute();
    $chartData = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    foreach ($chartData as $row) {
        echo "EC Code: {$row['ec']}, Count: {$row['count']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
