<?php
require_once __DIR__ . '/../config.php';

try {
    // Check if audit_logs table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'audit_logs'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        echo "✓ audit_logs table EXISTS\n\n";

        // Get table structure
        $stmt = $conn->query("DESCRIBE audit_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Table Structure:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
        }

        // Get row count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM audit_logs");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\nTotal records: $count\n";

        // Get recent logs
        if ($count > 0) {
            echo "\nRecent 5 logs:\n";
            $stmt = $conn->query("SELECT id, username, action, table_name, description, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 5");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($logs as $log) {
                echo "- [{$log['created_at']}] {$log['username']} - {$log['action']} on {$log['table_name']}: {$log['description']}\n";
            }
        }
    }
    else {
        echo "✗ audit_logs table DOES NOT EXIST\n";
        echo "\nYou need to create the table. Here's the SQL:\n\n";
        echo "CREATE TABLE `audit_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `username` VARCHAR(255) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `table_name` VARCHAR(255) NOT NULL,
  `record_id` INT(11) DEFAULT NULL,
  `description` TEXT,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
    }
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
