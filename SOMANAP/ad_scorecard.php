<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/ADScoreCardController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('ads');
$adsController = new ADScoreCardController($conn);
$auditLogger = new AuditLogger($conn);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $adsController->addADS();
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $adsController->updateADS();
        exit;
    }
}

// Check for delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $adsController->deleteADS();
    exit;
}

// Check for JSON fetch request (editing)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $adsController->getADSJSON();
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Get all ADS records
$allADS = $adsController->getAllADS();

// Fetch existing audit reports for autocomplete
$auditReportsStmt = $conn->prepare("SELECT DISTINCT audit_report FROM ads ORDER BY audit_report ASC");
$auditReportsStmt->execute();
$auditReports = $auditReportsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Handle search and filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterBy = isset($_GET['filterBy']) ? trim($_GET['filterBy']) : 'all';
$filteredADS = $allADS;

// Apply filters
if (!empty($searchTerm) || $filterBy !== 'all') {
    $filteredADS = array_filter($allADS, function ($record) use ($searchTerm, $filterBy) {
        // Apply search filter - search ALL fields
        if (!empty($searchTerm)) {
            $searchLower = strtolower($searchTerm);
            $searchableFields = [
                $record['audit_report'] ?? '',
                $record['adsyear'] ?? '',
                $record['scope'] ?? '',
                $record['bac_reso'] ?? '',
                $record['boa_reso'] ?? '',
                $record['remarks'] ?? '',
            ];

            $combined = strtolower(implode(' ', $searchableFields));
            if (strpos($combined, $searchLower) === false) {
                return false;
            }
        }

        return true;
    });
    $filteredADS = array_values($filteredADS);
}

// Pagination
$itemsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$totalItems = count($filteredADS);
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;
$records = array_slice($filteredADS, $offset, $itemsPerPage);

// Build pagination query
$paginationQuery = '';
if (!empty($searchTerm)) {
    $paginationQuery .= '&search=' . urlencode($searchTerm);
}
if ($filterBy !== 'all') {
    $paginationQuery .= '&filterBy=' . urlencode($filterBy);
}
if ($itemsPerPage !== 10) {
    $paginationQuery .= '&limit=' . $itemsPerPage;
}

ob_start();
?>

