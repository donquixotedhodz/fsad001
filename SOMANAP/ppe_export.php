<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();

// Build filter conditions
$whereConditions = [];
$params = [];

if (!empty($_GET['date_from'])) {
    $whereConditions[] = "date >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $whereConditions[] = "date <= ?";
    $params[] = $_GET['date_to'];
}
if (!empty($_GET['check_no'])) {
    $whereConditions[] = "check_no LIKE ?";
    $params[] = '%' . $_GET['check_no'] . '%';
}
if (!empty($_GET['dv_or_no'])) {
    $whereConditions[] = "dv_or_no LIKE ?";
    $params[] = '%' . $_GET['dv_or_no'] . '%';
}
if (!empty($_GET['particulars'])) {
    $whereConditions[] = "particulars LIKE ?";
    $params[] = '%' . $_GET['particulars'] . '%';
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Fetch PPE data from database
$sql = "SELECT date, check_no, dv_or_no, particulars, debit, credit, balance FROM ppe $whereClause ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$ppeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalDebit = 0;
$totalCredit = 0;
foreach ($ppeRecords as $record) {
    $totalDebit += $record['debit'];
    $totalCredit += $record['credit'];
}

$tempDir = sys_get_temp_dir() . '/ppe_' . uniqid();
mkdir($tempDir, 0777, true);
mkdir($tempDir . '/_rels', 0777, true);
mkdir($tempDir . '/xl/_rels', 0777, true);
mkdir($tempDir . '/xl/worksheets', 0777, true);

// Create _rels/.rels
file_put_contents($tempDir . '/_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

// Create xl/_rels/workbook.xml.rels
file_put_contents($tempDir . '/xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

// Create xl/workbook.xml
file_put_contents($tempDir . '/xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <fileVersion appName="xl" lastEdited="6" lowestEdited="6" rupBuild="9302"/>
    <workbookPr defaultTheme="1"/>
    <sheets>
        <sheet name="PPE Reports" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>');

// Create xl/styles.xml
file_put_contents($tempDir . '/xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFF"/><name val="Calibri"/><family val="2"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="366092"/></patternFill></fill>
    </fills>
    <borders count="2">
        <border><left/><right/><top/><bottom/><diagonal/></border>
        <border><left style="thin"/><right style="thin"/><top style="thin"/><bottom style="thin"/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="5">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1"/>
        <xf numFmtId="0.00" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/>
        <xf numFmtId="0.00" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/>
        <xf numFmtId="0.00" fontId="0" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyFont="1"/>
    </cellXfs>
</styleSheet>');

// Create xl/worksheets/sheet1.xml
$rowCount = count($ppeRecords) + 2;
$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetPr filterOn="false"/>
    <dimension ref="A1:G' . $rowCount . '"/>
    <sheetViews>
        <sheetView workbookViewId="0" activeCell="A1" sqref="A1"/>
    </sheetViews>
    <sheetFormatPr baseColWidth="10" defaultRowHeight="15"/>
    <cols>
        <col min="1" max="1" width="12" customWidth="true"/>
        <col min="2" max="2" width="15" customWidth="true"/>
        <col min="3" max="3" width="15" customWidth="true"/>
        <col min="4" max="4" width="35" customWidth="true"/>
        <col min="5" max="5" width="15" customWidth="true"/>
        <col min="6" max="6" width="15" customWidth="true"/>
        <col min="7" max="7" width="15" customWidth="true"/>
    </cols>
    <sheetData>
        <row r="1" spans="1:7" ht="20" customHeight="true">
            <c r="A1" s="1" t="str"><v>Date</v></c>
            <c r="B1" s="1" t="str"><v>Check No.</v></c>
            <c r="C1" s="1" t="str"><v>DV No.</v></c>
            <c r="D1" s="1" t="str"><v>Name</v></c>
            <c r="E1" s="1" t="str"><v>Debit</v></c>
            <c r="F1" s="1" t="str"><v>Credit</v></c>
            <c r="G1" s="1" t="str"><v>Balance</v></c>
        </row>';

$row = 2;
foreach ($ppeRecords as $record) {
    $date = date('m/d/Y', strtotime($record['date']));
    $checkNo = htmlspecialchars($record['check_no'] ?? '');
    $dvNo = htmlspecialchars($record['dv_or_no'] ?? '');
    $particulars = htmlspecialchars($record['particulars']);
    
    $sheetXml .= '
        <row r="' . $row . '" spans="1:7">
            <c r="A' . $row . '" s="0" t="str"><v>' . $date . '</v></c>
            <c r="B' . $row . '" s="0" t="str"><v>' . $checkNo . '</v></c>
            <c r="C' . $row . '" s="0" t="str"><v>' . $dvNo . '</v></c>
            <c r="D' . $row . '" s="0" t="str"><v>' . $particulars . '</v></c>
            <c r="E' . $row . '" s="2"><v>' . $record['debit'] . '</v></c>
            <c r="F' . $row . '" s="2"><v>' . $record['credit'] . '</v></c>
            <c r="G' . $row . '" s="2"><v>' . $record['balance'] . '</v></c>
        </row>';
    $row++;
}

$lastBalance = $ppeRecords ? end($ppeRecords)['balance'] : 0;
$sheetXml .= '
        <row r="' . $row . '" spans="1:7" ht="20" customHeight="true">
            <c r="A' . $row . '" s="0" t="str"><v></v></c>
            <c r="B' . $row . '" s="0" t="str"><v></v></c>
            <c r="C' . $row . '" s="0" t="str"><v></v></c>
            <c r="D' . $row . '" s="4" t="str"><v>TOTAL:</v></c>
            <c r="E' . $row . '" s="4"><v>' . $totalDebit . '</v></c>
            <c r="F' . $row . '" s="4"><v>' . $totalCredit . '</v></c>
            <c r="G' . $row . '" s="4"><v>' . $lastBalance . '</v></c>
        </row>
    </sheetData>
    <pageMargins left="0.7" top="0.75" right="0.7" bottom="0.75" header="0.3" footer="0.3"/>
</worksheet>';

file_put_contents($tempDir . '/xl/worksheets/sheet1.xml', $sheetXml);

// Create [Content_Types].xml
file_put_contents($tempDir . '/[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

// Create ZIP file
$zipPath = sys_get_temp_dir() . '/PPE_Reports_' . uniqid() . '.xlsx';

// Try different methods to create ZIP
if (class_exists('ZipArchive')) {
    // Method 1: Use ZipArchive if available
    createZipFromDirectoryZipArchive($tempDir, $zipPath);
} elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Method 2: Use PowerShell on Windows
    createZipFromDirectoryPowerShell($tempDir, $zipPath);
} else {
    // Method 3: Use system zip command on Linux/Mac
    createZipFromDirectoryCommand($tempDir, $zipPath);
}

// Send to browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="PPE_Reports_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: max-age=0');
header('Pragma: public');

readfile($zipPath);

// Cleanup
deleteDirectory($tempDir);
unlink($zipPath);
exit();

// Function to create ZIP using ZipArchive
function createZipFromDirectoryZipArchive($sourceDir, $zipPath) {
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception("Failed to create ZIP file");
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            $filePath = $file->getRealPath();
            $relativePath = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $filePath);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
}

// Function to create ZIP using PowerShell on Windows
function createZipFromDirectoryPowerShell($sourceDir, $zipPath) {
    $sourceDir = str_replace('/', '\\', $sourceDir);
    $zipPath = str_replace('/', '\\', $zipPath);
    
    $psScript = "[System.Reflection.Assembly]::LoadWithPartialName('System.IO.Compression.FileSystem') | Out-Null; " .
                "[System.IO.Compression.ZipFile]::CreateFromDirectory('" . str_replace("'", "''", $sourceDir) . "', '" . str_replace("'", "''", $zipPath) . "')";
    
    $tempScript = tempnam(sys_get_temp_dir(), 'zip') . '.ps1';
    file_put_contents($tempScript, $psScript);
    
    $output = shell_exec('powershell -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($tempScript) . ' 2>&1');
    
    unlink($tempScript);
    
    if (!file_exists($zipPath)) {
        throw new Exception("Failed to create ZIP file via PowerShell: " . $output);
    }
}

// Function to create ZIP using system command
function createZipFromDirectoryCommand($sourceDir, $zipPath) {
    $cmd = 'cd ' . escapeshellarg(dirname($sourceDir)) . ' && zip -r ' . escapeshellarg(basename($zipPath)) . ' ' . escapeshellarg(basename($sourceDir)) . ' 2>&1';
    $output = shell_exec($cmd);
    
    if (!file_exists($zipPath)) {
        throw new Exception("Failed to create ZIP file via command: " . $output);
    }
}

// Delete directory recursively
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
?>
