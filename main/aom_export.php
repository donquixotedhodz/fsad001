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
use PhpOffice\PhpSpreadsheet\Style\Color;

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

// Main report title row (same as print layout)
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'Justification of the Absense of Management Response to Audit Observation Memoranda (AOM) Issued by the Commission on Audit (COA)');
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 12,
        'color' => ['argb' => Color::COLOR_BLACK]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => Color::COLOR_WHITE]
    ],
    'borders' => [
        'outline' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => Color::COLOR_BLACK]
        ]
    ]
]);

// Column headers row
$headers = ['Item', 'Date', 'Department', 'Title', 'COA Observation', 'Recommendations', 'Comments / Justification'];
$sheet->fromArray($headers, null, 'A2');
$sheet->getStyle('A2:G2')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 11,
        'color' => ['argb' => Color::COLOR_BLACK]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFF2F2F2']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => Color::COLOR_BLACK]
        ]
    ]
]);

$sheet->getRowDimension(1)->setRowHeight(36);
$sheet->getRowDimension(2)->setRowHeight(24);

$currentRow = 3;
foreach ($records as $record) {
    $date = $record['date'] ? date('F d, Y', strtotime($record['date'])) : '';
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
if ($lastRow >= 2) {
    $bodyStyle = [
        'font' => [
            'size' => 11,
            'color' => ['argb' => Color::COLOR_BLACK]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_TOP,
            'wrapText' => true
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => Color::COLOR_WHITE]
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => Color::COLOR_BLACK]
            ]
        ]
    ];
    $sheet->getStyle('A3:G' . $lastRow)->applyFromArray($bodyStyle);

    // Center Align Item, Date, Department columns
    $sheet->getStyle('A3:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Left align text-heavy columns similar to print readability
    $sheet->getStyle('D3:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    for ($r = 3; $r <= $lastRow; $r++) {
        $sheet->getRowDimension($r)->setRowHeight(-1);
    }
}

// Match print column proportions
$sheet->getColumnDimension('A')->setWidth(10);
$sheet->getColumnDimension('B')->setWidth(14);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(28);
$sheet->getColumnDimension('E')->setWidth(36);
$sheet->getColumnDimension('F')->setWidth(36);
$sheet->getColumnDimension('G')->setWidth(36);

// Freeze headers for easier review
$sheet->freezePane('A3');

// Redirect output to a client's web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="AOM_Reports_' . date('Y-m-d_His') . '.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
