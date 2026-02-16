<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | FSAD' : 'FSAD'; ?></title>
    <link rel="icon" type="image/x-icon" href="app/views/layouts/nealogo.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Color theme CSS variables */
        :root {
            --theme-primary: #ea580c;
            --theme-primary-rgb: 234, 88, 12;
            --theme-secondary: #f59e0b;
            --theme-secondary-rgb: 245, 158, 11;
            --theme-accent: #dc2626;
            --theme-accent-rgb: 220, 38, 38;
            --theme-danger: #991b1b;
            --theme-danger-rgb: 153, 27, 27;
            --theme-success: #059669;
            --theme-success-rgb: 5, 150, 105;
            --theme-warning: #d97706;
            --theme-warning-rgb: 217, 119, 6;
            --theme-info: #3b82f6;
            --theme-info-rgb: 59, 130, 246;
        }

        /* Dynamic theme button styles using CSS classes */
        .btn-primary {
            background-color: var(--theme-primary);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background-color: var(--theme-secondary);
            color: white;
        }

        .btn-secondary:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background-color: var(--theme-danger);
            color: white;
        }

        .btn-danger:hover {
            opacity: 0.9;
        }

        .btn-success {
            background-color: var(--theme-success);
            color: white;
        }

        .btn-success:hover {
            opacity: 0.9;
        }

        /* SweetAlert2 Light Mode Override */
        .swal2-light-mode {
            --swal2-backdrop: rgba(0, 0, 0, 0.4) !important;
            --swal2-white: #ffffff !important;
            --swal2-black: #000000 !important;
            --swal2-grey: #6c757d !important;
            --swal2-border-radius: 0.375rem !important;
            --swal2-box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }

        .swal2-light-mode .swal2-popup {
            background: #ffffff !important;
            color: #000000 !important;
        }

        .swal2-light-mode .swal2-title {
            color: #000000 !important;
        }

        .swal2-light-mode .swal2-html-container {
            color: #000000 !important;
        }

        .swal2-light-mode .swal2-confirm {
            background-color: #dc2626 !important;
        }

        .swal2-light-mode .swal2-cancel {
            background-color: #6b7280 !important;
        }

        /* Custom Tooltip Styles */
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }

        .custom-tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 8px;
            position: absolute;
            z-index: 1000;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            white-space: nowrap;
        }

        .custom-tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Dark mode tooltip */
        .dark .custom-tooltip .tooltip-text {
            background-color: #1f2937;
            color: #f9fafb;
        }

        .dark .custom-tooltip .tooltip-text::after {
            border-color: #1f2937 transparent transparent transparent;
        }
    </style>
    <script>
        // Set PDF worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Color theme definitions
        const colorThemes = {
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

        // Apply color theme to CSS variables
        function applyColorTheme(themeName) {
            const theme = colorThemes[themeName] || colorThemes.autumn;
            const root = document.documentElement;
            
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

        // Load and apply color theme on page load
        window.addEventListener('DOMContentLoaded', function() {
            const savedColorTheme = localStorage.getItem('colorTheme') || 'autumn';
            applyColorTheme(savedColorTheme);
        });

        // Listen for color theme changes from other tabs/windows
        window.addEventListener('storage', function(e) {
            if (e.key === 'colorTheme') {
                applyColorTheme(e.newValue || 'autumn');
            }
        });

        // Listen for color theme changes from custom events
        window.addEventListener('colorThemeChanged', function(e) {
            if (e.detail && e.detail.theme) {
                applyColorTheme(e.detail.theme);
            }
        });
    </script>
</head>
<body
    x-data="{ darkMode: false }"
    x-init="initTheme()"
    @keydown.window="if(event.key === 'd' && event.ctrlKey) { darkMode = !darkMode; applyDarkMode(); }"
    :class="{ 'dark bg-gray-900': darkMode === true }"
    class="bg-white dark:bg-gray-900"
>
    <!-- Sidebar -->
    <?php
$username = $_SESSION['username'] ?? 'User';
$currentPage = isset($_SESSION['currentPage']) ? $_SESSION['currentPage'] : 'dashboard';
$userId = $_SESSION['user_id'] ?? null;

// Get user's profile image, full name, and role
$userProfileImage = null;
$userFullName = $username;
$userRole = $_SESSION['role'] ?? 'User';

if ($userId) {
    try {
        $stmt = $conn->prepare("SELECT profile_image, full_name, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userInfo) {
            $userProfileImage = $userInfo['profile_image'] ?? null;
            $userFullName = $userInfo['full_name'] ?? $username;
            $userRole = $userInfo['role'] ?? $_SESSION['role'] ?? 'User';
        }
    }
    catch (PDOException $e) {
    // If query fails, use defaults
    }
}

// Fetch AOM notification count (unexpired AOMs within 15-day window)
$aomNotificationCount = 0;
$latestAoms = [];
try {
    $aomNotifyStmt = $conn->prepare("SELECT COUNT(*) as total FROM aom_table WHERE date IS NOT NULL AND DATE_ADD(date, INTERVAL 15 DAY) >= CURDATE()");
    $aomNotifyStmt->execute();
    $actualAomCount = (int)($aomNotifyStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Get the maximum ID to detect new AOMs
    $maxAomStmt = $conn->prepare("SELECT MAX(id) as max_id FROM aom_table");
    $maxAomStmt->execute();
    $maxAomId = (int)($maxAomStmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0);

    // Clear notifications if visiting aom.php
    if (basename($_SERVER['PHP_SELF']) === 'aom.php') {
        $_SESSION['last_seen_aom_max_id'] = $maxAomId;
        $_SESSION['last_seen_aom_count'] = $actualAomCount;
    }

    // Determine if we should show the badge
    $hasNewAom = $maxAomId > ($_SESSION['last_seen_aom_max_id'] ?? 0);
    $countIncreased = $actualAomCount > ($_SESSION['last_seen_aom_count'] ?? 0);

    if ($actualAomCount > 0 && ($hasNewAom || $countIncreased || !isset($_SESSION['last_seen_aom_count']))) {
        $aomNotificationCount = $actualAomCount;
    }
    else {
        $aomNotificationCount = 0;
    }

    if ($actualAomCount > 0) {
        $aomLatestStmt = $conn->prepare("SELECT item, title, date FROM aom_table WHERE date IS NOT NULL AND DATE_ADD(date, INTERVAL 15 DAY) >= CURDATE() ORDER BY date DESC LIMIT 3");
        $aomLatestStmt->execute();
        $latestAoms = $aomLatestStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
catch (PDOException $e) {
// Silent fail if table doesn't exist yet
}

require_once __DIR__ . '/../partials/sidebar.php';
?>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Top Header -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20">
            <div class="flex items-center justify-between h-20 px-4">
                <!-- Mobile Menu Button -->
                <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-900 dark:hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="flex-1"></div>

                <!-- Right Header Items -->
                <div class="flex items-center gap-6">
                    <!-- AOM Notification Bell -->
                    <div class="relative group">
                        <a href="aom.php" class="p-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors relative" title="AOM Notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <?php if ($aomNotificationCount > 0): ?>
                                <span class="absolute top-1 right-1 flex h-4 w-4">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-4 w-4 bg-red-600 text-[10px] text-white items-center justify-center font-bold">
                                        <?php echo $aomNotificationCount; ?>
                                    </span>
                                </span>
                            <?php
endif; ?>
                        </a>
                        
                        <!-- Notification Dropdown -->
                        <div class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl py-2 border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 transform origin-top-right">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">AOM Notifications</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <?php if ($aomNotificationCount > 0): ?>
                                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <?php foreach ($latestAoms as $aom):
        $aomDate = new DateTime($aom['date']);
        $targetDate = clone $aomDate;
        $targetDate->modify('+15 days');
        $now = new DateTime();
        $interval = $now->diff($targetDate);
        $daysLeft = $interval->invert ? 0 : $interval->days;
?>
                                            <a href="aom.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($aom['title']); ?></p>
                                                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                                                    Item: <?php echo htmlspecialchars($aom['item']); ?> • 
                                                    <span class="<?php echo $daysLeft <= 5 ? 'text-orange-500 font-bold' : 'text-green-500'; ?>">
                                                        <?php echo $daysLeft; ?> days left
                                                    </span>
                                                </p>
                                            </a>
                                        <?php
    endforeach; ?>
                                    </div>
                                    <?php if ($aomNotificationCount > 3): ?>
                                        <div class="px-4 py-2 text-center text-[10px] text-gray-500 border-t border-gray-100 dark:border-gray-700">
                                            + <?php echo($aomNotificationCount - 3); ?> more notifications
                                        </div>
                                    <?php
    endif; ?>
                                <?php
else: ?>
                                    <div class="p-4 text-sm text-center text-gray-500 dark:text-gray-400">
                                        No active AOM notifications.
                                    </div>
                                <?php
endif; ?>
                            </div>
                            <div class="border-t border-gray-100 dark:border-gray-700 px-4 py-2">
                                <a href="aom.php" class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">View AOM Details →</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Section -->
                    <div class="flex items-center gap-3 pl-6 border-l border-gray-200 dark:border-gray-700">
                        <!-- Profile Image -->
                        <a href="settings.php" class="block">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center overflow-hidden hover:ring-2 hover:ring-blue-400 transition">
                                <?php if ($userProfileImage): ?>
                                    <img src="uploads/profile_images/<?php echo htmlspecialchars($userProfileImage); ?>" alt="<?php echo htmlspecialchars($userFullName); ?>" class="w-full h-full object-cover">
                                <?php
else: ?>
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                <?php
endif; ?>
                            </div>
                        </a>
                        
                        <!-- User Info -->
                        <a href="settings.php" class="hidden sm:block hover:text-blue-600 dark:hover:text-blue-400 transition">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($userFullName); ?></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars(ucfirst($userRole)); ?></span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 lg:p-4 pb-20">
            <?php echo $content; ?>
        </main>
    </div>

    <script>
    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'system';
        applyTheme(savedTheme);
        
        // Set initial darkMode value based on theme
        if (savedTheme === 'dark') {
            this.darkMode = true;
        } else if (savedTheme === 'light') {
            this.darkMode = false;
        } else if (savedTheme === 'system') {
            this.darkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
    }
    
    function applyDarkMode() {
        const isDark = document.body.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'light' : 'dark');
        
        if (isDark) {
            document.documentElement.classList.remove('dark');
        } else {
            document.documentElement.classList.add('dark');
        }
    }
    
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
    
    // Apply theme on page load
    window.addEventListener('load', function() {
        const savedTheme = localStorage.getItem('theme') || 'system';
        applyTheme(savedTheme);
    });

    // Sidebar toggle function for mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const isHidden = sidebar.classList.contains('-translate-x-full');
        
        if (isHidden) {
            // Show sidebar and overlay
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        } else {
            // Hide sidebar and overlay
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    // Logout confirmation function
    function confirmLogout() {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You will be logged out and redirected to the sign-in page.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
    </script>

    <!-- Footer -->
    <!-- <footer class="fixed bottom-0 right-0 lg:ml-64 w-full lg:w-auto lg:flex-1">
        <div class="flex flex-col items-center justify-between gap-4 px-6 py-4 sm:flex-row">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                &copy; 2026 donquixotedhodz. All rights reserved.
            </p>
        </div>
    </footer> -->
</body>
</html>
