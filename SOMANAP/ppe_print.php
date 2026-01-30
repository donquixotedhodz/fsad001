<?php
session_start();
require_once __DIR__ . '/../config.php';

// Get report name for filename
$reportName = 'Check_Issued_Receiving';

// Handle export formats
$format = $_GET['format'] ?? 'html';

// Build filter conditions
$whereConditions = ["check_no != 'ONLINE'"];
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
try {
    $sql = "SELECT check_no, dv_or_no, particulars, (debit + credit) as amount, date FROM ppe $whereClause ORDER BY date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ppeRecords = $stmt->fetchAll();
} catch (Exception $e) {
    $ppeRecords = [];
    $error = htmlspecialchars($e->getMessage());
}

// Handle Excel export
if ($format === 'excel') {
    $filename = $reportName . '_' . date('Y-m-d_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'h1 { font-size: 16px; font-weight: bold; margin: 10px 0 5px 0; text-transform: uppercase; }';
    echo 'h2 { font-size: 14px; font-weight: normal; margin: 5px 0 10px 0; text-transform: uppercase; }';
    echo '.date { font-size: 12px; margin-bottom: 15px; text-transform: uppercase; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; font-family: Arial, sans-serif; }';
    echo 'th { background-color: #e0e0e0; border: 1px solid #000; padding: 8px; text-align: left; font-weight: bold; font-size: 11px; text-transform: uppercase; }';
    echo 'td { border: 1px solid #ccc; padding: 8px; height: 20px; }';
    echo '.text-right { text-align: right; }';
    echo '.total-row { font-weight: bold; background-color: #f5f5f5; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h1>PPE PROVIDENT FUND INC.</h1>';
    echo '<h2>Check Issued - Receiving</h2>';
    echo '<div class="date">' . strtoupper(date('F d, Y')) . '</div>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 12%;">Check No.</th>';
    echo '<th style="width: 12%;">DV No.</th>';
    echo '<th style="width: 40%;">Name</th>';
    echo '<th style="width: 18%; text-align: right;">Amount</th>';
    echo '<th style="width: 18%; text-align: right;">Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $totalAmount = 0;
    foreach ($ppeRecords as $record) {
        $totalAmount += $record['amount'];
        $formattedDate = date('m/d/Y', strtotime($record['date']));
        echo '<tr>';
        echo '<td style="text-align: center;">' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
        echo '<td style="text-align: center;">' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
        echo '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
        echo '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
        echo '<td class="text-right">' . strtoupper($formattedDate) . '</td>';
        echo '</tr>';
    }
    
    echo '<tr class="total-row">';
    echo '<td colspan="3" style="text-align: right;">TOTAL</td>';
    echo '<td class="text-right">' . number_format($totalAmount, 2) . '</td>';
    echo '<td></td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
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
    ];
    
    try {
        $controller->exportCheckIssuedReceivingPDF($filters);
    } catch (Exception $e) {
        die('Error generating PDF: ' . htmlspecialchars($e->getMessage()));
    }
}

$dateGenerated = date('F d, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPE Provident Fund - Checks Issued</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            @page {
                size: 13in 8.5in landscape;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .page {
                margin: 0;
                padding: 0;
                page-break-after: always;
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
        
        /* Header */
        .header {
            text-align: left;
            margin-bottom: 10px;
            border-right: none;
            padding-right: 0;
        }
        
        .header h3 {
            font-size: 11px;
            font-weight: bold;
            margin: 0;
            writing-mode: initial;
            text-orientation: initial;
            float: none;
            margin-left: 0;
        }
        
        .title {
            text-align: left;
            margin-bottom: 15px;
        }
        
        .title h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .title h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 10px;
            color: #666;
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 10px;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
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
            font-size: 9px;
            text-align: center;
        }
        
        td {
            height: 25px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Footer Section */
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            font-size: 10px;
        }
        
        .signature-box {
            flex: 1;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 5px;
        }
        
        .signature-label {
            font-weight: bold;
            font-size: 9px;
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
        <button onclick="window.print()">Print</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div style="text-align: left; margin-bottom: 15px;">
            <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">PPE PROVIDENT FUND INC.</h1>
            <h2 style="font-size: 14px; margin-bottom: 5px; text-transform: uppercase;">CHECKS ISSUED-Receiving</h2>
            <div style="font-size: 14px; color: black; text-transform: uppercase;">
                <div><?php echo strtoupper(date('F d, Y')); ?></div>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">CHECK NO.</th>
                    <th style="width: 8%;">DV NO.</th>
                    <th style="width: 16%;">NAME</th>
                    <th style="width: 12%; text-align: right;">AMOUNT</th>
                    <th style="width: 10%;">DATE</th>
                    <th style="width: 12%;">DATE RELEASED</th>
                    <th style="width: 21%;">NAME</th>
                    <th style="width: 13%;">SIGNATURE</th>
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
                        echo '<td>' . htmlspecialchars(strtoupper($record['check_no'] ?? '')) . '</td>';
                        echo '<td>' . htmlspecialchars(strtoupper($record['dv_or_no'] ?? '')) . '</td>';
                        echo '<td>' . htmlspecialchars(strtoupper($record['particulars'])) . '</td>';
                        echo '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
                        echo '<td>' . htmlspecialchars(strtoupper($formattedDate)) . '</td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '<td></td>';
                        echo '</tr>';
                    }
                    // Add total row
                    echo '<tr style="font-weight: bold; border: none;">';
                    echo '<td colspan="3" style="text-align: right; border: none;">TOTAL</td>';
                    echo '<td class="text-right" style="border: none;">' . number_format($totalAmount, 2) . '</td>';
                    echo '<td style="border: none;"></td>';
                    echo '<td style="border: none;"></td>';
                    echo '<td style="border: none;"></td>';
                    echo '<td style="border: none;"></td>';
                    echo '</tr>';
                } else {
                    echo '<tr><td colspan="8" class="text-center">NO RECORDS FOUND</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <!-- Footer Section
        <div class="footer">
            <div class="signature-box">
                <div class="signature-label">DATE RELEASED</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">NAME</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">SIGNATURE</div>
            </div>
        </div> -->
    </div>
</body>
</html>
