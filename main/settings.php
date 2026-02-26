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
$user = null;
$profileImage = null;
$fullName = '';
$usernameDisplay = '';
$role = '';
$createdAt = '';
$email = 'Not set';
$phone = 'Not set';

if ($userId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $profileImage = $user['profile_image'] ?? null;
            $fullName = $user['full_name'] ?? '';
            $usernameDisplay = $user['username'] ?? '';
            $role = $user['role'] ?? 'staff';
            $createdAt = $user['created_at'] ?? '';
            $email = $user['email'] ?? 'Not set';
            $phone = $user['phone'] ?? 'Not set';
        }
    }
    catch (PDOException $e) {
    // Fallback or log
    }
}

// Ensure app settings table exists for system-level preferences
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
}
catch (Exception $e) {
}

$isSuperadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';

$chatbotSettings = [
    'chatbot_enabled' => '1',
    'chatbot_match_threshold' => '0.35',
    'chatbot_related_limit' => '3'
];

try {
    $defaultRows = [
        ['chatbot_enabled', '1'],
        ['chatbot_match_threshold', '0.35'],
        ['chatbot_related_limit', '3']
    ];

    $seedStmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = setting_value");
    foreach ($defaultRows as $row) {
        $seedStmt->execute($row);
    }

    $settingsStmt = $conn->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('chatbot_enabled', 'chatbot_match_threshold', 'chatbot_related_limit')");
    $settingsStmt->execute();
    $fetchedSettings = $settingsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($fetchedSettings as $row) {
        $chatbotSettings[$row['setting_key']] = $row['setting_value'];
    }
}
catch (Exception $e) {
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_chatbot_settings') {
    if (!$isSuperadmin) {
        $_SESSION['errorMessage'] = 'Only Superadmin can update chatbot settings.';
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $chatbotEnabled = isset($_POST['chatbot_enabled']) && $_POST['chatbot_enabled'] === '1' ? '1' : '0';
    $chatbotThreshold = isset($_POST['chatbot_match_threshold']) ? (float) $_POST['chatbot_match_threshold'] : 0.35;
    $chatbotRelatedLimit = isset($_POST['chatbot_related_limit']) ? (int) $_POST['chatbot_related_limit'] : 3;

    if ($chatbotThreshold < 0.10) {
        $chatbotThreshold = 0.10;
    }
    if ($chatbotThreshold > 0.95) {
        $chatbotThreshold = 0.95;
    }

    if ($chatbotRelatedLimit < 1) {
        $chatbotRelatedLimit = 1;
    }
    if ($chatbotRelatedLimit > 10) {
        $chatbotRelatedLimit = 10;
    }

    try {
        $updateStmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $updateStmt->execute(['chatbot_enabled', $chatbotEnabled]);
        $updateStmt->execute(['chatbot_match_threshold', number_format($chatbotThreshold, 2, '.', '')]);
        $updateStmt->execute(['chatbot_related_limit', (string) $chatbotRelatedLimit]);

        $_SESSION['successMessage'] = 'Chatbot settings updated successfully!';
    }
    catch (Exception $e) {
        $_SESSION['errorMessage'] = 'Error updating chatbot settings: ' . $e->getMessage();
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle Update Profile Information
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $newFullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $newUsername = isset($_POST['username']) ? trim($_POST['username']) : '';

    if ($userId && !empty($newFullName) && !empty($newUsername)) {
        try {
            // Check if username is already taken by another user
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$newUsername, $userId]);
            if ($checkStmt->fetch()) {
                $errorMessage = "Username is already taken!";
            }
            else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
                $stmt->execute([$newFullName, $newUsername, $userId]);
                $fullName = $newFullName;
                $usernameDisplay = $newUsername;
                $_SESSION['username'] = $newUsername;
                $successMessage = "Profile information updated successfully!";
            }
        }
        catch (PDOException $e) {
            $errorMessage = "Error updating profile: " . $e->getMessage();
        }
    }
    elseif (empty($newFullName) || empty($newUsername)) {
        $errorMessage = "Full name and Username cannot be empty!";
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
            }
            elseif ($fileSize > $maxFileSize) {
                $errorMessage = "File size exceeds 5MB limit.";
            }
            else {
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
                    }
                    else {
                        $errorMessage = "Failed to upload file.";
                    }
                }
                catch (Exception $e) {
                    $errorMessage = "Error uploading profile image: " . $e->getMessage();
                }
            }
        }
        else {
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
        }
        catch (PDOException $e) {
            $errorMessage = "Error adding staff: " . $e->getMessage();
        }
    }
    else {
        $errorMessage = "Name, Position, Username, and Password are required!";
    }
}

