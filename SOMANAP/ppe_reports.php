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
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">PPE Provident Fund - Reports</h1>
        <button onclick="document.getElementById('printModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            Print
        </button>
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
                // Fetch PPE data from database
                try {
                    $stmt = $conn->prepare("SELECT date, check_no, dv_or_no, particulars, debit, credit, balance FROM ppe ORDER BY date ASC");
                    $stmt->execute();
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
    <dialog id="printModal" class="rounded-lg shadow-lg max-w-md w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Select Print Report</h2>
        
        <div class="space-y-4">
            <a href="ppe_check_issued_print.php" target="_blank" class="block w-full px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-center font-medium">
                📋 Check Issued
            </a>
            <a href="ppe_print.php" target="_blank" class="block w-full px-6 py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition text-center font-medium">
                📑 Check Issued - Receiving
            </a>
            <a href="ppe_table_print.php" target="_blank" class="block w-full px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-center font-medium">
                📊 Cash Balance
            </a>
        </div>

        <div class="mt-6">
            <button type="button" onclick="document.getElementById('printModal').close()" class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                Close
            </button>
        </div>
    </dialog>
</div>

<?php
// Capture content and include master layout
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
