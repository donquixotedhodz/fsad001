<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/FaqController.php';

MainController::requireAuth();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['administrator', 'superadmin'])) {
    header("Location: dashboard.php");
    exit();
}

$canManage = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
$userRole = $_SESSION['role'] ?? 'staff';
$isReadOnly = strtolower($userRole) === 'admin' || strtolower($userRole) === 'administrator';

$controller = new MainController($conn);
$faqController = new FaqController($conn);
$controller->setCurrentPage('faq');

$currentPage = 'faq';
$username = $_SESSION['username'] ?? 'User';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_faq') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot add records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to add FAQ records.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $category = trim($_POST['category'] ?? 'General');
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $displayOrder = intval($_POST['display_order'] ?? 1);
    $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    if ($displayOrder < 1) {
        $displayOrder = 1;
    }

    if (!empty($category) && !empty($question) && !empty($answer)) {
        try {
            $faqController->addFaq($category, $question, $answer, $displayOrder, $isActive);
            $_SESSION['successMessage'] = "FAQ added successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        catch (Exception $e) {
            $errorMessage = "Error adding FAQ: " . htmlspecialchars($e->getMessage());
        }
    }
    else {
        $errorMessage = "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_faq') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot edit records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to edit FAQ records.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $faqId = intval($_POST['faq_id'] ?? 0);
    $category = trim($_POST['category'] ?? 'General');
    $question = trim($_POST['question'] ?? '');
    $answer = trim($_POST['answer'] ?? '');
    $displayOrder = intval($_POST['display_order'] ?? 1);
    $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    if ($displayOrder < 1) {
        $displayOrder = 1;
    }

    if ($faqId > 0 && !empty($category) && !empty($question) && !empty($answer)) {
        try {
            $faqController->updateFaq($faqId, $category, $question, $answer, $displayOrder, $isActive);
            $_SESSION['successMessage'] = "FAQ updated successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        catch (Exception $e) {
            $errorMessage = "Error updating FAQ: " . htmlspecialchars($e->getMessage());
        }
    }
    else {
        $errorMessage = "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_faq') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot delete records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to delete FAQ records.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $faqId = intval($_POST['faq_id'] ?? 0);

    if ($faqId > 0) {
        try {
            $faqController->deleteFaq($faqId);
            $_SESSION['successMessage'] = "FAQ deleted successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        catch (Exception $e) {
            $errorMessage = "Error deleting FAQ: " . htmlspecialchars($e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_faq') {
    header('Content-Type: application/json');
    $faqId = intval($_POST['id'] ?? 0);

    try {
        $faq = $faqController->getFaqById($faqId);
        if ($faq) {
            echo json_encode(['success' => true, 'faq' => $faq]);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'FAQ record not found']);
        }
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
    }
    exit;
}

$limitParam = $_GET['limit'] ?? '10';
$itemsPerPage = $limitParam === 'all' ? 'all' : max(1, intval($limitParam));
$searchTerm = trim($_GET['search'] ?? '');

try {
    $totalItems = $faqController->getFaqCount($searchTerm);

    if ($itemsPerPage === 'all') {
        $totalPages = 1;
        $currentPageNum = 1;
        $offset = 0;
        $faqRecords = $faqController->getAllFaq('all', 0, $searchTerm);
    }
    else {
        $totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
        $currentPageNum = isset($_GET['page']) ? intval($_GET['page']) : 1;
        if ($currentPageNum > $totalPages) {
            $currentPageNum = $totalPages;
        }
        if ($currentPageNum < 1) {
            $currentPageNum = 1;
        }

        $offset = ($currentPageNum - 1) * $itemsPerPage;
        $faqRecords = $faqController->getAllFaq($itemsPerPage, $offset, $searchTerm);
    }
}
catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $faqRecords = [];
    $totalItems = 0;
    $totalPages = 1;
    $currentPageNum = 1;
    $offset = 0;
}

ob_start();
?>

<div class="w-full">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">FAQ Management</h1>
            <?php if ($isReadOnly): ?>
                <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">📖 Read-only mode: Admins cannot edit or delete records</p>
            <?php endif; ?>
        </div>
        <?php if ($canManage && !$isReadOnly): ?>
        <button onclick="document.getElementById('addFaqModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            + Add FAQ
        </button>
        <?php endif; ?>
    </div>

    <?php
    $displaySuccess = isset($_SESSION['successMessage']) ? $_SESSION['successMessage'] : (isset($successMessage) ? $successMessage : null);
    $displayError = isset($_SESSION['errorMessage']) ? $_SESSION['errorMessage'] : (isset($errorMessage) ? $errorMessage : null);

    if ($displaySuccess):
        unset($_SESSION['successMessage']);
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?php echo addslashes($displaySuccess); ?>',
                confirmButtonColor: '#3b82f6'
            });
        });
    </script>
    <?php endif; ?>

    <?php if ($displayError): unset($_SESSION['errorMessage']); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo addslashes($displayError); ?>',
                confirmButtonColor: '#ef4444'
            });
        });
    </script>
    <?php endif; ?>

    <dialog id="addFaqModal" class="rounded-lg shadow-lg max-w-3xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Add New FAQ</h2>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_faq">

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                    <input type="text" name="category" required value="General" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Order *</label>
                    <input type="number" name="display_order" min="1" required value="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Question *</label>
                    <input type="text" name="question" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Answer *</label>
                    <textarea name="answer" rows="5" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                    <select name="is_active" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Add FAQ
                </button>
                <button type="button" onclick="document.getElementById('addFaqModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <dialog id="editFaqModal" class="rounded-lg shadow-lg max-w-3xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Edit FAQ</h2>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit_faq">
            <input type="hidden" id="editFaqId" name="faq_id" value="">

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                    <input type="text" id="editFaqCategory" name="category" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Order *</label>
                    <input type="number" id="editFaqOrder" name="display_order" min="1" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Question *</label>
                    <input type="text" id="editFaqQuestion" name="question" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Answer *</label>
                    <textarea id="editFaqAnswer" name="answer" rows="5" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                    <select id="editFaqStatus" name="is_active" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Update FAQ
                </button>
                <button type="button" onclick="document.getElementById('editFaqModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <div class="mb-4 flex items-center gap-2 flex-wrap">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo $limitParam === '5' ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo ($limitParam === '10' || !isset($_GET['limit'])) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo $limitParam === '25' ? 'selected' : ''; ?>>25</option>
            <option value="all" <?php echo $limitParam === 'all' ? 'selected' : ''; ?>>Show All</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>

        <div class="ml-auto flex gap-2">
            <input type="text" id="searchInput" placeholder="Search category, question, answer..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" onkeyup="handleSearch(this.value)">
            <?php if (!empty($searchTerm)): ?>
            <a href="?page=1&limit=<?php echo urlencode($limitParam); ?>" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                Clear
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white" style="width: 80px;">ID</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Category</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Question</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Answer</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white" style="width: 110px;">Order</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white" style="width: 110px;">Status</th>
                    <?php if ($canManage && !$isReadOnly): ?>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white" style="width: 150px;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($faqRecords)): ?>
                    <?php foreach ($faqRecords as $faq): ?>
                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($faq['id']); ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($faq['category']); ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($faq['question']); ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars(strlen($faq['answer']) > 140 ? substr($faq['answer'], 0, 140) . '...' : $faq['answer']); ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($faq['display_order']); ?></td>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">
                            <?php if ((int) $faq['is_active'] === 1): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($canManage && !$isReadOnly): ?>
                        <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">
                            <button onclick="editFaq(<?php echo (int) $faq['id']; ?>)" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition mr-2" style="background-color: #eab308;" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <button onclick='deleteFaq(<?php echo (int) $faq['id']; ?>, <?php echo json_encode($faq['question']); ?>)' class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-danger);" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="<?php echo ($canManage && !$isReadOnly) ? 7 : 6; ?>" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No FAQ records found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($itemsPerPage !== 'all' && $totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
        </div>
        <div class="flex gap-2">
            <?php if ($currentPageNum > 1): ?>
            <a href="?page=<?php echo $currentPageNum - 1; ?>&limit=<?php echo urlencode($limitParam); ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $currentPageNum - 2);
            $endPage = min($totalPages, $currentPageNum + 2);

            if ($startPage > 1) {
                echo '<a href="?page=1&limit=' . urlencode($limitParam) . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>';
                if ($startPage > 2) {
                    echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
                }
            }

            for ($i = $startPage; $i <= $endPage; $i++) {
                $activeClass = $i == $currentPageNum ? 'bg-blue-500 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
                echo '<a href="?page=' . $i . '&limit=' . urlencode($limitParam) . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-4 py-2 rounded-lg transition ' . $activeClass . '">' . $i . '</a>';
            }

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
                }
                echo '<a href="?page=' . $totalPages . '&limit=' . urlencode($limitParam) . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">' . $totalPages . '</a>';
            }
            ?>

            <?php if ($currentPageNum < $totalPages): ?>
            <a href="?page=<?php echo $currentPageNum + 1; ?>&limit=<?php echo urlencode($limitParam); ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
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
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        const limit = document.getElementById('limitSelect').value;
        const search = value ? '&search=' + encodeURIComponent(value) : '';
        window.location.href = '?page=1&limit=' + limit + search;
    }, 500);
}

function editFaq(id) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_faq&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editFaqId').value = data.faq.id;
            document.getElementById('editFaqCategory').value = data.faq.category;
            document.getElementById('editFaqQuestion').value = data.faq.question;
            document.getElementById('editFaqAnswer').value = data.faq.answer;
            document.getElementById('editFaqOrder').value = data.faq.display_order;
            document.getElementById('editFaqStatus').value = data.faq.is_active;
            document.getElementById('editFaqModal').showModal();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'FAQ record not found',
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load FAQ data',
            confirmButtonColor: '#ef4444'
        });
    });
}

function deleteFaq(id, question) {
    Swal.fire({
        title: 'Delete FAQ',
        html: `Are you sure you want to delete <strong>${question}</strong>?`,
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
                <input type="hidden" name="action" value="delete_faq">
                <input type="hidden" name="faq_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
