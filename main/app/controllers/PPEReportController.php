<?php
use Dompdf\Dompdf;

class PPEReportController {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Export Check Issued report to PDF
     */
    public function exportCheckIssuedPDF($filters = []) {
        try {
            // Build filter conditions
            $whereConditions = ["(debit > 0 OR credit > 0)", "check_no != 'ONLINE'"];
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "date <= ?";
                $params[] = $filters['date_to'];
            }
            if (!empty($filters['check_no'])) {
                $whereConditions[] = "check_no LIKE ?";
                $params[] = '%' . $filters['check_no'] . '%';
            }
            if (!empty($filters['dv_or_no'])) {
                $whereConditions[] = "dv_or_no LIKE ?";
                $params[] = '%' . $filters['dv_or_no'] . '%';
            }
            if (!empty($filters['particulars'])) {
                $whereConditions[] = "particulars LIKE ?";
                $params[] = '%' . $filters['particulars'] . '%';
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Fetch data
            $sql = "SELECT date, check_no, dv_or_no, particulars, (debit + credit) as amount FROM ppe $whereClause ORDER BY date ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            
            // Generate PDF
            $this->generateCheckIssuedPDF($records);
            
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Export Checks Issued-Receiving report to PDF
     */
    public function exportCheckIssuedReceivingPDF($filters = []) {
        try {
            // Build filter conditions
            $whereConditions = ["check_no != 'ONLINE'"];
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "date <= ?";
                $params[] = $filters['date_to'];
            }
            if (!empty($filters['check_no'])) {
                $whereConditions[] = "check_no LIKE ?";
                $params[] = '%' . $filters['check_no'] . '%';
            }
            if (!empty($filters['dv_or_no'])) {
                $whereConditions[] = "dv_or_no LIKE ?";
                $params[] = '%' . $filters['dv_or_no'] . '%';
            }
            if (!empty($filters['particulars'])) {
                $whereConditions[] = "particulars LIKE ?";
                $params[] = '%' . $filters['particulars'] . '%';
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Fetch data
            $sql = "SELECT check_no, dv_or_no, particulars, (debit + credit) as amount, date FROM ppe $whereClause ORDER BY date ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            
            // Generate PDF
            $this->generateCheckIssuedReceivingPDF($records);
            
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Export Remittance report to PDF
     */
    public function exportRemittancePDF($filters = []) {
        try {
            // Build filter conditions
            $whereConditions = ["particulars LIKE '%REMITTANCE%'"];
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "date <= ?";
                $params[] = $filters['date_to'];
            }
            if (!empty($filters['check_no'])) {
                $whereConditions[] = "check_no LIKE ?";
                $params[] = '%' . $filters['check_no'] . '%';
            }
            if (!empty($filters['dv_or_no'])) {
                $whereConditions[] = "dv_or_no LIKE ?";
                $params[] = '%' . $filters['dv_or_no'] . '%';
            }
            if (!empty($filters['particulars'])) {
                $whereConditions[] = "particulars LIKE ?";
                $params[] = '%' . $filters['particulars'] . '%';
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Fetch data
            $sql = "SELECT date, check_no, dv_or_no, particulars, (debit + credit) as amount FROM ppe $whereClause ORDER BY date ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            
            // Generate PDF
            $this->generateRemittancePDF($records);
            
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Export Cash Balance report to PDF
     */
    public function exportCashBalancePDF($filters = []) {
        try {
            // Build filter conditions
            $whereConditions = [];
            $params = [];
            
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "date >= ?";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "date <= ?";
                $params[] = $filters['date_to'];
            }
            if (!empty($filters['check_no'])) {
                $whereConditions[] = "check_no LIKE ?";
                $params[] = '%' . $filters['check_no'] . '%';
            }
            if (!empty($filters['dv_or_no'])) {
                $whereConditions[] = "dv_or_no LIKE ?";
                $params[] = '%' . $filters['dv_or_no'] . '%';
            }
            if (!empty($filters['particulars'])) {
                $whereConditions[] = "particulars LIKE ?";
                $params[] = '%' . $filters['particulars'] . '%';
            }
            
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Fetch data
            $sql = "SELECT date, check_no, dv_or_no, particulars, debit, credit, balance FROM ppe $whereClause ORDER BY date ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            
            // Generate PDF
            $this->generateCashBalancePDF($records);
            
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Check Issued PDF using dompdf
     */
    private function generateCheckIssuedPDF($records) {
        try {
            require_once(__DIR__ . '/../../../vendor/autoload.php');
            
            $html = $this->getCheckIssuedHTML($records);
            
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Check_Issued_' . date('Y-m-d_His') . '.pdf"');
            
            echo $dompdf->output();
            exit;
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Check Issued-Receiving PDF using dompdf
     */
    private function generateCheckIssuedReceivingPDF($records) {
        try {
            require_once(__DIR__ . '/../../../vendor/autoload.php');
            
            $html = $this->getCheckIssuedReceivingHTML($records);
            
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Check_Issued_Receiving_' . date('Y-m-d_His') . '.pdf"');
            
            echo $dompdf->output();
            exit;
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Remittance PDF using dompdf
     */
    private function generateRemittancePDF($records) {
        try {
            require_once(__DIR__ . '/../../../vendor/autoload.php');
            
            $html = $this->getRemittanceHTML($records);
            
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Remittance_' . date('Y-m-d_His') . '.pdf"');
            
            echo $dompdf->output();
            exit;
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Cash Balance PDF using dompdf
     */
    private function generateCashBalancePDF($records) {
        try {
            require_once(__DIR__ . '/../../../vendor/autoload.php');
            
            $html = $this->getCashBalanceHTML($records);
            
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Cash_Balance_' . date('Y-m-d_His') . '.pdf"');
            
            echo $dompdf->output();
            exit;
        } catch (Exception $e) {
            throw new Exception('Error generating PDF: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate Check Issued HTML content
     */
    private function getCheckIssuedHTML($records) {
        $totalAmount = 0;
        $tableRows = '';
        
        foreach ($records as $record) {
            $totalAmount += $record['amount'];
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
            $tableRows .= '<td class="text-right">' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
            $tableRows .= '</tr>';
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPE Provident Fund - Check Issued</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
        }
        
        .page {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }
        
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }
        
        td {
            height: auto;
            padding: 3px 4px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        
        h2 {
            font-size: 12px;
            font-weight: normal;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        
        .header-date {
            font-size: 9px;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>Check Issued</h2>
        <div class="header-date">
HTML;
        
        $html .= strtoupper(date('F d, Y'));
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">CHECK NO.</th>
                    <th style="width: 12%;">DV NO.</th>
                    <th style="width: 40%;">NAME</th>
                    <th style="width: 18%; text-align: right;">AMOUNT</th>
                    <th style="width: 18%; text-align: right;">DATE ISSUED</th>
                </tr>
            </thead>
            <tbody>
                $tableRows
                <tr style="font-weight: bold;">
                    <td colspan="3" style="text-align: right; border: none;">TOTAL</td>
                    <td style="text-align: right; border: none;">
HTML;
        
        $html .= number_format($totalAmount, 2);
        
        $html .= <<<HTML
                    </td>
                    <td style="text-align: right; border: none;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Generate Check Issued-Receiving HTML content
     */
    private function getCheckIssuedReceivingHTML($records) {
        $totalAmount = 0;
        $tableRows = '';
        
        foreach ($records as $record) {
            $totalAmount += $record['amount'];
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td>' . htmlspecialchars(strtoupper($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . htmlspecialchars(strtoupper($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . htmlspecialchars(strtoupper($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
            $tableRows .= '<td>' . htmlspecialchars(strtoupper($formattedDate)) . '</td>';
            $tableRows .= '<td></td>';
            $tableRows .= '<td></td>';
            $tableRows .= '<td></td>';
            $tableRows .= '</tr>';
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPE Provident Fund - Checks Issued-Receiving</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
        }
        
        .page {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: left;
        }
        
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 7px;
            text-align: center;
        }
        
        td {
            height: auto;
            padding: 2px 3px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        h1 {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        
        h2 {
            font-size: 11px;
            font-weight: normal;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        
        .header-date {
            font-size: 8px;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>CHECKS ISSUED-Receiving</h2>
        <div class="header-date">
HTML;
        
        $html .= strtoupper(date('F d, Y'));
        
        $html .= <<<HTML
        </div>

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
                $tableRows
                <tr style="font-weight: bold; border: none;">
                    <td colspan="3" style="text-align: right; border: none;">TOTAL</td>
                    <td class="text-right" style="border: none;">
HTML;
        
        $html .= number_format($totalAmount, 2);
        
        $html .= <<<HTML
                    </td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                    <td style="border: none;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Generate Remittance HTML content
     */
    private function getRemittanceHTML($records) {
        $totalAmount = 0;
        $tableRows = '';
        
        foreach ($records as $record) {
            $totalAmount += $record['amount'];
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
            $tableRows .= '<td class="text-right">' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
            $tableRows .= '</tr>';
        }
        
        $html = <<<HTML
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
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
        }
        
        .page {
            width: 100%;
            padding: 0;
            background-color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }
        
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }
        
        td {
            height: auto;
            padding: 3px 4px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        
        h2 {
            font-size: 12px;
            font-weight: normal;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        
        .header-date {
            font-size: 9px;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>Remittance</h2>
        <div class="header-date">
HTML;
        
        $html .= strtoupper(date('F d, Y'));
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">CHECK NO.</th>
                    <th style="width: 12%;">DV NO.</th>
                    <th style="width: 40%;">NAME</th>
                    <th style="width: 18%; text-align: right;">AMOUNT</th>
                    <th style="width: 18%; text-align: right;">DATE</th>
                </tr>
            </thead>
            <tbody>
                $tableRows
                <tr style="font-weight: bold;">
                    <td colspan="3" style="text-align: right; border: none;">TOTAL</td>
                    <td style="text-align: right; border: none;">
HTML;
        
        $html .= number_format($totalAmount, 2);
        
        $html .= <<<HTML
                    </td>
                    <td style="text-align: right; border: none;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    /**
     * Generate Cash Balance HTML content
     */
    private function getCashBalanceHTML($records) {
        $totalDebit = 0;
        $totalCredit = 0;
        $tableRows = '';
        
        foreach ($records as $record) {
            $totalDebit += $record['debit'];
            $totalCredit += $record['credit'];
            
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td>' . htmlspecialchars(strtoupper($formattedDate)) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['debit'], 2) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['credit'], 2) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['balance'], 2) . '</td>';
            $tableRows .= '</tr>';
        }
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPE Provident Fund - Cash Balance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
        }
        
        .page {
            width: 100%;
            padding: 0;
            background-color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: left;
        }
        
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 7px;
            text-align: center;
        }
        
        td {
            height: auto;
            padding: 2px 3px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        h1 {
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        
        h2 {
            font-size: 11px;
            font-weight: normal;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        
        .header-date {
            font-size: 8px;
            color: #333;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>Cash Balance</h2>
        <div class="header-date">
HTML;
                $html .= strtoupper(date('F d, Y'));
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">DATE</th>
                    <th style="width: 12%;">CHECK NO.</th>
                    <th style="width: 12%;">DV NO.</th>
                    <th style="width: 30%;">NAME</th>
                    <th style="width: 12%; text-align: right;">DEBIT</th>
                    <th style="width: 12%; text-align: right;">CREDIT</th>
                    <th style="width: 10%; text-align: right;">BALANCE</th>
                </tr>
            </thead>
            <tbody>
                $tableRows
                <tr style="font-weight: bold;">
                    <td colspan="4" style="text-align: right; border: none;">TOTAL</td>
                    <td class="text-right" style="border: none;">
HTML;
        
        $html .= number_format($totalDebit, 2);
        
        $html .= <<<HTML
                    </td>
                    <td class="text-right" style="border: none;">
HTML;
        
        $html .= number_format($totalCredit, 2);
        
        $html .= <<<HTML
                    </td>
                    <td style="border: none;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
}
