<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('settings');
$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'] ?? null;

// Get current user's profile data
$profileImage = null;
$fullName = '';
if ($userId) {
    try {
        $stmt = $conn->prepare("SELECT profile_image, full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $profileImage = $user['profile_image'] ?? null;
        $fullName = $user['full_name'] ?? '';
    } catch (PDOException $e) {
        // Profile image column might not exist yet
    }
}

// Handle Update Profile Information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $newFullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    
    if ($userId && !empty($newFullName)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$newFullName, $userId]);
            $fullName = $newFullName;
            $successMessage = "Profile information updated successfully!";
        } catch (PDOException $e) {
            $errorMessage = "Error updating profile: " . $e->getMessage();
        }
    } elseif (empty($newFullName)) {
        $errorMessage = "Full name cannot be empty!";
    }
}

// Handle Profile Image Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_profile_image') {
    if ($userId && isset($_FILES['profile_image'])) {
        $file = $_FILES['profile_image'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        // Validate file
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileName = basename($file['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileSize = $file['size'];
            
            if (!in_array($fileExt, $allowedExtensions)) {
                $errorMessage = "Invalid file type. Allowed types: " . implode(', ', $allowedExtensions);
            } elseif ($fileSize > $maxFileSize) {
                $errorMessage = "File size exceeds 5MB limit.";
            } else {
                try {
                    // Create directory if it doesn't exist
                    $uploadDir = __DIR__ . '/uploads/profile_images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $uniqueName = uniqid('profile_' . $userId . '_', true) . '.' . $fileExt;
                    $uploadPath = $uploadDir . $uniqueName;
                    
                    // Delete old profile image if exists
                    if ($profileImage) {
                        $oldImagePath = $uploadDir . $profileImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        // Update database
                        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $stmt->execute([$uniqueName, $userId]);
                        $profileImage = $uniqueName;
                        $successMessage = "Profile image updated successfully!";
                    } else {
                        $errorMessage = "Failed to upload file.";
                    }
                } catch (Exception $e) {
                    $errorMessage = "Error uploading profile image: " . $e->getMessage();
                }
            }
        } else {
            $errorMessage = "File upload error.";
        }
    }
}

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

    <!-- Settings Navigation Dropdown -->
    <div class="mb-6 flex items-center gap-4">
        <label for="settingsDropdown" class="text-sm font-medium text-gray-700 dark:text-gray-300">Go to:</label>
        <select id="settingsDropdown" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="navigateToSection(this.value)">
            <option value="#profile">Profile</option>
            <option value="#password">Password</option>
            <option value="#color-theme">Color Theme</option>
        </select>
    </div>

    <!-- Settings Content -->
    <div class="">
            <!-- Profile Settings -->
            <div id="profile" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Profile Information</h2>

                <!-- Profile Image Section -->
                <div class="mb-8">
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

                    <form id="profileImageForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="upload_profile_image">
                        
                        <div class="flex items-center gap-6">
                            <!-- Profile Image Display -->
                            <div class="relative">
                                <div id="profileImagePreview" class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center overflow-hidden shadow-lg">
                                    <?php if ($profileImage): ?>
                                        <img id="profileImageDisplay" src="../SOMANAP/uploads/profile_images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <svg id="defaultProfileIcon" class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <label for="profileImageInput" class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-2 cursor-pointer shadow-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </label>
                            </div>

                            <!-- Hidden File Input -->
                            <input type="file" id="profileImageInput" name="profile_image" accept="image/*" class="hidden" onchange="handleProfileImageSelect(event)">

                            <!-- Upload Info -->
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">Change Profile Picture</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">JPG, PNG, GIF, or WebP • Max 5MB</p>
                                <div class="flex gap-2">
                                    <button type="button" onclick="document.getElementById('profileImageInput').click()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg font-medium transition">
                                        Upload Photo
                                    </button>
                                    <?php if ($profileImage): ?>
                                        <button type="button" onclick="removeProfileImage()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg font-medium transition">
                                            Remove
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Image Preview for Upload -->
                        <div id="imagePreviewContainer" class="hidden mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <img id="imagePreview" src="" alt="Preview" class="max-w-xs rounded-lg">
                            <button type="submit" class="mt-3 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                                Confirm Upload
                            </button>
                            <button type="button" onclick="cancelProfileImageSelect()" class="mt-3 ml-2 px-4 py-2 bg-gray-400 hover:bg-gray-500 text-white rounded-lg font-medium transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Other Profile Information -->
                <form method="POST" class="space-y-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" placeholder="John Doe" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
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





            <!-- Color Theme Settings -->
            <div id="color-theme" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Color Theme</h2>

                <form id="colorThemeForm" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-4">Select Color Scheme</label>
                        <div class="space-y-4">
                            <!-- Autumn Theme -->
                            <label class="flex items-start gap-4 cursor-pointer p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-orange-400 dark:hover:border-orange-400 transition" onclick="selectColorTheme('autumn')">
                                <input type="radio" name="colorTheme" value="autumn" class="w-4 h-4 text-orange-600 mt-1">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Autumn</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Warm orange, amber, and red tones</p>
                                    <div class="flex gap-2 mt-3">
                                        <div class="w-8 h-8 bg-orange-500 rounded"></div>
                                        <div class="w-8 h-8 bg-amber-500 rounded"></div>
                                        <div class="w-8 h-8 bg-red-600 rounded"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Winter Theme -->
                            <label class="flex items-start gap-4 cursor-pointer p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-400 dark:hover:border-blue-400 transition" onclick="selectColorTheme('winter')">
                                <input type="radio" name="colorTheme" value="winter" class="w-4 h-4 text-blue-600 mt-1">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Winter</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cool blues, cyans, and ice tones</p>
                                    <div class="flex gap-2 mt-3">
                                        <div class="w-8 h-8 bg-blue-500 rounded"></div>
                                        <div class="w-8 h-8 bg-cyan-500 rounded"></div>
                                        <div class="w-8 h-8 bg-indigo-600 rounded"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Spring Theme -->
                            <label class="flex items-start gap-4 cursor-pointer p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-green-400 dark:hover:border-green-400 transition" onclick="selectColorTheme('spring')">
                                <input type="radio" name="colorTheme" value="spring" class="w-4 h-4 text-green-600 mt-1">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Spring</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Fresh greens, pinks, and pastels</p>
                                    <div class="flex gap-2 mt-3">
                                        <div class="w-8 h-8 bg-green-500 rounded"></div>
                                        <div class="w-8 h-8 bg-pink-500 rounded"></div>
                                        <div class="w-8 h-8 bg-emerald-600 rounded"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Summer Theme -->
                            <label class="flex items-start gap-4 cursor-pointer p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-yellow-400 dark:hover:border-yellow-400 transition" onclick="selectColorTheme('summer')">
                                <input type="radio" name="colorTheme" value="summer" class="w-4 h-4 text-yellow-600 mt-1">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Summer</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bright yellows, vibrant colors</p>
                                    <div class="flex gap-2 mt-3">
                                        <div class="w-8 h-8 bg-yellow-500 rounded"></div>
                                        <div class="w-8 h-8 bg-rose-500 rounded"></div>
                                        <div class="w-8 h-8 bg-yellow-600 rounded"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Monochrome Theme -->
                            <label class="flex items-start gap-4 cursor-pointer p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-400 transition" onclick="selectColorTheme('monochrome')">
                                <input type="radio" name="colorTheme" value="monochrome" class="w-4 h-4 text-gray-600 mt-1">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Monochrome</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Professional grays and neutral tones</p>
                                    <div class="flex gap-2 mt-3">
                                        <div class="w-8 h-8 bg-gray-400 rounded"></div>
                                        <div class="w-8 h-8 bg-gray-600 rounded"></div>
                                        <div class="w-8 h-8 bg-gray-800 rounded"></div>
                                    </div>
                                </div>
                            </label>

                            <!-- Gradient Theme -->
                            <label class="flex items-start gap-4 cursor-pointer p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg hover:border-blue-400 dark:hover:border-blue-400 transition" onclick="selectColorTheme('gradient')">
                                <input type="radio" name="colorTheme" value="gradient" class="w-4 h-4 text-blue-600 mt-1">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Gradient</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Blue view, white-to-red favorites, yellow edit, red delete</p>
                                    <div class="flex gap-2 mt-3">
                                        <div class="w-8 h-8 bg-blue-500 rounded"></div>
                                        <div class="w-8 h-8 bg-white border-2 border-gray-300 rounded"></div>
                                        <div class="w-8 h-8 bg-yellow-500 rounded"></div>
                                        <div class="w-8 h-8 bg-red-600 rounded"></div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
                            Apply Color Theme
                        </button>
                    </div>
                </form>
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
// Navigate to settings section from dropdown
function navigateToSection(value) {
    const section = document.querySelector(value);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

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
// Load color theme preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedColorTheme = localStorage.getItem('colorTheme') || 'autumn';
    const colorThemeRadios = document.querySelectorAll('input[name="colorTheme"]');
    
    colorThemeRadios.forEach(radio => {
        if (radio.value === savedColorTheme) {
            radio.checked = true;
        }
    });
    
    // Apply saved color theme
    applyColorThemeSettings(savedColorTheme);
});

