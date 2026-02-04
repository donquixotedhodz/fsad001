<?php
session_start();
require_once __DIR__ . '/../config.php';

// Fetch ADS data from database
try {
    $stmt = $conn->prepare("SELECT audit_report, scope, bac_date, bac_reso, boa_date, boa_reso, remarks FROM ads ORDER BY audit_report ASC");
    $stmt->execute();
    $adsRecords = $stmt->fetchAll();
} catch (Exception $e) {
    $adsRecords = [];
    $error = htmlspecialchars($e->getMessage());
}

$dateGenerated = date('F d, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Decision Scorecard Report</title>
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
                size: A4 landscape;
                margin: 0.5cm;
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
            width: 297mm;
            height: 210mm;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .header {
            text-align: left;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header-date {
            font-size: 11px;
            color: #333;
            margin-top: 8px;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 15px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
        }
        
        td {
            height: 25px;
            vertical-align: top;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Footer */
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
            padding-top: 30px;
        }
        
        .signature-label {
            font-weight: bold;
            font-size: 9px;
        }
        
        .print-button {
            margin-bottom: 10px;
        }
        
        .print-button button {
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .print-button button:hover {
            background-color: #1d4ed8;
        }
        
        /* Row styling */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f0f0f0;
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
        <button onclick="window.print()">Print Report</button>
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <h1>InternalAudit and Quality Standards Management Office (IAQSMO)</h1>
            <h2>Financial and Special Audit Division</h2>
            <div class="header-date">Report Date: <?php echo strtoupper($dateGenerated); ?></div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Title of Audit Report</th>
                    <th style="width: 15%;">Scope</th>
                    <th style="width: 10%;">BAC Date</th>
                    <th style="width: 12%;">BAC Resolution</th>
                    <th style="width: 10%;">BOA Date</th>
                    <th style="width: 12%;">BOA Resolution</th>
                    <th style="width: 19%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($adsRecords) > 0) {
                    foreach ($adsRecords as $record) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($record['audit_report'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($record['scope'] ?? '-') . '</td>';
                        echo '<td class="text-center">' . ($record['bac_date'] ? date('M d, Y', strtotime($record['bac_date'])) : '-') . '</td>';
                        echo '<td>' . htmlspecialchars($record['bac_reso'] ?? '-') . '</td>';
                        echo '<td class="text-center">' . ($record['boa_date'] ? date('M d, Y', strtotime($record['boa_date'])) : '-') . '</td>';
                        echo '<td>' . htmlspecialchars($record['boa_reso'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($record['remarks'] ?? '-') . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7" class="text-center">NO RECORDS FOUND</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <!-- Footer Section -->
        <!-- <div class="footer">
            <div class="signature-box">
                <div class="signature-label">Prepared By</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Reviewed By</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Approved By</div>
            </div>
        </div> -->
    </div>
</body>
</html>