ob_start();
?>

<div class="w-full">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Admin Profile</h1>
            <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">View and manage your account details</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button onclick="document.getElementById('editProfileModal').showModal()" class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-bold shadow-sm text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Edit Profile
            </button>
            <button onclick="document.getElementById('passwordModal').showModal()" class="inline-flex items-center gap-2 px-5 py-2 bg-[#eef4ff] text-[#3466f1] rounded-lg hover:bg-blue-100 transition font-bold shadow-sm text-sm border border-transparent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                Change Password
            </button>
            <button onclick="document.getElementById('colorThemeModal').showModal()" class="p-2 bg-gray-50 dark:bg-gray-700 text-gray-400 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition shadow-sm border border-gray-100 dark:border-gray-600" title="Theme Settings">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </button>
        </div>
    </div>

    <!-- Main Settings Design -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Left: Profile Summary Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 dark:border-gray-700 p-10 flex flex-col items-center">
            <div class="relative group mb-8">
                <!-- Double Blue Border Ring around profile image - Match precisely -->
                <div class="w-52 h-52 rounded-full border-4 border-blue-600 p-1 bg-white dark:bg-gray-800 shadow-lg">
                    <div class="w-full h-full rounded-full border-4 border-blue-50 overflow-hidden bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                        <?php if ($profileImage): ?>
                            <img src="../main/uploads/profile_images/<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php
else: ?>
                            <svg class="w-24 h-24 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                            </svg>
                        <?php
