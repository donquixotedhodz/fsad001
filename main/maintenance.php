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
$availableTables = [];

try {
    $tablesStmt = $conn->query("SHOW TABLES FROM neafsad");
    $availableTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Exception $e) {
    $availableTables = [];
}

function getStatementTableName($statement) {
    $patterns = [
        '/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i',
        '/^\s*DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i',
        '/^\s*INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i',
        '/^\s*ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i',
        '/^\s*TRUNCATE\s+TABLE\s+`?([a-zA-Z0-9_]+)`?/i',
        '/^\s*UPDATE\s+`?([a-zA-Z0-9_]+)`?/i',
        '/^\s*DELETE\s+FROM\s+`?([a-zA-Z0-9_]+)`?/i'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $statement, $matches)) {
            return strtolower($matches[1]);
        }
    }

    return null;
}

// Handle database export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    try {
        $dbName = 'neafsad';
        $exportMode = $_POST['export_mode'] ?? 'full';
        $selectedExportTables = $_POST['exportTables'] ?? [];

        if (!is_array($selectedExportTables)) {
            $selectedExportTables = [];
        }

        $selectedExportTables = array_values(array_unique(array_map(function ($table) {
            return strtolower(trim((string) $table));
        }, $selectedExportTables)));

        $timestamp = date('YmdHis');
        $filename = "neafsad_backup_{$timestamp}.sql";
        
        // Get all tables from the database
        $tablesStmt = $conn->query("SHOW TABLES FROM {$dbName}");
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

        if ($exportMode === 'selected') {
            if (empty($selectedExportTables)) {
                $message = 'Error: Please select at least one table to export.';
                $messageType = 'error';
                $tables = [];
            } else {
                $tableMap = [];
                foreach ($tables as $tableName) {
                    $tableMap[strtolower($tableName)] = $tableName;
                }

                $filteredTables = [];
                foreach ($selectedExportTables as $tableKey) {
                    if (isset($tableMap[$tableKey])) {
                        $filteredTables[] = $tableMap[$tableKey];
                    }
                }

                $tables = array_values(array_unique($filteredTables));
            }
        }
        
        if (empty($tables)) {
            if ($messageType !== 'error') {
                $message = 'Error: No tables found in the database.';
                $messageType = 'error';
            }
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
            $exportModeText = $exportMode === 'selected'
                ? 'Selected tables: ' . implode(', ', $tables)
                : 'Full export';
            $auditLogger->log('export', 'maintenance', 0, "Database exported: {$filename} | Mode: {$exportModeText}");
            
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
        $importMode = $_POST['import_mode'] ?? 'full';
        $selectedTables = $_POST['selectedTables'] ?? [];

        if (!is_array($selectedTables)) {
            $selectedTables = [];
        }

        $selectedTables = array_values(array_unique(array_map(function ($table) {
            return strtolower(trim((string) $table));
        }, $selectedTables)));

        if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Error: Please select a valid SQL file.';
            $messageType = 'error';
        } elseif ($importMode === 'selected' && empty($selectedTables)) {
            $message = 'Error: Please select at least one table to import.';
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
                $skippedCount = 0;
                $errors = [];
                
                // Execute each statement
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $statementTable = getStatementTableName($statement);

                        if ($importMode === 'selected' && $statementTable !== null && !in_array($statementTable, $selectedTables, true)) {
                            $skippedCount++;
                            continue;
                        }

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
                $modeText = $importMode === 'selected'
                    ? 'Selected tables: ' . implode(', ', $selectedTables)
                    : 'Full import';

                $auditLogger->log('import', 'maintenance', 0, 
                    "Database imported from: {$filename} | Mode: {$modeText} | Successful statements: {$successCount} | Failed statements: {$errorCount} | Skipped statements: {$skippedCount}");
                
                if ($errorCount === 0) {
                    if ($importMode === 'selected') {
                        $message = "Selected-table import completed successfully! {$successCount} SQL statements executed, {$skippedCount} skipped.";
                    } else {
                        $message = "Database imported successfully! {$successCount} SQL statements executed.";
                    }
                    $messageType = 'success';
                } else {
                    $message = "Database import completed with {$errorCount} errors. {$successCount} statements executed, {$skippedCount} skipped.";
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

<div class="w-full space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 lg:p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white">System Maintenance</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Back up and restore database data with safer, guided actions.</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Database</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">neafsad</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Access</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Superadmin only</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : ($messageType === 'error' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800'); ?>">
        <p class="font-medium <?php echo $messageType === 'success' ? 'text-green-800 dark:text-green-400' : ($messageType === 'error' ? 'text-red-800 dark:text-red-400' : 'text-yellow-800 dark:text-yellow-400'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Export Backup</h2>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">Create a full SQL backup containing table structures and all records.</p>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="export">

                <div class="space-y-2">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Step 1: Select export mode</p>
                    <div class="grid grid-cols-1 gap-2">
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                            <input type="radio" name="export_mode" value="full" class="mt-1 text-blue-600" checked>
                            <span>
                                <span class="block font-semibold text-gray-900 dark:text-white">Full database export</span>
                                <span class="block text-sm text-gray-600 dark:text-gray-400">Include all tables and their records.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                            <input type="radio" name="export_mode" value="selected" class="mt-1 text-blue-600">
                            <span>
                                <span class="block font-semibold text-gray-900 dark:text-white">Selected tables export</span>
                                <span class="block text-sm text-gray-600 dark:text-gray-400">Download only chosen tables.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div id="exportTableSelectionBlock" class="hidden space-y-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-900/30">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Step 2: Select tables to export</p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="exportSelectAllBtn">Select all</button>
                            <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="exportClearAllBtn">Clear</button>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo count($availableTables); ?> table(s) available.</div>
                    <div id="exportTableCheckboxList" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto pr-1">
                        <?php foreach ($availableTables as $tableName): ?>
                        <label class="flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="exportTables[]" value="<?php echo htmlspecialchars(strtolower($tableName)); ?>" class="export-table-checkbox text-blue-600 rounded">
                            <span><?php echo htmlspecialchars($tableName); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-700 dark:text-blue-300">Use this before imports or major updates to keep a rollback copy.</p>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Export Summary</p>
                    <p id="exportSummaryText" class="text-sm font-semibold text-gray-900 dark:text-white">Full database export</p>
                </div>

                <button type="submit" class="w-full px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Download SQL Backup
                </button>
            </form>
        </div>

        <div class="xl:col-span-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8v12a2 2 0 002 2h12a2 2 0 002-2V8"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12l-4-4-4 4m4-4v10"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Import Backup</h2>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">Upload a SQL file, then choose full import or only the tables you want to restore.</p>

            <form method="POST" enctype="multipart/form-data" class="space-y-5" id="importForm">
                <input type="hidden" name="action" value="import">

                <div class="space-y-2">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Step 1: Choose SQL file</p>
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center hover:border-green-500 dark:hover:border-green-500 transition cursor-pointer" id="importDropZone">
                        <input type="file" name="sqlFile" id="sqlFileInput" accept=".sql" class="hidden" required>
                        <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 0115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">Drop SQL file here or click to browse</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Only .sql files are accepted</p>
                    </div>
                </div>

                <div id="fileNameDisplay" class="hidden bg-green-50 dark:bg-green-900/20 p-3 rounded-lg border border-green-200 dark:border-green-800">
                    <p class="text-sm text-green-700 dark:text-green-300"><strong>Selected:</strong> <span id="selectedFileName"></span></p>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Step 2: Select import mode</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer" id="fullModeCard">
                            <input type="radio" name="import_mode" value="full" class="mt-1 text-green-600" checked>
                            <span>
                                <span class="block font-semibold text-gray-900 dark:text-white">Full database import</span>
                                <span class="block text-sm text-gray-600 dark:text-gray-400">Run all SQL statements from the file.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer" id="selectedModeCard">
                            <input type="radio" name="import_mode" value="selected" class="mt-1 text-green-600">
                            <span>
                                <span class="block font-semibold text-gray-900 dark:text-white">Selected tables only</span>
                                <span class="block text-sm text-gray-600 dark:text-gray-400">Apply statements only for checked tables.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div id="tableSelectionBlock" class="hidden space-y-3 rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/30">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Step 3: Select tables to import</p>
                        <div class="flex items-center gap-2">
                            <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="selectAllTablesBtn">Select all</button>
                            <button type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300" id="clearAllTablesBtn">Clear</button>
                        </div>
                    </div>
                    <div id="tableSelectionHint" class="text-sm text-gray-500 dark:text-gray-400">Upload a SQL file to detect available tables.</div>
                    <div id="tableCheckboxList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2"></div>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <p class="text-xs text-yellow-700 dark:text-yellow-300"><strong>Warning:</strong> Import operations can overwrite current data. Export a fresh backup before proceeding.</p>
                </div>

                <div id="importPreviewSummary" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Import Preview Summary</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div class="rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Mode</p>
                            <p id="previewMode" class="font-semibold text-gray-900 dark:text-white">Full database import</p>
                        </div>
                        <div class="rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Detected Tables</p>
                            <p id="previewDetectedCount" class="font-semibold text-gray-900 dark:text-white">0</p>
                        </div>
                        <div class="rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Selected Tables</p>
                            <p id="previewSelectedCount" class="font-semibold text-gray-900 dark:text-white">All</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full px-5 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" id="importBtn" disabled>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3"></path>
                    </svg>
                    Start Import
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Maintenance Guidelines</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Backup Frequency</p>
                <p>Create exports regularly, especially before system updates and data cleanup tasks.</p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Test Restore</p>
                <p>Validate backups periodically to make sure SQL files can be imported without errors.</p>
            </div>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Audit Trail</p>
                <p>All import/export operations are recorded in audit logs for traceability and security.</p>
            </div>
        </div>
    </div>
</div>

<script>
// File and import mode handling
const dropZone = document.getElementById('importDropZone');
const fileInput = document.getElementById('sqlFileInput');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const selectedFileName = document.getElementById('selectedFileName');
const importBtn = document.getElementById('importBtn');
const importForm = document.getElementById('importForm');
const tableSelectionBlock = document.getElementById('tableSelectionBlock');
const tableSelectionHint = document.getElementById('tableSelectionHint');
const tableCheckboxList = document.getElementById('tableCheckboxList');
const importModeInputs = document.querySelectorAll('input[name="import_mode"]');
const selectAllTablesBtn = document.getElementById('selectAllTablesBtn');
const clearAllTablesBtn = document.getElementById('clearAllTablesBtn');
const previewMode = document.getElementById('previewMode');
const previewDetectedCount = document.getElementById('previewDetectedCount');
const previewSelectedCount = document.getElementById('previewSelectedCount');
const exportModeInputs = document.querySelectorAll('input[name="export_mode"]');
const exportTableSelectionBlock = document.getElementById('exportTableSelectionBlock');
const exportTableCheckboxList = document.getElementById('exportTableCheckboxList');
const exportSelectAllBtn = document.getElementById('exportSelectAllBtn');
const exportClearAllBtn = document.getElementById('exportClearAllBtn');
const exportSummaryText = document.getElementById('exportSummaryText');

let detectedTables = [];

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
        const droppedFile = e.dataTransfer.files[0];
        if (droppedFile.name.toLowerCase().endsWith('.sql')) {
            fileInput.files = e.dataTransfer.files;
        }
        updateFileDisplay();
    }
});

