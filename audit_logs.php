<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/AuditLogsController.php';

MainController::requireAuth();

// Only administrator or superadmin can view audit logs
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['administrator', 'superadmin'])) {
    header("Location: dashboard.php");
    exit();
}

$controller = new MainController($conn);
$auditController = new AuditLogsController($conn);
$controller->setCurrentPage('audit_logs');

$currentPageName = 'audit_logs';
$username = $_SESSION['username'] ?? 'User';

// Filters
$filters = [
    'action' => $_GET['action'] ?? '',
    'table_name' => $_GET['table'] ?? '',
    'username' => $_GET['user'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Handle AJAX request to get log details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_log_details') {
    header('Content-Type: application/json');
    $log_id = intval($_POST['id'] ?? 0);

    try {
        $log = $auditController->getLogById($log_id);
        if ($log) {
            // Format JSON values for better display
            if ($log['old_values']) {
                $log['old_values_formatted'] = json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT);
            }
            if ($log['new_values']) {
                $log['new_values_formatted'] = json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT);
            }
            echo json_encode(['success' => true, 'log' => $log]);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Log entry not found']);
        }
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
    }
    exit;
}

// Pagination
$itemsPerPage = isset($_GET['limit']) ? ($_GET['limit'] === 'all' ? 'all' : intval($_GET['limit'])) : 10;
$totalItems = $auditController->getLogCount($filters);

if ($itemsPerPage === 'all') {
    $itemsPerPage = $totalItems > 0 ? $totalItems : 1;
}

$totalPages = ceil($totalItems / $itemsPerPage);
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page > $totalPages && $totalPages > 0)
    $page = $totalPages;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $itemsPerPage;

try {
    $logs = $auditController->getLogs($filters, $itemsPerPage, $offset);
    $availableActions = $auditController->getAvailableActions();
    $availableTables = $auditController->getAvailableTables();
    $availableUsers = $auditController->getAvailableUsers();
}
catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $logs = [];
}

ob_start();
?>

<div class="w-full">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Audit Logs</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Track all activities and changes in the system</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <input type="hidden" name="limit" value="<?php echo htmlspecialchars($itemsPerPage); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action</label>
                <select name="action" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    <?php foreach ($availableActions as $action): ?>
                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Table</label>
                <select name="table" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Tables</option>
                    <?php foreach ($availableTables as $table): ?>
                        <option value="<?php echo htmlspecialchars($table); ?>" <?php echo $filters['table_name'] === $table ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($table); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                <select name="user" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Users</option>
                    <?php foreach ($availableUsers as $user): ?>
                        <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $filters['username'] === $user ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="md:col-span-3 lg:col-span-5 flex justify-end gap-3 mt-2">
                <a href="audit_logs.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 font-medium">Reset</a>
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
            <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="10" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 10) ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 25) ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 50) ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 100) ? 'selected' : ''; ?>>100</option>
                <option value="all" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 'all') ? 'selected' : ''; ?>>Show All</option>
            </select>
            <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Total records: <span class="font-bold"><?php echo number_format($totalItems); ?></span>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Timestamp</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">User</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Action</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Table</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Description</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log):
        $actionColor = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        switch ($log['action']) {
            case 'CREATE':
                $actionColor = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                break;
            case 'UPDATE':
                $actionColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
                break;
            case 'DELETE':
                $actionColor = 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
                break;
            case 'READ':
                $actionColor = 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
                break;
            case 'LOGIN':
                $actionColor = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
                break;
        }
?>
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                <span class="font-medium"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></span>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $actionColor; ?>">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100">
                                <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded"><?php echo htmlspecialchars($log['table_name']); ?></code>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100 max-w-xs truncate">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </td>
                            <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">
                                <button onclick="viewLogDetails(<?php echo $log['id']; ?>)" class="text-blue-500 hover:text-blue-700 transition" title="View details">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    <?php
    endforeach; ?>
                <?php
else: ?>
                    <tr>
                        <td colspan="6" class="border border-gray-300 dark:border-gray-600 px-4 py-6 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>No audit logs found matching your criteria.</span>
                            </div>
                        </td>
                    </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing <?php echo($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
            </div>
            <div class="flex gap-1 overflow-x-auto pb-2 md:pb-0">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $itemsPerPage; ?>&action=<?php echo urlencode($filters['action']); ?>&table=<?php echo urlencode($filters['table_name']); ?>&user=<?php echo urlencode($filters['username']); ?>&date_from=<?php echo urlencode($filters['date_from']); ?>&date_to=<?php echo urlencode($filters['date_to']); ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Previous
                    </a>
                <?php
    endif; ?>

                <?php
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);

    if ($startPage > 1) {
        echo '<a href="?page=1&limit=' . $itemsPerPage . '&action=' . urlencode($filters['action']) . '&table=' . urlencode($filters['table_name']) . '&user=' . urlencode($filters['username']) . '&date_from=' . urlencode($filters['date_from']) . '&date_to=' . urlencode($filters['date_to']) . '" class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>';
        if ($startPage > 2)
            echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $isActive = ($i == $page);
        $class = $isActive
            ? 'bg-blue-500 text-white'
            : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
        echo '<a href="?page=' . $i . '&limit=' . $itemsPerPage . '&action=' . urlencode($filters['action']) . '&table=' . urlencode($filters['table_name']) . '&user=' . urlencode($filters['username']) . '&date_from=' . urlencode($filters['date_from']) . '&date_to=' . urlencode($filters['date_to']) . '" class="px-3 py-2 rounded-lg transition ' . $class . '">' . $i . '</a>';
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1)
            echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
        echo '<a href="?page=' . $totalPages . '&limit=' . $itemsPerPage . '&action=' . urlencode($filters['action']) . '&table=' . urlencode($filters['table_name']) . '&user=' . urlencode($filters['username']) . '&date_from=' . urlencode($filters['date_from']) . '&date_to=' . urlencode($filters['date_to']) . '" class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">' . $totalPages . '</a>';
    }
