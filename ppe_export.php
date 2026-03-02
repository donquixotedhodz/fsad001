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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

MainController::requireAuth();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: dashboard.php");
    exit();
}

// Build filter conditions
$whereConditions = [];
$params = [];

$dateFilter = $_GET['date_filter'] ?? '';
$today = date('Y-m-d');

if ($dateFilter === 'today') {
    $whereConditions[] = "date = ?";
    $params[] = $today;
}
elseif ($dateFilter === 'weekly') {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $whereConditions[] = "date >= ?";
    $params[] = $weekStart;
    $whereConditions[] = "date <= ?";
    $params[] = $today;
}
elseif ($dateFilter === 'monthly') {
    $selectedMonth = $_GET['selected_month'] ?? date('m');
    $selectedYear = $_GET['selected_year'] ?? date('Y');
    $monthStart = $selectedYear . '-' . $selectedMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $whereConditions[] = "date >= ?";
    $params[] = $monthStart;
    $whereConditions[] = "date <= ?";
    $params[] = $monthEnd;
}
elseif ($dateFilter === 'annual') {
    $selectedYear = $_GET['selected_year'] ?? date('Y');
    $whereConditions[] = "YEAR(date) = ?";
    $params[] = $selectedYear;
}
elseif ($dateFilter === 'custom') {
    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "date >= ?";
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "date <= ?";
        $params[] = $_GET['date_to'];
    }
}

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(check_no LIKE ? OR dv_or_no LIKE ? OR particulars LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch PPE data from database
$sql = "SELECT date, check_no, dv_or_no, particulars, debit, credit, balance 
        FROM ppe 
        $whereClause 
        ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('PPE Reports');

// Header row
$headers = ['Date', 'Check No.', 'DV No.', 'Particulars / Name', 'Debit', 'Credit', 'Balance'];
$sheet->fromArray($headers, NULL, 'A1');

// Style the header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']], // Emerald-500 color to match UI
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

$currentRow = 2;
$totalDebit = 0;
$totalCredit = 0;

foreach ($records as $record) {
    $date = $record['date'] ? date('m/d/Y', strtotime($record['date'])) : '';

    $sheet->setCellValue('A' . $currentRow, $date);
    $sheet->setCellValue('B' . $currentRow, $record['check_no'] ?? '');
    $sheet->setCellValue('C' . $currentRow, $record['dv_or_no'] ?? '');
    $sheet->setCellValue('D' . $currentRow, $record['particulars'] ?? '');
    $sheet->setCellValue('E' . $currentRow, $record['debit']);
    $sheet->setCellValue('F' . $currentRow, $record['credit']);
    $sheet->setCellValue('G' . $currentRow, $record['balance']);

    $totalDebit += $record['debit'];
    $totalCredit += $record['credit'];

    $currentRow++;
}

// Add Total Row
if ($currentRow > 2) {
    $sheet->setCellValue('D' . $currentRow, 'TOTAL:');
    $sheet->setCellValue('E' . $currentRow, $totalDebit);
    $sheet->setCellValue('F' . $currentRow, $totalCredit);
    $sheet->setCellValue('G' . $currentRow, !empty($records) ? end($records)['balance'] : 0);

    // Style the total row
    $totalStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray($totalStyle);
    $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

// Global formatting
$lastRow = $currentRow;
if ($lastRow >= 1) {
    $bodyStyle = [
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:G' . $lastRow)->applyFromArray($bodyStyle);

    // Number formatting for currency columns
    $sheet->getStyle('E2:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

    // Center Align Date, Check No, DV No
    $sheet->getStyle('A2:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Right Align Amount columns
    $sheet->getStyle('E2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
}

// Auto-size columns
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set specific widths for text-heavy columns if needed
$sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(50); // Particulars/Name

// Redirect output to a client's web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="PPE_Reports_' . date('Y-m-d_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