<div class="w-full">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Audit Report Scorecards</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage and track audit report scorecards (ADS)</p>
        </div>
        <button onclick="document.getElementById('addADSModal').classList.remove('hidden'); resetADSForm();" class="mt-4 md:mt-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Scorecard
        </button>
    </div>

    <!-- Filters and Search -->
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <form method="GET" class="flex flex-wrap gap-4 w-full" id="searchForm">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="limit" value="<?php echo htmlspecialchars($itemsPerPage); ?>">
            
            <!-- Search Input -->
            <div class="flex-1 relative min-w-xs">
                <input 
                    type="text" 
                    id="searchInput"
                    name="search"
                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                    placeholder="Search by audit report, year, scope, or resolution..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <svg class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Clear Filters Button -->
            <?php if (!empty($searchTerm) || $filterBy !== 'all'): ?>
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
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo($itemsPerPage == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo($itemsPerPage == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo($itemsPerPage == 25) ? 'selected' : ''; ?>>25</option>
            <option value="50" <?php echo($itemsPerPage == 50) ? 'selected' : ''; ?>>50</option>
            <option value="100" <?php echo($itemsPerPage == 100) ? 'selected' : ''; ?>>100</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
    </div>

    <!-- Records Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($allADS)): ?>
        <div class="text-center py-12 px-4">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No scorecards yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Start by adding your first audit decision scorecard</p>
        </div>
        <?php
else: ?>
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Title of Audit Report</th>
                    <!-- <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">ADS Year</th> -->
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Scope</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">BAC Date</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">BAC Resolution</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">BOA Date</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">BOA Resolution</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Remarks</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-medium">
                        <?php echo htmlspecialchars($record['audit_report']); ?>
                    </td>
                    <!-- <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars($record['adsyear'] ?? '-'); ?>
                    </td> -->
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars(substr($record['scope'] ?? '', 0, 50)) . (strlen($record['scope'] ?? '') > 50 ? '...' : ''); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo $record['bac_date'] ? date('F d, Y', strtotime($record['bac_date'])) : '-'; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars($record['bac_reso'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo $record['boa_date'] ? date('F d, Y', strtotime($record['boa_date'])) : '-'; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars($record['boa_reso'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars(substr($record['remarks'] ?? '', 0, 30)) . (strlen($record['remarks'] ?? '') > 30 ? '...' : ''); ?>
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2 flex items-center">
                        <!-- View Button -->
                        <button onclick="viewADS(<?php echo $record['id']; ?>)" title="View details" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-primary);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        
                        <!-- Edit Button -->
                        <button onclick="editADS(<?php echo $record['id']; ?>)" title="Edit scorecard" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: #eab308;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        
                        <!-- Print Button -->
                        <button onclick="printADS(<?php echo $record['id']; ?>)" title="Print scorecard" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-success);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                        </button>
                        
                        <!-- Delete Button -->
                        <button onclick="deleteADS(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['audit_report'])); ?>')" title="Delete scorecard" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-danger);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
                <?php
    endforeach; ?>
            </tbody>
        </table>
        <?php
endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> scorecards
        </div>
        <div class="flex gap-2">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php
    endif; ?>

            <?php
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1): ?>
            <a href="?page=1<?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>
            <?php if ($startPage > 2): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php
        endif; ?>
            <?php
    endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 rounded-lg transition <?php echo($i === $currentPage) ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
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

            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php
    endif; ?>
        </div>
    </div>
    <?php
endif; ?>
</div>

<!-- Add/Edit ADS Modal -->
<div id="addADSModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-3xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 id="modalTitle" class="text-3xl font-bold text-gray-900 dark:text-white">Add Audit Report</h2>
            <button onclick="closeADSModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="adsForm" class="space-y-6">
            <input type="hidden" name="action" value="add">
            <input type="hidden" id="adsId" name="id" value="">

            <!-- Audit Report -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Audit Report *</label>
                <div class="relative">
                    <input type="text" id="auditReport" name="audit_report" required placeholder="Enter audit report name" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <div id="auditReportSuggestions" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                </div>
            </div>

            <!-- ADS Year -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ADS Year</label>
                <input type="number" id="adsyear" name="adsyear" min="1900" max="2100" placeholder="Enter year (e.g., 2024)" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Scope -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scope</label>
                <textarea id="scope" name="scope" rows="3" placeholder="Enter audit scope..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            <!-- Two Column Layout: Dates and Resolutions -->
            <div class="space-y-6">
                <!-- BAC Section -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 pb-2 border-b border-gray-300 dark:border-gray-600">Board Audit Committee</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">BAC Date</label>
                            <input type="date" id="bacDate" name="bac_date" placeholder="Month DD, YYYY" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">BAC Resolution</label>
                            <input type="text" id="bacReso" name="bac_reso" placeholder="e.g., BAC Resolution No. 2024-001" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- BOA Section -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 pb-2 border-b border-gray-300 dark:border-gray-600">Board of Audit</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">BOA Date</label>
                            <input type="date" id="boaDate" name="boa_date" placeholder="Month DD, YYYY" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">BOA Resolution</label>
                            <input type="text" id="boaReso" name="boa_reso" placeholder="e.g., BOA Resolution No. 2024-001" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Remarks</label>
                <textarea id="remarks" name="remarks" rows="3" placeholder="Enter any remarks..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-300 dark:border-gray-600">
                <button type="submit" id="submitBtn" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    Add Scorecard
                </button>
                <button type="button" onclick="closeADSModal()" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Details Modal -->
<div id="viewDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-2xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Scorecard Details</h2>
            <button onclick="document.getElementById('viewDetailsModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div id="detailsContent" class="space-y-4">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>



<script>
// Autocomplete data for audit reports
const auditReportsData = <?php echo json_encode($auditReports); ?>;

function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('limit', limit);
    searchParams.set('page', '1');
    window.location.href = '?' + searchParams.toString();
}

function resetADSForm() {
    document.getElementById('adsForm').reset();
    document.getElementById('adsId').value = '';
    document.getElementById('submitBtn').textContent = 'Add Scorecard';
    document.getElementById('modalTitle').textContent = 'Add Audit Report';
    document.getElementById('adsForm').elements['action'].value = 'add';
    setupAuditReportAutocomplete();
}

function closeADSModal() {
    document.getElementById('addADSModal').classList.add('hidden');
    resetADSForm();
}

// Setup autocomplete for Audit Report
function setupAuditReportAutocomplete() {
    const input = document.getElementById('auditReport');
    const suggestionsDiv = document.getElementById('auditReportSuggestions');
    
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        // Filter suggestions based on input
        const filtered = auditReportsData.filter(item => 
            item.toLowerCase().includes(query)
        );
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
        } else {
            suggestionsDiv.innerHTML = filtered.map(item => 
                `<div onclick="selectAuditReportSuggestion(this, '${item.replace(/'/g, "\\'")}', false)" class="px-4 py-2 hover:bg-blue-100 dark:hover:bg-blue-900 cursor-pointer text-gray-900 dark:text-white">${item}</div>`
            ).join('');
            suggestionsDiv.classList.remove('hidden');
        }
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== input && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}

