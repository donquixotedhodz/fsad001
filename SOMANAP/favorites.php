<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/FavoritesController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('favorites');
$favoritesController = new FavoritesController($conn);

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $favoritesController->removeFavorite();
    exit;
}

$username = $_SESSION['username'] ?? 'User';

// Fetch electric cooperatives from database
$ecStmt = $conn->prepare("SELECT id, name, code FROM electric_cooperatives ORDER BY name ASC");
$ecStmt->execute();
$electricCooperatives = $ecStmt->fetchAll();

// Get favorited documents
$allDocuments = $favoritesController->getFavoritedDocuments();

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
        $matchSearch = empty($searchTerm) || 
            stripos($doc['ec'], $searchTerm) !== false ||
            stripos($doc['item'], $searchTerm) !== false ||
            stripos($doc['file_name'], $searchTerm) !== false ||
            stripos($doc['recommending_approvals'] ?? '', $searchTerm) !== false ||
            stripos($doc['approving_authority'] ?? '', $searchTerm) !== false;
        
        $matchItem = empty($filterItem) || 
            stripos($doc['item'], $filterItem) !== false;
        
        return $matchSearch && $matchItem;
    });
    // Re-index the array
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
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Favorite Documents</h1>
            <p class="text-gray-600 dark:text-gray-400">Your saved favorite documents</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" id="searchInput" placeholder="Search by EC, Item, or filename..." 
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                value="<?php echo htmlspecialchars($searchTerm); ?>">
        </div>
        <button onclick="searchDocuments()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            Search
        </button>
    </div>

    <!-- Show Entries and Documents Table -->
    <div class="mb-4 flex items-center gap-2">
        <label for="limitSelect" class="text-sm text-gray-600 dark:text-gray-400">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo $itemsPerPage === 5 ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo $itemsPerPage === 10 ? 'selected' : ''; ?>>10</option>
            <option value="20" <?php echo $itemsPerPage === 20 ? 'selected' : ''; ?>>20</option>
            <option value="50" <?php echo $itemsPerPage === 50 ? 'selected' : ''; ?>>50</option>
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
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No favorites yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Star your favorite documents to see them here</p>
            <a href="documents.php" class="inline-block px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                Browse Documents
            </a>
        </div>
        <?php else: ?>
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Item</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Recommending Approvals</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Approving Authority</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Control Point</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Department/Office</th>
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
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300 font-bold">
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
                        $fileExt = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                        $previewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                        ?>
                        <!-- Preview Button (Eye Icon) -->
                        <?php if (in_array($fileExt, $previewableTypes)): ?>
                        <button onclick="openPreviewModal(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['file_name']); ?>')" title="Preview document" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-primary);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                        <?php endif; ?>
                        <!-- Remove from Favorites Button (Star Icon) -->
                        <button onclick="removeFavorite(<?php echo $doc['id']; ?>, this)" title="Remove from favorites" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-danger);">
                            <svg class="w-4 h-4" fill="currentColor" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M12 2L15.09 10.26H24L17.45 14.74L20.54 23L12 18.52L3.46 23L6.55 14.74L0 10.26H8.91L12 2Z"/>
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
    $paginationQuery = '&limit=' . $itemsPerPage;
    if (!empty($searchTerm)) $paginationQuery .= '&search=' . urlencode($searchTerm);
    if (!empty($filterItem)) $paginationQuery .= '&item=' . urlencode($filterItem);
    ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> documents
        </div>
        <div class="flex gap-2">
            <?php if ($currentPage > 1): ?>
            <a href="?page=1<?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                First
            </a>
            <a href="?page=<?php echo $currentPage - 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php endif; ?>

            <?php 
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
            <a href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 rounded-lg <?php echo $i === $currentPage ? 'bg-blue-500 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?> transition">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <a href="?page=<?php echo $totalPages; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                Last
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Document Preview Modal -->
<div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white" id="previewFileName"></h2>
            <button onclick="closePreviewModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <div id="previewContent" class="flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-lg min-h-96"></div>
        </div>
    </div>
</div>

<script>
function searchDocuments() {
    const searchTerm = document.getElementById('searchInput').value;
    let url = '?page=1';
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    window.location.href = url;
}

function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const searchTerm = new URLSearchParams(window.location.search).get('search') || '';
    const itemFilter = new URLSearchParams(window.location.search).get('item') || '';
    
    let url = '?page=1&limit=' + limit;
    if (searchTerm) url += '&search=' + encodeURIComponent(searchTerm);
    if (itemFilter) url += '&item=' + encodeURIComponent(itemFilter);
    
    window.location.href = url;
}

function removeFavorite(documentId, button) {
    Swal.fire({
        title: 'Remove from Favorites?',
        text: 'Are you sure you want to remove this document from your favorites?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove it!',
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
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: documentId })
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Removed!',
                        text: 'Document has been removed from favorites.',
                        icon: 'success',
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (modal) => {
                            if (document.body.classList.contains('dark')) {
                                modal.classList.add('dark');
                            }
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to remove from favorites: ' + data.message,
                        icon: 'error',
                        didOpen: (modal) => {
                            if (document.body.classList.contains('dark')) {
                                modal.classList.add('dark');
                            }
                        }
                    });
                }
            });
        }
    });
}

function openPreviewModal(documentId, fileName) {
    const modal = document.getElementById('previewModal');
    const previewContent = document.getElementById('previewContent');
    const previewFileName = document.getElementById('previewFileName');
    
    previewFileName.textContent = fileName;
    previewContent.innerHTML = '<div class="flex items-center justify-center"><svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>';
    
    const isImage = /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(fileName);
    const isPDF = /\.pdf$/i.test(fileName);
    
    if (isImage) {
        previewContent.innerHTML = `<img src="preview_document.php?id=${documentId}" alt="${fileName}" class="max-w-full max-h-[600px] object-contain mx-auto rounded">`;
    } else if (isPDF) {
        previewContent.innerHTML = `<iframe src="preview_document.php?id=${documentId}" class="w-full h-[600px] border-0 rounded"></iframe>`;
    } else {
        previewContent.innerHTML = '<div class="text-center py-8 text-gray-600">Preview not available for this file type</div>';
    }
    
    modal.classList.remove('hidden');
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
    document.getElementById('previewContent').innerHTML = '';
}

// Close preview modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('previewModal');
    if (e.target === modal) {
        closePreviewModal();
    }
});

// Close preview modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreviewModal();
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Favorite Documents';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
