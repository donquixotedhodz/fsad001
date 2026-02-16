<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();

// Build filter conditions
$whereConditions = [];
$params = [];

$dateFilter = $_GET['date_filter'] ?? '';
$today = date('Y-m-d');

if ($dateFilter === 'today') {
    $whereConditions[] = "a.date = ?";
    $params[] = $today;
}
elseif ($dateFilter === 'weekly') {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $whereConditions[] = "a.date >= ?";
    $params[] = $weekStart;
    $whereConditions[] = "a.date <= ?";
    $params[] = $today;
}
elseif ($dateFilter === 'monthly') {
    $selectedMonth = $_GET['selected_month'] ?? date('m');
    $selectedYear = $_GET['selected_year'] ?? date('Y');
    $monthStart = $selectedYear . '-' . $selectedMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $whereConditions[] = "a.date >= ?";
    $params[] = $monthStart;
    $whereConditions[] = "a.date <= ?";
    $params[] = $monthEnd;
}
elseif ($dateFilter === 'annual') {
    $selectedYear = $_GET['selected_year'] ?? date('Y');
    $whereConditions[] = "YEAR(a.date) = ?";
    $params[] = $selectedYear;
}
elseif ($dateFilter === 'custom') {
    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "a.date >= ?";
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "a.date <= ?";
        $params[] = $_GET['date_to'];
    }
}

if (!empty($_GET['department_id'])) {
    $whereConditions[] = "a.department_id = ?";
    $params[] = $_GET['department_id'];
}

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(a.item LIKE ? OR a.title LIKE ? OR a.coa_observation LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch AOM data from database
$sql = "SELECT a.*, d.name as department_name, d.acronym as department_acronym 
        FROM aom_table a 
        LEFT JOIN neadept_table d ON a.department_id = d.id 
        $whereClause 
        ORDER BY a.date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create Excel (XLSX) manually like in ppe_export.php
$tempDir = sys_get_temp_dir() . '/aom_' . uniqid();
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
        <sheet name="AOM Reports" sheetId="1" r:id="rId1"/>
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
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    </cellXfs>
</styleSheet>');

// Create xl/worksheets/sheet1.xml
$rowCount = count($records) + 1;
$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <dimension ref="A1:G' . $rowCount . '"/>
    <cols>
        <col min="1" max="1" width="15" customWidth="true"/>
        <col min="2" max="2" width="12" customWidth="true"/>
        <col min="3" max="3" width="20" customWidth="true"/>
        <col min="4" max="4" width="30" customWidth="true"/>
        <col min="5" max="5" width="40" customWidth="true"/>
        <col min="6" max="6" width="30" customWidth="true"/>
        <col min="7" max="7" width="30" customWidth="true"/>
    </cols>
    <sheetData>
        <row r="1" ht="25" customHeight="true">
            <c r="A1" s="1" t="str"><v>Item</v></c>
            <c r="B1" s="1" t="str"><v>Date</v></c>
            <c r="C1" s="1" t="str"><v>Department</v></c>
            <c r="D1" s="1" t="str"><v>Title</v></c>
            <c r="E1" s="1" t="str"><v>COA Observation</v></c>
            <c r="F1" s="1" t="str"><v>Recommendations</v></c>
            <c r="G1" s="1" t="str"><v>Comments / Justification</v></c>
        </row>';

$row = 2;
foreach ($records as $record) {
    $date = $record['date'] ? date('m/d/Y', strtotime($record['date'])) : '';
    $item = htmlspecialchars($record['item'] ?? '');
    $dept = htmlspecialchars($record['department_acronym'] ?: ($record['department_name'] ?? ''));
    $title = htmlspecialchars($record['title'] ?? '');
    $obs = htmlspecialchars($record['coa_observation'] ?? '');

    // Recommendations and Justifications
    $recs = [];
    $justs = [];
    try {
        $recs = json_decode($record['coa_recommendation'], true);
        if (!is_array($recs))
            $recs = $record['coa_recommendation'] ? [$record['coa_recommendation']] : [];
    }
    catch (Exception $e) {
        $recs = [];
    }

    try {
        $justs = json_decode($record['comments_justification'], true);
        if (!is_array($justs))
            $justs = $record['comments_justification'] ? [$record['comments_justification']] : [];
    }
    catch (Exception $e) {
        $justs = [];
    }

    $maxRows = max(count($recs), count($justs));
    $recItems = [];
    $justItems = [];
    for ($i = 0; $i < $maxRows; $i++) {
        $recItems[] = isset($recs[$i]) ? trim($recs[$i]) : '';
        $justItems[] = isset($justs[$i]) ? trim($justs[$i]) : '';
    }
    $recsStr = htmlspecialchars(implode("\n\n", $recItems));
    $justsStr = htmlspecialchars(implode("\n\n", $justItems));

    $sheetXml .= '
        <row r="' . $row . '">
            <c r="A' . $row . '" s="0" t="str"><v>' . $item . '</v></c>
            <c r="B' . $row . '" s="0" t="str"><v>' . $date . '</v></c>
            <c r="C' . $row . '" s="0" t="str"><v>' . $dept . '</v></c>
            <c r="D' . $row . '" s="0" t="str"><v>' . $title . '</v></c>
            <c r="E' . $row . '" s="0" t="str"><v>' . $obs . '</v></c>
            <c r="F' . $row . '" s="0" t="str"><v>' . $recsStr . '</v></c>
            <c r="G' . $row . '" s="0" t="str"><v>' . $justsStr . '</v></c>
        </row>';
    $row++;
}

$sheetXml .= '
    </sheetData>
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
$zipPath = sys_get_temp_dir() . '/AOM_Reports_' . uniqid() . '.xlsx';

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($tempDir . DIRECTORY_SEPARATOR, '', $filePath);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
    }
}
elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $sourceDirWin = str_replace('/', '\\', $tempDir);
    $zipPathWin = str_replace('/', '\\', $zipPath);
    $psScript = "[System.Reflection.Assembly]::LoadWithPartialName('System.IO.Compression.FileSystem') | Out-Null; " .
        "[System.IO.Compression.ZipFile]::CreateFromDirectory('" . str_replace("'", "''", $sourceDirWin) . "', '" . str_replace("'", "''", $zipPathWin) . "')";
    $tempScript = tempnam(sys_get_temp_dir(), 'zip') . '.ps1';
    file_put_contents($tempScript, $psScript);
    shell_exec('powershell -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($tempScript));
    unlink($tempScript);
}

// Send to browser
if (file_exists($zipPath)) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="AOM_Reports_' . date('Y-m-d') . '.xlsx"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
}

// Cleanup
function deleteDir($dir)
{
    if (!is_dir($dir))
        return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..')
            continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path))
            deleteDir($path);
        else
            unlink($path);
    }
    rmdir($dir);
}
deleteDir($tempDir);
exit();
?>