// Handle color theme form submission
const colorThemeForm = document.getElementById('colorThemeForm');
if (colorThemeForm) {
    colorThemeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedColorTheme = document.querySelector('input[name="colorTheme"]:checked').value;
        
        // Apply theme immediately
        applyColorThemeSettings(selectedColorTheme);
        
        // Dispatch custom event so other tabs/windows and the current page can update
        window.dispatchEvent(new CustomEvent('colorThemeChanged', { detail: { theme: selectedColorTheme } }));
        
        // Show success message
        const button = colorThemeForm.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        button.textContent = '✓ Applied';
        button.classList.add('bg-green-600', 'hover:bg-green-700');
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    });
}

// Color theme definitions (same as master.php)
const colorThemesSettings = {
    autumn: {
        primary: '#ea580c',      // orange-500
        primaryRgb: '234, 88, 12',
        secondary: '#f59e0b',    // amber-500
        secondaryRgb: '245, 158, 11',
        accent: '#dc2626',       // red-600
        accentRgb: '220, 38, 38',
        danger: '#991b1b',       // red-900
        dangerRgb: '153, 27, 27',
        success: '#059669',      // emerald-600
        successRgb: '5, 150, 105',
        warning: '#d97706',      // amber-600
        warningRgb: '217, 119, 6',
        info: '#3b82f6',         // blue-500
        infoRgb: '59, 130, 246'
    },
    winter: {
        primary: '#3b82f6',      // blue-500
        primaryRgb: '59, 130, 246',
        secondary: '#06b6d4',    // cyan-500
        secondaryRgb: '6, 182, 212',
        accent: '#4f46e5',       // indigo-600
        accentRgb: '79, 70, 229',
        danger: '#dc2626',       // red-600
        dangerRgb: '220, 38, 38',
        success: '#059669',      // emerald-600
        successRgb: '5, 150, 105',
        warning: '#0284c7',      // sky-600
        warningRgb: '2, 132, 199',
        info: '#3b82f6',         // blue-500
        infoRgb: '59, 130, 246'
    },
    spring: {
        primary: '#10b981',      // emerald-500
        primaryRgb: '16, 185, 129',
        secondary: '#ec4899',    // pink-500
        secondaryRgb: '236, 72, 153',
        accent: '#059669',       // emerald-600
        accentRgb: '5, 150, 105',
        danger: '#dc2626',       // red-600
        dangerRgb: '220, 38, 38',
        success: '#10b981',      // emerald-500
        successRgb: '16, 185, 129',
        warning: '#f59e0b',      // amber-500
        warningRgb: '245, 158, 11',
        info: '#06b6d4',         // cyan-500
        infoRgb: '6, 182, 212'
    },
    summer: {
        primary: '#eab308',      // yellow-500
        primaryRgb: '234, 179, 8',
        secondary: '#f43f5e',    // rose-500
        secondaryRgb: '244, 63, 94',
        accent: '#ca8a04',       // yellow-600
        accentRgb: '202, 138, 4',
        danger: '#dc2626',       // red-600
        dangerRgb: '220, 38, 38',
        success: '#10b981',      // emerald-500
        successRgb: '16, 185, 129',
        warning: '#f97316',      // orange-500
        warningRgb: '249, 115, 22',
        info: '#fbbf24',         // amber-400
        infoRgb: '251, 191, 36'
    },
    monochrome: {
        primary: '#4b5563',      // gray-600
        primaryRgb: '75, 85, 99',
        secondary: '#6b7280',    // gray-500
        secondaryRgb: '107, 114, 128',
        accent: '#1f2937',       // gray-800
        accentRgb: '31, 41, 55',
        danger: '#374151',       // gray-700
        dangerRgb: '55, 65, 81',
        success: '#4b5563',      // gray-600
        successRgb: '75, 85, 99',
        warning: '#6b7280',      // gray-500
        warningRgb: '107, 114, 128',
        info: '#6b7280',         // gray-500
        infoRgb: '107, 114, 128'
    },
    gradient: {
        primary: '#3b82f6',      // blue-500 (view/default)
        primaryRgb: '59, 130, 246',
        secondary: '#ffffff',    // white (base for favorites - turns red on select)
        secondaryRgb: '255, 255, 255',
        accent: '#eab308',       // yellow-500 (edit)
        accentRgb: '234, 179, 8',
        danger: '#dc2626',       // red-600 (delete)
        dangerRgb: '220, 38, 38',
        success: '#10b981',      // emerald-500
        successRgb: '16, 185, 129',
        warning: '#eab308',      // yellow-500
        warningRgb: '234, 179, 8',
        info: '#3b82f6',         // blue-500
        infoRgb: '59, 130, 246'
    }
};