endif; ?>
                    </div>
                </div>
                <!-- Mini Upload Button -->
                <button onclick="document.getElementById('profileImageInput').click()" class="absolute bottom-4 right-4 bg-blue-600 text-white p-2.5 rounded-full shadow-lg hover:bg-blue-700 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </button>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($fullName); ?></h2>
            <p class="text-gray-500 dark:text-gray-400 font-medium mt-1">Administrator</p>

            <form id="profileImageForm" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="hidden" name="action" value="upload_profile_image">
                <input type="file" id="profileImageInput" name="profile_image" accept="image/*" onchange="handleProfileImageSelect(event)">
            </form>
        </div>

        <!-- Right: Account Information Card -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-50 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Account Information</h3>
            </div>
            
            <div class="px-8 py-4 divide-y divide-gray-100 dark:divide-gray-700">
                <div class="flex py-6">
                    <span class="text-gray-500 dark:text-gray-400 font-medium w-full md:w-1/3 text-sm">Full Name</span>
                    <span class="text-gray-900 dark:text-white font-medium flex-1 text-sm"><?php echo htmlspecialchars($fullName); ?></span>
                </div>

                <div class="flex py-6">
                    <span class="text-gray-500 dark:text-gray-400 font-medium w-full md:w-1/3 text-sm">Username</span>
                    <span class="text-gray-900 dark:text-white font-medium flex-1 text-sm"><?php echo htmlspecialchars($usernameDisplay); ?></span>
                </div>

                <div class="flex py-6">
                    <span class="text-gray-500 dark:text-gray-400 font-medium w-full md:w-1/3 text-sm">Email</span>
                    <span class="text-gray-900 dark:text-white font-medium flex-1 text-sm"><?php echo htmlspecialchars($email); ?></span>
                </div>

                <div class="flex py-6">
                    <span class="text-gray-500 dark:text-gray-400 font-medium w-full md:w-1/3 text-sm">Phone</span>
                    <span class="text-gray-900 dark:text-white font-medium flex-1 text-sm"><?php echo htmlspecialchars($phone); ?></span>
                </div>

                <!-- <div class="flex py-6">
                    <span class="text-gray-500 dark:text-gray-400 font-medium w-full md:w-1/3 text-sm">Member Since</span>
                    <span class="text-gray-900 dark:text-white font-medium flex-1 text-sm"><?php echo !empty($createdAt) ? date('F d, Y', strtotime($createdAt)) : 'August 10, 2025'; ?></span>
                </div> -->
            </div>
        </div>
    </div>

    <!-- Chatbot Settings -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">FAQ Chatbot Settings</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure chatbot visibility and answer matching behavior.</p>
            </div>
            <?php if (!$isSuperadmin): ?>
                <span class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Read-only</span>
            <?php endif; ?>
        </div>

        <div class="p-8">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_chatbot_settings">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Enable Chatbot</label>
                        <select name="chatbot_enabled" <?php echo !$isSuperadmin ? 'disabled' : ''; ?> class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1" <?php echo ($chatbotSettings['chatbot_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                            <option value="0" <?php echo ($chatbotSettings['chatbot_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Match Threshold (0.10 - 0.95)</label>
                        <input type="number" step="0.01" min="0.10" max="0.95" name="chatbot_match_threshold" value="<?php echo htmlspecialchars($chatbotSettings['chatbot_match_threshold'] ?? '0.35'); ?>" <?php echo !$isSuperadmin ? 'disabled' : ''; ?> class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Related Questions Limit (1 - 10)</label>
                        <input type="number" min="1" max="10" name="chatbot_related_limit" value="<?php echo htmlspecialchars($chatbotSettings['chatbot_related_limit'] ?? '3'); ?>" <?php echo !$isSuperadmin ? 'disabled' : ''; ?> class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        <strong>Tip:</strong> Lower threshold gives more answers (broader match), higher threshold gives stricter answers.
                    </p>
                </div>

                <?php if ($isSuperadmin): ?>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition shadow-lg shadow-blue-500/20">
                        Save Chatbot Settings
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Modals Container -->
<div id="allModals">
    <!-- Edit Profile Modal -->
    <dialog id="editProfileModal" class="rounded-2xl shadow-2xl max-w-xl w-full p-0 overflow-hidden dark:bg-gray-800 backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/20">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Edit Profile</h2>
            <button onclick="this.closest('dialog').close()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-8">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_profile">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($usernameDisplay); ?>" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <!-- Email and Phone placeholders for future functionality -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Phone Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($phone); ?>" disabled class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400">
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition shadow-lg shadow-blue-500/20">
                        Save Changes
                    </button>
                    <button type="button" onclick="this.closest('dialog').close()" class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white rounded-xl font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Change Password Modal -->
    <dialog id="passwordModal" class="rounded-2xl shadow-2xl max-w-md w-full p-0 overflow-hidden dark:bg-gray-800 backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/20">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Change Password</h2>
            <button onclick="this.closest('dialog').close()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-8">
            <form id="passwordForm" class="space-y-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Current Password</label>
                        <div class="relative">
                            <input type="password" id="currentPassword" placeholder="••••••••" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('currentPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 show-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hide-icon hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">New Password</label>
                        <div class="relative">
                            <input type="password" id="newPassword" placeholder="••••••••" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('newPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 show-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hide-icon hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" id="confirmPassword" placeholder="••••••••" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('confirmPassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 show-icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 hide-icon hidden">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition shadow-lg shadow-blue-500/20">Update Password</button>
                    <button type="button" onclick="this.closest('dialog').close()" class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white rounded-xl font-bold transition">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Color Theme Modal -->
    <dialog id="colorThemeModal" class="rounded-2xl shadow-2xl max-w-2xl w-full p-0 overflow-hidden dark:bg-gray-800 backdrop:bg-black/50 backdrop:backdrop-blur-sm">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/20">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Customize Appearance</h2>
            <button onclick="this.closest('dialog').close()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-8 max-h-[70vh] overflow-y-auto">
            <form id="colorThemeForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-6">Select Your Favorite Color Scheme</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Theme options -->
                        <label class="group relative flex items-center gap-4 cursor-pointer p-4 border-2 border-transparent bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-white dark:hover:bg-gray-700 hover:border-blue-500 transition-all shadow-sm" onclick="selectColorTheme('autumn')">
                            <input type="radio" name="colorTheme" value="autumn" class="hidden">
                            <div class="w-12 h-12 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">A</div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Autumn</p>
                                <div class="flex gap-1 mt-1">
                                    <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-red-600 rounded-full"></div>
                                </div>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 cursor-pointer p-4 border-2 border-transparent bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-white dark:hover:bg-gray-700 hover:border-blue-500 transition-all shadow-sm" onclick="selectColorTheme('winter')">
                            <input type="radio" name="colorTheme" value="winter" class="hidden">
                            <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">W</div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Winter</p>
                                <div class="flex gap-1 mt-1">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-cyan-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-indigo-600 rounded-full"></div>
                                </div>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 cursor-pointer p-4 border-2 border-transparent bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-white dark:hover:bg-gray-700 hover:border-green-500 transition-all shadow-sm" onclick="selectColorTheme('spring')">
                            <input type="radio" name="colorTheme" value="spring" class="hidden">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">S</div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Spring</p>
                                <div class="flex gap-1 mt-1">
                                    <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-pink-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-emerald-700 rounded-full"></div>
                                </div>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 cursor-pointer p-4 border-2 border-transparent bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-white dark:hover:bg-gray-700 hover:border-yellow-500 transition-all shadow-sm" onclick="selectColorTheme('summer')">
                            <input type="radio" name="colorTheme" value="summer" class="hidden">
                            <div class="w-12 h-12 rounded-xl bg-yellow-500 flex items-center justify-center text-white shadow-lg shadow-yellow-500/20">S</div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Summer</p>
                                <div class="flex gap-1 mt-1">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-rose-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-700 rounded-full"></div>
                                </div>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 cursor-pointer p-4 border-2 border-transparent bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-white dark:hover:bg-gray-700 hover:border-gray-500 transition-all shadow-sm" onclick="selectColorTheme('monochrome')">
                            <input type="radio" name="colorTheme" value="monochrome" class="hidden">
                            <div class="w-12 h-12 rounded-xl bg-gray-600 flex items-center justify-center text-white shadow-lg shadow-gray-600/20">M</div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Monochrome</p>
                                <div class="flex gap-1 mt-1">
                                    <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                    <div class="w-3 h-3 bg-gray-600 rounded-full"></div>
                                    <div class="w-3 h-3 bg-gray-800 rounded-full"></div>
                                </div>
                            </div>
                        </label>

                        <label class="group relative flex items-center gap-4 cursor-pointer p-4 border-2 border-transparent bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-white dark:hover:bg-gray-700 hover:border-blue-500 transition-all shadow-sm" onclick="selectColorTheme('gradient')">
                            <input type="radio" name="colorTheme" value="gradient" class="hidden">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow-lg">G</div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Gradient</p>
                                <div class="flex gap-1 mt-1">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-white border border-gray-300 rounded-full"></div>
                                    <div class="w-3 h-3 bg-red-600 rounded-full"></div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="flex gap-4 pt-6">
                    <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition shadow-lg shadow-blue-500/20">Apply Selected Theme</button>
                    <button type="button" onclick="this.closest('dialog').close()" class="flex-1 px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white rounded-xl font-bold transition">Cancel</button>
                </div>
            </form>
        </div>
    </dialog>
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
// Handle settings actions from dropdown
function handleDropdownAction(value) {
    if (!value) return;
    
    if (value === 'profile') {
        document.getElementById('profile').scrollIntoView({ behavior: 'smooth' });
    } else {
        const modal = document.getElementById(value);
        if (modal) {
            modal.showModal();
        }
    }
}

// Navigate to settings section from dropdown (legacy support)
function navigateToSection(value) {
    const sectionId = value.startsWith('#') ? value.substring(1) : value;
    const section = document.getElementById(sectionId);
    if (section) {
        if (section.tagName === 'DIALOG') {
            section.showModal();
        } else {
            section.scrollIntoView({ behavior: 'smooth' });
        }
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
    Swal.fire({
        title: 'Delete User',
        text: `Are you sure you want to delete user "${username}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Coming Soon', 'User deletion feature is under development.', 'info');
        }
    });
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
    Swal.fire({
        title: 'Delete Staff',
        text: `Are you sure you want to delete staff "${name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Coming Soon', 'Staff deletion feature is under development.', 'info');
        }
    });
}

document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire('Coming Soon', 'User edit feature is under development.', 'info');
    closeEditModal();
});

