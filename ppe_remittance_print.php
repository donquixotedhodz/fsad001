<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    die("Access Denied: Only Super Admin can print reports.");
}
require_once __DIR__ . '/../config.php';

// Get report name for filename
$reportName = 'Remittance';

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Handle export formats
$format = $_GET['format'] ?? 'html';

// Build filter conditions - only show remittances
$whereConditions = ["particulars LIKE '%REMITTANCE%'"];
$params = [];

// Handle date filters
$dateFilter = $_GET['date_filter'] ?? '';
$selectedYear = $_GET['selected_year'] ?? date('Y');
$selectedMonth = $_GET['selected_month'] ?? date('m');

if ($dateFilter !== 'all_time') {
    if ($dateFilter === 'today') {
        $_GET['date_from'] = date('Y-m-d');
        $_GET['date_to'] = date('Y-m-d');
    }
    elseif ($dateFilter === 'weekly') {
        $_GET['date_from'] = date('Y-m-d', strtotime('monday this week'));
        $_GET['date_to'] = date('Y-m-d', strtotime('sunday this week'));
    }
    elseif ($dateFilter === 'monthly' || $dateFilter === 'monthly_period') {
        $_GET['date_from'] = date('Y-m-d', strtotime("$selectedYear-$selectedMonth-01"));
        $_GET['date_to'] = date('Y-m-t', strtotime("$selectedYear-$selectedMonth-01"));
    }
    elseif ($dateFilter === 'annual') {
        $_GET['date_from'] = "$selectedYear-01-01";
        $_GET['date_to'] = "$selectedYear-12-31";
    }

    if (!empty($_GET['date_from'])) {
        $whereConditions[] = "date >= ?";
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $whereConditions[] = "date <= ?";
        $params[] = $_GET['date_to'];
    }
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

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Fetch PPE data from database
try {
    $sql = "SELECT date, check_no, dv_or_no, particulars, CASE WHEN debit > 0 AND credit = 0 THEN debit WHEN credit > 0 AND debit = 0 THEN credit ELSE ABS(credit - debit) END as amount FROM ppe $whereClause ORDER BY date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ppeRecords = $stmt->fetchAll();
}
catch (Exception $e) {
    $ppeRecords = [];
    $error = htmlspecialchars($e->getMessage());
}

// Handle Excel export
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Remittance Report');
    $spreadsheet->getDefaultStyle()->getFont()->setName('Century Gothic')->setSize(14);

    // Title and Header
    $sheet->setCellValue('A1', 'PPE PROVIDENT FUND INC.');
    $sheet->setCellValue('A2', 'Remittance');
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

    $sheet->mergeCells('A1:E1');
    $sheet->mergeCells('A2:E2');
    $sheet->mergeCells('A3:E3');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A3')->getFont()->setSize(14);
    $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // Header row
    $headers = ['DATE', 'DESCRIPTION', 'CHECK NO.', 'DV NO.', 'AMOUNT'];
    $sheet->fromArray($headers, NULL, 'A5');

    // Style the header
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A5:E5')->applyFromArray($headerStyle);

    $currentRow = 6;
    $totalAmount = 0;
    foreach ($ppeRecords as $record) {
        $totalAmount += $record['amount'];
        $formattedDate = date('m/d/Y', strtotime($record['date']));

        $sheet->setCellValue('A' . $currentRow, $formattedDate);
        $sheet->setCellValue('B' . $currentRow, strtoupper($record['particulars']));
        $sheet->setCellValue('C' . $currentRow, $record['check_no'] ?? '');
        $sheet->setCellValue('D' . $currentRow, $record['dv_or_no'] ?? '');
        $sheet->setCellValue('E' . $currentRow, $record['amount']);

        $currentRow++;
    }

    // Total row
    $sheet->setCellValue('D' . $currentRow, 'TOTAL');
    $sheet->setCellValue('E' . $currentRow, $totalAmount);

    $totalStyle = [
        'font' => ['bold' => true],
        'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($totalStyle);
    $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $currentRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

    // Formatting
    $lastRow = $currentRow;
    $sheet->getStyle('A5:E' . $lastRow)->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
    $sheet->getStyle('A6:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C6:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('E6:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E6:E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

    $preparedByName = strtoupper($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System User');
    $preparedByTitle = $_SESSION['position'] ?? $_SESSION['designation'] ?? $_SESSION['job_title'] ?? 'Treasurer';
    $preparedByRow = $currentRow + 3;

    $sheet->mergeCells('A' . $preparedByRow . ':E' . $preparedByRow);
    $sheet->mergeCells('A' . ($preparedByRow + 2) . ':E' . ($preparedByRow + 2));
    $sheet->mergeCells('A' . ($preparedByRow + 3) . ':E' . ($preparedByRow + 3));
    $sheet->setCellValue('A' . $preparedByRow, 'Prepared by:');
    $sheet->setCellValue('A' . ($preparedByRow + 2), $preparedByName);
    $sheet->setCellValue('A' . ($preparedByRow + 3), $preparedByTitle);
    $sheet->getStyle('A' . ($preparedByRow + 2))->getFont()->setBold(true);

    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getColumnDimension('C')->setAutoSize(false)->setWidth(50);

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
        $controller->exportRemittancePDF($filters);
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
    <title>PPE Provident Fund - Remittance</title>
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
                font-size: 14px;
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
            height: 20px;
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
            font-weight: normal;
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
            background-color: #4f46e5;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button button:hover {
            background-color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="no-print print-button">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div style="text-align: left; margin-bottom: 15px;">
            <h1 style="font-size: 14pt; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">PPE PROVIDENT FUND INC.</h1>
            <h2 style="font-size: 14pt; margin-bottom: 5px; text-transform: uppercase;">Remittance</h2>
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
elseif ($dateFilter === 'weekly') {
    $weekStartText = strtoupper(date('F d, Y', strtotime('monday this week')));
    $weekEndText = strtoupper(date('F d, Y', strtotime('sunday this week')));
    echo "FOR THE WEEK OF " . $weekStartText . " - " . $weekEndText;
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
                    <th style="width: 12%;">DATE</th>
                    <th style="width: 40%;">DESCRIPTION</th>
                    <th style="width: 15%;">CHECK NO.</th>
                    <th style="width: 15%;">DV NO.</th>
                    <th style="width: 18%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <?php
if (count($ppeRecords) > 0) {
    $totalAmount = 0;

    foreach ($ppeRecords as $record) {
        $totalAmount += $record['amount'];

        $formattedDate = date('m/d/Y', strtotime($record['date']));
        echo '<tr>';
        echo '<td class="text-center">' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
        echo '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
        echo '<td class="text-center">' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
        echo '<td class="text-center">' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
        echo '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
        echo '</tr>';
    }

    // Add total row
    echo '<tr style="font-weight: bold;">';
    echo '<td colspan="4" style="text-align: right; border: none;"></td>';
    echo '<td style="text-align: right; border: none;">TOTAL&nbsp;&nbsp;&nbsp;<span style="text-decoration: underline;">' . number_format($totalAmount, 2) . '</span></td>';
    echo '</tr>';
}
else {
    echo '<tr><td colspan="5" class="text-center">NO REMITTANCE RECORDS FOUND</td></tr>';
}
?>
            </tbody>
        </table>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
