<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'neafsad');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Test connection
    $conn->query("SELECT 1");
    
} catch(PDOException $e) {
    // Log error for debugging
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display user-friendly error
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;'>
        <h2 style='color: #d32f2f;'>Database Connection Error</h2>
        <p><strong>Error:</strong> Unable to connect to the database.</p>
        <p><strong>Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <h3>Troubleshooting:</h3>
        <ul>
            <li>Ensure MySQL server is running (Laragon must be started)</li>
            <li>Verify database name is 'neafsad'</li>
            <li>Check database credentials: host='localhost', user='root', password=''</li>
            <li>Run setup.sql to create the database and table</li>
            <li>Visit: <code>http://localhost/FSAD/test-connection.php</code> to verify connection</li>
        </ul>
    </div>
    ");
}
?>
