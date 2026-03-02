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
            if (isset($filters['records']) && is_array($filters['records'])) {
                $records = $filters['records'];
                $this->generateCheckIssuedPDF($records);
                return;
            }

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
            $sql = "SELECT date, check_no, dv_or_no, particulars, CASE WHEN debit > 0 AND credit = 0 THEN debit WHEN credit > 0 AND debit = 0 THEN credit ELSE ABS(credit - debit) END as amount FROM ppe $whereClause ORDER BY date ASC";
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
            if (isset($filters['records']) && is_array($filters['records'])) {
                $records = $filters['records'];
                $this->generateCheckIssuedReceivingPDF($records);
                return;
            }

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
            $sql = "SELECT check_no, dv_or_no, particulars, CASE WHEN debit > 0 AND credit = 0 THEN debit WHEN credit > 0 AND debit = 0 THEN credit ELSE ABS(credit - debit) END as amount, date FROM ppe $whereClause ORDER BY date ASC";
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
            if (isset($filters['records']) && is_array($filters['records'])) {
                $records = $filters['records'];
                $this->generateRemittancePDF($records);
                return;
            }

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
            $sql = "SELECT date, check_no, dv_or_no, particulars, CASE WHEN debit > 0 AND credit = 0 THEN debit WHEN credit > 0 AND debit = 0 THEN credit ELSE ABS(credit - debit) END as amount FROM ppe $whereClause ORDER BY date ASC";
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
            if (isset($filters['records']) && is_array($filters['records'])) {
                $records = $filters['records'];
                $this->generateCashBalancePDF($records);
                return;
            }

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
                $dompdf->set_option('defaultFont', 'Century Gothic');
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
                $dompdf->set_option('defaultFont', 'Century Gothic');
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
                $dompdf->set_option('defaultFont', 'Century Gothic');
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
                $dompdf->set_option('defaultFont', 'Century Gothic');
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
        $commonPdfCss = $this->getCommonPdfCss();
        $preparedByHtml = $this->getPreparedByHtml();
        
        foreach ($records as $record) {
            $totalAmount += $record['amount'];
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
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
        {$commonPdfCss}
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>Check Issued</h2>
        <div class="header-date">
HTML;
        
        $html .= $this->getAsOfDateText();
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">CHECK NO.</th>
                    <th style="width: 12%;">DV NO.</th>
                    <th style="width: 40%;">NAME</th>
                    <th style="width: 18%;">AMOUNT</th>
                    <th style="width: 18%;">DATE ISSUED</th>
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
        {$preparedByHtml}
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
        $commonPdfCss = $this->getCommonPdfCss();
        $preparedByHtml = $this->getPreparedByHtml();
        
        foreach ($records as $record) {
            $totalAmount += $record['amount'];
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td class="text-center">' . htmlspecialchars(strtoupper($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td class="text-center">' . htmlspecialchars(strtoupper($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . htmlspecialchars(strtoupper($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
            $tableRows .= '<td class="text-center">' . htmlspecialchars(strtoupper($formattedDate)) . '</td>';
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
        {$commonPdfCss}
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>CHECKS ISSUED-Receiving</h2>
        <div class="header-date">
HTML;
        
        $html .= $this->getAsOfDateText();
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">CHECK NO.</th>
                    <th style="width: 8%;">DV NO.</th>
                    <th style="width: 16%;">NAME</th>
                    <th style="width: 12%;">AMOUNT</th>
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
        {$preparedByHtml}
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
        $commonPdfCss = $this->getCommonPdfCss();
        $preparedByHtml = $this->getPreparedByHtml();
        
        foreach ($records as $record) {
            $totalAmount += $record['amount'];
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-right">' . number_format($record['amount'], 2) . '</td>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($formattedDate)) . '</td>';
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
        {$commonPdfCss}
    </style>
</head>
<body>
    <div class="page">
        <h1>PPE PROVIDENT FUND INC.</h1>
        <h2>Remittance</h2>
        <div class="header-date">
HTML;
        
        $html .= $this->getAsOfDateText();
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">CHECK NO.</th>
                    <th style="width: 12%;">DV NO.</th>
                    <th style="width: 40%;">NAME</th>
                    <th style="width: 18%;">AMOUNT</th>
                    <th style="width: 18%;">DATE</th>
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
        {$preparedByHtml}
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
        $commonPdfCss = $this->getCommonPdfCss();
        $preparedByHtml = $this->getPreparedByHtml();
        
        foreach ($records as $record) {
            $totalDebit += $record['debit'];
            $totalCredit += $record['credit'];
            
            $formattedDate = date('m/d/Y', strtotime($record['date']));
            
            $tableRows .= '<tr>';
            $tableRows .= '<td class="text-center">' . htmlspecialchars(strtoupper($formattedDate)) . '</td>';
            $tableRows .= '<td>' . strtoupper(htmlspecialchars($record['particulars'])) . '</td>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($record['check_no'] ?? '')) . '</td>';
            $tableRows .= '<td class="text-center">' . strtoupper(htmlspecialchars($record['dv_or_no'] ?? '')) . '</td>';
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
        {$commonPdfCss}
    </style>
</head>
<body>
    <div class="page">
    <h1>PPE PROVIDENT FUND INC.</h1>
    <h2>Cash Balance</h2>
    <div class="header-date">
HTML;
                $html .= $this->getAsOfDateText();
        
        $html .= <<<HTML
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">DATE</th>
                    <th style="width: 28%;">PARTICULARS</th>
                    <th style="width: 10%;">CHECK NO.</th>
                    <th style="width: 10%;">DV/OR NO.</th>
                    <th style="width: 14%;">DEBIT</th>
                    <th style="width: 14%;">CREDIT</th>
                    <th style="width: 14%;">BALANCE</th>
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
                    <td class="text-right" style="border: none;">
HTML;
        
        $html .= number_format(abs($totalCredit - $totalDebit), 2);
        
        $html .= <<<HTML
                    </td>
                </tr>
            </tbody>
        </table>
        {$preparedByHtml}
    </div>
</body>
</html>
HTML;
        
        return $html;
    }

    private function getCommonPdfCss() {
        return "
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
            font-size: 14px;
        }

        body {
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #000;
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
            font-size: 14px;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 15px;
            text-align: center;
        }

        td {
            height: auto;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }

        h2 {
            font-size: 16px;
            font-weight: normal;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }

        .header-date {
            font-size: 16px;
            color: #333;
            margin-bottom: 10px;
        }

        .prepared-by {
            margin-top: 24px;
            width: 320px;
            font-size: 14px;
        }

        .prepared-by .label {
            margin-bottom: 26px;
        }

        .prepared-by .name-line {
            text-align: left;
            padding-top: 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        .prepared-by .title-line {
            text-align: left;
            margin-top: 2px;
            font-size: 14px;
            text-transform: none;
        }
        ";
    }

    private function getPreparedByHtml() {
        $preparedBy = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System User';
        $preparedByTitle = $_SESSION['position'] ?? $_SESSION['designation'] ?? $_SESSION['job_title'] ?? 'Treasurer';
        $preparedBy = strtoupper(htmlspecialchars($preparedBy));
        $preparedByTitle = htmlspecialchars(ucwords(str_replace('_', ' ', (string) $preparedByTitle)));

        $html = '<div class="prepared-by">'
            . '<div class="label">Prepared by:</div>'
            . '<div class="name-line">' . $preparedBy . '</div>';

        if ($preparedByTitle !== '') {
            $html .= '<div class="title-line">' . $preparedByTitle . '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function getAsOfDateText() {
        return 'As of ' . date('F d, Y');
    }
}
