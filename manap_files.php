<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/ManapFilesController.php';

MainController::requireAuth();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['administrator', 'superadmin'])) {
    header('Location: dashboard.php');
    exit();
}

$controller = new MainController($conn);
$controller->setCurrentPage('manap_files');
$manapFilesController = new ManapFilesController($conn);

$currentPage = 'manap_files';
$username = $_SESSION['username'] ?? 'User';
$canManage = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_manap_file') {
    header('Content-Type: application/json');
    $id = (int) ($_POST['id'] ?? 0);

    $record = $manapFilesController->getById($id);
    if ($record) {
        echo json_encode(['success' => true, 'record' => $record]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_manap_file') {
    $result = $manapFilesController->createRecord($_POST, $_FILES['file_upload'] ?? null);
    $_SESSION[$result['success'] ? 'successMessage' : 'errorMessage'] = $result['message'];
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_manap_file') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['errorMessage'] = 'Invalid record ID.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }

    $result = $manapFilesController->updateRecord($id, $_POST, $_FILES['file_upload'] ?? null);
    $_SESSION[$result['success'] ? 'successMessage' : 'errorMessage'] = $result['message'];
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_manap_file') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['errorMessage'] = 'Invalid record ID.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }

    $result = $manapFilesController->deleteRecord($id);
    $_SESSION[$result['success'] ? 'successMessage' : 'errorMessage'] = $result['message'];
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

$limitParam = $_GET['limit'] ?? '10';
$itemsPerPage = $limitParam === 'all' ? 'all' : max(1, (int) $limitParam);
$searchTerm = trim($_GET['search'] ?? '');

$totalItems = $manapFilesController->getTotalCount($searchTerm);

if ($itemsPerPage === 'all') {
    $totalPages = 1;
    $currentPageNum = 1;
    $offset = 0;
    $records = $manapFilesController->getRecords('all', 0, $searchTerm);
} else {
    $totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
    $currentPageNum = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($currentPageNum < 1) {
        $currentPageNum = 1;
    }
    if ($currentPageNum > $totalPages) {
        $currentPageNum = $totalPages;
    }
    $offset = ($currentPageNum - 1) * $itemsPerPage;
    $records = $manapFilesController->getRecords($itemsPerPage, $offset, $searchTerm);
}

$displaySuccess = $_SESSION['successMessage'] ?? null;
$displayError = $_SESSION['errorMessage'] ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);

ob_start();
?>

<link rel="stylesheet" href="app/css/manap_files.css">

