<?php
require_once '../config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM aom_table');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total AOM records: ' . $result['count'] . PHP_EOL;

    if ($result['count'] > 0) {
        $stmt = $pdo->query('SELECT id, item, coa_observation, coa_observation_image FROM aom_table LIMIT 3');
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo 'Sample records:' . PHP_EOL;
        foreach ($records as $record) {
            echo 'ID: ' . $record['id'] . ', Item: ' . $record['item'] . PHP_EOL;
            echo 'COA Obs: ' . $record['coa_observation'] . PHP_EOL;
            echo 'COA Images: ' . $record['coa_observation_image'] . PHP_EOL;
            echo '---' . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>