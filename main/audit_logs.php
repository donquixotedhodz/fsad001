<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('audit_logs');

$currentPage = 'audit_logs';
$username = $_SESSION['username'] ?? 'User';

// Initialize audit logger
$auditLogger = new AuditLogger($conn);

// Get filter values
$filterAction = isset($_GET['action']) ? trim($_GET['action']) : '';
$filterTable = isset($_GET['table']) ? trim($_GET['table']) : '';
$filterUser = isset($_GET['user']) ? trim($_GET['user']) : '';
$filterDateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filterDateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Pagination setup
$itemsPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Build filter array
$filters = [];
if (!empty($filterAction)) $filters['action'] = $filterAction;
if (!empty($filterTable)) $filters['table_name'] = $filterTable;
if (!empty($filterUser)) $filters['username'] = $filterUser;
if (!empty($filterDateFrom)) $filters['date_from'] = $filterDateFrom;
if (!empty($filterDateTo)) $filters['date_to'] = $filterDateTo;

// Get total count for pagination
$totalLogs = count($auditLogger->getLogs($filters));
$totalPages = ceil($totalLogs / $itemsPerPage);

// Get paginated logs
$filters['limit'] = $itemsPerPage;
$filters['offset'] = $offset;
$logs = $auditLogger->getLogs($filters);

// Get statistics
$actionStats = $auditLogger->getLogCountByAction();
$tableStats = $auditLogger->getLogCountByTable();
$userStats = $auditLogger->getLogCountByUser();

// Start output buffering
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Audit Log</h1>
        <p class="text-gray-600 dark:text-gray-400">Track all system activities and changes</p>
    </div>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Filters</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action</label>
                <select name="action" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    <option value="CREATE" <?php echo $filterAction === 'CREATE' ? 'selected' : ''; ?>>Create</option>
                    <option value="READ" <?php echo $filterAction === 'READ' ? 'selected' : ''; ?>>Read</option>
                    <option value="UPDATE" <?php echo $filterAction === 'UPDATE' ? 'selected' : ''; ?>>Update</option>
                    <option value="DELETE" <?php echo $filterAction === 'DELETE' ? 'selected' : ''; ?>>Delete</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Table</label>
                <select name="table" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Tables</option>
                    <?php foreach ($tableStats as $table): ?>
                    <option value="<?php echo htmlspecialchars($table['table_name']); ?>" <?php echo $filterTable === $table['table_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($table['table_name']); ?> (<?php echo $table['count']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">User</label>
                <select name="user" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Users</option>
                    <?php foreach ($userStats as $user): ?>
                    <option value="<?php echo htmlspecialchars($user['username']); ?>" <?php echo $filterUser === $user['username'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['username']); ?> (<?php echo $user['count']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium text-sm">
                    Apply Filters
                </button>
                <a href="audit_logs.php" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg hover:bg-gray-400 dark:hover:bg-gray-700 transition font-medium text-sm">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Date & Time</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">User</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Action</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Table</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Record ID</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Description</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars($log['username'] ?? 'System'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php
                                $actionColor = match($log['action']) {
                                    'CREATE' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                                    'UPDATE' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                                    'DELETE' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
                                    'READ' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
                                    default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                                };
                                ?>
                                <span class="px-2 py-1 <?php echo $actionColor; ?> rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo $log['record_id'] ?? '-'; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 font-mono text-xs"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                No audit logs found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $itemsPerPage, $totalLogs); ?></span> of <span class="font-medium"><?php echo $totalLogs; ?></span> entries
            </div>
            
            <div class="flex gap-2">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=1<?php echo (!empty($filterAction) ? '&action=' . urlencode($filterAction) : '') . (!empty($filterTable) ? '&table=' . urlencode($filterTable) : '') . (!empty($filterUser) ? '&user=' . urlencode($filterUser) : '') . (!empty($filterDateFrom) ? '&date_from=' . urlencode($filterDateFrom) : '') . (!empty($filterDateTo) ? '&date_to=' . urlencode($filterDateTo) : ''); ?>" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">First</a>
                    <a href="?page=<?php echo $currentPage - 1; ?><?php echo (!empty($filterAction) ? '&action=' . urlencode($filterAction) : '') . (!empty($filterTable) ? '&table=' . urlencode($filterTable) : '') . (!empty($filterUser) ? '&user=' . urlencode($filterUser) : '') . (!empty($filterDateFrom) ? '&date_from=' . urlencode($filterDateFrom) : '') . (!empty($filterDateTo) ? '&date_to=' . urlencode($filterDateTo) : ''); ?>" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">Previous</a>
                <?php endif; ?>

                <div class="flex items-center gap-1">
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm font-medium"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo (!empty($filterAction) ? '&action=' . urlencode($filterAction) : '') . (!empty($filterTable) ? '&table=' . urlencode($filterTable) : '') . (!empty($filterUser) ? '&user=' . urlencode($filterUser) : '') . (!empty($filterDateFrom) ? '&date_from=' . urlencode($filterDateFrom) : '') . (!empty($filterDateTo) ? '&date_to=' . urlencode($filterDateTo) : ''); ?>" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?><?php echo (!empty($filterAction) ? '&action=' . urlencode($filterAction) : '') . (!empty($filterTable) ? '&table=' . urlencode($filterTable) : '') . (!empty($filterUser) ? '&user=' . urlencode($filterUser) : '') . (!empty($filterDateFrom) ? '&date_from=' . urlencode($filterDateFrom) : '') . (!empty($filterDateTo) ? '&date_to=' . urlencode($filterDateTo) : ''); ?>" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">Next</a>
                    <a href="?page=<?php echo $totalPages; ?><?php echo (!empty($filterAction) ? '&action=' . urlencode($filterAction) : '') . (!empty($filterTable) ? '&table=' . urlencode($filterTable) : '') . (!empty($filterUser) ? '&user=' . urlencode($filterUser) : '') . (!empty($filterDateFrom) ? '&date_from=' . urlencode($filterDateFrom) : '') . (!empty($filterDateTo) ? '&date_to=' . urlencode($filterDateTo) : ''); ?>" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">Last</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Audit Log';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
