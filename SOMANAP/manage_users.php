<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/controllers/UsersController.php';

MainController::requireAuth();

// Check if user is administrator or superadmin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['administrator', 'superadmin'])) {
    header("Location: dashboard.php");
    exit();
}

// Only superadmin can add/edit/delete users
$canManage = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';

// Check if user is admin - if so, make this read-only
$userRole = $_SESSION['role'] ?? 'staff';
$isReadOnly = strtolower($userRole) === 'admin' || strtolower($userRole) === 'administrator';

$controller = new MainController($conn);
$usersController = new UsersController($conn);
$controller->setCurrentPage('manage_users');

$currentPage = 'manage_users';
$username = $_SESSION['username'] ?? 'User';

// Handle add user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot add records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to add users.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $new_username = htmlspecialchars($_POST['username'] ?? '');
    $full_name = htmlspecialchars($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = htmlspecialchars($_POST['role'] ?? 'staff');

    if (!empty($new_username) && !empty($full_name) && !empty($password)) {
        try {
            $usersController->addUser($new_username, $full_name, $password, $role);
            $_SESSION['successMessage'] = "User added successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Exception $e) {
            $errorMessage = "Error adding user: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot edit records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to edit users.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $user_id = intval($_POST['user_id'] ?? 0);
    $full_name = htmlspecialchars($_POST['full_name'] ?? '');
    $role = htmlspecialchars($_POST['role'] ?? 'staff');
    $password = $_POST['password'] ?? '';

    if ($user_id > 0 && !empty($full_name)) {
        try {
            $usersController->updateUser($user_id, $full_name, $role, $password ?: null);
            $_SESSION['successMessage'] = "User updated successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Exception $e) {
            $errorMessage = "Error updating user: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if ($isReadOnly) {
        $_SESSION['errorMessage'] = "Admin users cannot delete records. This is read-only mode.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    if (!$canManage) {
        $_SESSION['errorMessage'] = "You do not have permission to delete users.";
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        try {
            $usersController->deleteUser($user_id);
            $_SESSION['successMessage'] = "User deleted successfully!";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (Exception $e) {
            $errorMessage = "Error deleting user: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle AJAX request to get user data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_user') {
    header('Content-Type: application/json');
    $user_id = intval($_POST['id'] ?? 0);

    try {
        $user = $usersController->getUserById($user_id);
        if ($user) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
    }
    exit;
}

// Pagination
$itemsPerPage = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
if ($itemsPerPage == 'all') {
    $itemsPerPage = $usersController->getUserCount();
}
$totalItems = $usersController->getUserCount();
$totalPages = ceil($totalItems / $itemsPerPage);
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage > $totalPages) $currentPage = $totalPages;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $itemsPerPage;

try {
    $users = $usersController->getAllUsers($itemsPerPage, $offset);
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $users = [];
}

ob_start();
?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Users</h1>
            <?php if ($isReadOnly): ?>
                <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">📖 Read-only mode: Admins cannot edit or delete records</p>
            <?php endif; ?>
        </div>
        <?php if ($canManage && !$isReadOnly): ?>
        <button onclick="document.getElementById('addUserModal').showModal()" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            + Add User
        </button>
        <?php endif; ?>
    </div>

    <!-- Success/Error Messages -->
    <?php 
    $displaySuccess = isset($_SESSION['successMessage']) ? $_SESSION['successMessage'] : (isset($successMessage) ? $successMessage : null);
    if ($displaySuccess): 
    ?>
        <div id="successMessage" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded flex justify-between items-center">
            <span><?php echo $displaySuccess; ?></span>
            <button onclick="document.getElementById('successMessage').style.display = 'none'" class="text-green-700 hover:text-green-900 font-bold ml-4">✕</button>
        </div>
        <?php unset($_SESSION['successMessage']); ?>
    <?php endif; ?>
    <?php 
    $displayError = isset($_SESSION['errorMessage']) ? $_SESSION['errorMessage'] : (isset($errorMessage) ? $errorMessage : null);
    if ($displayError): 
    ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded flex justify-between items-center">
            <span><?php echo $displayError; ?></span>
            <button onclick="this.parentElement.style.display = 'none'" class="text-red-700 hover:text-red-900 font-bold ml-4">✕</button>
        </div>
        <?php unset($_SESSION['errorMessage']); ?>
    <?php endif; ?>

    <!-- Add User Modal -->
    <dialog id="addUserModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Add New User</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_user">
            
            <div class="grid grid-cols-2 gap-6">
                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username *</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                    <input type="text" name="full_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password *</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="staff">Staff</option>
                        <option value="administrator">Administrator</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Add User
                </button>
                <button type="button" onclick="document.getElementById('addUserModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Edit User Modal -->
    <dialog id="editUserModal" class="rounded-lg shadow-lg max-w-2xl w-full p-8 dark:bg-gray-800">
        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white">Edit User</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" id="editUserId" name="user_id" value="">
            
            <div class="grid grid-cols-2 gap-6">
                <!-- Username (Read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                    <input type="text" id="editUsername" disabled class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-400 bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none">
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name *</label>
                    <input type="text" id="editFullName" name="full_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                    <input type="password" id="editPassword" name="password" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Leave empty to keep current">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role *</label>
                    <select id="editRole" name="role" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="staff">Staff</option>
                        <option value="administrator">Administrator</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    Update User
                </button>
                <button type="button" onclick="document.getElementById('editUserModal').close()" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </dialog>

    <!-- Users Table -->
    <div class="mb-4 flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 25) ? 'selected' : ''; ?>>25</option>
            <option value="all" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 'all') ? 'selected' : ''; ?>>Show All</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900">
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Username</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Full Name</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Role</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Created</th>
                    <?php if ($canManage && !$isReadOnly): ?>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($users)):
                    foreach ($users as $user):
                        $roleColor = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
                        if ($user['role'] === 'administrator') {
                            $roleColor = 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
                        } elseif ($user['role'] === 'superadmin') {
                            $roleColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                        }
                ?>
                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3">
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $roleColor; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-900 dark:text-gray-100"><?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></td>
                    <?php if ($canManage && !$isReadOnly): ?>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center">
                        <button onclick="editUser(<?php echo $user['id']; ?>)" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition mr-2" style="background-color: var(--theme-secondary);" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')" class="inline-flex items-center justify-center w-8 h-8 text-white rounded hover:opacity-90 transition" style="background-color: var(--theme-danger);" title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php 
                    endforeach;
                else:
                ?>
                <tr>
                    <td colspan="<?php echo ($canManage && !$isReadOnly) ? 5 : 4; ?>" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No users found</td>
                </tr>
                <?php endif; ?>
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
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            if ($startPage > 1) {
                echo '<a href="?page=1&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>';
                if ($startPage > 2) echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
            }
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                $active = $i == $currentPage ? 'bg-blue-500 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700';
                echo '<a href="?page=' . $i . '&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . '" class="px-4 py-2 rounded-lg transition ' . $active . '">' . $i . '</a>';
            }
            
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) echo '<span class="px-2 py-2 text-gray-700 dark:text-gray-300">...</span>';
                echo '<a href="?page=' . $totalPages . '&limit=' . (isset($_GET['limit']) ? $_GET['limit'] : 10) . '" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">' . $totalPages . '</a>';
            }
            ?>

            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>&limit=<?php echo isset($_GET['limit']) ? $_GET['limit'] : 10; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

<script>
function changeLimit() {
    const limit = document.getElementById('limitSelect').value;
    window.location.href = '?page=1&limit=' + limit;
}

function editUser(id) {
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_user&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Populate all fields with existing data
            document.getElementById('editUserId').value = data.user.id;
            document.getElementById('editUsername').value = data.user.username;
            document.getElementById('editFullName').value = data.user.full_name;
            document.getElementById('editRole').value = data.user.role;
            // Clear password field when opening edit modal
            document.getElementById('editPassword').value = '';
            // Open the modal
            document.getElementById('editUserModal').showModal();
        } else {
            alert('Error loading user data: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to load user data');
    });
}

function deleteUser(id, fullName) {
    if (confirm('Are you sure you want to delete user "' + fullName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</div>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
