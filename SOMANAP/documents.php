<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/DocumentController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('documents');
$documentController = new DocumentController($conn);
$auditLogger = new AuditLogger($conn);

// Handle upload and delete requests - MUST EXIT to prevent HTML output
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        $documentController->uploadDocument();
        exit;
    }
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $documentController->editDocument();
        exit;
    }
}

// Check for delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $documentController->deleteDocument();
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Fetch electric cooperatives from database
$ecStmt = $conn->prepare("SELECT id, name, code FROM electric_cooperatives ORDER BY name ASC");
$ecStmt->execute();
$electricCooperatives = $ecStmt->fetchAll();

// Fetch items for dropdown
$itemsStmt = $conn->prepare("SELECT id, name FROM items ORDER BY name ASC");
$itemsStmt->execute();
$itemsList = $itemsStmt->fetchAll();

// Fetch recommending approvals for dropdown
$recStmt = $conn->prepare("SELECT id, name FROM recommending_approvals ORDER BY name ASC");
$recStmt->execute();
$recommendingApprovals = $recStmt->fetchAll();

// Fetch approving authority for dropdown
$appStmt = $conn->prepare("SELECT id, name FROM approving_authority ORDER BY name ASC");
$appStmt->execute();
$approvingAuthorities = $appStmt->fetchAll();

// Fetch departments for dropdown
$deptStmt = $conn->prepare("SELECT id, name FROM departments ORDER BY name ASC");
$deptStmt->execute();
$departmentsList = $deptStmt->fetchAll();

// Fetch teams for dropdown
$teamStmt = $conn->prepare("SELECT id, name FROM teams ORDER BY name ASC");
$teamStmt->execute();
$teamsList = $teamStmt->fetchAll();

// Get all documents
$allDocuments = $documentController->getAllDocuments();

// Create cooperative color map
$ecColors = [];
$colorPalette = [
    'bg-red-200 dark:bg-red-900/30',
    'bg-blue-200 dark:bg-blue-900/30',
    'bg-green-200 dark:bg-green-900/30',
    'bg-yellow-200 dark:bg-yellow-900/30',
    'bg-purple-200 dark:bg-purple-900/30',
    'bg-pink-200 dark:bg-pink-900/30',
    'bg-indigo-200 dark:bg-indigo-900/30',
    'bg-teal-200 dark:bg-teal-900/30',
    'bg-orange-200 dark:bg-orange-900/30',
    'bg-cyan-200 dark:bg-cyan-900/30',
];

$colorIndex = 0;
foreach ($allDocuments as $doc) {
    $ec = $doc['ec'];
    if (!isset($ecColors[$ec])) {
        $ecColors[$ec] = $colorPalette[$colorIndex % count($colorPalette)];
        $colorIndex++;
    }
}

// Handle search filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterItem = isset($_GET['item']) ? trim($_GET['item']) : '';
$filteredDocuments = $allDocuments;

// Apply filters
if (!empty($searchTerm) || !empty($filterItem)) {
    $filteredDocuments = array_filter($allDocuments, function($doc) use ($searchTerm, $filterItem) {
        // Apply search filter
        if (!empty($searchTerm)) {
            $searchLower = strtolower($searchTerm);
            $searchableFields = [
                $doc['ec'] ?? '',
                $doc['item'] ?? '',
                $doc['file_name'] ?? '',
                $doc['recommending_approvals'] ?? '',
                $doc['approving_authority'] ?? '',
                $doc['control_point'] ?? ''
            ];
            
            $combined = strtolower(implode(' ', $searchableFields));
            if (strpos($combined, $searchLower) === false) {
                return false;
            }
        }
        
        // Apply Item filter (exact match after trim)
        if (!empty($filterItem)) {
            $docItem = isset($doc['item']) ? trim($doc['item']) : '';
            $filterItemTrimmed = trim($filterItem);
            if ($docItem !== $filterItemTrimmed) {
                return false;
            }
        }
        
        return true;
    });
    // Re-index the array to avoid issues with pagination
    $filteredDocuments = array_values($filteredDocuments);
}

