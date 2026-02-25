<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    die("Access Denied: Only Super Admin can print reports.");
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Get report name for filename
$reportName = 'Cash_Balance';

// Handle export formats
$format = $_GET['format'] ?? 'html';

// Build filter conditions
$whereConditions = [];
$params = [];

$dateFilter = $_GET['date_filter'] ?? '';
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');

// Handle date filter
if ($dateFilter !== 'all_time') {
    if ($dateFilter === 'today') {
        $whereConditions[] = "date >= ?";
        $params[] = $today;
        $whereConditions[] = "date <= ?";
        $params[] = $today;
    }
    elseif ($dateFilter === 'weekly') {
        $whereConditions[] = "date >= ?";
        $params[] = $weekStart;
        $whereConditions[] = "date <= ?";
        $params[] = $today;
    }
    elseif ($dateFilter === 'monthly' || $dateFilter === 'monthly_period') {
        $selectedMonth = $_GET['selected_month'] ?? date('m');
        $selectedYear = $_GET['selected_year'] ?? date('Y');
        $monthStart = $selectedYear . '-' . $selectedMonth . '-01';

        if ($dateFilter === 'monthly') {
            $selectedDay = $_GET['selected_day'] ?? date('t', strtotime($monthStart));
            $monthEnd = date('Y-m-d', strtotime($selectedYear . '-' . $selectedMonth . '-' . $selectedDay));
        }
        else {
            $monthEnd = date('Y-m-t', strtotime($monthStart));
        }

        $whereConditions[] = "date >= ?";
        $params[] = $monthStart;
        $whereConditions[] = "date <= ?";
        $params[] = $monthEnd;
    }
    elseif ($dateFilter === 'annual') {
        $selectedYear = $_GET['selected_year'] ?? date('Y');
        $yearStart = $selectedYear . '-01-01';
        $yearEnd = $selectedYear . '-12-31';
        $whereConditions[] = "date >= ?";
        $params[] = $yearStart;
        $whereConditions[] = "date <= ?";
        $params[] = $yearEnd;
    }
    elseif ($dateFilter === 'custom' || (!empty($_GET['date_from']) && !empty($_GET['date_to']))) {
        if (!empty($_GET['date_from'])) {
            $whereConditions[] = "date >= ?";
            $params[] = $_GET['date_from'];
        }
        if (!empty($_GET['date_to'])) {
            $whereConditions[] = "date <= ?";
            $params[] = $_GET['date_to'];
        }
    }
}

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(check_no LIKE ? OR dv_or_no LIKE ? OR particulars LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Fetch PPE data from database
try {
    $orderBy = 'date ASC, id ASC';
    $sql = "SELECT id, date, check_no, dv_or_no, particulars, debit, credit, balance FROM ppe $whereClause ORDER BY $orderBy";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ppeRecords = $stmt->fetchAll();
}
catch (Exception $e) {
    $ppeRecords = [];
    $error = htmlspecialchars($e->getMessage());
}

// Get Balance Forward (last balance before filtered month) if filtering by month
$balanceForward = 0;
$balanceForwardDate = '';
if ($dateFilter === 'monthly') {
    $selectedMonth = $_GET['selected_month'] ?? date('m');
    $selectedYear = $_GET['selected_year'] ?? date('Y');
    $monthStart = $selectedYear . '-' . $selectedMonth . '-01';

    // Get the last transaction before the filtered month starts
    try {
        $forwardSql = "SELECT date, balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
        $forwardStmt = $conn->prepare($forwardSql);
        $forwardStmt->execute([$monthStart]);
        $forwardRecord = $forwardStmt->fetch();

        if ($forwardRecord) {
            $balanceForward = $forwardRecord['balance'];
            $balanceForwardDate = date('m/d/Y', strtotime($forwardRecord['date']));
        }
    }
    catch (Exception $e) {
        $balanceForward = 0;
    }
}
elseif ($dateFilter === 'custom' && !empty($_GET['date_from'])) {
    $dateFrom = $_GET['date_from'];

    // Get the last transaction before the custom start date
    try {
        $forwardSql = "SELECT date, balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
        $forwardStmt = $conn->prepare($forwardSql);
        $forwardStmt->execute([$dateFrom]);
        $forwardRecord = $forwardStmt->fetch();

        if ($forwardRecord) {
            $balanceForward = $forwardRecord['balance'];
            $balanceForwardDate = date('m/d/Y', strtotime($forwardRecord['date']));
        }
    }
    catch (Exception $e) {
        $balanceForward = 0;
    }
}

// Starting balance used to recalculate running balances in the report
$startingBalance = 0.00;

try {
    $beforeParams = [];
    $beforeFilterSql = '';

    if ($dateFilter === 'today') {
        $beforeFilterSql = "SELECT balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
        $beforeParams = [$today];
    }
    elseif ($dateFilter === 'weekly') {
        $beforeFilterSql = "SELECT balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
        $beforeParams = [$weekStart];
    }
    elseif ($dateFilter === 'monthly' || $dateFilter === 'monthly_period') {
        $selectedMonth = $_GET['selected_month'] ?? date('m');
        $selectedYear = $_GET['selected_year'] ?? date('Y');
        $monthStart = $selectedYear . '-' . $selectedMonth . '-01';
        $beforeFilterSql = "SELECT balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
        $beforeParams = [$monthStart];
    }
    elseif ($dateFilter === 'annual') {
        $selectedYear = $_GET['selected_year'] ?? date('Y');
        $yearStart = $selectedYear . '-01-01';
        $beforeFilterSql = "SELECT balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
        $beforeParams = [$yearStart];
    }
    elseif ($dateFilter === 'custom' || (!empty($_GET['date_from']) && !empty($_GET['date_to']))) {
        if (!empty($_GET['date_from'])) {
            $beforeFilterSql = "SELECT balance FROM ppe WHERE date < ? ORDER BY date DESC, id DESC LIMIT 1";
            $beforeParams = [$_GET['date_from']];
        }
    }

    if (!empty($beforeFilterSql) && !empty($beforeParams)) {
        $beforeStmt = $conn->prepare($beforeFilterSql);
        $beforeStmt->execute($beforeParams);
        $beforeRecord = $beforeStmt->fetch();
        $startingBalance = $beforeRecord ? floatval($beforeRecord['balance']) : 0.00;
    }
}
catch (Exception $e) {
    $startingBalance = 0.00;
}

// Handle Excel export
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Cash Balance Report');
    $spreadsheet->getDefaultStyle()->getFont()->setName('Century Gothic')->setSize(14);

    // Title and Header
    $sheet->setCellValue('A1', 'PPE PROVIDENT FUND INC.');
    $sheet->setCellValue('A2', 'Cash Balance');
    $dateFilter = $_GET['date_filter'] ?? '';
    $yearNum = $_GET['selected_year'] ?? date('Y');
    $monthNum = $_GET['selected_month'] ?? date('m');

    if ($dateFilter === 'all_time') {
        $dateText = 'As of ' . date('F d, Y');
    }
    elseif ($dateFilter === 'annual') {
        $dateText = "As of December 31, " . $yearNum;
    }
    elseif ($dateFilter === 'monthly') {
        $selectedDay = $_GET['selected_day'] ?? date('t', strtotime($yearNum . '-' . $monthNum . '-01'));
        $endOfMonth = date('F d, Y', strtotime($yearNum . '-' . $monthNum . '-' . $selectedDay));
        $dateText = "As of " . $endOfMonth;
    }
    elseif ($dateFilter === 'monthly_period') {
        $dateText = "For the Month of " . date('F t, Y', mktime(0, 0, 0, $monthNum, 1, $yearNum));
    }
    elseif ($dateFilter === 'custom' || (!empty($_GET['date_from']) && !empty($_GET['date_to']))) {
        $dateFrom = date('F d, Y', strtotime($_GET['date_from']));
        $dateTo = date('F d, Y', strtotime($_GET['date_to']));
        $dateText = "From " . $dateFrom . " to " . $dateTo;
    }
    else {
        $dateText = 'As of ' . date('F d, Y');
    }
    $sheet->setCellValue('A3', $dateText);

    $sheet->mergeCells('A1:G1');
    $sheet->mergeCells('A2:G2');
    $sheet->mergeCells('A3:G3');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A3')->getFont()->setSize(14);
    $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // Header row
    $headers = ['DATE', 'PARTICULARS', 'CHECK NO.', 'DV/OR NO.', 'DEBIT', 'CREDIT', 'BALANCE'];
    $sheet->fromArray($headers, NULL, 'A5');

    // Style the header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A5:G5')->applyFromArray($headerStyle);

    $currentRow = 6;
    $totalDebit = 0;
    $totalCredit = 0;
    $currentBalance = $startingBalance;

    if ($dateFilter === 'monthly' && $balanceForwardDate) {
        $sheet->setCellValue('A' . $currentRow, $balanceForwardDate);
        $sheet->setCellValue('B' . $currentRow, 'BALANCE FORWARDED');
        $sheet->setCellValue('G' . $currentRow, $currentBalance);

        $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->getFont()->setBold(true);
        $currentRow++;
    }

    foreach ($ppeRecords as $record) {
        $totalDebit += $record['debit'];
        $totalCredit += $record['credit'];
        $formattedDate = date('m/d/Y', strtotime($record['date']));

        if ($dateFilter === 'all_time') {
            $currentBalance = floatval($record['balance']);
        }
        else {
            $currentBalance = $currentBalance + $record['credit'] - $record['debit'];
        }

        $sheet->setCellValue('A' . $currentRow, $formattedDate);
        $sheet->setCellValue('B' . $currentRow, strtoupper($record['particulars']));
        $sheet->setCellValue('C' . $currentRow, $record['check_no'] ?? '');
        $sheet->setCellValue('D' . $currentRow, $record['dv_or_no'] ?? '');
        $sheet->setCellValue('E' . $currentRow, $record['debit']);
        $sheet->setCellValue('F' . $currentRow, $record['credit']);
        $sheet->setCellValue('G' . $currentRow, $currentBalance);

        $currentRow++;
    }

    $sheet->setCellValue('B' . $currentRow, 'TOTAL');
    $sheet->setCellValue('E' . $currentRow, $totalDebit);
    $sheet->setCellValue('F' . $currentRow, $totalCredit);
    $sheet->setCellValue('G' . $currentRow, $currentBalance);

    $totalStyle = [
        'font' => ['bold' => true],
        'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray($totalStyle);
    $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Formatting
    $lastRow = $currentRow;
    $sheet->getStyle('A5:G' . $lastRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C6:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E6:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E6:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

    $preparedByName = strtoupper($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System User');
    $preparedByTitle = $_SESSION['position'] ?? $_SESSION['designation'] ?? $_SESSION['job_title'] ?? 'Treasurer';
    $preparedByRow = $currentRow + 3;

    $sheet->mergeCells('A' . $preparedByRow . ':G' . $preparedByRow);
    $sheet->mergeCells('A' . ($preparedByRow + 2) . ':G' . ($preparedByRow + 2));
    $sheet->mergeCells('A' . ($preparedByRow + 3) . ':G' . ($preparedByRow + 3));
    $sheet->setCellValue('A' . $preparedByRow, 'Prepared by:');
    $sheet->setCellValue('A' . ($preparedByRow + 2), $preparedByName);
    $sheet->setCellValue('A' . ($preparedByRow + 3), $preparedByTitle);
    $sheet->getStyle('A' . ($preparedByRow + 2))->getFont()->setBold(true);

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(50);

    $filename = $reportName . '_' . date('Y-m-d_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Handle PDF export
if ($format === 'pdf') {
    // Include the controller
    require_once __DIR__ . '/app/controllers/PPEReportController.php';

    $controller = new PPEReportController($conn);

    // Prepare filters
    $filters = [
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'check_no' => $_GET['check_no'] ?? null,
        'dv_or_no' => $_GET['dv_or_no'] ?? null,
        'particulars' => $_GET['particulars'] ?? null,
        'records' => $ppeRecords,
    ];

    try {
        $controller->exportCashBalancePDF($filters);
    }
    catch (Exception $e) {
        die('Error generating PDF: ' . htmlspecialchars($e->getMessage()));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPE Provident Fund - Full Table</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
            font-size: 14px;
            background-color: #f5f5f5;
        }
        
        @media print {
            body {
                font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
                font-size:14px;
                background-color: white;
                margin: 0;
                padding: 0;
            }
            * {
                font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif !important;
            }
            @page {
                size: landscape;
                margin: 0.5in;
            }
            .no-print {
                display: none !important;
            }
            .page {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                page-break-after: auto;
                height: auto !important;
                min-height: 0 !important;
            }
        }
        
        .page {
            width: 13in;
            height: 8.5in;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
        }
        
        td {
            height: auto;
            padding: 8px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Header */
        .header {
            text-align: left;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .header-date {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .print-button {
            margin-bottom: 10px;
        }
        
        .print-button button {
            padding: 8px 20px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button button:hover {
            background-color: #1d4ed8;
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    <div class="no-print print-button">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div style="text-align: left; margin-bottom: 15px;">
            <h1 style="font-size: 14pt; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">PPE PROVIDENT FUND INC.</h1>
            <h2 style="font-size: 14pt; margin-bottom: 5px; text-transform: uppercase;">Cash Balance</h2>
            <div style="font-size: 14pt; color: black;">
                <div><?php
$dateFilter = $_GET['date_filter'] ?? '';
$yearNum = $_GET['selected_year'] ?? date('Y');
$monthNum = $_GET['selected_month'] ?? date('m');

if ($dateFilter === 'all_time') {
    echo 'As of ' . date('F d, Y');
}
elseif ($dateFilter === 'annual') {
    echo "As of December 31, " . $yearNum;
}
elseif ($dateFilter === 'monthly') {
    $selectedDay = $_GET['selected_day'] ?? date('t', strtotime($yearNum . '-' . $monthNum . '-01'));
    $endOfMonth = date('F d, Y', strtotime($yearNum . '-' . $monthNum . '-' . $selectedDay));
    echo "As of " . $endOfMonth;
}
elseif ($dateFilter === 'monthly_period') {
    echo "For the Month of " . date('F t, Y', mktime(0, 0, 0, $monthNum, 1, $yearNum));
}
elseif ($dateFilter === 'custom' || (!empty($_GET['date_from']) && !empty($_GET['date_to']))) {
    $dateFrom = date('F d, Y', strtotime($_GET['date_from']));
    $dateTo = date('F d, Y', strtotime($_GET['date_to']));
    echo "FROM " . strtoupper($dateFrom) . " TO " . strtoupper($dateTo);
}
else {
    echo strtoupper(date('F d, Y'));
}
?></div>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">DATE</th>
                    <th style="width: 22%;">PARTICULARS</th>
                    <th style="width: 8%;">CHECK NO.</th>
                    <th style="width: 8%;">DV/OR NO.</th>
                    <th style="width: 11%;">DEBIT</th>
                    <th style="width: 11%;">CREDIT</th>
                    <th style="width: 12%;">BALANCE</th>
                </tr>
            </thead>
            <tbody>
                <?php
$totalDebit = 0;
$totalCredit = 0;
$currentBalance = $startingBalance;
$hasRecords = false;

// Display Balance Forwarded Row if applicable
if (($dateFilter === 'monthly' || ($dateFilter === 'custom' && !empty($_GET['date_from']))) && $balanceForwardDate) {
    $hasRecords = true;
    echo '<tr style="font-weight: bold;">';
    echo '<td class="text-center">' . strtoupper(htmlspecialchars($balanceForwardDate)) . '</td>';
    echo '<td>' . strtoupper('BALANCE FORWARDED') . '</td>';
    echo '<td class="text-center">&nbsp;</td>';
    echo '<td class="text-center">&nbsp;</td>';
    echo '<td class="text-right">&nbsp;</td>';
    echo '<td class="text-right">&nbsp;</td>';
    echo '<td class="text-right">' . number_format($currentBalance, 2) . '</td>';
    echo '</tr>';
}

if (count($ppeRecords) > 0) {
    $hasRecords = true;
    foreach ($ppeRecords as $record) {
        $totalDebit += $record['debit'];
        $totalCredit += $record['credit'];

        if ($dateFilter === 'all_time') {
            $currentBalance = floatval($record['balance']);
        }
        else {
            $currentBalance = $currentBalance + $record['credit'] - $record['debit'];
        }

        $formattedDate = date('m/d/Y', strtotime($record['date']));
        echo '<tr>';
        echo '<td class="text-center">' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
        echo '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
        echo '<td class="text-center">' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
        echo '<td class="text-center">' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
        echo '<td class="text-right">' . number_format($record['debit'], 2) . '</td>';
        echo '<td class="text-right">' . number_format($record['credit'], 2) . '</td>';
        echo '<td class="text-right">' . number_format($currentBalance, 2) . '</td>';
        echo '</tr>';
    }
}

if ($hasRecords) {
    echo '<tr style="font-weight: bold;">';
    echo '<td colspan="4" style="text-align: right; border: none;">TOTAL</td>';
    echo '<td style="text-align: right; border: none;">' . number_format($totalDebit, 2) . '</td>';
    echo '<td style="text-align: right; border: none;">' . number_format($totalCredit, 2) . '</td>';
    echo '<td style="text-align: right; border: none;">' . number_format($currentBalance, 2) . '</td>';
    echo '</tr>';
}
else {
    echo '<tr><td colspan="7" class="text-center">NO RECORDS FOUND</td></tr>';
}
?>
            </tbody>
        </table>

        <!-- Prepared By Section -->
        <div style="margin-top: 20px; width: 30%; font-family: 'Inter', sans-serif;">
            <p style="font-size: 14px; margin: 0;">Prepared by:</p>
            <p style="margin-top: 30px; font-size: 14px; margin-bottom: 0; font-weight: bold;">MARIA LAARNI B. CO</p>
            <p style="font-size: 14px; margin: 0;">Treasurer</p>
        </div>
    </div>
</body>
</html>