<div id="manapFilesPage"
     class="w-full"
     data-success-message="<?php echo htmlspecialchars($displaySuccess ?? '', ENT_QUOTES); ?>"
     data-error-message="<?php echo htmlspecialchars($displayError ?? '', ENT_QUOTES); ?>">

    <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manap Files</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage uploaded files from the MANAP table.</p>
        </div>
        <?php if ($canManage): ?>
        <button type="button" id="openAddModalBtn" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            + Add Manap File
        </button>
        <?php endif; ?>
    </div>

    <form method="GET" id="filtersForm" class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-2">
            <label for="limitSelect" class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
            <select id="limitSelect" name="limit" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="10" <?php echo $limitParam === '10' ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $limitParam === '25' ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $limitParam === '50' ? 'selected' : ''; ?>>50</option>
                <option value="all" <?php echo $limitParam === 'all' ? 'selected' : ''; ?>>All</option>
            </select>
            <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
        </div>

        <div class="flex w-full md:w-auto gap-2">
            <input type="text" id="searchInput" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search file name or path..." class="w-full md:w-80 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">Search</button>
            <?php if (!empty($searchTerm)): ?>
            <a href="manap_files.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
        <?php if (empty($records)): ?>
        <div class="p-10 text-center text-gray-500 dark:text-gray-400">No MANAP file records found.</div>
        <?php else: ?>
        <table class="w-full min-w-[700px]">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">File Name</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">File Path</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($record['file_name'] ?? ''); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 break-all"><?php echo htmlspecialchars($record['file_path'] ?? ''); ?></td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex gap-2">
                            <button type="button"
                                    class="previewRecordBtn p-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
                                    data-id="<?php echo (int) $record['id']; ?>"
                                    data-file-name="<?php echo htmlspecialchars($record['file_name'] ?? 'Document', ENT_QUOTES); ?>"
                                    title="Preview">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                            <button type="button"
                                    class="editRecordBtn p-2 bg-amber-500 text-white rounded hover:bg-amber-600 transition"
                                    data-id="<?php echo (int) $record['id']; ?>"
                                    title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button type="button"
                                    class="deleteRecordBtn p-2 bg-red-600 text-white rounded hover:bg-red-700 transition"
                                    data-id="<?php echo (int) $record['id']; ?>"
                                    data-file-name="<?php echo htmlspecialchars($record['file_name'] ?? 'record', ENT_QUOTES); ?>"
                                    title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <?php if ($itemsPerPage !== 'all' && $totalPages > 1): ?>
    <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($currentPageNum > 1): ?>
                <a class="px-3 py-1 border rounded-lg text-sm text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600" href="?page=<?php echo $currentPageNum - 1; ?>&limit=<?php echo urlencode((string) $limitParam); ?>&search=<?php echo urlencode($searchTerm); ?>">Previous</a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $currentPageNum - 2);
            $endPage = min($totalPages, $currentPageNum + 2);
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <?php if ($i === $currentPageNum): ?>
                    <span class="px-3 py-1 bg-blue-500 text-white rounded-lg text-sm"><?php echo $i; ?></span>
                <?php else: ?>
                    <a class="px-3 py-1 border rounded-lg text-sm text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600" href="?page=<?php echo $i; ?>&limit=<?php echo urlencode((string) $limitParam); ?>&search=<?php echo urlencode($searchTerm); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPageNum < $totalPages): ?>
                <a class="px-3 py-1 border rounded-lg text-sm text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600" href="?page=<?php echo $currentPageNum + 1; ?>&limit=<?php echo urlencode((string) $limitParam); ?>&search=<?php echo urlencode($searchTerm); ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<dialog id="addRecordModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
    <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Upload MANAP File</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="add_manap_file">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File *</label>
            <input type="file" name="file_upload" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>

        <div class="flex gap-3 pt-3">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">Save</button>
            <button type="button" class="closeModalBtn px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">Cancel</button>
        </div>
    </form>
</dialog>

<dialog id="editRecordModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
    <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Edit MANAP File</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4" id="editRecordForm">
        <input type="hidden" name="action" value="edit_manap_file">
        <input type="hidden" name="id" id="editRecordId">

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File Name</label>
            <input type="text" id="editFileName" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">File Path</label>
            <input type="text" id="editFilePath" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Replace File (optional)</label>
            <input type="file" name="file_upload" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="currentFileName"></p>
        </div>

        <div class="flex gap-3 pt-3">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">Update</button>
            <button type="button" class="closeModalBtn px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">Cancel</button>
        </div>
    </form>
</dialog>

<!-- Document Preview Modal -->
<div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
            <h2 id="previewTitle" class="text-2xl font-bold text-gray-900 dark:text-white truncate"></h2>
            <button type="button" id="closePreviewModalBtn" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6">
            <div id="previewContent" class="flex items-center justify-center min-h-[300px] bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center justify-center gap-3">
                    <div class="animate-spin">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <span class="text-gray-600 dark:text-gray-300 font-medium">Loading preview...</span>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button type="button" id="closePreviewModalFooterBtn" class="w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium">
                Close
            </button>
        </div>
    </div>
</div>

<form id="deleteRecordForm" method="POST" class="hidden">
    <input type="hidden" name="action" value="delete_manap_file">
    <input type="hidden" name="id" id="deleteRecordId">
</form>

<script src="app/js/manap_files.js"></script>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
