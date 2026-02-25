<?php
session_start();

$host = 'localhost';
$dbname = 'neafsad';
$username = 'root';
$password = '';
$message = '';
$messageType = '';

function splitSqlStatementsSetup($sqlContent) {
    $statements = [];
    $buffer = '';
    $length = strlen($sqlContent);
    $delimiter = ';';
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $sqlContent[$index];
        $nextChar = $index + 1 < $length ? $sqlContent[$index + 1] : '';

        if ($inLineComment) {
            $buffer .= $char;
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            $buffer .= $char;
            if ($char === '*' && $nextChar === '/') {
                $buffer .= $nextChar;
                $index++;
                $inBlockComment = false;
            }
            continue;
        }

        if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            if ($char === '-' && $nextChar === '-') {
                $charAfter = $index + 2 < $length ? $sqlContent[$index + 2] : '';
                if ($charAfter === ' ' || $charAfter === "\t" || $charAfter === "\r" || $charAfter === "\n") {
                    $buffer .= $char . $nextChar;
                    $index++;
                    $inLineComment = true;
                    continue;
                }
            }

            if ($char === '#') {
                $buffer .= $char;
                $inLineComment = true;
                continue;
            }

            if ($char === '/' && $nextChar === '*') {
                $buffer .= $char . $nextChar;
                $index++;
                $inBlockComment = true;
                continue;
            }
        }

        if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
            $isEscaped = $index > 0 && $sqlContent[$index - 1] === '\\';
            if (!$isEscaped) {
                $inSingleQuote = !$inSingleQuote;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '"' && !$inSingleQuote && !$inBacktick) {
            $isEscaped = $index > 0 && $sqlContent[$index - 1] === '\\';
            if (!$isEscaped) {
                $inDoubleQuote = !$inDoubleQuote;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
            $inBacktick = !$inBacktick;
            $buffer .= $char;
            continue;
        }

        if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            if ($char === "\n" || $char === "\r") {
                $trimmedBuffer = trim($buffer);
                if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmedBuffer, $delimiterMatch)) {
                    $delimiter = trim($delimiterMatch[1]);
                    $buffer = '';
                    continue;
                }
            }

            $delimiterLength = strlen($delimiter);
            if ($delimiterLength > 0 && substr($sqlContent, $index, $delimiterLength) === $delimiter) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                $index += $delimiterLength - 1;
                continue;
            }
        }

        $buffer .= $char;
    }

    $remainingStatement = trim($buffer);
    if ($remainingStatement !== '' && !preg_match('/^DELIMITER\s+/i', $remainingStatement)) {
        $statements[] = $remainingStatement;
    }

    return $statements;
}

function convertInsertToUpsertSetup($statement) {
    if (!preg_match('/^\s*INSERT\s+INTO\s+`?[a-zA-Z0-9_]+`?\s*\(([^)]+)\)\s*VALUES\s+/is', $statement, $matches)) {
        return $statement;
    }

    if (stripos($statement, 'ON DUPLICATE KEY UPDATE') !== false) {
        return $statement;
    }

    $columns = array_filter(array_map('trim', explode(',', $matches[1])));
    if (empty($columns)) {
        return $statement;
    }

    $updateParts = [];
    foreach ($columns as $column) {
        $columnName = trim($column, "` \t\n\r\0\x0B");
        if ($columnName === '') {
            continue;
        }
        $safeColumn = '`' . str_replace('`', '``', $columnName) . '`';
        $updateParts[] = "{$safeColumn} = VALUES({$safeColumn})";
    }

    if (empty($updateParts)) {
        return $statement;
    }

    return rtrim($statement) . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);
}

