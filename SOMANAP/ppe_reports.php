<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('ppe_reports');

// Set current page for sidebar active state
$currentPage = 'ppe_reports';
$username = $_SESSION['username'] ?? 'User';

// Start output buffering to capture content
ob_start();
?>

<div class="p-6" style="font-family: Arial, sans-serif;">
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">PPE Provident Fund - Reports</h1>
            <div class="flex gap-3">
                <button onclick="document.getElementById('printModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Print
                </button>
                <button onclick="exportToExcel()" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                    Export to Excel
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg mb-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check No.</label>
                    <input type="text" name="check_no" value="<?php echo htmlspecialchars($_GET['check_no'] ?? ''); ?>" placeholder="Search..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">DV/OR No.</label>
                    <input type="text" name="dv_or_no" value="<?php echo htmlspecialchars($_GET['dv_or_no'] ?? ''); ?>" placeholder="Search..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input type="text" name="particulars" value="<?php echo htmlspecialchars($_GET['particulars'] ?? ''); ?>" placeholder="Search..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                </div>
                <div class="flex gap-2 col-span-1 md:col-span-5">
                    <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium">
                        Filter
                    </button>
                    <a href="?>" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition font-medium">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- PPE Reports Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Date</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Check No.</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">DV No.</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Name</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Debit</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Credit</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Build filter conditions
                $whereConditions = [];
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
                    $sql = "SELECT date, check_no, dv_or_no, particulars, debit, credit, balance FROM ppe $whereClause ORDER BY date ASC";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $ppeRecords = $stmt->fetchAll();
                    
                    if (count($ppeRecords) > 0) {
                        $totalDebit = 0;
                        $totalCredit = 0;
                        
                        foreach ($ppeRecords as $record) {
                            $totalDebit += $record['debit'];
                            $totalCredit += $record['credit'];
                            
                            $formattedDate = date('m/d/Y', strtotime($record['date']));
                            echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($formattedDate) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['check_no'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['dv_or_no'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['particulars']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-700 dark:text-gray-300">' . number_format($record['debit'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-700 dark:text-gray-300">' . number_format($record['credit'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">' . number_format($record['balance'], 2) . '</td>';
                            echo '</tr>';
                        }
                        
                        // Add total row
                        echo '<tr class="bg-gray-200 dark:bg-gray-700 font-bold">';
                        echo '<td colspan="4" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-white text-right">Total:</td>';
                        echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-900 dark:text-white">' . number_format($totalDebit, 2) . '</td>';
                        echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-900 dark:text-white">' . number_format($totalCredit, 2) . '</td>';
                        echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-900 dark:text-white">' . number_format(end($ppeRecords)['balance'], 2) . '</td>';
                        echo '</tr>';
                    } else {
                        echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No records found</td></tr>';
                    }
                } catch (Exception $e) {
                    echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-red-500">Error loading data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Print Modal -->
    <dialog id="printModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Select Report & Export Format</h2>
        
        <div class="space-y-6">
            <?php
            // Build query string from current filters
            $queryString = '';
            $filterParams = [];
            if (!empty($_GET['date_from'])) $filterParams[] = 'date_from=' . urlencode($_GET['date_from']);
            if (!empty($_GET['date_to'])) $filterParams[] = 'date_to=' . urlencode($_GET['date_to']);
            if (!empty($_GET['check_no'])) $filterParams[] = 'check_no=' . urlencode($_GET['check_no']);
            if (!empty($_GET['dv_or_no'])) $filterParams[] = 'dv_or_no=' . urlencode($_GET['dv_or_no']);
            if (!empty($_GET['particulars'])) $filterParams[] = 'particulars=' . urlencode($_GET['particulars']);
            
            if (!empty($filterParams)) {
                $queryString = '?' . implode('&', $filterParams);
            }
            
            $reports = [
                [
                    'title' => 'Check Issued',
                    'printUrl' => 'ppe_check_issued_print.php',
                    'color' => 'blue'
                ],
                [
                    'title' => 'Remittance',
                    'printUrl' => 'ppe_remittance_print.php',
                    'color' => 'indigo'
                ],
                [
                    'title' => 'Check Issued - Receiving',
                    'printUrl' => 'ppe_print.php',
                    'color' => 'purple'
                ],
                [
                    'title' => 'Cash Balance',
                    'printUrl' => 'ppe_table_print.php',
                    'color' => 'green'
                ]
            ];
            
            foreach ($reports as $report):
            ?>
                <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3"><?php echo $report['title']; ?></h3>
                    <div class="grid grid-cols-3 gap-2">
                        <a href="<?php echo $report['printUrl'] . $queryString; ?>" target="_blank" class="px-4 py-2 bg-<?php echo $report['color']; ?>-500 text-white rounded-lg hover:bg-<?php echo $report['color']; ?>-600 transition text-center text-sm font-medium">
                            Print
                        </a>
                        <a href="<?php echo $report['printUrl'] . ($queryString ? $queryString . '&format=pdf' : '?format=pdf'); ?>" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-center text-sm font-medium">
                            Export PDF
                        </a>
                        <a href="<?php echo $report['printUrl'] . ($queryString ? $queryString . '&format=excel' : '?format=excel'); ?>" class="px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition text-center text-sm font-medium">
                            Export Excel
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6">
            <button type="button" onclick="document.getElementById('printModal').close()" class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                Close
            </button>
        </div>
    </dialog>

    <script>
        function exportToExcel() {
            // Get filter parameters from form
            const dateFrom = document.querySelector('input[name="date_from"]').value;
            const dateTo = document.querySelector('input[name="date_to"]').value;
            const checkNo = document.querySelector('input[name="check_no"]').value;
            const dvOrNo = document.querySelector('input[name="dv_or_no"]').value;
            const particulars = document.querySelector('input[name="particulars"]').value;
            
            // Build query string
            let params = [];
            if (dateFrom) params.push('date_from=' + encodeURIComponent(dateFrom));
            if (dateTo) params.push('date_to=' + encodeURIComponent(dateTo));
            if (checkNo) params.push('check_no=' + encodeURIComponent(checkNo));
            if (dvOrNo) params.push('dv_or_no=' + encodeURIComponent(dvOrNo));
            if (particulars) params.push('particulars=' + encodeURIComponent(particulars));
            
            const queryString = params.length > 0 ? '?' + params.join('&') : '';
            
            // Redirect to export handler
            window.location.href = 'ppe_export.php' + queryString;
        }
    </script>
</div>

<?php
// Capture content and include master layout
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