document.getElementById('editStaffForm').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire('Coming Soon', 'Staff edit feature is under development.', 'info');
    closeEditStaffModal();
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
require_once __DIR__ . '/app/views/layouts/master.php';
?>

<script src="../main/app/js/ChartColorHelper.js"></script>
<script>
// Load color theme preference on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedColorTheme = localStorage.getItem('colorTheme') || 'autumn';
    
    // Apply saved color theme
    applyColorThemeSettings(savedColorTheme);
    
    // Highlight original selection
    selectColorTheme(savedColorTheme);
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
        
        // Dispatch ChartColorHelper theme change event for chart updates
        if (typeof ChartColorHelper !== 'undefined') {
            ChartColorHelper.dispatchThemeChange(selectedColorTheme);
        }
        
        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Theme Applied',
            text: 'Your color theme preferences have been updated successfully.',
            confirmButtonColor: '#3b82f6'
        });
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
// Select color theme function
function selectColorTheme(themeName) {
    // Update radio buttons
    const radio = document.querySelector(`input[name="colorTheme"][value="${themeName}"]`);
    if (radio) {
        radio.checked = true;
    }

    // Update visual highlighting
    const allLabels = document.querySelectorAll('#colorThemeForm label');
    allLabels.forEach(label => {
        const input = label.querySelector('input[name="colorTheme"]');
        if (input && input.value === themeName) {
            label.classList.add('border-blue-500', 'ring-2', 'ring-blue-100', 'dark:ring-blue-900/20', 'bg-white', 'dark:bg-gray-700');
            label.classList.remove('border-transparent', 'bg-gray-50', 'dark:bg-gray-700/50');
        } else {
            label.classList.remove('border-blue-500', 'ring-2', 'ring-blue-100', 'dark:ring-blue-900/20', 'bg-white', 'dark:bg-gray-700');
            label.classList.add('border-transparent', 'bg-gray-50', 'dark:bg-gray-700/50');
        }
    });
}