try {
    $bootstrapConn = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password);
    $bootstrapConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $dbCheckStmt = $bootstrapConn->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1");
    $dbCheckStmt->execute([$dbname]);
    $databaseExists = (bool) $dbCheckStmt->fetchColumn();

    $requiredTablesExist = false;
    if ($databaseExists) {
        $tableCheckStmt = $bootstrapConn->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('users', 'staff')"
        );
        $tableCheckStmt->execute([$dbname]);
        $requiredTablesExist = ((int) $tableCheckStmt->fetchColumn()) >= 1;
    }

    if ($databaseExists && $requiredTablesExist) {
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
        if (!isset($_FILES['sqlFile']) || $_FILES['sqlFile']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Please select a valid SQL file.';
            $messageType = 'error';
        } else {
            $file = $_FILES['sqlFile'];
            $filename = basename($file['name']);

            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'sql') {
                $message = 'Only .sql files are allowed.';
                $messageType = 'error';
            } else {
                $sqlContent = file_get_contents($file['tmp_name']);
                $statements = splitSqlStatementsSetup($sqlContent);

                $successCount = 0;
                $errorCount = 0;
                $errors = [];

                foreach ($statements as $statement) {
                    $statementToExecute = $statement;
                    if (preg_match('/^\s*INSERT\s+INTO\s+/i', $statementToExecute)) {
                        $statementToExecute = convertInsertToUpsertSetup($statementToExecute);
                    }

                    try {
                        $bootstrapConn->exec($statementToExecute);
                        $successCount++;
                    } catch (PDOException $e) {
                        $errorCount++;
                        if (count($errors) < 3) {
                            $errors[] = substr($statementToExecute, 0, 90) . '... - ' . $e->getMessage();
                        }
                    }
                }

                if ($errorCount === 0) {
                    header('Location: index.php');
                    exit;
                }

                $message = "Import completed with {$errorCount} error(s), {$successCount} statement(s) executed. " . (!empty($errors) ? 'Sample: ' . implode(' | ', $errors) : '');
                $messageType = 'warning';
            }
        }
    }
} catch (PDOException $e) {
    $message = 'Database server connection failed: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Setup | FSAD</title>
    <link rel="icon" type="image/x-icon" href="main/images/nealogo.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-lg border border-gray-200 p-6 md:p-8">
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Initial Database Setup</h1>
                <p class="text-gray-600 mt-2">No initialized database was detected. Import your SQL backup to continue to login.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-5 p-4 rounded-lg border <?php echo $messageType === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-yellow-50 border-yellow-200 text-yellow-700'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-5" id="setupImportForm">
                <input type="hidden" name="action" value="import">

                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center" id="setupDropZone">
                    <input type="file" name="sqlFile" id="setupSqlFile" accept=".sql" class="hidden" required>
                    <p class="text-gray-800 font-medium">Drop SQL file here or click to select</p>
                    <p class="text-sm text-gray-500 mt-1">Only .sql files are accepted</p>
                </div>

                <div id="setupFileInfo" class="hidden bg-blue-50 border border-blue-200 text-blue-700 rounded-lg p-3 text-sm"></div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-700">
                    This setup page is available only until required database tables are imported successfully.
                </div>

                <button type="submit" id="setupImportBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition disabled:opacity-50" disabled>
                    Import Database and Continue
                </button>
            </form>
        </div>
    </div>

    <div id="setupLoadingOverlay" class="fixed inset-0 bg-white/90 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="text-center px-6">
            <svg class="w-14 h-14 text-blue-600 animate-spin mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-4 text-lg font-semibold text-gray-800">Importing database...</p>
            <p class="text-sm text-gray-600 mt-1">Please wait and do not close this page.</p>
        </div>
    </div>

    <script>
    const setupDropZone = document.getElementById('setupDropZone');
    const setupSqlFile = document.getElementById('setupSqlFile');
    const setupFileInfo = document.getElementById('setupFileInfo');
    const setupImportBtn = document.getElementById('setupImportBtn');
    const setupImportForm = document.getElementById('setupImportForm');
    const setupLoadingOverlay = document.getElementById('setupLoadingOverlay');

    setupDropZone.addEventListener('click', function() {
        setupSqlFile.click();
    });

    setupDropZone.addEventListener('dragover', function(event) {
        event.preventDefault();
        setupDropZone.classList.add('border-blue-500', 'bg-blue-50');
    });

    setupDropZone.addEventListener('dragleave', function() {
        setupDropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    setupDropZone.addEventListener('drop', function(event) {
        event.preventDefault();
        setupDropZone.classList.remove('border-blue-500', 'bg-blue-50');

        if (event.dataTransfer.files.length > 0) {
            const dropped = event.dataTransfer.files[0];
            if (dropped.name.toLowerCase().endsWith('.sql')) {
                setupSqlFile.files = event.dataTransfer.files;
            }
            updateSetupFileDisplay();
        }
    });

    setupSqlFile.addEventListener('change', updateSetupFileDisplay);

    function updateSetupFileDisplay() {
        if (!setupSqlFile.files.length) {
            setupFileInfo.classList.add('hidden');
            setupImportBtn.disabled = true;
            return;
        }

        const file = setupSqlFile.files[0];
        if (!file.name.toLowerCase().endsWith('.sql')) {
            alert('Please select a valid .sql file');
            setupSqlFile.value = '';
            setupFileInfo.classList.add('hidden');
            setupImportBtn.disabled = true;
            return;
        }

        setupFileInfo.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
        setupFileInfo.classList.remove('hidden');
        setupImportBtn.disabled = false;
    }

    setupImportForm.addEventListener('submit', function() {
        setupImportBtn.disabled = true;
        setupImportBtn.textContent = 'Importing...';
        setupLoadingOverlay.classList.remove('hidden');
        setupLoadingOverlay.classList.add('flex');
    });
    </script>
</body>
</html>
