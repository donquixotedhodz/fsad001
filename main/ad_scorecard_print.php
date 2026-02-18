<?php
session_start();
require_once __DIR__ . '/../config.php';

// Get filter values from URL
$filterAuditReport = isset($_GET['audit_report']) ? trim($_GET['audit_report']) : '';
$filterScope = isset($_GET['scope']) ? trim($_GET['scope']) : '';
$filterYear = isset($_GET['year']) ? trim($_GET['year']) : '';

// Fetch ADS data from database with filters applied
try {
    $query = "SELECT audit_report, adsyear, scope, bac_date, bac_reso, boa_date, boa_reso, remarks FROM ads WHERE 1=1";
    
    if (!empty($filterAuditReport)) {
        $query .= " AND audit_report = :audit_report";
    }
    if (!empty($filterScope)) {
        $query .= " AND scope = :scope";
    }
    if (!empty($filterYear)) {
        $query .= " AND adsyear = :year";
    }
    
    $query .= " ORDER BY adsyear ASC, audit_report ASC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($filterAuditReport)) {
        $stmt->bindParam(':audit_report', $filterAuditReport);
    }
    if (!empty($filterScope)) {
        $stmt->bindParam(':scope', $filterScope);
    }
    if (!empty($filterYear)) {
        $stmt->bindParam(':year', $filterYear, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $adsRecords = $stmt->fetchAll();
} catch (Exception $e) {
    $adsRecords = [];
    $error = htmlspecialchars($e->getMessage());
}

// Extract years from adsyear
$years = [];
foreach ($adsRecords as $record) {
    if (!empty($record['adsyear'])) {
        $year = $record['adsyear'];
        if (!in_array($year, $years)) {
            $years[] = $year;
        }
    }
}
sort($years);
$yearRange = !empty($years) ? (count($years) === 1 ? $years[0] : $years[0] . ' - ' . $years[count($years) - 1]) : date('Y');

$dateGenerated = date('F d, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report Scorecard Report</title>
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
            /* border-bottom: 2px solid #000; */
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
            <h2>Internal Audit and Quality Standards Management Office (IAQSMO)</h2>
            <h2>Financial and Special Audit Division</h2>
            <h2 class="header-date">Accomplishment for Departmental Scorecard <?php echo $yearRange; ?></h2>
        </div>
        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">No.</th>
                    <th style="width: 25%;">Title of Audit Report</th>
                    <!-- <th style="width: 8%;">ADS Year</th> -->
                    <th style="width: 15%;">Scope</th>
                    <th style="width: 10%;">BAC Date</th>
                    <th style="width: 5%;">BAC Resolution</th>
                    <th style="width: 10%;">BOA Date</th>
                    <th style="width: 10%;">BOA Resolution</th>
                    <th style="width: 13%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (count($adsRecords) > 0) {
                    $counter = 1;
                    foreach ($adsRecords as $record) {
                        echo '<tr>';
                        echo '<td class="text-center">' . $counter++ . '</td>';
                        echo '<td>' . htmlspecialchars($record['audit_report'] ?? '') . '</td>';
                        // echo '<td class="text-center">' . htmlspecialchars($record['adsyear'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($record['scope'] ?? '-') . '</td>';
                        echo '<td class="text-center">' . ($record['bac_date'] ? date('F d, Y', strtotime($record['bac_date'])) : '-') . '</td>';
                        echo '<td class="text-center">' . htmlspecialchars($record['bac_reso'] ?? '-') . '</td>';
                        echo '<td class="text-center">' . ($record['boa_date'] ? date('F d, Y', strtotime($record['boa_date'])) : '-') . '</td>';
                        echo '<td class="text-center">' . htmlspecialchars($record['boa_reso'] ?? '-') . '</td>';
                        echo '<td>' . htmlspecialchars($record['remarks'] ?? '-') . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="9" class="text-center">NO RECORDS FOUND</td></tr>';
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