// Pagination
$itemsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$totalItems = count($filteredDocuments);
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $totalPages)) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;
$documents = array_slice($filteredDocuments, $offset, $itemsPerPage);

ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Documents</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage and organize your documents</p>
        </div>
        <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="mt-4 md:mt-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Upload Document
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
                    placeholder="Search documents..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <svg class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Item Filter -->
            <div class="min-w-xs">
                <select name="item" onchange="document.getElementById('searchForm').submit()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Items</option>
                    <?php foreach ($itemsList as $item): ?>
                        <option value="<?php echo htmlspecialchars($item['name']); ?>" <?php echo ($filterItem === $item['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Clear Filters Button -->
            <?php if (!empty($searchTerm) || !empty($filterItem)): ?>
            <button type="button" onclick="window.location.href='?'" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                Clear Filters
            </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Show Entries and Documents Table -->
    <div class="mb-4 flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo ($itemsPerPage == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo ($itemsPerPage == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo ($itemsPerPage == 25) ? 'selected' : ''; ?>>25</option>
            <option value="50" <?php echo ($itemsPerPage == 50) ? 'selected' : ''; ?>>50</option>
            <option value="100" <?php echo ($itemsPerPage == 100) ? 'selected' : ''; ?>>100</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
    </div>

    <!-- Documents Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <?php if (empty($allDocuments)): ?>
        <div class="text-center py-12 px-4">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No documents yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Start by uploading your first document</p>
        </div>
        <?php else: ?>
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Item</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Recommending Approvals</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Approving Authority</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Control Point</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Department</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">EC</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Team</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Date</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 transition <?php echo isset($ecColors[$doc['ec']]) ? $ecColors[$doc['ec']] : ''; ?>">
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-medium">
                        <?php echo htmlspecialchars($doc['item']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars($doc['recommending_approvals'] ?: '-'); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars($doc['approving_authority'] ?: '-'); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php 
                        if (!empty($doc['control_point'])) {
                            $points = array_filter(array_map('trim', explode("\n", $doc['control_point'])));
                            if (!empty($points)) {
                                echo '<div class="space-y-1">';
                                foreach ($points as $point) {
                                    echo '<div class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">' . htmlspecialchars($point) . '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '-';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php 
                        if (!empty($doc['department'])) {
                            $depts = array_filter(array_map('trim', explode("\n", $doc['department'])));
                            if (!empty($depts)) {
                                echo '<div class="space-y-1">';
                                foreach ($depts as $dept) {
                                    $deptName = preg_replace('/^\d+\.\s+/', '', $dept);
                                    echo '<div class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">' . htmlspecialchars($deptName) . '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '-';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo htmlspecialchars($doc['ec']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php 
                        if (!empty($doc['team'])) {
                            $teams = array_filter(array_map('trim', explode("\n", $doc['team'])));
                            if (!empty($teams)) {
                                echo '<div class="space-y-1">';
                                foreach ($teams as $team) {
                                    $teamName = preg_replace('/^\d+\.\s+/', '', $team);
                                    echo '<div class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">' . htmlspecialchars($teamName) . '</div>';
                                }
                                echo '</div>';
                            } else {
                                echo '-';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                        <?php echo date('M d, Y', strtotime($doc['created_at'])); ?>
                    </td>
                    <td class="px-6 py-4 text-sm space-x-3 flex items-center justify-center">
                        <?php if (!empty($doc['file_path'])): ?>
                        <?php 
                        $filePath = $doc['file_path'];
                        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                        $isExcel = in_array($fileExt, ['xlsx', 'xls', 'csv']);
                        $fullPath = htmlspecialchars('../' . $filePath);
                        ?>
                        <?php if ($isExcel): ?>
                        <button onclick="previewExcel('<?php echo $fullPath; ?>')" title="Preview file" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <?php else: ?>
                        <a href="<?php echo $fullPath; ?>" target="_blank" title="Preview file" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($doc)); ?>)" title="Edit document" class="inline-flex items-center justify-center w-8 h-8 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="Delete document" class="inline-flex items-center justify-center w-8 h-8 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <?php 
    // Build query string for pagination links
    $paginationQuery = '&limit=' . $itemsPerPage;
    if (!empty($searchTerm)) $paginationQuery .= '&search=' . urlencode($searchTerm);
    if (!empty($filterItem)) $paginationQuery .= '&item=' . urlencode($filterItem);
    ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> documents
        </div>
        <div class="flex gap-2">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            if ($startPage > 1): ?>
            <a href="?page=1<?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>
            <?php if ($startPage > 2): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 rounded-lg transition <?php echo ($i === $currentPage) ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php endif; ?>
            <a href="?page=<?php echo $totalPages; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"><?php echo $totalPages; ?></a>
            <?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

<!-- Excel Preview Modal -->
<div id="excelPreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-6xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Excel Preview</h2>
            <button onclick="document.getElementById('excelPreviewModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Sheet Tabs -->
        <div id="sheetTabs" class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto"></div>
        
        <!-- Sheet Content -->
        <div id="sheetContent" class="overflow-x-auto">
            <table class="border-collapse border border-gray-300 dark:border-gray-600">
                <tbody id="sheetData"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-6xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Upload Document</h2>
            <button id="closeModalBtn" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="uploadForm" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="action" value="upload">
            
            <!-- First Row: EC -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Electric Cooperative *</label>
                <div class="relative">
                    <input type="text" id="ecInput" name="ec" required placeholder="Type to search EC..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                    <div id="ecSuggestions" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                </div>
            </div>

            <!-- Three Column Layout: Items, Recommending Approvals, and Approving Authority -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Items Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Items *</label>
                    <div id="itemsContainer" class="space-y-2 mb-3">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 relative">
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                                <input type="text" name="items[]" placeholder="Type to search item..." class="item-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                                <div class="item-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addItem()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                        + Add Item
                    </button>
                </div>

                <!-- Recommending Approvals Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recommending Approvals</label>
                    <div id="recAppListContainer" class="space-y-2 mb-3">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 relative">
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                                <input type="text" name="recommending_approvals_list[]" placeholder="Type to search approval..." class="rec-app-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                                <div class="rec-app-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addRecApp()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                        + Add Approving Approval
                    </button>
                </div>

                <!-- Approving Authority Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Approving Authority</label>
                    <div id="appAuthListContainer" class="space-y-2 mb-3">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 relative">
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                                <input type="text" name="approving_authority_list[]" placeholder="Type to search authority..." class="app-auth-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                                <div class="app-auth-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addAppAuth()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                        + Add Approving Authority
                    </button>
                </div>
            </div>

            <!-- Full Width Control Points -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Control Points</label>
                <div id="controlPointsContainer" class="space-y-2 mb-3">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="control_points[]" placeholder="Enter control point" class="control-point-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addControlPoint()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                    + Add Control Point
                </button>
            </div>

            <!-- Two Column Layout: Department and Team -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Department Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                    <div id="departmentContainer" class="space-y-2 mb-3">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 relative">
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                                <input type="text" name="departments[]" placeholder="Type to search department..." class="department-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                                <div class="department-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addDepartment()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                        + Add Department
                    </button>
                </div>

                <!-- Team Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Team</label>
                    <div id="teamContainer" class="space-y-2 mb-3">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 relative">
                                <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                                <input type="text" name="teams[]" placeholder="Type to search team..." class="team-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                                <div class="team-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addTeam()" class="text-sm px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                        + Add Team
                    </button>
                </div>
            </div>

            <!-- Full Width File Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload Files</label>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer">
                    <input type="file" name="files[]" multiple class="hidden" id="fileInput">
                    <label for="fileInput" class="cursor-pointer text-center block">
                        <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Click to select files or drag and drop</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PDF, Word, Excel, Image files (Max 50MB per file)</p>
                    </label>
                </div>
                <div id="fileList" class="mt-3 text-sm text-gray-600 dark:text-gray-400"></div>
            </div>

            <div id="uploadMessage" class="hidden p-4 rounded-lg text-sm font-medium"></div>

            <!-- Loading Indicator -->
            <div id="uploadLoading" class="hidden flex items-center justify-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <div class="animate-spin">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <span class="text-blue-700 dark:text-blue-300 font-medium">Uploading documents...</span>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button id="cancelBtn" type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">
                    Cancel
                </button>
                <button id="submitBtn" type="submit" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    Upload Documents
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Document Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-6xl p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Document</h2>
            <button id="closeEditModalBtn" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="editForm" class="space-y-6">
            <input type="hidden" id="editDocId" name="doc_id">
            <input type="hidden" name="action" value="edit">
            
            <!-- EC Selection -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">
                    Electric Cooperative <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="text" id="editEcInput" placeholder="Select EC..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                    <div id="editEcSuggestions" class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                </div>
            </div>

            <!-- Items -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Items <span class="text-red-500">*</span></label>
                <div id="editItemsContainer" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="edit_items[]" placeholder="Type to search item..." class="edit-item-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <div class="edit-item-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                        </div>
                        <button type="button" onclick="this.parentElement.remove(); updateEditItemNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addEditItem()" class="mt-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">+ Add Item</button>
            </div>

            <!-- Recommending Approvals -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Recommending Approvals</label>
                <div id="editRecAppListContainer" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="edit_recommending_approvals_list[]" placeholder="Type to search approval..." class="edit-rec-app-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <div class="edit-rec-app-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                        </div>
                        <button type="button" onclick="this.parentElement.remove(); updateEditRecAppNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addEditRecApp()" class="mt-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">+ Add Approval</button>
            </div>

            <!-- Approving Authority -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Approving Authority</label>
                <div id="editAppAuthListContainer" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="edit_approving_authority_list[]" placeholder="Type to search authority..." class="edit-app-auth-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <div class="edit-app-auth-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                        </div>
                        <button type="button" onclick="this.parentElement.remove(); updateEditAppAuthNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addEditAppAuth()" class="mt-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">+ Add Authority</button>
            </div>

            <!-- Control Points -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Control Points</label>
                <div id="editControlPointsContainer" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="edit_control_points[]" placeholder="Enter control point" class="edit-control-point-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                        </div>
                        <button type="button" onclick="this.parentElement.remove(); updateEditControlPointNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addEditControlPoint()" class="mt-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">+ Add Control Point</button>
            </div>

            <!-- Departments -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Departments</label>
                <div id="editDepartmentContainer" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="edit_departments[]" placeholder="Type to search department..." class="edit-department-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <div class="edit-department-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                        </div>
                        <button type="button" onclick="this.parentElement.remove(); updateEditDepartmentNumbers()" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">✕</button>
                    </div>
                </div>
                <button type="button" onclick="addEditDepartment()" class="mt-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">+ Add Department</button>
            </div>

            <!-- Teams -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Teams</label>
                <div id="editTeamContainer" class="space-y-2">
                    <div class="flex gap-2 items-end">
                        <div class="flex-1 relative">
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">1.</span>
                            <input type="text" name="edit_teams[]" placeholder="Type to search team..." class="edit-team-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
                            <div class="edit-team-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
                        </div>
                        <button type="button" onclick="this.parentElement.remove(); updateEditTeamNumbers()" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">✕</button>
                    </div>
                </div>
                <button type="button" onclick="addEditTeam()" class="mt-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50 transition text-sm font-medium">+ Add Team</button>
            </div>

            <!-- File Upload -->
            <div>
                <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-2">Replace File (Optional)</label>
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer" id="editFileDropZone">
                    <input type="file" name="edit_file" class="hidden" id="editFileInput">
                    <label for="editFileInput" class="cursor-pointer text-center block">
                        <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Click to select a file or drag and drop</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PDF, Word, Excel, Image files (Max 50MB)</p>
                    </label>
                </div>
                <div id="editFileList" class="mt-3 text-sm text-gray-600 dark:text-gray-400"></div>
            </div>

            <!-- Error Message -->
            <div id="editMessage" class="hidden px-4 py-3 rounded-lg"></div>

            <!-- Loading Indicator -->
            <div id="editUploadLoading" class="hidden flex items-center justify-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <div class="animate-spin">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <span class="text-blue-700 dark:text-blue-300 font-medium">Updating document...</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button id="editCancelBtn" type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">
                    Cancel
                </button>
                <button id="editSubmitBtn" type="submit" class="flex-1 px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Autocomplete data
const itemsData = <?php echo json_encode(array_map(function($item) { return $item['name']; }, $itemsList)); ?>;
const recAppData = <?php echo json_encode(array_map(function($rec) { return $rec['name']; }, $recommendingApprovals)); ?>;
const appAuthData = <?php echo json_encode(array_map(function($auth) { return $auth['name']; }, $approvingAuthorities)); ?>;
const departmentsData = <?php echo json_encode(array_map(function($dept) { return $dept['name']; }, $departmentsList)); ?>;
const teamsData = <?php echo json_encode(array_map(function($team) { return $team['name']; }, $teamsList)); ?>;
const ecData = <?php echo json_encode(array_map(function($ec) { return ['name' => $ec['name'], 'code' => $ec['code']]; }, $electricCooperatives)); ?>;

// Autocomplete function
function setupAutocomplete(inputId, suggestionsId, data, displayFn = null) {
    const input = document.getElementById(inputId);
    const suggestionsDiv = document.getElementById(suggestionsId);
    
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        let filtered;
        if (Array.isArray(data) && data[0]?.code) {
            // EC data
            filtered = data.filter(item => 
                item.name.toLowerCase().includes(query) || 
                item.code.toLowerCase().includes(query)
            );
        } else {
            filtered = data.filter(item => item.toLowerCase().includes(query));
        }
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        suggestionsDiv.innerHTML = filtered.map((item, index) => {
            const display = Array.isArray(data) && data[0]?.code 
                ? `${item.name} (${item.code})` 
                : item;
            return `<div class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer" onclick="selectSuggestion('${inputId}', '${display}', '${Array.isArray(data) && data[0]?.code ? item.code : item}')">${display}</div>`;
        }).join('');
        
        suggestionsDiv.classList.remove('hidden');
    });
    
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}

function selectSuggestion(inputId, displayValue, actualValue) {
    const input = document.getElementById(inputId);
    
    // For EC input, extract just the name part (remove code)
    if (inputId === 'ecInput') {
        // displayValue is like "Name (Code)", we need just "Name"
        const match = displayValue.match(/^(.+?)\s*\(/);
        input.value = match ? match[1].trim() : displayValue;
    } else {
        input.value = displayValue;
    }
    
    const suggestionsId = inputId.replace('Input', 'Suggestions');
    const suggestionsDiv = document.getElementById(suggestionsId);
    if (suggestionsDiv) {
        suggestionsDiv.classList.add('hidden');
    }
}

// Setup autocomplete for items inputs
function setupItemAutocomplete(input, data) {
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const suggestionsDiv = this.nextElementSibling;
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        const filtered = data.filter(item => {
            const itemLower = item.toLowerCase().trim();
            // Include if it contains the query and is not an exact match
            return itemLower.includes(query) && itemLower !== query;
        });
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        suggestionsDiv.innerHTML = filtered.map(item => 
            `<div class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer" onclick="selectItemSuggestion(this)">${item}</div>`
        ).join('');
        
        suggestionsDiv.classList.remove('hidden');
    });
    
    document.addEventListener('click', function(e) {
        const suggestionsDiv = input.nextElementSibling;
        if (!input.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}

function selectItemSuggestion(element) {
    const value = element.textContent;
    const input = element.parentElement.previousElementSibling;
    input.value = value;
    element.parentElement.classList.add('hidden');
}

// Setup autocomplete for rec approvals inputs
function setupRecAppAutocomplete(input, data) {
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const suggestionsDiv = this.nextElementSibling;
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        const filtered = data.filter(item => item.toLowerCase().includes(query));
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        suggestionsDiv.innerHTML = filtered.map(item => 
            `<div class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer" onclick="selectRecAppSuggestion(this)">${item}</div>`
        ).join('');
        
        suggestionsDiv.classList.remove('hidden');
    });
    
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !input.nextElementSibling.contains(e.target)) {
            input.nextElementSibling.classList.add('hidden');
        }
    });
}

function selectRecAppSuggestion(element) {
    const value = element.textContent;
    const suggestionsDiv = element.parentElement;
    const input = suggestionsDiv.previousElementSibling;
    input.value = value;
    suggestionsDiv.classList.add('hidden');
}

// Setup autocomplete for app auth inputs
function setupAppAuthAutocomplete(input, data) {
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const suggestionsDiv = this.nextElementSibling;
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        const filtered = data.filter(item => item.toLowerCase().includes(query));
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        suggestionsDiv.innerHTML = filtered.map(item => 
            `<div class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer" onclick="selectAppAuthSuggestion(this)">${item}</div>`
        ).join('');
        
        suggestionsDiv.classList.remove('hidden');
    });
    
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !input.nextElementSibling.contains(e.target)) {
            input.nextElementSibling.classList.add('hidden');
        }
    });
}

