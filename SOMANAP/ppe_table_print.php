<?php
session_start();
require_once __DIR__ . '/../config.php';

// Fetch PPE data from database
try {
    $stmt = $conn->prepare("SELECT date, check_no, dv_or_no, particulars, debit, credit, balance FROM ppe ORDER BY date ASC");
    $stmt->execute();
    $ppeRecords = $stmt->fetchAll();
} catch (Exception $e) {
    $ppeRecords = [];
    $error = htmlspecialchars($e->getMessage());
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
            font-size: 16px;
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
            font-size: 10px;
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
</head>
<body>
    <div class="no-print print-button">
        <button onclick="window.print()">🖨️ Print</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div style="text-align: left; margin-bottom: 15px;">
            <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">PPE PROVIDENT FUND INC.</h1>
            <h2 style="font-size: 14px; margin-bottom: 5px; text-transform: uppercase;">Cash Balance</h2>
            <div style="font-size: 14px; color: black; text-transform: uppercase;">
                <div><?php echo strtoupper(date('F d, Y')); ?></div>
            </div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">DATE</th>
                    <th style="width: 8%;">CHECK NO.</th>
                    <th style="width: 8%;">DV NO.</th>
                    <th style="width: 22%;">PARTICULARS</th>
                    <th style="width: 11%;">DEBIT</th>
                    <th style="width: 11%;">CREDIT</th>
                    <th style="width: 12%;">BALANCE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($ppeRecords) > 0) {
                    $totalDebit = 0;
                    $totalCredit = 0;
                    
                    foreach ($ppeRecords as $record) {
                        $totalDebit += $record['debit'];
                        $totalCredit += $record['credit'];
                        
                        $formattedDate = date('m/d/Y', strtotime($record['date']));
                        echo '<tr>';
                        echo '<td>' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
                        echo '<td>' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
                        echo '<td>' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
                        echo '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
                        echo '<td class="text-right">' . number_format($record['debit'], 2) . '</td>';
                        echo '<td class="text-right">' . number_format($record['credit'], 2) . '</td>';
                        echo '<td class="text-right">' . number_format($record['balance'], 2) . '</td>';
                        echo '</tr>';
                    }
                    
                    // Add total row
                    echo '<tr style="font-weight: bold;">';
                    echo '<td colspan="4" style="text-align: right; border: none;">TOTAL</td>';
                    echo '<td style="text-align: right; border: none;">' . number_format($totalDebit, 2) . '</td>';
                    echo '<td style="text-align: right; border: none;">' . number_format($totalCredit, 2) . '</td>';
                    echo '<td style="text-align: right; border: none;">' . number_format(end($ppeRecords)['balance'], 2) . '</td>';
                    echo '</tr>';
                } else {
                    echo '<tr><td colspan="7" class="text-center">NO RECORDS FOUND</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <!-- Prepared By Section -->
        <div style="margin-top: 20px; width: 30%; font-family: Arial, sans-serif;">
            <p style="font-size: 12px; margin: 0;">Prepared by:</p>
            <p style="margin-top: 30px; font-size: 12px; margin-bottom: 0; font-weight: bold;">MARIA LAARNI B. CO</p>
            <p style="font-size: 12px; margin: 0;">Treasurer</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