// Function to apply color theme - update CSS variables in root
function applyColorThemeSettings(themeName) {
    const theme = colorThemesSettings[themeName] || colorThemesSettings.autumn;
    const root = document.documentElement;
    
    // Set CSS variables matching master.php
    root.style.setProperty('--theme-primary', theme.primary);
    root.style.setProperty('--theme-primary-rgb', theme.primaryRgb);
    root.style.setProperty('--theme-secondary', theme.secondary);
    root.style.setProperty('--theme-secondary-rgb', theme.secondaryRgb);
    root.style.setProperty('--theme-accent', theme.accent);
    root.style.setProperty('--theme-accent-rgb', theme.accentRgb);
    root.style.setProperty('--theme-danger', theme.danger);
    root.style.setProperty('--theme-danger-rgb', theme.dangerRgb);
    root.style.setProperty('--theme-success', theme.success);
    root.style.setProperty('--theme-success-rgb', theme.successRgb);
    root.style.setProperty('--theme-warning', theme.warning);
    root.style.setProperty('--theme-warning-rgb', theme.warningRgb);
    root.style.setProperty('--theme-info', theme.info);
    root.style.setProperty('--theme-info-rgb', theme.infoRgb);

    // Store in localStorage for persistence
    localStorage.setItem('colorTheme', themeName);
}