function selectAppAuthSuggestion(element) {
    const value = element.textContent;
    const suggestionsDiv = element.parentElement;
    const input = suggestionsDiv.previousElementSibling;
    input.value = value;
    suggestionsDiv.classList.add('hidden');
}

// Initialize EC autocomplete
setupAutocomplete('ecInput', 'ecSuggestions', ecData);
setupItemAutocomplete(document.querySelector('#itemsContainer .item-input'), itemsData);
setupRecAppAutocomplete(document.querySelector('#recAppListContainer .rec-app-input'), recAppData);
setupAppAuthAutocomplete(document.querySelector('#appAuthListContainer .app-auth-input'), appAuthData);
setupDepartmentAutocomplete(document.querySelector('#departmentContainer .department-input'), departmentsData);
setupTeamAutocomplete(document.querySelector('#teamContainer .team-input'), teamsData);

// Handle Item bulk adding
function addItem() {
    const container = document.getElementById('itemsContainer');
    const currentCount = container.querySelectorAll('[name="items[]"]').length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${currentCount}.</span>
            <input type="text" name="items[]" placeholder="Type to search item..." class="item-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
            <div class="item-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateItemNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">
            Remove
        </button>
    `;
    container.appendChild(div);
    setupItemAutocomplete(div.querySelector('.item-input'), itemsData);
}

// Handle Recommending Approvals bulk adding
function addRecApp() {
    const container = document.getElementById('recAppListContainer');
    const currentCount = container.querySelectorAll('[name="recommending_approvals_list[]"]').length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${currentCount}.</span>
            <input type="text" name="recommending_approvals_list[]" placeholder="Type to search approval..." class="rec-app-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
            <div class="rec-app-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateRecAppNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">
            Remove
        </button>
    `;
    container.appendChild(div);
    setupRecAppAutocomplete(div.querySelector('.rec-app-input'), recAppData);
}