?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $itemsPerPage; ?>&action=<?php echo urlencode($filters['action']); ?>&table=<?php echo urlencode($filters['table_name']); ?>&user=<?php echo urlencode($filters['username']); ?>&date_from=<?php echo urlencode($filters['date_from']); ?>&date_to=<?php echo urlencode($filters['date_to']); ?>" class="px-3 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Next
                    </a>
                <?php
    endif; ?>
            </div>
        </div>
    <?php
endif; ?>

    <!-- Log Details Modal -->
    <dialog id="logDetailsModal" class="rounded-xl shadow-2xl max-w-4xl w-full p-0 dark:bg-gray-800 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Audit Log Details</h2>
            <button onclick="document.getElementById('logDetailsModal').close()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto max-h-[80vh]">
            <div id="logDetailsContent" class="space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">Timestamp</label>
                        <p id="detailTimestamp" class="text-gray-900 dark:text-gray-100 font-medium"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">User</label>
                        <p id="detailUser" class="text-gray-900 dark:text-gray-100 font-medium"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">Action</label>
                        <div><span id="detailAction" class="px-2.5 py-0.5 rounded-full text-xs font-bold"></span></div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">Table Affected</label>
                        <p id="detailTable" class="text-gray-900 dark:text-gray-100 font-medium font-mono"></p>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">Description</label>
                    <p id="detailDescription" class="text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg border border-gray-200 dark:border-gray-700"></p>
                </div>

                <!-- Values Comparison -->
                <div id="valuesComparison" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span> Old Values
                        </label>
                        <pre id="detailOldValues" class="text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto min-h-[100px]"></pre>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span> New Values
                        </label>
                        <pre id="detailNewValues" class="text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 overflow-x-auto min-h-[100px]"></pre>
                    </div>
                </div>

                <!-- Technical Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">IP Address</label>
                        <p id="detailIpAddress" class="text-gray-600 dark:text-gray-400 text-sm"></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase text-gray-500 tracking-wider">User Agent</label>
                        <p id="detailUserAgent" class="text-gray-600 dark:text-gray-400 text-xs break-all"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button onclick="document.getElementById('logDetailsModal').close()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 font-medium">
                Close
            </button>
        </div>
    </dialog>
</div>

<script>
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const url = new URL(window.location.href);
    url.searchParams.set('page', '1');
    url.searchParams.set('limit', limit);
    window.location.href = url.toString();
}

function viewLogDetails(id) {
    // Show loading state if needed
    fetch('audit_logs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_log_details&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const log = data.log;
            
            // Basic info
            document.getElementById('detailTimestamp').textContent = log.created_at;
            document.getElementById('detailUser').textContent = log.username || 'System';
            document.getElementById('detailTable').textContent = log.table_name;
            document.getElementById('detailDescription').textContent = log.description;
            document.getElementById('detailIpAddress').textContent = log.ip_address || 'Unknown';
            document.getElementById('detailUserAgent').textContent = log.user_agent || 'Unknown';
            
            // Action badge
            const actionEl = document.getElementById('detailAction');
            actionEl.textContent = log.action;
            actionEl.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold ';
            
            switch(log.action) {
                case 'CREATE': actionEl.classList.add('bg-green-100', 'text-green-800'); break;
                case 'UPDATE': actionEl.classList.add('bg-blue-100', 'text-blue-800'); break;
                case 'DELETE': actionEl.classList.add('bg-red-100', 'text-red-800'); break;
                case 'READ': actionEl.classList.add('bg-purple-100', 'text-purple-800'); break;
                case 'LOGIN': actionEl.classList.add('bg-amber-100', 'text-amber-800'); break;
                default: actionEl.classList.add('bg-gray-100', 'text-gray-800');
            }

            // Values
            const oldValues = document.getElementById('detailOldValues');
            const newValues = document.getElementById('detailNewValues');
            
            oldValues.textContent = log.old_values ? log.old_values_formatted : 'No old data';
            newValues.textContent = log.new_values ? log.new_values_formatted : 'No new data';
            
            // Show/Hide comparison if both are empty
            if (!log.old_values && !log.new_values) {
                document.getElementById('valuesComparison').classList.add('hidden');
            } else {
                document.getElementById('valuesComparison').classList.remove('hidden');
            }

            // Open the modal
            document.getElementById('logDetailsModal').showModal();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Log details not found',
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load log details',
            confirmButtonColor: '#ef4444'
        });
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
