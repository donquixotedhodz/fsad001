<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('ad_scorecard_reports');

$currentPage = 'ad_scorecard_reports';
$username = $_SESSION['username'] ?? 'User';

// Get filter values
$filterAuditReport = isset($_GET['audit_report']) ? trim($_GET['audit_report']) : '';
$filterScope = isset($_GET['scope']) ? trim($_GET['scope']) : '';

ob_start();
?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Audit Decision Scorecard Report</h1>
        <a href="ad_scorecard_print.php" target="_blank" class="inline-flex items-center px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4m16 0a2 2 0 00-2-2H5a2 2 0 00-2 2m16 0v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4a2 2 0 012-2h16z"></path>
            </svg>
            Print Report
        </a>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <form method="GET" class="flex flex-wrap gap-4 w-full" id="filterForm">
            <!-- Audit Report Filter -->
            <div class="flex-1 min-w-xs">
                <select name="audit_report" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Audit Reports</option>
                    <?php
                    try {
                        $auditStmt = $conn->prepare("SELECT DISTINCT audit_report FROM ads ORDER BY audit_report ASC");
                        $auditStmt->execute();
                        $auditList = $auditStmt->fetchAll();
                        
                        foreach ($auditList as $audit) {
                            $selected = ($filterAuditReport === $audit['audit_report']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($audit['audit_report']) . '" ' . $selected . '>' . htmlspecialchars($audit['audit_report']) . '</option>';
                        }
                    } catch (Exception $e) {
                        // Handle error silently
                    }
                    ?>
                </select>
            </div>

            <!-- Scope Filter -->
            <div class="flex-1 min-w-xs">
                <select name="scope" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Scopes</option>
                    <?php
                    try {
                        $scopeStmt = $conn->prepare("SELECT DISTINCT scope FROM ads WHERE scope IS NOT NULL AND scope != '' ORDER BY scope ASC");
                        $scopeStmt->execute();
                        $scopeList = $scopeStmt->fetchAll();
                        
                        foreach ($scopeList as $scope) {
                            $selected = ($filterScope === $scope['scope']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($scope['scope']) . '" ' . $selected . '>' . htmlspecialchars(substr($scope['scope'], 0, 50)) . (strlen($scope['scope']) > 50 ? '...' : '') . '</option>';
                        }
                    } catch (Exception $e) {
                        // Handle error silently
                    }
                    ?>
                </select>
            </div>

            <!-- Clear Filters Button -->
            <?php if (!empty($filterAuditReport) || !empty($filterScope)): ?>
            <button type="button" onclick="window.location.href='?'" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                Clear Filters
            </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- AD Scorecard Report Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Title of Audit Report</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Scope</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">BAC Date</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">BAC Resolution</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">BOA Date</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">BOA Resolution</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $query = "SELECT audit_report, scope, bac_date, bac_reso, boa_date, boa_reso, remarks FROM ads WHERE 1=1";
                    
                    if (!empty($filterAuditReport)) {
                        $query .= " AND audit_report = :audit_report";
                    }
                    if (!empty($filterScope)) {
                        $query .= " AND scope = :scope";
                    }
                    
                    $query .= " ORDER BY audit_report ASC";
                    
                    $stmt = $conn->prepare($query);
                    
                    if (!empty($filterAuditReport)) {
                        $stmt->bindParam(':audit_report', $filterAuditReport);
                    }
                    if (!empty($filterScope)) {
                        $stmt->bindParam(':scope', $filterScope);
                    }
                    
                    $stmt->execute();
                    $adsRecords = $stmt->fetchAll();
                    
                    if (count($adsRecords) > 0) {
                        foreach ($adsRecords as $record) {
                            echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['audit_report']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['scope'] ?? '-') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">';
                            echo $record['bac_date'] ? date('M d, Y', strtotime($record['bac_date'])) : '-';
                            echo '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['bac_reso'] ?? '-') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">';
                            echo $record['boa_date'] ? date('M d, Y', strtotime($record['boa_date'])) : '-';
                            echo '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['boa_reso'] ?? '-') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['remarks'] ?? '-') . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No audit decision scorecards found</td></tr>';
                    }
                } catch (Exception $e) {
                    echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-red-500">Error loading data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Print Styles -->
    <style media="print">
        @page {
            margin: 1cm;
            size: A4 landscape;
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        .no-print {
            display: none !important;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</div>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