// Handle Approving Authority bulk adding
function addAppAuth() {
    const container = document.getElementById('appAuthListContainer');
    const currentCount = container.querySelectorAll('[name="approving_authority_list[]"]').length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${currentCount}.</span>
            <input type="text" name="approving_authority_list[]" placeholder="Type to search authority..." class="app-auth-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
            <div class="app-auth-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateAppAuthNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">
            Remove
        </button>
    `;
    container.appendChild(div);
    setupAppAuthAutocomplete(div.querySelector('.app-auth-input'), appAuthData);
}

// Update item numbering
function updateItemNumbers() {
    const container = document.getElementById('itemsContainer');
    const selects = container.querySelectorAll('[name="items[]"]');
    selects.forEach((select, index) => {
        const span = select.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Update recommending approvals numbering
function updateRecAppNumbers() {
    const container = document.getElementById('recAppListContainer');
    const selects = container.querySelectorAll('[name="recommending_approvals_list[]"]');
    selects.forEach((select, index) => {
        const span = select.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Update approving authority numbering
function updateAppAuthNumbers() {
    const container = document.getElementById('appAuthListContainer');
    const selects = container.querySelectorAll('[name="approving_authority_list[]"]');
    selects.forEach((select, index) => {
        const span = select.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Add department input field
function addDepartment() {
    const container = document.getElementById('departmentContainer');
    const currentCount = container.querySelectorAll('input[name="departments[]"]').length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${currentCount}.</span>
            <input type="text" name="departments[]" placeholder="Type to search department..." class="department-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
            <div class="department-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateDepartmentNumbers()" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
            ✕
        </button>
    `;
    container.appendChild(div);
    setupDepartmentAutocomplete(div.querySelector('.department-input'), departmentsData);
}

