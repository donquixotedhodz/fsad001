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
            --theme-secondary: #f59e0b;
            --theme-accent: #dc2626;
            --theme-danger: #991b1b;
            --theme-success: #059669;
            --theme-warning: #d97706;
            --theme-info: #3b82f6;
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
                secondary: '#f59e0b',    // amber-500
                accent: '#dc2626',       // red-600
                danger: '#991b1b',       // red-900
                success: '#059669',      // emerald-600
                warning: '#d97706',      // amber-600
                info: '#3b82f6'          // blue-500
            },
            winter: {
                primary: '#3b82f6',      // blue-500
                secondary: '#06b6d4',    // cyan-500
                accent: '#4f46e5',       // indigo-600
                danger: '#dc2626',       // red-600
                success: '#059669',      // emerald-600
                warning: '#0284c7',      // sky-600
                info: '#3b82f6'          // blue-500
            },
            spring: {
                primary: '#10b981',      // emerald-500
                secondary: '#ec4899',    // pink-500
                accent: '#059669',       // emerald-600
                danger: '#dc2626',       // red-600
                success: '#10b981',      // emerald-500
                warning: '#f59e0b',      // amber-500
                info: '#06b6d4'          // cyan-500
            },
            summer: {
                primary: '#eab308',      // yellow-500
                secondary: '#f43f5e',    // rose-500
                accent: '#ca8a04',       // yellow-600
                danger: '#dc2626',       // red-600
                success: '#10b981',      // emerald-500
                warning: '#f97316',      // orange-500
                info: '#fbbf24'          // amber-400
            },
            monochrome: {
                primary: '#4b5563',      // gray-600
                secondary: '#6b7280',    // gray-500
                accent: '#1f2937',       // gray-800
                danger: '#374151',       // gray-700
                success: '#4b5563',      // gray-600
                warning: '#6b7280',      // gray-500
                info: '#6b7280'          // gray-500
            }
        };

        // Apply color theme to CSS variables
        function applyColorTheme(themeName) {
            const theme = colorThemes[themeName] || colorThemes.autumn;
            const root = document.documentElement;
            
            root.style.setProperty('--theme-primary', theme.primary);
            root.style.setProperty('--theme-secondary', theme.secondary);
            root.style.setProperty('--theme-accent', theme.accent);
            root.style.setProperty('--theme-danger', theme.danger);
            root.style.setProperty('--theme-success', theme.success);
            root.style.setProperty('--theme-warning', theme.warning);
            root.style.setProperty('--theme-info', theme.info);

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
    x-data="{ darkMode: false, sidebarOpen: false }"
    x-init="initTheme()"
    @keydown.window="if(event.key === 'd' && event.ctrlKey) { darkMode = !darkMode; applyDarkMode(); }"
    :class="{ 'dark bg-gray-900': darkMode === true }"
    class="bg-white dark:bg-gray-900"
>
    <!-- Sidebar -->
    <?php
    $username = $_SESSION['username'] ?? 'User';
    $currentPage = isset($_SESSION['currentPage']) ? $_SESSION['currentPage'] : 'dashboard';
    require_once __DIR__ . '/../partials/sidebar.php';
    ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Top Header -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-20">
            <div class="flex items-center justify-between h-20 px-6">
                <!-- Mobile Menu Button -->
                <button @click="document.getElementById('sidebar').classList.toggle('-translate-x-full'); document.getElementById('sidebarOverlay').classList.toggle('hidden')" class="lg:hidden text-gray-500 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

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
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6 lg:p-8">
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
</body>
</html>
