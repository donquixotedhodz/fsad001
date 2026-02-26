<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/AomController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('aom'); // Set current page for sidebar highlight
$aomController = new AomController($conn);
$auditLogger = new AuditLogger($conn);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $aomController->addAOM();
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $aomController->updateAOM();
        exit;
    }
}

// Check for delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $aomController->deleteAOM();
    exit;
}

// Check for JSON fetch request (editing)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] === 'get') {
    $aomController->getAOMJSON();
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Get all AOM records
$allAOM = $aomController->getAllAOM();

// Fetch departments for dropdown
$deptStmt = $conn->prepare("SELECT id, name FROM neadept_table ORDER BY name ASC");
$deptStmt->execute();
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search and filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredAOM = $allAOM;

// Apply filters
if (!empty($searchTerm)) {
    $filteredAOM = array_filter($allAOM, function ($record) use ($searchTerm) {
        $searchLower = strtolower($searchTerm);
        $searchableFields = [
            $record['item'] ?? '',
            $record['title'] ?? '',
            $record['department_name'] ?? '',
            $record['coa_observation'] ?? '',
            $record['coa_recommendation'] ?? '',
            $record['comments_justification'] ?? '',
        ];

        $combined = strtolower(implode(' ', $searchableFields));
        return strpos($combined, $searchLower) !== false;
    });
    $filteredAOM = array_values($filteredAOM);
}

// Pagination
$itemsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$totalItems = count($filteredAOM);
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;
$records = array_slice($filteredAOM, $offset, $itemsPerPage);

// Build pagination query
$paginationQuery = '';
if (!empty($searchTerm)) {
    $paginationQuery .= '&search=' . urlencode($searchTerm);
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
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Audit Observation Memorandum</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage and track audit observations and recommendations</p>
        </div>
        <button onclick="document.getElementById('addAOMModal').classList.remove('hidden'); resetAOMForm();" class="mt-4 md:mt-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add AOM
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
                    placeholder="Search by title, department, observation..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <svg class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Clear Filters Button -->
            <?php if (!empty($searchTerm)): ?>
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
        <?php if (empty($allAOM)): ?>
        <div class="text-center py-12 px-4">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No AOMs yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Start by adding your first audit observation memorandum</p>
        </div>
        <?php
else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th style="width: 10%; text-align: center;" class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">Item</th>
                        <th style="width: 7%; text-align: center;" class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">Date</th>
                        <th style="width: 5%; text-align: center;" class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">Department</th>
                        <th style="width: 15%;" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Title</th>
                        <th style="width: 20%;" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">COA Observation</th>
                        <th style="width: 20%;" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">COA Recommendation</th>
                        <th style="width: 20%;" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Comments/Justification</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $idx => $record): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition align-top">
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-medium text-center">
                            <?php echo htmlspecialchars($record['item'] ?? ''); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 text-center">
                            <?php echo $record['date'] ? date('M/d/Y', strtotime($record['date'])) : ''; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 text-center">
                            <?php echo htmlspecialchars($record['department_acronym'] ?: ($record['department_name'] ?? '')); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 align-top">
                            <div class="content-wrapper" id="title-<?php echo $record['id']; ?>">
                                <?php
        $content = $record['title'];
        if (strlen($content) > 50) {
            echo '<div class="short-content">' . htmlspecialchars(substr($content, 0, 50)) . '...</div>';
            echo '<div class="full-content hidden whitespace-pre-wrap">' . htmlspecialchars($content) . '</div>';
            echo '<button onclick="toggleContent(\'title-' . $record['id'] . '\')" class="text-blue-600 hover:text-blue-800 text-xs mt-1 font-medium focus:outline-none">See More</button>';
        }
        else {
            echo '<div class="whitespace-pre-wrap">' . htmlspecialchars($content) . '</div>';
        }
?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 align-top">
                            <div class="content-wrapper" id="obs-<?php echo $record['id']; ?>">
                                <?php
        $content = $record['coa_observation'] ?? '';
        if (strlen($content) > 100) {
            echo '<div class="short-content">' . htmlspecialchars(substr($content, 0, 100)) . '...</div>';
            echo '<div class="full-content hidden whitespace-pre-wrap">' . htmlspecialchars($content) . '</div>';
            echo '<button onclick="toggleContent(\'obs-' . $record['id'] . '\')" class="text-blue-600 hover:text-blue-800 text-xs mt-1 font-medium focus:outline-none">See More</button>';
        }
        else {
            echo '<div class="whitespace-pre-wrap">' . htmlspecialchars($content) . '</div>';
        }