fileInput.addEventListener('change', updateFileDisplay);

function updateFileDisplay() {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        if (file.name.toLowerCase().endsWith('.sql')) {
            selectedFileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
            fileNameDisplay.classList.remove('hidden');
            importBtn.disabled = false;
            detectTablesFromFile(file);
        } else {
            alert('Please select a valid .sql file');
            fileInput.value = '';
            fileNameDisplay.classList.add('hidden');
            importBtn.disabled = true;
            detectedTables = [];
            renderTableChecklist();
        }
    } else {
        fileNameDisplay.classList.add('hidden');
        importBtn.disabled = true;
        detectedTables = [];
        renderTableChecklist();
    }

    updateImportModeUI();
}

function detectTablesFromFile(file) {
    const reader = new FileReader();
    reader.onload = function(event) {
        const sqlContent = String(event.target.result || '');
        const matches = [...sqlContent.matchAll(/(?:CREATE\s+TABLE|INSERT\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE\s+TABLE|UPDATE|DELETE\s+FROM)\s+(?:IF\s+(?:NOT\s+)?EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/gi)];
        const tables = matches.map(match => match[1].toLowerCase());
        detectedTables = [...new Set(tables)].sort();
        renderTableChecklist();
        updateImportModeUI();
    };
    reader.readAsText(file);
}

function renderTableChecklist() {
    tableCheckboxList.innerHTML = '';

    if (detectedTables.length === 0) {
        tableSelectionHint.textContent = 'No table names detected yet. Use a valid SQL dump file.';
        updateImportPreview();
        return;
    }

    tableSelectionHint.textContent = detectedTables.length + ' table(s) detected from SQL file.';

    detectedTables.forEach((tableName) => {
        const label = document.createElement('label');
        label.className = 'flex items-center gap-2 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'selectedTables[]';
        checkbox.value = tableName;
        checkbox.className = 'text-green-600 rounded';

        const span = document.createElement('span');
        span.textContent = tableName;

        label.appendChild(checkbox);
        label.appendChild(span);
        tableCheckboxList.appendChild(label);
    });

    updateImportPreview();
}

function updateImportModeUI() {
    const selectedMode = document.querySelector('input[name="import_mode"]:checked')?.value;
    const hasFile = fileInput.files.length > 0;
    const showTableSelection = selectedMode === 'selected';

    tableSelectionBlock.classList.toggle('hidden', !showTableSelection);

    const checkboxes = tableCheckboxList.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((checkbox) => {
        checkbox.disabled = !showTableSelection || !hasFile;
    });

    if (showTableSelection && !hasFile) {
        tableSelectionHint.textContent = 'Upload a SQL file first to detect available tables.';
    }

    updateImportPreview();
}

function updateImportPreview() {
    const selectedMode = document.querySelector('input[name="import_mode"]:checked')?.value || 'full';
    const selectedCount = tableCheckboxList.querySelectorAll('input[type="checkbox"]:checked').length;

    previewMode.textContent = selectedMode === 'selected' ? 'Selected tables only' : 'Full database import';
    previewDetectedCount.textContent = String(detectedTables.length);

    if (selectedMode === 'selected') {
        previewSelectedCount.textContent = String(selectedCount);
    } else {
        previewSelectedCount.textContent = 'All';
    }
}

importModeInputs.forEach((radio) => {
    radio.addEventListener('change', updateImportModeUI);
});

selectAllTablesBtn.addEventListener('click', function() {
    const checkboxes = tableCheckboxList.querySelectorAll('input[type="checkbox"]:not(:disabled)');
    checkboxes.forEach((checkbox) => {
        checkbox.checked = true;
    });
    updateImportPreview();
});

clearAllTablesBtn.addEventListener('click', function() {
    const checkboxes = tableCheckboxList.querySelectorAll('input[type="checkbox"]:not(:disabled)');
    checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });
    updateImportPreview();
});

