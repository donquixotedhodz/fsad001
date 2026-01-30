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
        } catch (PDOException $e) {
            // If query fails, use defaults
        }
    }
    
    require_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Top Header -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20">
            <div class="flex items-center justify-between h-20 px-6">
                <div class="flex-1"></div>

                <!-- Right Header Items -->
                <div class="flex items-center gap-6">
                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode" class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"></path>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>

                    <!-- User Profile Section -->
                    <div class="flex items-center gap-3 pl-6 border-l border-gray-200 dark:border-gray-700">
                        <!-- Profile Image -->
                        <a href="settings.php" class="block">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center overflow-hidden hover:ring-2 hover:ring-blue-400 transition">
                                <?php if ($userProfileImage): ?>
                                    <img src="uploads/profile_images/<?php echo htmlspecialchars($userProfileImage); ?>" alt="<?php echo htmlspecialchars($userFullName); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                <?php endif; ?>
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
        <main class="p-6 lg:p-8 pb-20">
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