// Update numbering when departments are removed
function updateDepartmentNumbers() {
    const container = document.getElementById('departmentContainer');
    const inputs = container.querySelectorAll('input[name="departments[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Add team input field
function addTeam() {
    const container = document.getElementById('teamContainer');
    const currentCount = container.querySelectorAll('input[name="teams[]"]').length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${currentCount}.</span>
            <input type="text" name="teams[]" placeholder="Type to search team..." class="team-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
            <div class="team-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateTeamNumbers()" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">
            ✕
        </button>
    `;
    container.appendChild(div);
    setupTeamAutocomplete(div.querySelector('.team-input'), teamsData);
}

// Update numbering when teams are removed
function updateTeamNumbers() {
    const container = document.getElementById('teamContainer');
    const inputs = container.querySelectorAll('input[name="teams[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Add control point input field
function addControlPoint() {
    const container = document.getElementById('controlPointsContainer');
    const currentCount = container.querySelectorAll('input[name="control_points[]"]').length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${currentCount}.</span>
            <input type="text" name="control_points[]" placeholder="Enter control point" class="control-point-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off">
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateControlPointNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">
            Remove
        </button>
    `;
    container.appendChild(div);
}

// Update numbering when control points are removed
function updateControlPointNumbers() {
    const container = document.getElementById('controlPointsContainer');
    const inputs = container.querySelectorAll('input[name="control_points[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Setup autocomplete for department inputs
function setupDepartmentAutocomplete(input, data) {
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const suggestionsDiv = this.nextElementSibling;
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        const filtered = data.filter(item => item.toLowerCase().includes(query));
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        suggestionsDiv.innerHTML = filtered.map(item => 
            `<div onclick="selectDepartmentSuggestion(this)" class="px-4 py-2 hover:bg-blue-100 dark:hover:bg-blue-900 cursor-pointer text-gray-900 dark:text-white">${item}</div>`
        ).join('');        
        suggestionsDiv.classList.remove('hidden');
    });
}

function selectDepartmentSuggestion(element) {
    const value = element.textContent;
    const suggestionsDiv = element.parentElement;
    const input = suggestionsDiv.previousElementSibling;
    input.value = value;
    suggestionsDiv.classList.add('hidden');
}

// Setup autocomplete for team inputs
function setupTeamAutocomplete(input, data) {
    if (!input) return;
    
    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const suggestionsDiv = this.nextElementSibling;
        
        if (query.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        const filtered = data.filter(item => item.toLowerCase().includes(query));
        
        if (filtered.length === 0) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        suggestionsDiv.innerHTML = filtered.map(item => 
            `<div onclick="selectTeamSuggestion(this)" class="px-4 py-2 hover:bg-blue-100 dark:hover:bg-blue-900 cursor-pointer text-gray-900 dark:text-white">${item}</div>`
        ).join('');        
        suggestionsDiv.classList.remove('hidden');
    });
}

function selectTeamSuggestion(element) {
    const value = element.textContent;
    const suggestionsDiv = element.parentElement;
    const input = suggestionsDiv.previousElementSibling;
    input.value = value;
    suggestionsDiv.classList.add('hidden');
}

// File upload handling
const fileInput = document.getElementById('fileInput');
const fileDropZone = fileInput.parentElement;

fileInput.addEventListener('change', updateFileList);

fileDropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileDropZone.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400', 'dark:border-blue-500');
});

fileDropZone.addEventListener('dragleave', () => {
    fileDropZone.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400', 'dark:border-blue-500');
});

fileDropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    fileDropZone.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400', 'dark:border-blue-500');
    fileInput.files = e.dataTransfer.files;
    updateFileList();
});

function updateFileList() {
    const fileList = document.getElementById('fileList');
    const files = fileInput.files;
    
    if (files.length > 0) {
        let html = '<div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg"><p class="font-medium mb-2 text-gray-900 dark:text-white">Selected files (' + files.length + '):</p><ul class="space-y-1">';
        for (let i = 0; i < files.length; i++) {
            const sizeMB = (files[i].size / 1024 / 1024).toFixed(2);
            html += '<li class="text-sm text-gray-700 dark:text-gray-300">📄 ' + files[i].name + ' <span class="text-gray-500 dark:text-gray-400">(' + sizeMB + ' MB)</span></li>';
        }
        html += '</ul></div>';
        fileList.innerHTML = html;
    } else {
        fileList.innerHTML = '';
    }
}

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Lock the form
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const uploadLoading = document.getElementById('uploadLoading');
    const uploadMessage = document.getElementById('uploadMessage');
    const uploadForm = document.getElementById('uploadForm');
    
    if (fileInput.files.length === 0) {
        alert('Please select at least one file');
        return;
    }
    
    // Show loading indicator and disable buttons
    uploadLoading.classList.remove('hidden');
    submitBtn.disabled = true;
    cancelBtn.disabled = true;
    closeModalBtn.disabled = true;
    uploadForm.style.opacity = '0.6';
    uploadForm.style.pointerEvents = 'none';
    uploadMessage.classList.add('hidden');
    
    // Validate and get Item values
    const itemSelects = document.querySelectorAll('[name="items[]"]');
    const items = [];
    let isValid = true;
    
    itemSelects.forEach((select) => {
        let value = select.value;
        if (value === '__new__') {
            const input = select.parentElement.querySelector('.item-input');
            value = input.value.trim();
            if (!value) {
                isValid = false;
                return;
            }
        }
        if (value) items.push(value);
    });
    
    if (!isValid) {
        alert('Please enter all new item names');
        return;
    }
    if (items.length === 0) {
        alert('Please select or enter at least one item');
        return;
    }

    // Get Recommending Approvals values (preserve index positions, keep blanks as blanks)
    const recAppSelects = document.querySelectorAll('[name="recommending_approvals_list[]"]');
    const recApps = [];
    
    recAppSelects.forEach((select) => {
        let value = select.value;
        if (value === '__new__') {
            const input = select.parentElement.querySelector('.rec-app-input');
            value = input.value.trim();
        }
        recApps.push(value); // Push all values, including blank strings
    });

    // Get Approving Authority values (preserve index positions, keep blanks as blanks)
    const appAuthSelects = document.querySelectorAll('[name="approving_authority_list[]"]');
    const appAuths = [];
    
    appAuthSelects.forEach((select) => {
        let value = select.value;
        if (value === '__new__') {
            const input = select.parentElement.querySelector('.app-auth-input');
            value = input.value.trim();
        }
        appAuths.push(value); // Push all values, including blank strings
    });
    
    const formData = new FormData(document.getElementById('uploadForm'));
    
    // Remove old fields and set new ones
    formData.delete('item');
    formData.delete('recommending_approvals');
    formData.delete('approving_authority');
    formData.delete('items[]');
    formData.delete('recommending_approvals_list[]');
    formData.delete('approving_authority_list[]');
    
    // Add processed values (preserving blank entries to maintain index alignment)
    items.forEach(item => formData.append('items[]', item));
    recApps.forEach(app => formData.append('recommending_approvals_list[]', app));
    appAuths.forEach(auth => formData.append('approving_authority_list[]', auth));
    
    // Combine departments with numbering
    const departmentInputs = document.querySelectorAll('input[name="departments[]"]');
    const departments = Array.from(departmentInputs)
        .map((input, index) => {
            const value = input.value.trim();
            return value ? (index + 1) + '. ' + value : null;
        })
        .filter(dept => dept !== null)
        .join('\n');
    
    if (departments) {
        formData.set('department', departments);
    }

    // Combine teams with numbering
    const teamInputs = document.querySelectorAll('input[name="teams[]"]');
    const teams = Array.from(teamInputs)
        .map((input, index) => {
            const value = input.value.trim();
            return value ? (index + 1) + '. ' + value : null;
        })
        .filter(team => team !== null)
        .join('\n');
    
    if (teams) {
        formData.set('team', teams);
    }
    
    // Combine control points with numbering
    const controlPointInputs = document.querySelectorAll('input[name="control_points[]"]');
    const controlPoints = Array.from(controlPointInputs)
        .map((input, index) => {
            const value = input.value.trim();
            return value ? (index + 1) + '. ' + value : null;
        })
        .filter(cp => cp !== null)
        .join('\n');
    
    formData.set('control_point', controlPoints);
    
    const messageDiv = document.getElementById('uploadMessage');
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            uploadLoading.classList.add('hidden');
            messageDiv.classList.remove('hidden', 'bg-red-50', 'text-red-800', 'dark:bg-red-900/20', 'dark:text-red-400');
            messageDiv.classList.add('bg-green-50', 'text-green-800', 'dark:bg-green-900/20', 'dark:text-green-400');
            messageDiv.textContent = '✓ ' + data.message;
            
            setTimeout(() => {
                document.getElementById('uploadModal').classList.add('hidden');
                location.reload();
            }, 1500);
        } else {
            uploadLoading.classList.add('hidden');
            submitBtn.disabled = false;
            cancelBtn.disabled = false;
            closeModalBtn.disabled = false;
            uploadForm.style.opacity = '1';
            uploadForm.style.pointerEvents = 'auto';
            
            messageDiv.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'dark:bg-green-900/20', 'dark:text-green-400');
            messageDiv.classList.add('bg-red-50', 'text-red-800', 'dark:bg-red-900/20', 'dark:text-red-400');
            messageDiv.textContent = '✗ ' + (data.message || 'Upload failed');
        }
    } catch (error) {
        uploadLoading.classList.add('hidden');
        submitBtn.disabled = false;
        cancelBtn.disabled = false;
        closeModalBtn.disabled = false;
        uploadForm.style.opacity = '1';
        uploadForm.style.pointerEvents = 'auto';
        
        messageDiv.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'dark:bg-green-900/20', 'dark:text-green-400');
        messageDiv.classList.add('bg-red-50', 'text-red-800', 'dark:bg-red-900/20', 'dark:text-red-400');
        messageDiv.textContent = '✗ Error uploading documents: ' + error.message;
    }
});