tableCheckboxList.addEventListener('change', function(event) {
    if (event.target && event.target.matches('input[type="checkbox"]')) {
        updateImportPreview();
    }
});

importForm.addEventListener('submit', function(event) {
    const selectedMode = document.querySelector('input[name="import_mode"]:checked')?.value;

    if (selectedMode === 'selected') {
        const selectedCount = tableCheckboxList.querySelectorAll('input[type="checkbox"]:checked').length;
        if (selectedCount === 0) {
            event.preventDefault();
            alert('Please select at least one table for selected-table import.');
        }
    }
});

function updateExportModeUI() {
    const selectedMode = document.querySelector('input[name="export_mode"]:checked')?.value || 'full';
    const showSelection = selectedMode === 'selected';

    if (exportTableSelectionBlock) {
        exportTableSelectionBlock.classList.toggle('hidden', !showSelection);
    }

    updateExportSummary();
}

function updateExportSummary() {
    if (!exportSummaryText) {
        return;
    }

    const selectedMode = document.querySelector('input[name="export_mode"]:checked')?.value || 'full';
    const totalCount = exportTableCheckboxList
        ? exportTableCheckboxList.querySelectorAll('input[type="checkbox"]').length
        : 0;
    const selectedCount = exportTableCheckboxList
        ? exportTableCheckboxList.querySelectorAll('input[type="checkbox"]:checked').length
        : 0;

    if (selectedMode === 'selected') {
        exportSummaryText.textContent = 'Selected tables export (' + selectedCount + ' / ' + totalCount + ')';
    } else {
        exportSummaryText.textContent = 'Full database export';
    }
}

