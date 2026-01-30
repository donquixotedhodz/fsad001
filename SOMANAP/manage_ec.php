<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/ECController.php';

MainController::requireAuth();
$mainController = new MainController($conn);
$mainController->setCurrentPage('manage_ec');
$ecController = new ECController($conn);

// Set current page for sidebar active state
$currentPage = 'manage_ec';
$username = $_SESSION['username'] ?? 'User';

// Handle form submission for adding EC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ec') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $code = htmlspecialchars($_POST['code'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');
    
    if (!empty($name) && !empty($code)) {
        $result = $ecController->addEC($name, $code, $description);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    } else {
        $errorMessage = "Please fill in Name and Code fields.";
    }
}

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_ec') {
    $ec_id = intval($_POST['ec_id'] ?? 0);
    $name = htmlspecialchars($_POST['name'] ?? '');
    $code = htmlspecialchars($_POST['code'] ?? '');
    $description = htmlspecialchars($_POST['description'] ?? '');
    
    if ($ec_id > 0 && !empty($name) && !empty($code)) {
        $result = $ecController->updateEC($ec_id, $name, $code, $description);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ec') {
    $ec_id = intval($_POST['ec_id'] ?? 0);
    
    if ($ec_id > 0) {
        $result = $ecController->deleteEC($ec_id);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// Handle AJAX request to get EC data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_ec') {
    header('Content-Type: application/json');
    $ec_id = intval($_POST['id'] ?? 0);
    
    $record = $ecController->getEC($ec_id);
    if ($record) {
        echo json_encode(['success' => true, 'record' => $record]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }
    exit;
}

// Fetch all electric cooperatives with pagination
$itemsPerPage = isset($_GET['limit']) && $_GET['limit'] !== 'all' ? intval($_GET['limit']) : 10;
$currentPageNum = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($currentPageNum - 1) * $itemsPerPage;

// Get search term from GET parameter
$searchTerm = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Fetch records based on search
if (!empty($searchTerm)) {
    $totalItems = $ecController->getSearchCount($searchTerm);
    $totalPages = $itemsPerPage === 0 ? 1 : ceil($totalItems / $itemsPerPage);
    $ecs = $ecController->searchECs($searchTerm, $itemsPerPage === 0 ? 0 : $itemsPerPage, $offset);
} else {
    $totalItems = $ecController->getTotalCount();
    $totalPages = $itemsPerPage === 0 ? 1 : ceil($totalItems / $itemsPerPage);
    $ecs = $ecController->getAllECs($itemsPerPage === 0 ? 0 : $itemsPerPage, $offset);
}

// Start output buffering to capture content
ob_start();
?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">EC Records</h1>
        <button onclick="document.getElementById('addECModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            + Add EC
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

    <!-- Add EC Modal -->
    <dialog id="addECModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Add Electric Cooperative</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_ec">
            
            <div class="grid grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Code *</label>
                    <input type="text" name="code" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Add EC
                </button>
                <button type="button" onclick="document.getElementById('addECModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Edit EC Modal -->
    <dialog id="editECModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Edit Electric Cooperative</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit_ec">
            <input type="hidden" id="editECId" name="ec_id" value="">
            
            <div class="grid grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name *</label>
                    <input type="text" id="editName" name="name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Code *</label>
                    <input type="text" id="editCode" name="code" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <textarea id="editDescription" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Update EC
                </button>
                <button type="button" onclick="document.getElementById('editECModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Show Entries and EC Table -->
    <div class="mb-4 flex items-center gap-2 flex-wrap">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 25) ? 'selected' : ''; ?>>25</option>
            <option value="all" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 'all') ? 'selected' : ''; ?>>Show All</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>

        <!-- Search Bar -->
        <div class="ml-auto flex gap-2">
            <input type="text" id="searchInput" placeholder="Search EC name, code..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" onkeyup="handleSearch(this.value)">
            <?php if (!empty($searchTerm)): ?>
            <a href="?page=1&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?>" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                Clear
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">ID</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Name</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Code</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Description</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ecs as $ec): ?>
                <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($ec['id']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($ec['name']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($ec['code']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($ec['description'] ?? '-'); ?></td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex gap-2 justify-center">
                            <button onclick="editEC(<?php echo $ec['id']; ?>)" class="p-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="deleteEC(<?php echo $ec['id']; ?>, '<?php echo htmlspecialchars($ec['name']); ?>')" class="p-2 bg-red-500 text-white rounded hover:bg-red-600 transition" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
        </div>
        <div class="flex gap-2">
            <?php if ($currentPageNum > 1): ?>
            <a href="?page=<?php echo $currentPageNum - 1; ?>&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $currentPageNum - 2);
            $endPage = min($totalPages, $currentPageNum + 2);
            
            if ($startPage > 1) {
                echo '<a href="?page=1&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . (! empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>';
                if ($startPage > 2) echo '<span class="px-2 py-2">...</span>';
            }
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i === $currentPageNum) {
                    echo '<span class="px-4 py-2 bg-blue-500 text-white rounded-lg">' . $i . '</span>';
                } else {
                    echo '<a href="?page=' . $i . '&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . (! empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">' . $i . '</a>';
                }
            }
            
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) echo '<span class="px-2 py-2">...</span>';
                echo '<a href="?page=' . $totalPages . '&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . (! empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">' . $totalPages . '</a>';
            }
            ?>

            <?php if ($currentPageNum < $totalPages): ?>
            <a href="?page=<?php echo $currentPageNum + 1; ?>&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    const searchTerm = document.getElementById('searchInput').value;
    const search = searchTerm ? '&search=' + encodeURIComponent(searchTerm) : '';
    window.location.href = '?page=1&limit=' + limit + search;
}

function handleSearch(value) {
    // Debounce search
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        const limit = document.getElementById('limitSelect').value;
        const search = value ? '&search=' + encodeURIComponent(value) : '';
        window.location.href = '?page=1&limit=' + limit + search;
    }, 500);
}

function editEC(id) {
    fetch(``, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_ec&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editECId').value = data.record.id;
            document.getElementById('editName').value = data.record.name;
            document.getElementById('editCode').value = data.record.code;
            document.getElementById('editDescription').value = data.record.description || '';
            document.getElementById('editECModal').showModal();
        }
    });
}

function deleteEC(id, name) {
    Swal.fire({
        title: 'Delete Electric Cooperative',
        html: `Are you sure you want to delete <strong>${name}</strong>?`,
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
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_ec">
                <input type="hidden" name="ec_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php
// Capture content and include master layout
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
