<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/DepartmentsController.php';

MainController::requireAuth();

// Check if user is administrator or superadmin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['administrator', 'superadmin'])) {
    header("Location: dashboard.php");
    exit();
}

// Only superadmin can add/edit/delete 
$canManage = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';

// Check if user is admin - if so, make this read-only
$userRole = $_SESSION['role'] ?? 'staff';
$isReadOnly = strtolower($userRole) === 'admin' || strtolower($userRole) === 'administrator';

$controller = new MainController($conn);
$departmentsController = new DepartmentsController($conn);
$controller->setCurrentPage('manage_departments');

$currentPage = 'manage_departments';
$username = $_SESSION['username'] ?? 'User';

// Handle add department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_department') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot add records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to add departments.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $name = htmlspecialchars($_POST['name'] ?? '');
    $acronym = htmlspecialchars($_POST['acronym'] ?? '');

    if (!empty($name)) {
        try {
            $departmentsController->addDepartment($name, $acronym);
            $_SESSION['successMessage'] = "Department added successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        catch (Exception $e) {
            $errorMessage = "Error adding department: " . htmlspecialchars($e->getMessage());
        }
    }
    else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle edit department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_department') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot edit records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to edit departments.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $department_id = intval($_POST['department_id'] ?? 0);
    $name = htmlspecialchars($_POST['name'] ?? '');
    $acronym = htmlspecialchars($_POST['acronym'] ?? '');

    if ($department_id > 0 && !empty($name)) {
        try {
            $departmentsController->updateDepartment($department_id, $name, $acronym);
            $_SESSION['successMessage'] = "Department updated successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        catch (Exception $e) {
            $errorMessage = "Error updating department: " . htmlspecialchars($e->getMessage());
        }
    }
    else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle delete department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_department') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot delete records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to delete departments.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $department_id = intval($_POST['department_id'] ?? 0);

    if ($department_id > 0) {
        try {
            $departmentsController->deleteDepartment($department_id);
            $_SESSION['successMessage'] = "Department deleted successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
        catch (Exception $e) {
            $errorMessage = "Error deleting department: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle AJAX request to get department data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_department') {
    header('Content-Type: application/json');
    $department_id = intval($_POST['id'] ?? 0);

    try {
        $department = $departmentsController->getDepartmentById($department_id);
        if ($department) {
            echo json_encode(['success' => true, 'department' => $department]);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Department not found']);
        }
    }
    catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
    }
    exit;
}

// Pagination
$itemsPerPage = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
if ($itemsPerPage == 'all') {
    $itemsPerPage = $departmentsController->getDepartmentCount();
}
// Handle case where itemsPerPage might be 0 if parsing fails or logical error, default to 10
if ($itemsPerPage <= 0)
    $itemsPerPage = 10;

$totalItems = $departmentsController->getDepartmentCount();
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage > $totalPages && $totalPages > 0)
    $currentPage = $totalPages;
if ($currentPage < 1)
    $currentPage = 1;

$offset = ($currentPage - 1) * $itemsPerPage;

try {
    // If asking for all, method might expect null or something else depending on implementation, 
    // but our controller handles 'all' string logic outside or inside. 
    // Here we pass specific limit and offset.
    // If itemsPerPage is totalItems, it's effectively 'all'.
    $departments = $departmentsController->getAllDepartments($itemsPerPage, $offset);
}
catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $departments = [];
}

ob_start();
?>

<div class="w-full">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Departments</h1>
            <?php if ($isReadOnly): ?>
                <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">📖 Read-only mode: Admins cannot edit or delete records</p>
            <?php
endif; ?>
        </div>
        <?php if ($canManage && !$isReadOnly): ?>
        <button onclick="document.getElementById('addDepartmentModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            + Add Department
        </button>
        <?php
endif; ?>
    </div>

    <!-- Success/Error Messages (SweetAlert2) -->
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
    <?php
endif; ?>

    <?php
if ($displayError):
    unset($_SESSION['errorMessage']);
?>
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
    <?php
endif; ?>

    <!-- Add Department Modal -->
    <dialog id="addDepartmentModal" class="rounded-lg shadow-lg max-w-xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Add New Department</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_department">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department Name *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Acronym</label>
                <input type="text" name="acronym" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Add Department
                </button>
                <button type="button" onclick="document.getElementById('addDepartmentModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Edit Department Modal -->
    <dialog id="editDepartmentModal" class="rounded-lg shadow-lg max-w-xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Edit Department</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit_department">
            <input type="hidden" id="editDepartmentId" name="department_id" value="">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department Name *</label>
                <input type="text" id="editDepartmentName" name="name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Acronym</label>
                <input type="text" id="editDepartmentAcronym" name="acronym" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Update Department
                </button>
                <button type="button" onclick="document.getElementById('editDepartmentModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Departments Table -->
    <div class="mb-4 flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo(!isset($_GET['limit']) || $_GET['limit'] == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 25) ? 'selected' : ''; ?>>25</option>
            <option value="all" <?php echo(isset($_GET['limit']) && $_GET['limit'] == 'all') ? 'selected' : ''; ?>>Show All</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white" style="width: 100px;">ID</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Department Name</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Acronym</th>
                    <?php if ($canManage && !$isReadOnly): ?>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white" style="width: 150px;">Actions</th>
                    <?php
endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
if (!empty($departments)):
    foreach ($departments as $dept):
?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($dept['id']); ?></td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($dept['name']); ?></td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($dept['acronym'] ?? ''); ?></td>
                    <?php if ($canManage && !$isReadOnly): ?>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">
                        <button onclick="editDepartment(<?php echo $dept['id']; ?>)" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition mr-2" style="background-color: #eab308;" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars(addslashes($dept['name'])); ?>')" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-danger);" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                    <?php
        endif; ?>
                </tr>
                <?php
    endforeach;
else:
?>
                <tr>
                    <td colspan="<?php echo($canManage && !$isReadOnly) ? 4 : 3; ?>" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No departments found</td>
                </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
        </div>
        <div class="flex gap-2">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php
    endif; ?>

            <?php
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1) {
        echo '<a href="?page=1&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>';
        if ($startPage > 2)
            echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? 'bg-blue-500 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
        echo '<a href="?page=' . $i . '&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . '" class="px-4 py-2 rounded-lg transition ' . $active . '">' . $i . '</a>';
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1)
            echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
        echo '<a href="?page=' . $totalPages . '&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">' . $totalPages . '</a>';
    }
?>

            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php
    endif; ?>
        </div>
    </div>
    <?php
endif; ?>

<script>
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    window.location.href = '?page=1&limit=' + limit;
}

function editDepartment(id) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_department&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate all fields with existing data
            document.getElementById('editDepartmentId').value = data.department.id;
            document.getElementById('editDepartmentName').value = data.department.name;
            document.getElementById('editDepartmentAcronym').value = data.department.acronym || '';
            // Open the modal
            document.getElementById('editDepartmentModal').showModal();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error loading department data: ' + data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load department data',
            confirmButtonColor: '#ef4444'
        });
    });
}

function deleteDepartment(id, name) {
    Swal.fire({
        title: 'Delete Department',
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
                <input type="hidden" name="action" value="delete_department">
                <input type="hidden" name="department_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

</div>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