exportModeInputs.forEach((radio) => {
    radio.addEventListener('change', updateExportModeUI);
});

exportSelectAllBtn?.addEventListener('click', function() {
    const checkboxes = exportTableCheckboxList.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((checkbox) => {
        checkbox.checked = true;
    });
    updateExportSummary();
});

exportClearAllBtn?.addEventListener('click', function() {
    const checkboxes = exportTableCheckboxList.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
    });
    updateExportSummary();
});

exportTableCheckboxList?.addEventListener('change', function(event) {
    if (event.target && event.target.matches('input[type="checkbox"]')) {
        updateExportSummary();
    }
});

document.addEventListener('submit', function(event) {
    const form = event.target;
    if (!form || !form.matches('form') || !form.querySelector('input[name="action"][value="export"]')) {
        return;
    }

    const selectedMode = document.querySelector('input[name="export_mode"]:checked')?.value || 'full';
    if (selectedMode === 'selected') {
        const selectedCount = exportTableCheckboxList
            ? exportTableCheckboxList.querySelectorAll('input[type="checkbox"]:checked').length
            : 0;
        if (selectedCount === 0) {
            event.preventDefault();
            alert('Please select at least one table to export.');
        }
    }
});

updateImportModeUI();
updateImportPreview();
updateExportModeUI();
updateExportSummary();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Maintenance';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
