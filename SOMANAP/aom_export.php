<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('AOM Reports');

// Header row
$headers = ['Item', 'Date', 'Department', 'Title', 'COA Observation', 'Recommendations', 'Comments / Justification'];
$sheet->fromArray($headers, NULL, 'A1');

// Style the header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

$currentRow = 2;
foreach ($records as $record) {
    $date = $record['date'] ? date('M/d/Y', strtotime($record['date'])) : '';
    $item = $record['item'] ?? '';
    $dept = $record['department_acronym'] ?: ($record['department_name'] ?? '');
    $title = $record['title'] ?? '';
    $obs = $record['coa_observation'] ?? '';

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

    $maxSubRows = max(count($recs), count($justs));
    $startRow = $currentRow;

    for ($i = 0; $i < $maxSubRows; $i++) {
        $rec = isset($recs[$i]) ? trim($recs[$i]) : '';
        $just = isset($justs[$i]) ? trim($justs[$i]) : '';

        if ($i === 0) {
            $sheet->setCellValue('A' . $currentRow, $item);
            $sheet->setCellValue('B' . $currentRow, $date);
            $sheet->setCellValue('C' . $currentRow, $dept);
            $sheet->setCellValue('D' . $currentRow, $title);
            $sheet->setCellValue('E' . $currentRow, $obs);
        }
        $sheet->setCellValue('F' . $currentRow, $rec);
        $sheet->setCellValue('G' . $currentRow, $just);
        $currentRow++;
    }

    if ($maxSubRows > 1) {
        $endRow = $currentRow - 1;
        // Merge cells for the main record info
        $sheet->mergeCells("A$startRow:A$endRow");
        $sheet->mergeCells("B$startRow:B$endRow");
        $sheet->mergeCells("C$startRow:C$endRow");
        $sheet->mergeCells("D$startRow:D$endRow");
        $sheet->mergeCells("E$startRow:E$endRow");
    }
}

// Global formatting
$lastRow = $currentRow - 1;
if ($lastRow >= 1) {
    $bodyStyle = [
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:G' . $lastRow)->applyFromArray($bodyStyle);

    // Center Align Item, Date, Department columns
    $sheet->getStyle('A2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Justify Align Title, Observation, Recs, Justs (Spreadsheet doesn't have true "justify" like HTML, but we can set it)
    $sheet->getStyle('D2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_JUSTIFY);
}

// Auto-size columns with limits
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
// Set specific widths for text-heavy columns
$sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(30); // Title
$sheet->getColumnDimension('E')->setAutoSize(false)->setWidth(40); // Observation
$sheet->getColumnDimension('F')->setAutoSize(false)->setWidth(40); // Recommendations
$sheet->getColumnDimension('G')->setAutoSize(false)->setWidth(40); // Justifications

// Redirect output to a client's web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="AOM_Reports_' . date('Y-m-d_His') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
