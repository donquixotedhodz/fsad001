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
$STARTING_CHECK_NO = 69001;
$STARTING_BALANCE = 0.00;

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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ppe') {
    $ppe_id = intval($_POST['ppe_id'] ?? 0);
    
    if ($ppe_id > 0) {
        try {
            // Get the record to be deleted
            $stmt = $conn->prepare("SELECT balance FROM ppe WHERE id = ?");
            $stmt->execute([$ppe_id]);
            $recordToDelete = $stmt->fetch();
            
            if ($recordToDelete) {
                // Get the previous balance
                $stmt = $conn->prepare("SELECT balance FROM ppe WHERE id < ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$ppe_id]);
                $prevRecord = $stmt->fetch();
                $newBalance = $prevRecord ? floatval($prevRecord['balance']) : $STARTING_BALANCE;
                
                // Delete the record
                $stmt = $conn->prepare("DELETE FROM ppe WHERE id = ?");
                $stmt->execute([$ppe_id]);
                
                // Recalculate balances for all records after this one
                $stmt = $conn->prepare("SELECT id, debit, credit FROM ppe WHERE id > ? ORDER BY id ASC");
                $stmt->execute([$ppe_id]);
                $recordsAfter = $stmt->fetchAll();
                
                $currentBalance = $newBalance;
                foreach ($recordsAfter as $record) {
                    $currentBalance = $currentBalance - $record['debit'] + $record['credit'];
                    $updateStmt = $conn->prepare("UPDATE ppe SET balance = ? WHERE id = ?");
                    $updateStmt->execute([$currentBalance, $record['id']]);
                }
                
                // Update PPE Provident Fund remaining balance
                $updateFundStmt = $conn->prepare("UPDATE ppe_funds SET remaining_balance = ? WHERE fund_name = 'PPE Provident Fund'");
                $updateFundStmt->execute([$currentBalance]);
                
                $successMessage = "PPE record deleted successfully!";
            }
        } catch (Exception $e) {
            $errorMessage = "Error deleting record: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_ppe') {
    $ppe_id = intval($_POST['ppe_id'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $particulars = htmlspecialchars($_POST['particulars'] ?? '');
    $check_type = htmlspecialchars($_POST['check_type'] ?? 'actual');
    $check_no = $check_type === 'online' ? 'ONLINE' : intval($_POST['check_no'] ?? 0);
    $dv_or_no = htmlspecialchars($_POST['dv_or_no'] ?? '');
    $debit = floatval($_POST['debit'] ?? 0);
    $credit = floatval($_POST['credit'] ?? 0);
    
    if ($ppe_id > 0 && !empty($particulars) && ($check_no > 0 || $check_no === 'ONLINE')) {
        try {
            // Get the current record
            $stmt = $conn->prepare("SELECT debit, credit FROM ppe WHERE id = ?");
            $stmt->execute([$ppe_id]);
            $currentRecord = $stmt->fetch();
            
            // Get the previous balance
            $stmt = $conn->prepare("SELECT balance FROM ppe WHERE id < ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$ppe_id]);
            $prevRecord = $stmt->fetch();
            $prevBalance = $prevRecord ? floatval($prevRecord['balance']) : $STARTING_BALANCE;
            
            // Calculate the new balance for this record
            $newBalance = $prevBalance - $debit + $credit;
            
            // Update the record
            $stmt = $conn->prepare("UPDATE ppe SET date = ?, particulars = ?, check_no = ?, dv_or_no = ?, debit = ?, credit = ?, balance = ? WHERE id = ?");
            $stmt->execute([$date, $particulars, $check_no, $dv_or_no, $debit, $credit, $newBalance, $ppe_id]);
            
            // Recalculate balances for all records after this one
            $stmt = $conn->prepare("SELECT id, debit, credit FROM ppe WHERE id > ? ORDER BY id ASC");
            $stmt->execute([$ppe_id]);
            $recordsAfter = $stmt->fetchAll();
            
            $currentBalance = $newBalance;
            foreach ($recordsAfter as $record) {
                $currentBalance = $currentBalance - $record['debit'] + $record['credit'];
                $updateStmt = $conn->prepare("UPDATE ppe SET balance = ? WHERE id = ?");
                $updateStmt->execute([$currentBalance, $record['id']]);
            }
            
            // Update PPE Provident Fund remaining balance
            $updateFundStmt = $conn->prepare("UPDATE ppe_funds SET remaining_balance = ? WHERE fund_name = 'PPE Provident Fund'");
            $updateFundStmt->execute([$currentBalance]);
            
            $successMessage = "PPE record updated successfully!";
        } catch (Exception $e) {
            $errorMessage = "Error updating record: " . htmlspecialchars($e->getMessage());
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

// Handle AJAX request to get PPE record data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_ppe') {
    header('Content-Type: application/json');
    $ppe_id = intval($_POST['id'] ?? 0);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM ppe WHERE id = ?");
        $stmt->execute([$ppe_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo json_encode(['success' => true, 'record' => $record]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Record not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
    }
    exit;
}

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
                    <input type="date" id="addDate" name="date" value="<?php echo date('Y-m-d'); ?>" onchange="updateDVNumber()" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">DV/OR No. (Format: YYYY-MM-###)</label>
                    <div class="flex gap-2">
                        <input type="text" id="addDVPrefix" name="dv_prefix" disabled class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white bg-gray-200 text-gray-600 focus:outline-none" placeholder="YYYY-MM-">
                        <input type="text" id="addDVSuffix" name="dv_suffix" maxlength="3" placeholder="###" class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="updateFullDVNumber()">
                    </div>
                    <input type="hidden" id="addDVNumber" name="dv_or_no" value="">
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

    <!-- Edit PPE Modal -->
    <dialog id="editPPEModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Edit PPE Record</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit_ppe">
            <input type="hidden" id="editPPEId" name="ppe_id" value="">
            
            <div class="grid grid-cols-2 gap-6">
                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date *</label>
                    <input type="date" id="editDate" name="date" onchange="updateEditDVNumber()" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Check No Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check Type *</label>
                    <select id="editCheckType" name="check_type" required onchange="toggleCheckNoInputEdit()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="actual">Actual Check No.</option>
                        <option value="online">Online</option>
                    </select>
                </div>

                <!-- Check No (Actual) -->
                <div id="editCheckNoDiv">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Check No. *</label>
                    <input type="number" id="editCheckNoInput" name="check_no" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Particulars -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Particulars (Names) *</label>
                    <input type="text" id="editParticulars" name="particulars" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- DV/OR No -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">DV/OR No. (Format: YYYY-MM-###)</label>
                    <div class="flex gap-2">
                        <input type="text" id="editDVPrefix" name="dv_prefix" disabled class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white bg-gray-200 text-gray-600 focus:outline-none" placeholder="YYYY-MM-">
                        <input type="text" id="editDVSuffix" name="dv_suffix" maxlength="3" placeholder="###" class="w-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="updateFullEditDVNumber()">
                    </div>
                    <input type="hidden" id="editDVNumber" name="dv_or_no" value="">
                </div>

                <!-- Credit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Credit</label>
                    <input type="number" id="editCredit" name="credit" step="0.01" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Debit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Debit</label>
                    <input type="number" id="editDebit" name="debit" step="0.01" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Update Record
                </button>
                <button type="button" onclick="document.getElementById('editPPEModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>
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
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">Actions</th>
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
                            $formattedDate = date('m/d/Y', strtotime($record['date']));
                            echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700">';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($formattedDate) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['particulars']) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['check_no'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['dv_or_no'] ?? '') . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-700 dark:text-gray-300">' . number_format($record['debit'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right text-gray-700 dark:text-gray-300">' . number_format($record['credit'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">' . number_format($record['balance'], 2) . '</td>';
                            echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">';
                            echo '<button onclick="editPPE(' . $record['id'] . ')" class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 text-white rounded hover:bg-blue-600 transition mr-2" title="Edit" style="font-size: 14px;"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>';
                            echo '<button onclick="deletePPE(' . $record['id'] . ')" class="inline-flex items-center justify-center w-8 h-8 bg-red-500 text-white rounded hover:bg-red-600 transition" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>';
                            echo '</td>';
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

function toggleCheckNoInputEdit() {
    const checkType = document.getElementById('editCheckType').value;
    const checkNoDiv = document.getElementById('editCheckNoDiv');
    const checkNoInput = document.getElementById('editCheckNoInput');
    
    if (checkType === 'online') {
        checkNoDiv.style.display = 'none';
        checkNoInput.required = false;
    } else {
        checkNoDiv.style.display = 'block';
        checkNoInput.required = true;
    }
}

function updateDVNumber() {
    const dateInput = document.getElementById('addDate').value;
    if (dateInput) {
        const dateObj = new Date(dateInput);
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        document.getElementById('addDVPrefix').value = `${year}-${month}-`;
        updateFullDVNumber();
    }
}

function updateFullDVNumber() {
    const prefix = document.getElementById('addDVPrefix').value;
    const suffix = document.getElementById('addDVSuffix').value;
    const fullDV = prefix + suffix;
    document.getElementById('addDVNumber').value = fullDV;
}

function updateEditDVNumber() {
    const dateInput = document.getElementById('editDate').value;
    if (dateInput) {
        const dateObj = new Date(dateInput);
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        document.getElementById('editDVPrefix').value = `${year}-${month}-`;
        updateFullEditDVNumber();
    }
}

function updateFullEditDVNumber() {
    const prefix = document.getElementById('editDVPrefix').value;
    const suffix = document.getElementById('editDVSuffix').value;
    const fullDV = prefix + suffix;
    document.getElementById('editDVNumber').value = fullDV;
}

function editPPE(id) {
    fetch(``, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_ppe&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editPPEId').value = data.record.id;
            document.getElementById('editDate').value = data.record.date;
            document.getElementById('editParticulars').value = data.record.particulars;
            document.getElementById('editCheckType').value = data.record.check_no === 'ONLINE' ? 'online' : 'actual';
            document.getElementById('editCheckNoInput').value = data.record.check_no === 'ONLINE' ? '' : data.record.check_no;
            document.getElementById('editDebit').value = data.record.debit;
            document.getElementById('editCredit').value = data.record.credit;
            
            // Parse the existing DV number
            if (data.record.dv_or_no) {
                const dvParts = data.record.dv_or_no.split('-');
                if (dvParts.length >= 3) {
                    document.getElementById('editDVPrefix').value = `${dvParts[0]}-${dvParts[1]}-`;
                    document.getElementById('editDVSuffix').value = dvParts[2];
                    document.getElementById('editDVNumber').value = data.record.dv_or_no;
                }
            } else {
                updateEditDVNumber();
            }
            
            toggleCheckNoInputEdit();
            document.getElementById('editPPEModal').showModal();
        }
    });
}

function deletePPE(id) {
    if (confirm('Are you sure you want to delete this record? This will recalculate all subsequent balances.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_ppe">
            <input type="hidden" name="ppe_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCheckNoInput();
    toggleCheckNoInputEdit();
    updateDVNumber();
});
</script>
</div>

<?php
// Capture content and include master layout
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