function selectAuditReportSuggestion(element, value, isNew) {
    const input = document.getElementById('auditReport');
    input.value = value;
    document.getElementById('auditReportSuggestions').classList.add('hidden');
}

function editADS(id) {
    fetch('?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const record = data.data;
                document.getElementById('adsId').value = record.id;
                document.getElementById('auditReport').value = record.audit_report;
                document.getElementById('adsyear').value = record.adsyear || '';
                document.getElementById('scope').value = record.scope || '';
                document.getElementById('bacDate').value = record.bac_date || '';
                document.getElementById('bacReso').value = record.bac_reso || '';
                document.getElementById('boaDate').value = record.boa_date || '';
                document.getElementById('boaReso').value = record.boa_reso || '';
                document.getElementById('remarks').value = record.remarks || '';
                
                document.getElementById('submitBtn').textContent = 'Update Scorecard';
                document.getElementById('modalTitle').textContent = 'Edit Audit Report';
                document.getElementById('adsForm').elements['action'].value = 'edit';
                
                setupAuditReportAutocomplete();
                document.getElementById('addADSModal').classList.remove('hidden');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error loading record: ' + (data.message || 'Unknown error')
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Error loading record'
            });
        });
}

function viewADS(id) {
    fetch('?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const record = data.data;
                let html = `
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Audit Report</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">${escapeHtml(record.audit_report)}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ADS Year</p>
                            <p class="text-gray-900 dark:text-white">${escapeHtml(record.adsyear || '-')}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Scope</p>
                            <p class="text-gray-900 dark:text-white">${escapeHtml(record.scope || '-')}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">BAC Date</p>
                                <p class="text-gray-900 dark:text-white">${record.bac_date ? new Date(record.bac_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : '-'}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">BAC Resolution</p>
                                <p class="text-gray-900 dark:text-white">${escapeHtml(record.bac_reso || '-')}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">BOA Date</p>
                                <p class="text-gray-900 dark:text-white">${record.boa_date ? new Date(record.boa_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : '-'}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">BOA Resolution</p>
                                <p class="text-gray-900 dark:text-white">${escapeHtml(record.boa_reso || '-')}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Remarks</p>
                            <p class="text-gray-900 dark:text-white">${escapeHtml(record.remarks || '-')}</p>
                        </div>
                    </div>
                `;
                document.getElementById('detailsContent').innerHTML = html;
                document.getElementById('viewDetailsModal').classList.remove('hidden');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error loading record: ' + (data.message || 'Unknown error')
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Error loading record'
            });
        });
}

function deleteADS(id, name) {
    // Fetch full record data to show in confirmation modal
    fetch('?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const record = data.data;
                const auditReport = record.audit_report || 'N/A';
                const scope = record.scope ? record.scope.substring(0, 50) : 'N/A';
                const remarks = record.remarks ? record.remarks.substring(0, 50) : 'N/A';
                
                Swal.fire({
                    title: 'Delete Scorecard',
                    html: `
                        <div class="text-left">
                            <p class="mb-4 text-gray-700 dark:text-gray-300"><strong>Are you sure you want to delete this scorecard?</strong></p>
                            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-sm space-y-2">
                                <div><span class="font-semibold text-gray-800 dark:text-gray-200">Audit Report:</span> <span class="text-gray-600 dark:text-gray-400">${auditReport}</span></div>
                                <div><span class="font-semibold text-gray-800 dark:text-gray-200">Scope:</span> <span class="text-gray-600 dark:text-gray-400">${scope}</span></div>
                                <div><span class="font-semibold text-gray-800 dark:text-gray-200">Remarks:</span> <span class="text-gray-600 dark:text-gray-400">${remarks}</span></div>
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    didOpen: (modal) => {
                        if (document.body.classList.contains('dark')) {
                            modal.classList.add('dark');
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('?', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id: id })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Unknown error occurred'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Error deleting record'
                            });
                        });
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Error loading record'
            });
        });
}

function printADS(id) {
    // You can implement a print function here
    Swal.fire({
        icon: 'info',
        title: 'Print Functionality',
        text: 'Print functionality for ID: ' + id + ' - Coming soon!',
        confirmButtonText: 'OK'
    });
    // In a real implementation, you could open a print preview or generate a PDF
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle form submission
document.getElementById('adsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const auditReportValue = document.getElementById('auditReport').value.trim();
    
    fetch('?', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add new audit report to suggestions if it's not already there
            if (auditReportValue && !auditReportsData.includes(auditReportValue)) {
                auditReportsData.push(auditReportValue);
                auditReportsData.sort();
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'Unknown error occurred'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Error saving record'
        });
    });
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Audit Report Scorecards';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
