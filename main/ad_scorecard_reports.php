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
$filterYear = isset($_GET['year']) ? trim($_GET['year']) : '';

// Get pagination parameters
$itemsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$currentPageNum = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

ob_start();
?>

<div class="w-full">
    <div class="mb-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Audit Decision Scorecard Report</h1>
        <a href="ad_scorecard_print.php<?php echo(!empty($filterAuditReport) ? '?audit_report=' . urlencode($filterAuditReport) : '') . (!empty($filterScope) ? (empty($filterAuditReport) ? '?' : '&') . 'scope=' . urlencode($filterScope) : '') . (!empty($filterYear) ? (empty($filterAuditReport) && empty($filterScope) ? '?' : '&') . 'year=' . urlencode($filterYear) : ''); ?>" target="_blank" class="inline-flex items-center px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
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
}
catch (Exception $e) {
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
}
catch (Exception $e) {
// Handle error silently
}
?>
                </select>
            </div>

            <!-- Year Filter -->
            <div class="flex-1 min-w-xs">
                <select name="year" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Years</option>
                    <?php
try {
    $yearStmt = $conn->prepare("SELECT DISTINCT adsyear as year FROM ads WHERE adsyear IS NOT NULL ORDER BY year DESC");
    $yearStmt->execute();
    $yearList = $yearStmt->fetchAll();

    foreach ($yearList as $year) {
        $yearValue = (string)$year['year'];
        $selected = ($filterYear === $yearValue) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($yearValue) . '" ' . $selected . '>' . htmlspecialchars($yearValue) . '</option>';
    }
}
catch (Exception $e) {
// Handle error silently
}
?>
                </select>
            </div>

            <!-- Clear Filters Button -->
            <?php if (!empty($filterAuditReport) || !empty($filterScope) || !empty($filterYear)): ?>
            <button type="button" onclick="window.location.href='?'" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                Clear Filters
            </button>
            <?php
endif; ?>
        </form>
    </div>

    <!-- Show Entries and Records Table -->
    <div class="mb-4 flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimitReports()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo($itemsPerPage == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo($itemsPerPage == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo($itemsPerPage == 25) ? 'selected' : ''; ?>>25</option>
            <option value="50" <?php echo($itemsPerPage == 50) ? 'selected' : ''; ?>>50</option>
            <option value="100" <?php echo($itemsPerPage == 100) ? 'selected' : ''; ?>>100</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Title of Audit Report</th>
                    <!-- <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">ADS Year</th> -->
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
    // First, get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM ads WHERE 1=1";

    if (!empty($filterAuditReport)) {
        $countQuery .= " AND audit_report = :audit_report";
    }
    if (!empty($filterScope)) {
        $countQuery .= " AND scope = :scope";
    }
    if (!empty($filterYear)) {
        $countQuery .= " AND adsyear = :year";
    }

    $countStmt = $conn->prepare($countQuery);

    if (!empty($filterAuditReport)) {
        $countStmt->bindParam(':audit_report', $filterAuditReport);
    }
    if (!empty($filterScope)) {
        $countStmt->bindParam(':scope', $filterScope);
    }
    if (!empty($filterYear)) {
        $countStmt->bindParam(':year', $filterYear, PDO::PARAM_INT);
    }

    $countStmt->execute();
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPageNum = min($currentPageNum, max(1, $totalPages));
    $offset = ($currentPageNum - 1) * $itemsPerPage;

    // Now get the paginated data
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

    $query .= " ORDER BY audit_report ASC LIMIT :limit OFFSET :offset";

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

    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $adsRecords = $stmt->fetchAll();

    if (count($adsRecords) > 0) {
        foreach ($adsRecords as $record) {
            echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['audit_report']) . '</td>';
            // echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['adsyear'] ?? '-') . '</td>';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['scope'] ?? '-') . '</td>';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">';
            echo $record['bac_date'] ? date('F d, Y', strtotime($record['bac_date'])) : '-';
            echo '</td>';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['bac_reso'] ?? '-') . '</td>';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">';
            echo $record['boa_date'] ? date('F d, Y', strtotime($record['boa_date'])) : '-';
            echo '</td>';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['boa_reso'] ?? '-') . '</td>';
            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['remarks'] ?? '-') . '</td>';
            echo '</tr>';
        }
    }
    else {
        echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No audit decision scorecards found</td></tr>';
    }
}
catch (Exception $e) {
    echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-red-500">Error loading data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> scorecards
        </div>
        <div class="flex gap-2">
            <?php
    $paginationQuery = '';
    if (!empty($filterAuditReport)) {
        $paginationQuery .= '&audit_report=' . urlencode($filterAuditReport);
    }
    if (!empty($filterScope)) {
        $paginationQuery .= '&scope=' . urlencode($filterScope);
    }
    if (!empty($filterYear)) {
        $paginationQuery .= '&year=' . urlencode($filterYear);
    }
    if ($itemsPerPage !== 10) {
        $paginationQuery .= '&limit=' . $itemsPerPage;
    }
?>
            
            <?php if ($currentPageNum > 1): ?>
            <a href="?page=<?php echo $currentPageNum - 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php
    endif; ?>

            <?php
    $startPage = max(1, $currentPageNum - 2);
    $endPage = min($totalPages, $currentPageNum + 2);

    if ($startPage > 1): ?>
            <a href="?page=1<?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>
            <?php if ($startPage > 2): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php
        endif; ?>
            <?php
    endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 rounded-lg transition <?php echo($i === $currentPageNum) ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                <?php echo $i; ?>
            </a>
            <?php
    endfor; ?>

            <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php
        endif; ?>
            <a href="?page=<?php echo $totalPages; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"><?php echo $totalPages; ?></a>
            <?php
    endif; ?>

            <?php if ($currentPageNum < $totalPages): ?>
            <a href="?page=<?php echo $currentPageNum + 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php
    endif; ?>
        </div>
    </div>
    <?php
endif; ?>

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

<script>
function changeLimitReports() {
    const limit = document.getElementById('limitSelect').value;
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('limit', limit);
    searchParams.set('page', '1');
    window.location.href = '?' + searchParams.toString();
}
</script>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
