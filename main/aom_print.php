<?php
session_start();
require_once __DIR__ . '/../config.php';

$conn->exec("\n    CREATE TABLE IF NOT EXISTS aom_departments (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        aom_id INT NOT NULL,\n        department_id INT NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        UNIQUE KEY uniq_aom_department (aom_id, department_id),\n        INDEX idx_aom_id (aom_id),\n        INDEX idx_department_id (department_id),\n        CONSTRAINT fk_aom_departments_aom FOREIGN KEY (aom_id) REFERENCES aom_table(id) ON DELETE CASCADE ON UPDATE CASCADE,\n        CONSTRAINT fk_aom_departments_department FOREIGN KEY (department_id) REFERENCES neadept_table(id) ON DELETE CASCADE ON UPDATE CASCADE\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");

// Build filter conditions (same as in aom_reports.php)
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
    $whereConditions[] = "(EXISTS (SELECT 1 FROM aom_departments adf WHERE adf.aom_id = a.id AND adf.department_id = ?) OR a.department_id = ?)";
    $params[] = $_GET['department_id'];
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

try {
    $sql = "SELECT 
                a.*,
                COALESCE(da.department_names, d_fallback.name) as department_name,
                COALESCE(da.department_acronyms, d_fallback.acronym) as department_acronym
            FROM aom_table a 
            LEFT JOIN (
                SELECT
                    ad.aom_id,
                    GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') as department_names,
                    GROUP_CONCAT(DISTINCT d.acronym ORDER BY d.name SEPARATOR ', ') as department_acronyms
                FROM aom_departments ad
                INNER JOIN neadept_table d ON ad.department_id = d.id
                GROUP BY ad.aom_id
            ) da ON da.aom_id = a.id
            LEFT JOIN neadept_table d_fallback ON a.department_id = d_fallback.id
            $whereClause 
            ORDER BY a.date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();
}
catch (Exception $e) {
    $records = [];
    $error = $e->getMessage();
}

$dateGenerated = date('F d, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AOM Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
        }
        
        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
            }
            @page {
                size: 13in 8.5in;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .page {
            width: 13in;
            min-height: 8.5in;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        @media print {
            .page {
                width: 100%;
                margin: 0;
                padding: 0.5in;
                box-shadow: none;
            }
        }
        
        /* Header */
        .header {
            text-align: left;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .header-date {
            font-size: 11px;
            color: #333;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 15px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        
        td {
            vertical-align: top;
        }
        
        .text-center {
            text-align: center;
        }

        .list-disc {
            list-style-type: disc;
            margin-left: 15px;
        }
        
        .print-button {
            text-align: right;
            margin: 20px auto;
            width: 297mm;
        }
        
        .print-button button {
            padding: 10px 20px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="no-print print-button">
        <button onclick="window.print()">Print Report</button>
    </div>

    <div class="page">

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th colspan="7" style="text-align: center; padding: 5px; font-size: 12px; background-color: white; border-bottom: 1.5px solid #000;">
                        Justification of the Absense of Management Response to Audit Observation Memoranda (AOM) Issued by the Commission on Audit (COA)
                    </th>
                </tr>
                <tr>
                    <th style="width: 10%; text-align: center;">Item</th>
                    <th style="width: 7%; text-align: center;">Date</th>
                    <th style="width: 5%; text-align: center;">Department</th>
                    <th style="width: 15%;">Title</th>
                    <th style="width: 20%;">COA Observation</th>
                    <th style="width: 20%;">Recommendations</th>
                    <th style="width: 20%;">Comments / Justification</th>
                </tr>
            </thead>
            <tbody>
                <?php
if (count($records) > 0) {
    foreach ($records as $record) {
        $formattedDate = $record['date'] ? date('F d, Y', strtotime($record['date'])) : '';

        // Parse recommendations and justifications
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

        for ($i = 0; $i < $maxRows; $i++) {
            echo '<tr>';
            if ($i === 0) {
                echo '<td rowspan="' . $maxRows . '" style="text-align: center;">' . htmlspecialchars($record['item'] ?? '') . '</td>';
                echo '<td rowspan="' . $maxRows . '" style="text-align: center;">' . htmlspecialchars($formattedDate) . '</td>';
                echo '<td rowspan="' . $maxRows . '" style="text-align: center;">' . htmlspecialchars($record['department_acronym'] ?: ($record['department_name'] ?? '')) . '</td>';
                echo '<td rowspan="' . $maxRows . '" style="text-align: justify;">' . htmlspecialchars($record['title']) . '</td>';
                echo '<td rowspan="' . $maxRows . '" style="white-space: pre-wrap; text-align: justify;">' . htmlspecialchars($record['coa_observation'] ?? '') . (!empty($record['coa_observation_image']) ? '<br><img src="' . htmlspecialchars($record['coa_observation_image']) . '" alt="COA Observation Image" style="max-width: 100%; height: auto; margin-top: 8px; border: 1px solid #d1d5db; border-radius: 4px;">' : '') . '</td>';
            }

            $rec = isset($recs[$i]) ? trim($recs[$i]) : '';
            $just = isset($justs[$i]) ? trim($justs[$i]) : '';

            $borderStyle = "";
            if ($i > 0)
                $borderStyle .= "border-top: none; ";
            if ($i < $maxRows - 1)
                $borderStyle .= "border-bottom: none; ";

            echo '<td style="white-space: pre-wrap; text-align: justify; ' . $borderStyle . '">' . htmlspecialchars($rec) . '</td>';
            echo '<td style="white-space: pre-wrap; text-align: justify; ' . $borderStyle . '">' . htmlspecialchars($just) . '</td>';
            echo '</tr>';
        }
    }
}
else {
    echo '<tr><td colspan="7" class="text-center">NO RECORDS FOUND</td></tr>';
}
?>
            </tbody>
        </table>
    </div>
</body>
</html>