// Change pagination limit
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const searchTerm = new URLSearchParams(window.location.search).get('search') || '';
    const itemFilter = new URLSearchParams(window.location.search).get('item') || '';
    
    let url = '?page=1&limit=' + limit;
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (itemFilter) url += '&item=' + encodeURIComponent(itemFilter);
    
    window.location.href = url;
}

function deleteDocument(id) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    }).then(response => response.json()).then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete document: ' + data.message);
        }
    });
}

// Open Edit Modal and populate with document data
function openEditModal(doc) {
    const modal = document.getElementById('editModal');
    document.getElementById('editDocId').value = doc.id;
    document.getElementById('editEcInput').value = doc.ec;
    
    // Clear and populate items
    const itemsContainer = document.getElementById('editItemsContainer');
    itemsContainer.innerHTML = '';
    
    if (doc.item) {
        const items = doc.item.split('\n').filter(i => i.trim());
        items.forEach((item, index) => {
            addEditItemToModal(item, index + 1);
        });
    } else {
        addEditItemToModal('', 1);
    }
    
    // Clear and populate recommending approvals
    const recAppContainer = document.getElementById('editRecAppListContainer');
    recAppContainer.innerHTML = '';
    
    if (doc.recommending_approvals) {
        const recApps = doc.recommending_approvals.split('\n');
        recApps.forEach((app, index) => {
            addEditRecAppToModal(app || '', index + 1);
        });
    } else {
        addEditRecAppToModal('', 1);
    }
    
    // Clear and populate approving authority
    const appAuthContainer = document.getElementById('editAppAuthListContainer');
    appAuthContainer.innerHTML = '';
    
    if (doc.approving_authority) {
        const appAuths = doc.approving_authority.split('\n');
        appAuths.forEach((auth, index) => {
            addEditAppAuthToModal(auth || '', index + 1);
        });
    } else {
        addEditAppAuthToModal('', 1);
    }
    
    // Clear and populate control points
    const cpContainer = document.getElementById('editControlPointsContainer');
    cpContainer.innerHTML = '';
    
    if (doc.control_point) {
        const points = doc.control_point.split('\n').filter(p => p.trim());
        points.forEach((point, index) => {
            addEditControlPointToModal(point, index + 1);
        });
    } else {
        addEditControlPointToModal('', 1);
    }
    
    // Clear and populate departments
    const deptContainer = document.getElementById('editDepartmentContainer');
    deptContainer.innerHTML = '';
    
    if (doc.department) {
        const depts = doc.department.split('\n').filter(d => d.trim());
        depts.forEach((dept, index) => {
            addEditDepartmentToModal(dept, index + 1);
        });
    } else {
        addEditDepartmentToModal('', 1);
    }
    
    // Clear and populate teams
    const teamContainer = document.getElementById('editTeamContainer');
    teamContainer.innerHTML = '';
    
    if (doc.team) {
        const teams = doc.team.split('\n').filter(t => t.trim());
        teams.forEach((team, index) => {
            addEditTeamToModal(team, index + 1);
        });
    } else {
        addEditTeamToModal('', 1);
    }
    
    // Setup autocomplete for all fields
    setTimeout(() => {
        setupAutocomplete('editEcInput', 'editEcSuggestions', ecData);
        document.querySelectorAll('.edit-item-input').forEach(input => {
            setupItemAutocomplete(input, itemsData);
        });
        document.querySelectorAll('.edit-rec-app-input').forEach(input => {
            setupRecAppAutocomplete(input, recAppData);
        });
        document.querySelectorAll('.edit-app-auth-input').forEach(input => {
            setupAppAuthAutocomplete(input, appAuthData);
        });
        document.querySelectorAll('.edit-department-input').forEach(input => {
            setupDepartmentAutocomplete(input, departmentsData);
        });
        document.querySelectorAll('.edit-team-input').forEach(input => {
            setupTeamAutocomplete(input, teamsData);
        });
    }, 100);
    
    // Setup file upload handling for edit form
    setupEditFileUpload();
    
    modal.classList.remove('hidden');
}

// Setup file handling for edit form
function setupEditFileUpload() {
    const editFileInput = document.getElementById('editFileInput');
    const editFileDropZone = editFileInput.parentElement;
    const editFileList = document.getElementById('editFileList');
    
    editFileInput.addEventListener('change', updateEditFileList);
    
    editFileDropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        editFileDropZone.classList.add('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400', 'dark:border-blue-500');
    });
    
    editFileDropZone.addEventListener('dragleave', () => {
        editFileDropZone.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400', 'dark:border-blue-500');
    });
    
    editFileDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        editFileDropZone.classList.remove('bg-blue-100', 'dark:bg-blue-900/30', 'border-blue-400', 'dark:border-blue-500');
        editFileInput.files = e.dataTransfer.files;
        updateEditFileList();
    });
}

