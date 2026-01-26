<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('ppe');

// Set current page for sidebar active state
$currentPage = 'ppe';
$username = $_SESSION['username'] ?? 'User';

// Constants for PPE
$STARTING_CHECK_NO = 690067;
$STARTING_BALANCE = 7377280.01;

// Handle form submission for adding PPE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ppe') {
    $date = $_POST['date'] ?? date('Y-m-d'); // User-provided date or current date
    $particulars = htmlspecialchars($_POST['particulars'] ?? '');
    $check_type = htmlspecialchars($_POST['check_type'] ?? 'actual');
    $check_no = $check_type === 'online' ? 'ONLINE' : intval($_POST['check_no'] ?? 0);
    $dv_or_no = htmlspecialchars($_POST['dv_or_no'] ?? '');
    $debit = floatval($_POST['debit'] ?? 0);
    $credit = floatval($_POST['credit'] ?? 0);
    
    if (!empty($particulars) && ($check_no > 0 || $check_no === 'ONLINE')) {
        try {
            // Get the last balance
            $stmt = $conn->prepare("SELECT balance FROM ppe ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $lastRecord = $stmt->fetch();
            $lastBalance = $lastRecord ? floatval($lastRecord['balance']) : $STARTING_BALANCE;
            
            // Calculate new balance
            $newBalance = $lastBalance - $debit + $credit;
            
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO ppe (date, particulars, check_no, dv_or_no, debit, credit, balance) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$date, $particulars, $check_no, $dv_or_no, $debit, $credit, $newBalance]);
            
            // Update PPE Provident Fund remaining balance
            $updateFundStmt = $conn->prepare("UPDATE ppe_funds SET remaining_balance = ? WHERE fund_name = 'PPE Provident Fund'");
            $updateFundStmt->execute([$newBalance]);
            
            $successMessage = "PPE record added successfully!";
        } catch (Exception $e) {
            $errorMessage = "Error adding record: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Get next check number
$stmt = $conn->prepare("SELECT MAX(CAST(check_no AS UNSIGNED)) as max_check FROM ppe WHERE check_no != 'ONLINE' AND check_no IS NOT NULL");
$stmt->execute();
$result = $stmt->fetch();
$nextCheckNo = ($result && $result['max_check']) ? intval($result['max_check']) + 1 : $STARTING_CHECK_NO;

// Start output buffering to capture content
ob_start();
?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">PPE Provident Fund</h1>
        <button onclick="document.getElementById('addPPEModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            + Add PPE
        </button>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($successMessage)): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($errorMessage)): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <!-- Add PPE Modal -->
    <dialog id="addPPEModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Add PPE Record</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_ppe">
            
            <div class="grid grid-cols-2 gap-6">
                <!-- Date (Auto) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Check No Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check Type *</label>
                    <select id="checkType" name="check_type" required onchange="toggleCheckNoInput()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="actual">Actual Check No.</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                <!-- Check No (Actual) -->
                <div id="checkNoDiv">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check No. *</label>
                    <input type="number" id="checkNoInput" name="check_no" value="<?php echo $nextCheckNo; ?>" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Particulars -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Particulars (Names) *</label>
                    <input type="text" name="particulars" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- DV/OR No -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">DV/OR No.</label>
                    <input type="text" name="dv_or_no" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Credit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Credit</label>
                    <input type="number" name="credit" step="0.01" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Debit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Debit</label>
                    <input type="number" name="debit" step="0.01" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Add Record
                </button>
                <button type="button" onclick="document.getElementById('addPPEModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Print Modal -->
    <dialog id="printModal" class="rounded-lg shadow-lg max-w-md w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Select Print Report</h2>
        
        <div class="space-y-4">
            <a href="ppe_print.php" target="_blank" class="block w-full px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-center font-medium">
                📋 Check Issued
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

    <!-- PPE Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Date</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Particulars (Names)</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Check No.</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">DV/OR No.</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Debit</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Credit</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch PPE data from database
                try {
                    $stmt = $conn->prepare("SELECT * FROM ppe ORDER BY date ASC");
                    $stmt->execute();
                    $ppeRecords = $stmt->fetchAll();
                    
                    if (count($ppeRecords) > 0) {
                        foreach ($ppeRecords as $record) {
                            echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['date']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['particulars']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['check_no'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['dv_or_no'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-700 dark:text-gray-300">' . number_format($record['debit'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-700 dark:text-gray-300">' . number_format($record['credit'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">' . number_format($record['balance'], 2) . '</td>';
                            echo '</tr>';
                        }
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

<script>
function toggleCheckNoInput() {
    const checkType = document.getElementById('checkType').value;
    const checkNoDiv = document.getElementById('checkNoDiv');
    const checkNoInput = document.getElementById('checkNoInput');
    
    if (checkType === 'online') {
        checkNoDiv.style.display = 'none';
        checkNoInput.required = false;
    } else {
        checkNoDiv.style.display = 'block';
        checkNoInput.required = true;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCheckNoInput();
});
</script>
</div>

<?php
// Capture content and include master layout
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
