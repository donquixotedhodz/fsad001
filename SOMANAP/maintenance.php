<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('maintenance');

// Only superadmin can access maintenance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: dashboard.php');
    exit;
}

$auditLogger = new AuditLogger($conn);
$username = $_SESSION['username'] ?? 'User';
$message = '';
$messageType = '';

// Handle database export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    try {
        $dbName = 'neafsad';
        $timestamp = date('YmdHis');
        $filename = "neafsad_backup_{$timestamp}.sql";
        
        // Get all tables from the database
        $tablesStmt = $conn->query("SHOW TABLES FROM {$dbName}");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            $message = 'Error: No tables found in the database.';
            $messageType = 'error';
        } else {
            // Start with MySQL settings (HeidiSQL format)
            $sqlDump = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
            $sqlDump .= "/*!40101 SET NAMES utf8 */;\n";
            $sqlDump .= "/*!50503 SET NAMES utf8mb4 */;\n";
            $sqlDump .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
            $sqlDump .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
            $sqlDump .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
            $sqlDump .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
            $sqlDump .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
            
            // Create database
            $sqlDump .= "CREATE DATABASE IF NOT EXISTS `{$dbName}` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;\n";
            $sqlDump .= "USE `{$dbName}`;\n\n";
            
            // Export each table
            foreach ($tables as $table) {
                // Get CREATE TABLE statement
                $createStmt = $conn->query("SHOW CREATE TABLE {$table}");
                $createResult = $createStmt->fetch(PDO::FETCH_ASSOC);
                
                // Add comment for table
                $sqlDump .= "-- Dumping structure for table {$dbName}.{$table}\n";
                $sqlDump .= "CREATE TABLE IF NOT EXISTS `{$table}` (\n";
                
                // Extract column definitions from CREATE TABLE
                $createTableSQL = $createResult['Create Table'];
                preg_match('/\((.*)\)\s*ENGINE/s', $createTableSQL, $matches);
                if (!empty($matches[1])) {
                    $sqlDump .= $matches[1] . "\n";
                }
                
                // Get table engine and charset
                preg_match('/ENGINE=(\w+).*CHARSET=(\w+).*COLLATE=(\w+)/s', $createTableSQL, $engineMatch);
                if (!empty($engineMatch)) {
                    $engine = $engineMatch[1] ?? 'InnoDB';
                    $charset = $engineMatch[2] ?? 'utf8mb4';
                    $collate = $engineMatch[3] ?? 'utf8mb4_0900_ai_ci';
                    $sqlDump .= ") ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collate};\n\n";
                } else {
                    $sqlDump .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;\n\n";
                }
                
                // Get all data from table - fetch ALL rows without limit
                $dataStmt = $conn->query("SELECT * FROM {$table}");
                $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    // Get column names from the first row
                    $columns = array_keys(reset($rows));
                    $columnNames = implode('`, `', $columns);
                    
                    // Add data dump comment
                    $rowCount = count($rows);
                    $sqlDump .= "-- Dumping data for table {$dbName}.{$table}: ~{$rowCount} rows (approximately)\n";
                    $sqlDump .= "INSERT INTO `{$table}` (`{$columnNames}`) VALUES\n";
                    
                    $valuesList = [];
                    // Export all data rows
                    foreach ($rows as $row) {
                        $values = array_map(function($value) use ($conn) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            // Use PDO::quote which adds the necessary quotes
                            return $conn->quote($value);
                        }, $row);
                        
                        $valuesList[] = "\t(" . implode(', ', $values) . ")";
                    }
                    
                    // Use single INSERT INTO with multiple VALUES with proper formatting
                    $sqlDump .= implode(",\n", $valuesList) . ";\n\n";
                }
            }
            
            // End with MySQL settings
            $sqlDump .= "/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;\n";
            $sqlDump .= "/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;\n";
            $sqlDump .= "/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;\n";
            $sqlDump .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
            $sqlDump .= "/*!40111 SET @OLD_SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;\n";
            
            // Log the export
            $auditLogger->log('export', 'maintenance', 0, "Database exported: {$filename}");
            
            // Send file to download
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sqlDump));
            echo $sqlDump;
            exit;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle database import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    try {
        if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Error: Please select a valid SQL file.';
            $messageType = 'error';
        } else {
            $file = $_FILES['sqlFile'];
            $filename = basename($file['name']);
            
            // Validate file extension
            if (pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
                $message = 'Error: Only .sql files are allowed.';
                $messageType = 'error';
            } else {
                // Read the SQL file
                $sqlContent = file_get_contents($file['tmp_name']);
                
                // Split SQL statements
                $splitResult = preg_split('/;(?=([^\']*\'[^\']*\')*[^\']*$)/', $sqlContent);
                if ($splitResult === false) {
                    $splitResult = explode(';', $sqlContent);
                }
                $statements = array_filter(array_map('trim', $splitResult));
                
                $successCount = 0;
                $errorCount = 0;
                $errors = [];
                
                // Execute each statement
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $conn->exec($statement);
                            $successCount++;
                        } catch (PDOException $e) {
                            $errorCount++;
                            $errors[] = substr($statement, 0, 50) . '... - Error: ' . $e->getMessage();
                        }
                    }
                }
                
                // Log the import
                $auditLogger->log('import', 'maintenance', 0, 
                    "Database imported from: {$filename} | Successful statements: {$successCount} | Failed statements: {$errorCount}");
                
                if ($errorCount === 0) {
                    $message = "Database imported successfully! {$successCount} SQL statements executed.";
                    $messageType = 'success';
                } else {
                    $message = "Database import completed with {$errorCount} errors. {$successCount} statements executed.";
                    $messageType = 'warning';
                }
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">System Maintenance</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage database backups and restoration</p>
    </div>

    <!-- Messages -->
    <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ($messageType === 'error' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'); ?>">
        <p class="<?php echo $messageType === 'success' ? 'text-green-800 dark:text-green-400' : ($messageType === 'error' ? 'text-red-800 dark:text-red-400' : 'text-yellow-800 dark:text-yellow-400'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Maintenance Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Export Database -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Export Database</h2>
            </div>
            
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Download a complete backup of the database as a SQL file. This includes all tables, data, and structure.
            </p>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="export">
                
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Database:</strong> neafsad
                    </p>
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export Database
                </button>
            </form>
        </div>

        <!-- Import Database -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8v12a2 2 0 002 2h12a2 2 0 002-2V8m-6 4v4m-4-4v4m8-10H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2h-4l-2-2H8l-2 2H4z"></path>
                </svg>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Import Database</h2>
            </div>
            
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Restore the database from a SQL backup file. This will replace the current database with the backup data.
            </p>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="import">
                
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-green-500 dark:hover:border-green-500 transition cursor-pointer" id="importDropZone">
                    <input type="file" name="sqlFile" id="sqlFileInput" accept=".sql" class="hidden" required>
                    
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    
                    <p class="text-gray-700 dark:text-gray-300 font-medium mb-1">Drop SQL file here or click to select</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Only .sql files are accepted</p>
                </div>

                <div id="fileNameDisplay" class="hidden bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <strong>Selected file:</strong> <span id="selectedFileName"></span>
                    </p>
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" id="importBtn" disabled>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Import Database
                </button>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">
                        <strong>Warning:</strong> Importing a database backup will replace all current data. Make sure you have backed up the current database before proceeding.
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Information -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Maintenance Information</h3>
        
        <div class="space-y-4">
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Database Name</h4>
                <p class="text-gray-600 dark:text-gray-400">neafsad</p>
            </div>

            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Backup Recommendations</h4>
                <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1">
                    <li>Export database backups regularly (weekly recommended)</li>
                    <li>Store backups in a secure location</li>
                    <li>Test restoration procedures periodically</li>
                    <li>Always backup before major updates</li>
                </ul>
            </div>

            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Security Notice</h4>
                <p class="text-gray-600 dark:text-gray-400">
                    This feature is restricted to superadmin users only. All export and import operations are logged in the audit log for security purposes.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// File drop zone handling
const dropZone = document.getElementById('importDropZone');
const fileInput = document.getElementById('sqlFileInput');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const selectedFileName = document.getElementById('selectedFileName');
const importBtn = document.getElementById('importBtn');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-green-500', 'dark:border-green-500', 'bg-green-50', 'dark:bg-green-900/10');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-green-500', 'dark:border-green-500', 'bg-green-50', 'dark:bg-green-900/10');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-green-500', 'dark:border-green-500', 'bg-green-50', 'dark:bg-green-900/10');
    
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        updateFileDisplay();
    }
});

fileInput.addEventListener('change', updateFileDisplay);

function updateFileDisplay() {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        if (file.name.endsWith('.sql')) {
            selectedFileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
            fileNameDisplay.classList.remove('hidden');
            importBtn.disabled = false;
        } else {
            alert('Please select a valid .sql file');
            fileInput.value = '';
            fileNameDisplay.classList.add('hidden');
            importBtn.disabled = true;
        }
    } else {
        fileNameDisplay.classList.add('hidden');
        importBtn.disabled = true;
    }
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Maintenance';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