?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 align-top">
                            <div class="content-wrapper" id="rec-<?php echo $record['id']; ?>">
                                <?php
        $recs = [];
        $justs = [];
        try {
            $recs = json_decode($record['coa_recommendation'], true);
            if (!is_array($recs))
                $recs = $record['coa_recommendation'] ? [$record['coa_recommendation']] : [];
        }
        catch (Exception $e) {
            $recs = [];
        }

        try {
            $justs = json_decode($record['comments_justification'], true);
            if (!is_array($justs))
                $justs = $record['comments_justification'] ? [$record['comments_justification']] : [];
        }
        catch (Exception $e) {
            $justs = [];
        }

        $maxRows = max(count($recs), count($justs));

        $fullItems = [];
        $visibleItems = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $rec = isset($recs[$i]) ? $recs[$i] : '';
            $just = isset($justs[$i]) ? $justs[$i] : '';

            if ($rec === '' && $just !== '') {
                $itemHtml = '<span style="opacity:0; user-select:none; pointer-events:none;">' . htmlspecialchars($just) . '</span>';
            }
            else {
                $itemHtml = htmlspecialchars($rec);
            }
            $fullItems[] = $itemHtml;
            $visibleItems[] = htmlspecialchars($rec);
        }
        $content = implode("\n\n", $fullItems);
        $visibleContent = implode("\n\n", $visibleItems);

        if (strlen(strip_tags($visibleContent)) > 100) {
            echo '<div class="short-content">' . substr(strip_tags($visibleContent), 0, 100) . '...</div>';
            echo '<div class="full-content hidden whitespace-pre-wrap">' . $content . '</div>';
            echo '<button onclick="toggleContent(\'rec-' . $record['id'] . '\')" class="text-blue-600 hover:text-blue-800 text-xs mt-1 font-medium focus:outline-none">See More</button>';
        }
        else {
            echo '<div class="whitespace-pre-wrap">' . $content . '</div>';
        }
?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 align-top">
                            <div class="content-wrapper" id="just-<?php echo $record['id']; ?>">
                                <?php
        $fullItems = [];
        $visibleItems = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $rec = isset($recs[$i]) ? $recs[$i] : '';
            $just = isset($justs[$i]) ? $justs[$i] : '';

            if ($just === '' && $rec !== '') {
                $itemHtml = '<span style="opacity:0; user-select:none; pointer-events:none;">' . htmlspecialchars($rec) . '</span>';
            }
            else {
                $itemHtml = htmlspecialchars($just);
            }
            $fullItems[] = $itemHtml;
            $visibleItems[] = htmlspecialchars($just);
        }
        $content = implode("\n\n", $fullItems);
        $visibleContent = implode("\n\n", $visibleItems);

        if (strlen(strip_tags($visibleContent)) > 100) {
            echo '<div class="short-content">' . substr(strip_tags($visibleContent), 0, 100) . '...</div>';
            echo '<div class="full-content hidden whitespace-pre-wrap">' . $content . '</div>';
            echo '<button onclick="toggleContent(\'just-' . $record['id'] . '\')" class="text-blue-600 hover:text-blue-800 text-xs mt-1 font-medium focus:outline-none">See More</button>';
        }
        else {
            echo '<div class="whitespace-pre-wrap">' . $content . '</div>';
        }
?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <!-- View Button -->
                                <button onclick="viewAOM(<?php echo $record['id']; ?>)" title="View details" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-primary);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Edit Button -->
                                <button onclick="editAOM(<?php echo $record['id']; ?>)" title="Edit AOM" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: #eab308;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                
                                <!-- Delete Button -->
                                <button onclick="deleteAOM(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars(addslashes($record['title'])); ?>')" title="Delete AOM" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-danger);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php
    endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
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
        endif;
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

