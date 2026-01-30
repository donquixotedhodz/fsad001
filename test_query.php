<?php
require_once 'config.php';

try {
    echo "=== NEW QUERY RESULT (LIKE matching) ===\n";
    $stmt = $conn->prepare("
        SELECT COALESCE(ec.code, m.ec) as ec, COUNT(*) as count 
        FROM manap m
        LEFT JOIN electric_cooperatives ec ON TRIM(m.ec) LIKE CONCAT('%', TRIM(ec.name), '%')
        GROUP BY COALESCE(ec.code, m.ec)
        ORDER BY count DESC
    ");
    $stmt->execute();
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($chartData as $row) {
        echo "EC: {$row['ec']}, Count: {$row['count']}\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    echo "\nFull trace: " . $e->getTraceAsString();
}
?>
