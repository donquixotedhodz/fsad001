<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('settings');
$username = $_SESSION['username'] ?? 'User';

// Handle Add Staff Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $name = isset($_POST['staff_name']) ? trim($_POST['staff_name']) : '';
    $position = isset($_POST['staff_position']) ? trim($_POST['staff_position']) : '';
    $department = isset($_POST['staff_department']) ? trim($_POST['staff_department']) : '';
    $username = isset($_POST['staff_username']) ? trim($_POST['staff_username']) : '';
    $password = isset($_POST['staff_password']) ? trim($_POST['staff_password']) : '';
    
    if (!empty($name) && !empty($position) && !empty($username) && !empty($password)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO staff (name, position, department, username, password, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $position, $department, $username, $hashedPassword]);
            $successMessage = "Staff added successfully!";
        } catch (PDOException $e) {
            $errorMessage = "Error adding staff: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Name, Position, Username, and Password are required!";
    }
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Settings</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage your account and preferences</p>
    </div>

    <!-- Settings Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Sidebar Navigation -->
        <div class="md:col-span-1">
            <nav class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <a href="#profile" class="block px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg mb-2">
                    Profile
                </a>
                <a href="#password" class="block px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mb-2">
                    Password
                </a>
                <a href="#notifications" class="block px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mb-2">
                    Notifications
                </a>
                <a href="#preferences" class="block px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mb-2">
                    Preferences
                </a>
                <a href="#privacy" class="block px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg mb-2">
                    Privacy & Security
                </a>
                <a href="#manage-users" class="block px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    Manage Users
                </a>
            </nav>
        </div>

        <!-- Settings Content -->
        <div class="md:col-span-3">
            <!-- Profile Settings -->
            <div id="profile" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Profile Information</h2>

                <form class="space-y-6">
                    <div class="flex items-center gap-6 mb-6">
                        <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <button type="button" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Change Avatar
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($username); ?>" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Full Name</label>
                            <input type="text" placeholder="John Doe" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Settings -->
            <div id="password" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Change Password</h2>

                <form class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Current Password</label>
                        <input type="password" placeholder="Enter current password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">New Password</label>
                        <input type="password" placeholder="Enter new password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Confirm Password</label>
                        <input type="password" placeholder="Confirm new password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notifications Settings -->
            <div id="notifications" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Notification Preferences</h2>

                <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" checked class="w-5 h-5 rounded bg-blue-500 border-blue-600 text-blue-600">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Email Notifications</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Receive email updates about your activity</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" checked class="w-5 h-5 rounded bg-blue-500 border-blue-600 text-blue-600">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Document Updates</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get notified when documents are updated</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="w-5 h-5 rounded bg-blue-500 border-blue-600 text-blue-600">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Report Generation</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Notify when reports are ready</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" checked class="w-5 h-5 rounded bg-blue-500 border-blue-600 text-blue-600">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Weekly Summary</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Receive weekly activity summary</p>
                        </div>
                    </label>
                </div>

                <div class="mt-6">
                    <button class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                        Save Preferences
                    </button>
                </div>
            </div>

            <!-- Preferences Settings -->
            <div id="preferences" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Display Preferences</h2>

                <form id="preferencesForm" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-4">Theme</label>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" name="theme" value="light" class="w-4 h-4 text-blue-600">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Light Mode</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Use light theme for the interface</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" name="theme" value="dark" class="w-4 h-4 text-blue-600">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Dark Mode</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Use dark theme for the interface</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" name="theme" value="system" class="w-4 h-4 text-blue-600">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">System Default</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Follow system theme settings</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            Save Preferences
                        </button>
                    </div>
                </form>
            </div>

            <!-- Privacy & Security -->
            <div id="privacy" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Privacy & Security</h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Two-Factor Authentication</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Add an extra layer of security</p>
                        </div>
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                            Enable
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Active Sessions</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">View and manage active login sessions</p>
                        </div>
                        <button class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                            Manage
                        </button>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Account Deletion</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Permanently delete your account</p>
                        </div>
                        <button class="px-4 py-2 bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-200 dark:hover:bg-red-900/40 transition">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Manage Users Section -->
            <div id="manage-users" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Manage Users</h2>

                <!-- Add New User Form -->
                <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New User</h3>
                    <form class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                                <input type="text" placeholder="Enter username" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input type="text" placeholder="Enter full name" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                                <input type="password" placeholder="Enter password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            Add User
                        </button>
                    </form>
                </div>

                <!-- Users List Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Username</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Full Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Created Date</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("SELECT id, username, full_name, created_at FROM users ORDER BY created_at DESC");
                            $stmt->execute();
                            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($users) > 0):
                                foreach ($users as $user):
                            ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="px-6 py-4 text-sm text-center">
                                    <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')" class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition text-xs font-medium mr-2">
                                        Edit
                                    </button>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition text-xs font-medium">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No users found
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Divider -->
                <div class="my-8 border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Staff Management Section -->
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 mt-8">Staff Management</h3>

                <!-- Add New Staff Form -->
                <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Staff</h4>
                    <?php if (isset($successMessage)): ?>
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300 rounded-lg border border-green-300 dark:border-green-700">
                            <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($errorMessage)): ?>
                        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300 rounded-lg border border-red-300 dark:border-red-700">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_staff">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input type="text" name="staff_name" placeholder="Enter staff name" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Position</label>
                                <input type="text" name="staff_position" placeholder="Enter position" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                                <input type="text" name="staff_department" placeholder="Enter department" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                                <input type="text" name="staff_username" placeholder="Enter username" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                                <input type="password" name="staff_password" placeholder="Enter password" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                            Add Staff
                        </button>
                    </form>
                </div>

                <!-- Staff List Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Username</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Position</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Department</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $staffStmt = $conn->prepare("SELECT id, name, username, position, department, status FROM staff ORDER BY name ASC");
                            $staffStmt->execute();
                            $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($staffList) > 0):
                                foreach ($staffList as $staff):
                            ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($staff['username']); ?></code></td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($staff['position']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($staff['department'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $staff['status'] === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'; ?>">
                                        <?php echo ucfirst($staff['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-center">
                                    <button onclick="editStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['name']); ?>', '<?php echo htmlspecialchars($staff['position']); ?>', '<?php echo htmlspecialchars($staff['department']); ?>')" class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition text-xs font-medium mr-2">
                                        Edit
                                    </button>
                                    <button onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['name']); ?>')" class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition text-xs font-medium">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No staff found
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Edit User</h3>
        <form id="editUserForm" class="space-y-4">
            <input type="hidden" id="editUserId" value="">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                <input type="text" id="editUsername" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                <input type="text" id="editFullName" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password (Leave blank to keep current)</label>
                <input type="password" id="editPassword" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                    Save Changes
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg font-medium hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div id="editStaffModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Edit Staff</h3>
        <form id="editStaffForm" class="space-y-4">
            <input type="hidden" id="editStaffId" value="">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                <input type="text" id="editStaffName" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Position</label>
                <input type="text" id="editStaffPosition" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <input type="text" id="editStaffDepartment" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                    Save Changes
                </button>
                <button type="button" onclick="closeEditStaffModal()" class="flex-1 px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg font-medium hover:bg-gray-400 dark:hover:bg-gray-700 transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, username, fullName) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editFullName').value = fullName;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

function deleteUser(id, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        // Delete functionality would go here
        alert('User deletion feature coming soon');
    }
}

function editStaff(id, name, position, department) {
    document.getElementById('editStaffId').value = id;
    document.getElementById('editStaffName').value = name;
    document.getElementById('editStaffPosition').value = position;
    document.getElementById('editStaffDepartment').value = department;
    document.getElementById('editStaffModal').classList.remove('hidden');
}

function closeEditStaffModal() {
    document.getElementById('editStaffModal').classList.add('hidden');
}

function deleteStaff(id, name) {
    if (confirm(`Are you sure you want to delete staff "${name}"? This action cannot be undone.`)) {
        // Delete functionality would go here
        alert('Staff deletion feature coming soon');
    }
}

document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Edit functionality would go here
    alert('User edit feature coming soon');
    closeEditModal();
});

document.getElementById('editStaffForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Edit functionality would go here
    alert('Staff edit feature coming soon');
    closeEditStaffModal();
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require_once __DIR__ . '/app/views/layouts/master.php';
?>

<script>
// Load theme preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'system';
    const themeRadios = document.querySelectorAll('input[name="theme"]');
    
    themeRadios.forEach(radio => {
        if (radio.value === savedTheme) {
            radio.checked = true;
        }
    });
});

// Handle theme preference form submission
const preferencesForm = document.getElementById('preferencesForm');
if (preferencesForm) {
    preferencesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedTheme = document.querySelector('input[name="theme"]:checked').value;
        localStorage.setItem('theme', selectedTheme);
        
        // Apply theme immediately
        applyTheme(selectedTheme);
        
        // Show success message
        const button = preferencesForm.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        button.textContent = '✓ Saved';
        button.classList.add('bg-green-600', 'hover:bg-green-700');
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    });
}

// Function to apply theme
function applyTheme(theme) {
    if (theme === 'light') {
        document.documentElement.classList.remove('dark');
    } else if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (theme === 'system') {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}

// Apply saved theme on page load
window.addEventListener('load', function() {
    const savedTheme = localStorage.getItem('theme') || 'system';
    applyTheme(savedTheme);
});
</script>