<!-- Add/Edit AOM Modal -->
<div id="addAOMModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-6xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 id="modalTitle" class="text-3xl font-bold text-gray-900 dark:text-white">Add AOM</h2>
            <button onclick="closeAOMModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="aomForm" class="space-y-6">
            <input type="hidden" name="action" value="add">
            <input type="hidden" id="aomId" name="id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Item -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item Ref</label>
                    <input type="text" id="item" name="item" placeholder="e.g., AOM-2024-001" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                    <input type="date" id="date" name="date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
            </div>

            <!-- Department -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <div id="bulkDepartmentWrapper">
                    <div id="bulkDepartmentContainer" class="space-y-3"></div>
                    <button type="button" onclick="addBulkDepartmentRow()" class="mt-3 text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                        + Add Department
                    </button>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Use + Add Department to assign multiple departments to one AOM.</p>
                </div>
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title *</label>
                <input type="text" id="title" name="title" required placeholder="Enter AOM title" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- COA Observation -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">COA Observation</label>
                <textarea id="coaObservation" name="coa_observation" rows="3" placeholder="Enter COA observation..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>
            
            <!-- Recommendations and Management Action Plan -->
            <div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recommendations & Action Plan</label>
                </div>
                <div id="actionPlanContainer" class="space-y-4 mb-4">
                    <!-- Dynamic rows will be added here via JS -->
                </div>
                <button type="button" onclick="addRefJustRow()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                    + Add Item
                </button>
            </div>

            <!-- Form Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-300 dark:border-gray-600">
                <button type="submit" id="submitBtn" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    Add AOM
                </button>
                <button type="button" onclick="closeAOMModal()" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">
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
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">AOM Details</h2>
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
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('limit', limit);
    searchParams.set('page', '1');
    window.location.href = '?' + searchParams.toString();
}

// Template for a new row
function getRowTemplate(rec = '', just = '') {
    return `
        <div class="p-4 rounded-lg relative group">
            <button type="button" onclick="this.closest('.group').remove()" class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">COA Recommendation</label>
                    <textarea name="coa_recommendation[]" rows="3" placeholder="Recommendation..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">${escapeHtml(rec)}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Comments/Justification</label>
                    <textarea name="comments_justification[]" rows="3" placeholder="Justification..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">${escapeHtml(just)}</textarea>
                </div>
            </div>
        </div>
    `;
}

function addRefJustRow(rec = '', just = '') {
    const container = document.getElementById('actionPlanContainer');
    const div = document.createElement('div');
    div.innerHTML = getRowTemplate(rec, just);
    container.appendChild(div.firstElementChild);
}