function updateEditFileList() {
    const editFileList = document.getElementById('editFileList');
    const editFileInput = document.getElementById('editFileInput');
    const files = editFileInput.files;
    
    if (files.length > 0) {
        let html = '<div class="space-y-2">';
        for (let file of files) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            html += `
                <div class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${file.name}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">${fileSize} MB</span>
                </div>
            `;
        }
        html += '</div>';
        editFileList.innerHTML = html;
    } else {
        editFileList.innerHTML = '';
    }
}

function addEditItemToModal(value = '', number) {
    const container = document.getElementById('editItemsContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${number}.</span>
            <input type="text" name="edit_items[]" placeholder="Type to search item..." class="edit-item-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" value="${value}">
            <div class="edit-item-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateEditItemNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
    `;
    container.appendChild(div);
    setupItemAutocomplete(div.querySelector('.edit-item-input'), itemsData);
}

function addEditRecAppToModal(value = '', number) {
    const container = document.getElementById('editRecAppListContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${number}.</span>
            <input type="text" name="edit_recommending_approvals_list[]" placeholder="Type to search approval..." class="edit-rec-app-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" value="${value}">
            <div class="edit-rec-app-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateEditRecAppNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
    `;
    container.appendChild(div);
    setupRecAppAutocomplete(div.querySelector('.edit-rec-app-input'), recAppData);
}

function addEditAppAuthToModal(value = '', number) {
    const container = document.getElementById('editAppAuthListContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${number}.</span>
            <input type="text" name="edit_approving_authority_list[]" placeholder="Type to search authority..." class="edit-app-auth-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" value="${value}">
            <div class="edit-app-auth-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateEditAppAuthNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
    `;
    container.appendChild(div);
    setupAppAuthAutocomplete(div.querySelector('.edit-app-auth-input'), appAuthData);
}

function addEditControlPointToModal(value = '', number) {
    const container = document.getElementById('editControlPointsContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${number}.</span>
            <input type="text" name="edit_control_points[]" placeholder="Enter control point" class="edit-control-point-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" value="${value}">
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateEditControlPointNumbers();" class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">Remove</button>
    `;
    container.appendChild(div);
}

function addEditDepartmentToModal(value = '', number) {
    const container = document.getElementById('editDepartmentContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${number}.</span>
            <input type="text" name="edit_departments[]" placeholder="Type to search department..." class="edit-department-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" value="${value}">
            <div class="edit-department-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateEditDepartmentNumbers()" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">✕</button>
    `;
    container.appendChild(div);
    setupDepartmentAutocomplete(div.querySelector('.edit-department-input'), departmentsData);
}

function addEditTeamToModal(value = '', number) {
    const container = document.getElementById('editTeamContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 items-end';
    div.innerHTML = `
        <div class="flex-1 relative">
            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">${number}.</span>
            <input type="text" name="edit_teams[]" placeholder="Type to search team..." class="edit-team-input w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" value="${value}">
            <div class="edit-team-suggestions absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden z-10"></div>
        </div>
        <button type="button" onclick="this.parentElement.remove(); updateEditTeamNumbers()" class="px-3 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition">✕</button>
    `;
    container.appendChild(div);
    setupTeamAutocomplete(div.querySelector('.edit-team-input'), teamsData);
}

function addEditItem() {
    const container = document.getElementById('editItemsContainer');
    const currentCount = container.querySelectorAll('input[name="edit_items[]"]').length + 1;
    addEditItemToModal('', currentCount);
}

function addEditRecApp() {
    const container = document.getElementById('editRecAppListContainer');
    const currentCount = container.querySelectorAll('input[name="edit_recommending_approvals_list[]"]').length + 1;
    addEditRecAppToModal('', currentCount);
}

function addEditAppAuth() {
    const container = document.getElementById('editAppAuthListContainer');
    const currentCount = container.querySelectorAll('input[name="edit_approving_authority_list[]"]').length + 1;
    addEditAppAuthToModal('', currentCount);
}

function addEditControlPoint() {
    const container = document.getElementById('editControlPointsContainer');
    const currentCount = container.querySelectorAll('input[name="edit_control_points[]"]').length + 1;
    addEditControlPointToModal('', currentCount);
}

function addEditDepartment() {
    const container = document.getElementById('editDepartmentContainer');
    const currentCount = container.querySelectorAll('input[name="edit_departments[]"]').length + 1;
    addEditDepartmentToModal('', currentCount);
}

function addEditTeam() {
    const container = document.getElementById('editTeamContainer');
    const currentCount = container.querySelectorAll('input[name="edit_teams[]"]').length + 1;
    addEditTeamToModal('', currentCount);
}

function updateEditItemNumbers() {
    const container = document.getElementById('editItemsContainer');
    const inputs = container.querySelectorAll('input[name="edit_items[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

function updateEditRecAppNumbers() {
    const container = document.getElementById('editRecAppListContainer');
    const inputs = container.querySelectorAll('input[name="edit_recommending_approvals_list[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

function updateEditAppAuthNumbers() {
    const container = document.getElementById('editAppAuthListContainer');
    const inputs = container.querySelectorAll('input[name="edit_approving_authority_list[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

function updateEditControlPointNumbers() {
    const container = document.getElementById('editControlPointsContainer');
    const inputs = container.querySelectorAll('input[name="edit_control_points[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

function updateEditDepartmentNumbers() {
    const container = document.getElementById('editDepartmentContainer');
    const inputs = container.querySelectorAll('input[name="edit_departments[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

function updateEditTeamNumbers() {
    const container = document.getElementById('editTeamContainer');
    const inputs = container.querySelectorAll('input[name="edit_teams[]"]');
    inputs.forEach((input, index) => {
        const span = input.parentElement.querySelector('span');
        span.textContent = (index + 1) + '.';
    });
}

// Handle edit form submission
document.getElementById('editForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const docId = document.getElementById('editDocId').value;
    const submitBtn = document.getElementById('editSubmitBtn');
    const cancelBtn = document.getElementById('editCancelBtn');
    const closeBtn = document.getElementById('closeEditModalBtn');
    const messageDiv = document.getElementById('editMessage');
    
    // Validate EC
    const ec = document.getElementById('editEcInput').value.trim();
    if (!ec) {
        messageDiv.textContent = 'Please select an Electric Cooperative';
        messageDiv.className = 'px-4 py-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
        messageDiv.classList.remove('hidden');
        return;
    }
    
    // Validate items
    const itemInputs = document.querySelectorAll('input[name="edit_items[]"]');
    const items = [];
    let isValid = true;
    
    itemInputs.forEach((input) => {
        const value = input.value.trim();
        if (!value) {
            messageDiv.textContent = 'All items must have values';
            messageDiv.className = 'px-4 py-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
            messageDiv.classList.remove('hidden');
            isValid = false;
        }
        if (value) items.push(value);
    });
    
    if (!isValid || items.length === 0) return;
    
    // Get other field values
    const recAppInputs = document.querySelectorAll('input[name="edit_recommending_approvals_list[]"]');
    const recApps = [];
    recAppInputs.forEach((input) => {
        recApps.push(input.value.trim());
    });
    
    const appAuthInputs = document.querySelectorAll('input[name="edit_approving_authority_list[]"]');
    const appAuths = [];
    appAuthInputs.forEach((input) => {
        appAuths.push(input.value.trim());
    });
    
    const cpInputs = document.querySelectorAll('input[name="edit_control_points[]"]');
    const controlPoints = Array.from(cpInputs)
        .map((input, index) => {
            const value = input.value.trim();
            return value ? (index + 1) + '. ' + value : null;
        })
        .filter(cp => cp !== null)
        .join('\n');
    
    const deptInputs = document.querySelectorAll('input[name="edit_departments[]"]');
    const departments = Array.from(deptInputs)
        .map((input, index) => {
            const value = input.value.trim();
            return value ? (index + 1) + '. ' + value : null;
        })
        .filter(dept => dept !== null)
        .join('\n');
    
    const teamInputs = document.querySelectorAll('input[name="edit_teams[]"]');
    const teams = Array.from(teamInputs)
        .map((input, index) => {
            const value = input.value.trim();
            return value ? (index + 1) + '. ' + value : null;
        })
        .filter(team => team !== null)
        .join('\n');
    
    submitBtn.disabled = true;
    cancelBtn.disabled = true;
    closeBtn.disabled = true;
    
    const editUploadLoading = document.getElementById('editUploadLoading');
    const editFileInput = document.getElementById('editFileInput');
    editUploadLoading.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('action', 'edit');
        formData.append('doc_id', docId);
        formData.append('ec', ec);
        formData.append('items', items.join('\n'));
        formData.append('recommending_approvals', recApps.join('\n'));
        formData.append('approving_authority', appAuths.join('\n'));
        formData.append('control_point', controlPoints);
        formData.append('department', departments);
        formData.append('team', teams);
        
        // Add file if selected
        if (editFileInput.files.length > 0) {
            formData.append('edit_file', editFileInput.files[0]);
        }
        
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.textContent = 'Document updated successfully!';
            messageDiv.className = 'px-4 py-3 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
            messageDiv.classList.remove('hidden');
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = 'Error: ' + data.message;
            messageDiv.className = 'px-4 py-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
            messageDiv.classList.remove('hidden');
        }
    } catch (error) {
        messageDiv.textContent = 'Error updating document';
        messageDiv.className = 'px-4 py-3 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
        messageDiv.classList.remove('hidden');
    } finally {
        editUploadLoading.classList.add('hidden');
        submitBtn.disabled = false;
        cancelBtn.disabled = false;
        closeBtn.disabled = false;
    }
});

// Excel Preview Function
function previewExcel(filePath) {
    // Wait for XLSX library to load with retry mechanism
    if (typeof XLSX === 'undefined') {
        setTimeout(() => previewExcel(filePath), 500); // Retry after 500ms
        return;
    }
    
    // Show loading modal
    const modal = document.getElementById('excelPreviewModal');
    const sheetData = document.getElementById('sheetData');
    sheetData.innerHTML = '<tr><td colspan="10" class="px-4 py-8 text-center text-gray-600">Loading...</td></tr>';
    modal.classList.remove('hidden');
    
    console.log('Loading file:', filePath);
    
    fetch(filePath)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.arrayBuffer();
        })
        .then(data => {
            console.log('File loaded, size:', data.byteLength);
            try {
                const workbook = XLSX.read(data, { type: 'array' });
                console.log('Workbook sheets:', workbook.SheetNames);
                const firstSheet = workbook.SheetNames[0];
                
                // Create sheet tabs
                const sheetTabs = document.getElementById('sheetTabs');
                sheetTabs.innerHTML = '';
                
                workbook.SheetNames.forEach((sheetName, index) => {
                    const tab = document.createElement('button');
                    tab.textContent = sheetName;
                    tab.className = `px-4 py-2 border-b-2 transition font-medium ${index === 0 ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300'}`;
                    tab.type = 'button';
                    tab.onclick = () => displaySheet(workbook, sheetName, index);
                    sheetTabs.appendChild(tab);
                });
                
                // Display first sheet
                displaySheet(workbook, firstSheet, 0);
            } catch (error) {
                console.error('Error parsing Excel:', error);
                sheetData.innerHTML = `<tr><td colspan="10" class="px-4 py-4 text-center text-red-600">Error: ${error.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error loading Excel file:', error);
            sheetData.innerHTML = `<tr><td colspan="10" class="px-4 py-4 text-center text-red-600">Error: ${error.message || 'Failed to load file'}</td></tr>`;
            alert('Error loading Excel file: ' + error.message);
        });
}

function displaySheet(workbook, sheetName, tabIndex) {
    const worksheet = workbook.Sheets[sheetName];
    const data = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
    
    const sheetData = document.getElementById('sheetData');
    sheetData.innerHTML = '';
    
    // Update active tab styling
    const tabs = document.querySelectorAll('#sheetTabs button');
    tabs.forEach((tab, index) => {
        if (index === tabIndex) {
            tab.classList.remove('border-transparent', 'text-gray-600', 'dark:text-gray-400');
            tab.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        } else {
            tab.classList.add('border-transparent', 'text-gray-600', 'dark:text-gray-400');
            tab.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        }
    });
    
    // Render data
    data.forEach((row, rowIndex) => {
        const tr = document.createElement('tr');
        tr.className = rowIndex === 0 ? 'bg-gray-100 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50';
        
        row.forEach((cell) => {
            const td = document.createElement('td');
            td.className = 'border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm text-gray-900 dark:text-gray-100';
            td.textContent = cell !== null && cell !== undefined ? cell : '';
            if (rowIndex === 0) {
                const th = document.createElement('th');
                th.className = 'border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700';
                th.textContent = cell !== null && cell !== undefined ? cell : '';
                tr.appendChild(th);
            } else {
                tr.appendChild(td);
            }
        });
        
        sheetData.appendChild(tr);
    });
}

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchForm.submit();
        }, 1000);
    });
}

function deleteDocument(id) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    }).then(response => response.json()).then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to delete document: ' + data.message);
        }
    });
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Documents';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