// Load color theme on page load
window.addEventListener('load', function() {
    const savedColorTheme = localStorage.getItem('colorTheme') || 'autumn';
    applyColorThemeSettings(savedColorTheme);
    selectColorTheme(savedColorTheme);
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
        Swal.fire({
            icon: 'success',
            title: 'Preferences Saved',
            text: 'Your appearance settings have been updated.',
            confirmButtonColor: '#3b82f6'
        });
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
            Swal.fire('Invalid File', 'Please select a valid image file (JPG, PNG, GIF, or WebP)', 'error');
            event.target.value = '';
            return;
        }

        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            Swal.fire('File Too Large', 'File size must be less than 5MB', 'error');
            event.target.value = '';
            return;
        }

        // Show preview in SweetAlert2 confirmation
        const reader = new FileReader();
        reader.onload = function(e) {
            Swal.fire({
                title: 'Update Profile Image',
                text: 'Do you want to use this image for your profile?',
                imageUrl: e.target.result,
                imageWidth: 200,
                imageHeight: 200,
                imageAlt: 'Profile Preview',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, upload it!',
                cancelButtonText: 'Cancel',
                imageClass: 'rounded-full border-4 border-blue-100 shadow-lg'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Uploading...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('profileImageForm').submit();
                } else {
                    event.target.value = ''; // Reset if cancelled
                }
            });
        };
        reader.readAsDataURL(file);
    }
}

function removeProfileImage() {
    Swal.fire({
        title: 'Remove Profile Image?',
        text: 'Are you sure you want to remove your profile image and go back to the default icon?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Coming Soon', 'Profile image removal feature is under development.', 'info');
            // Logic for removal would go here:
            // window.location.href = 'settings.php?action=remove_image';
        }
    });
}

// Handle password form submission
const passwordForm = document.getElementById('passwordForm');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire('Coming Soon', 'Password update feature is under development.', 'info');
        this.closest('dialog').close();
    });
}

// Global SweetAlert2 notification handler for PHP messages
window.addEventListener('load', function() {
    <?php
$displaySuccess = $_SESSION['successMessage'] ?? $successMessage ?? null;
$displayError = $_SESSION['errorMessage'] ?? $errorMessage ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);
?>

    <?php if ($displaySuccess): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: <?php echo json_encode($displaySuccess); ?>,
            confirmButtonColor: '#3b82f6'
        });
    <?php
endif; ?>

    <?php if ($displayError): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: <?php echo json_encode($displayError); ?>,
            confirmButtonColor: '#3b82f6'
        });
    <?php
endif; ?>
});

function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const showIcon = button.querySelector('.show-icon');
    const hideIcon = button.querySelector('.hide-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        showIcon.classList.add('hidden');
        hideIcon.classList.remove('hidden');
    } else {
        input.type = 'password';
        showIcon.classList.remove('hidden');
        hideIcon.classList.add('hidden');
    }
}
</script>