function getBulkDepartmentTemplate(selectedValue = '') {
    return `
        <div class="flex items-center gap-2 bulk-dept-row">
            <select name="department_ids[]" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>" ${selectedValue == '<?php echo $dept['id']; ?>' ? 'selected' : ''}><?php echo htmlspecialchars($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="removeBulkDepartmentRow(this)" class="px-3 py-2 text-gray-400 hover:text-red-500 transition" title="Remove department">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
}

function addBulkDepartmentRow(selectedValue = '') {
    const container = document.getElementById('bulkDepartmentContainer');
    const div = document.createElement('div');
    div.innerHTML = getBulkDepartmentTemplate(selectedValue);
    const row = div.firstElementChild;
    container.appendChild(row);

    const select = row.querySelector('select[name="department_ids[]"]');
    if (selectedValue) {
        select.value = selectedValue;
    }
}

function removeBulkDepartmentRow(button) {
    const container = document.getElementById('bulkDepartmentContainer');
    const rows = container.querySelectorAll('.bulk-dept-row');
    if (rows.length <= 1) {
        const select = rows[0].querySelector('select[name="department_ids[]"]');
        select.value = '';
        return;
    }
    button.closest('.bulk-dept-row').remove();
}

function resetAOMForm() {
    document.getElementById('aomForm').reset();
    document.getElementById('aomId').value = '';
    document.getElementById('submitBtn').textContent = 'Add AOM';
    document.getElementById('modalTitle').textContent = 'Add AOM';
    document.getElementById('aomForm').elements['action'].value = 'add';
    const bulkDepartmentContainer = document.getElementById('bulkDepartmentContainer');
    bulkDepartmentContainer.innerHTML = '';
    addBulkDepartmentRow();
    
    // Clear and add one empty row
    document.getElementById('actionPlanContainer').innerHTML = '';
    addRefJustRow();
}

function closeAOMModal() {
    document.getElementById('addAOMModal').classList.add('hidden');
    resetAOMForm();
}

function toggleContent(id) {
    const container = document.getElementById(id);
    const shortContent = container.querySelector('.short-content');
    const fullContent = container.querySelector('.full-content');
    const button = container.querySelector('button');
    
    if (fullContent.classList.contains('hidden')) {
        shortContent.classList.add('hidden');
        fullContent.classList.remove('hidden');
        button.textContent = 'See Less';
    } else {
        shortContent.classList.remove('hidden');
        fullContent.classList.add('hidden');
        button.textContent = 'See More';
    }
}

function editAOM(id) {
    fetch('?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const record = data.data;
                document.getElementById('aomId').value = record.id;
                document.getElementById('item').value = record.item || '';
                document.getElementById('date').value = record.date || '';
                document.getElementById('title').value = record.title || '';
                document.getElementById('coaObservation').value = record.coa_observation || '';

                const bulkDeptContainer = document.getElementById('bulkDepartmentContainer');
                bulkDeptContainer.innerHTML = '';
                const departmentIds = Array.isArray(record.department_ids) ? record.department_ids : [];
                if (departmentIds.length > 0) {
                    departmentIds.forEach(deptId => addBulkDepartmentRow(String(deptId)));
                } else if (record.department_id) {
                    addBulkDepartmentRow(String(record.department_id));
                } else {
                    addBulkDepartmentRow();
                }
                
                // Handle dynamic rows
                const container = document.getElementById('actionPlanContainer');
                container.innerHTML = '';
                
                let recs = [];
                let justs = [];
                
                // Try to parse as JSON, fallback to treating as single string
                try {
                    recs = JSON.parse(record.coa_recommendation);
                    if (!Array.isArray(recs)) recs = [record.coa_recommendation];
                } catch(e) {
                    recs = [record.coa_recommendation];
                }
                
                try {
                    justs = JSON.parse(record.comments_justification);
                    if (!Array.isArray(justs)) justs = [record.comments_justification];
                } catch(e) {
                    justs = [record.comments_justification];
                }
                
                const maxLen = Math.max(recs.length, justs.length);
                if (maxLen > 0 && (recs[0] || justs[0])) {
                    for (let i = 0; i < maxLen; i++) {
                        addRefJustRow(recs[i] || '', justs[i] || '');
                    }
                } else {
                    addRefJustRow();
                }
                
                document.getElementById('submitBtn').textContent = 'Update AOM';
                document.getElementById('modalTitle').textContent = 'Edit AOM';
                document.getElementById('aomForm').elements['action'].value = 'edit';
                
                document.getElementById('addAOMModal').classList.remove('hidden');
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

function viewAOM(id) {
    fetch('?action=get&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const record = data.data;
                let deptName = record.department_name || '';

                // Parse action plan items
                let recs = [];
                let justs = [];
                try {
                    recs = JSON.parse(record.coa_recommendation);
                    if (!Array.isArray(recs)) recs = [record.coa_recommendation];
                } catch(e) { recs = [record.coa_recommendation]; }
                
                try {
                    justs = JSON.parse(record.comments_justification);
                    if (!Array.isArray(justs)) justs = [record.comments_justification];
                } catch(e) { justs = [record.comments_justification]; }

                let actionPlanHtml = '';
                const maxLen = Math.max(recs.length, justs.length);
                
                if (maxLen > 0 && (recs[0] || justs[0])) {
                    actionPlanHtml = '<div class="space-y-4">';
                    for (let i = 0; i < maxLen; i++) {
                        actionPlanHtml += `
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-100 dark:border-gray-700 relative">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Recommendation</p>
                                        <div class="text-gray-900 dark:text-white">${escapeHtml(recs[i] || '')}</div>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Justification</p>
                                        <div class="text-gray-900 dark:text-white">${escapeHtml(justs[i] || '')}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    actionPlanHtml += '</div>';
                } else {
                    actionPlanHtml = '<p class="text-gray-500 italic">No action plan items recorded.</p>';
                }

                let html = `
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Item Ref</p>
                                <p class="text-gray-900 dark:text-white font-semibold">${escapeHtml(record.item || '')}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Date</p>
                                <p class="text-gray-900 dark:text-white">${record.date ? (() => {
                                    const d = new Date(record.date);
                                    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                    return `${months[d.getMonth()]}/${String(d.getDate()).padStart(2, '0')}/${d.getFullYear()}`;
                                })() : ''}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Department</p>
                            <p class="text-gray-900 dark:text-white">${escapeHtml(deptName)}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Title</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">${escapeHtml(record.title)}</p>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">COA Observation</h3>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 text-gray-900 dark:text-white whitespace-pre-wrap">${escapeHtml(record.coa_observation || '')}</div>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Recommendations & Action Plan</h3>
                            ${actionPlanHtml}
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

function deleteAOM(id, title) {
    Swal.fire({
        title: 'Delete AOM',
        html: `
            <div class="text-left">
                <p class="mb-4 text-gray-700 dark:text-gray-300"><strong>Are you sure you want to delete this AOM?</strong></p>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-sm">
                    <div><span class="font-semibold text-gray-800 dark:text-gray-200">Title:</span> <span class="text-gray-600 dark:text-gray-400">${title}</span></div>
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle form submission
document.getElementById('aomForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('?', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
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

addBulkDepartmentRow();
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Audit Observation Memorandum';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
