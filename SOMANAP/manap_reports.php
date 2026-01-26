<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('manap_reports');

$currentPage = 'manap_reports';
$username = $_SESSION['username'] ?? 'User';

// Get filter values
$filterEC = isset($_GET['ec']) ? trim($_GET['ec']) : '';
$filterItem = isset($_GET['item']) ? trim($_GET['item']) : '';

ob_start();
?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">MANAP Documents Report</h1>
        <a href="manap_print.php" target="_blank" class="inline-flex items-center px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4m16 0a2 2 0 00-2-2H5a2 2 0 00-2 2m16 0v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4a2 2 0 012-2h16z"></path>
            </svg>
            Print Report
        </a>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <form method="GET" class="flex flex-wrap gap-4 w-full" id="filterForm">
            <!-- EC Filter -->
            <div class="flex-1 min-w-xs">
                <select name="ec" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Electric Cooperatives</option>
                    <?php
                    try {
                        $ecStmt = $conn->prepare("SELECT DISTINCT ec FROM manap ORDER BY ec ASC");
                        $ecStmt->execute();
                        $ecList = $ecStmt->fetchAll();
                        
                        foreach ($ecList as $ec) {
                            $selected = ($filterEC === $ec['ec']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($ec['ec']) . '" ' . $selected . '>' . htmlspecialchars($ec['ec']) . '</option>';
                        }
                    } catch (Exception $e) {
                        // Handle error silently
                    }
                    ?>
                </select>
            </div>

            <!-- Item Filter -->
            <div class="flex-1 min-w-xs">
                <select name="item" onchange="document.getElementById('filterForm').submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Items</option>
                    <?php
                    try {
                        $itemStmt = $conn->prepare("SELECT DISTINCT item FROM manap ORDER BY item ASC");
                        $itemStmt->execute();
                        $itemList = $itemStmt->fetchAll();
                        
                        foreach ($itemList as $item) {
                            $selected = ($filterItem === $item['item']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($item['item']) . '" ' . $selected . '>' . htmlspecialchars($item['item']) . '</option>';
                        }
                    } catch (Exception $e) {
                        // Handle error silently
                    }
                    ?>
                </select>
            </div>

            <!-- Clear Filters Button -->
            <?php if (!empty($filterEC) || !empty($filterItem)): ?>
            <button type="button" onclick="window.location.href='?'" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                Clear Filters
            </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- MANAP Report Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Electric Cooperative (EC)</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Item</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Recommending Approval</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Approving Authority</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $query = "SELECT ec, item, recommending_approvals, approving_authority FROM manap WHERE 1=1";
                    
                    if (!empty($filterEC)) {
                        $query .= " AND ec = :ec";
                    }
                    if (!empty($filterItem)) {
                        $query .= " AND item = :item";
                    }
                    
                    $query .= " ORDER BY ec ASC";
                    
                    $stmt = $conn->prepare($query);
                    
                    if (!empty($filterEC)) {
                        $stmt->bindParam(':ec', $filterEC);
                    }
                    if (!empty($filterItem)) {
                        $stmt->bindParam(':item', $filterItem);
                    }
                    
                    $stmt->execute();
                    $manapRecords = $stmt->fetchAll();
                    
                    if (count($manapRecords) > 0) {
                        foreach ($manapRecords as $record) {
                            echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['ec']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['item']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['recommending_approvals'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['approving_authority'] ?? '') . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No MANAP documents found</td></tr>';
                    }
                } catch (Exception $e) {
                    echo '<tr><td colspan="4" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-red-500">Error loading data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
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