// Select color theme function
function selectColorTheme(themeName) {
    const radio = document.querySelector(`input[name="colorTheme"][value="${themeName}"]`);
    if (radio) {
        radio.checked = true;
    }
}

// Load color theme on page load
window.addEventListener('load', function() {
    const savedColorTheme = localStorage.getItem('colorTheme') || 'autumn';
    applyColorTheme(savedColorTheme);
});

// Store and load theme preference
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

// Profile Image Upload Functions
function handleProfileImageSelect(event) {
    const file = event.target.files[0];
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
            event.target.value = '';
            return;
        }

        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File size must be less than 5MB');
            event.target.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('imagePreview');
            previewImg.src = e.target.result;
            document.getElementById('imagePreviewContainer').classList.remove('hidden');
            
            // Hide the default icon if showing
            const defaultIcon = document.getElementById('defaultProfileIcon');
            if (defaultIcon) {
                defaultIcon.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    }
}

function cancelProfileImageSelect() {
    document.getElementById('profileImageInput').value = '';
    document.getElementById('imagePreviewContainer').classList.add('hidden');
    
    // Show default icon if no profile image exists
    const defaultIcon = document.getElementById('defaultProfileIcon');
    if (defaultIcon && !document.getElementById('profileImageDisplay')) {
        defaultIcon.style.display = 'block';
    }
}

function removeProfileImage() {
    if (confirm('Are you sure you want to remove your profile image?')) {
        // We'll need to implement a delete endpoint, for now just submit empty form
        const form = document.getElementById('profileImageForm');
        const fileInput = document.getElementById('profileImageInput');
        fileInput.value = '';
        // Note: You may want to add a delete endpoint to handle actual removal
    }
}
</script>
