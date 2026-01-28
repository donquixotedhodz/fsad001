<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-300 bg-white border-r border-gray-200 lg:translate-x-0 dark:bg-gray-900 dark:border-gray-700 -translate-x-full lg:-translate-x-0">
    <!-- Sidebar Header -->
    <div class="h-20 flex items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <img src="app/views/partials/nealogo.png" alt="NEA" class="w-10 h-10 object-contain">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">IAQSMO</h2>
        </div>
        <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="lg:hidden text-gray-500 hover:text-gray-900 dark:hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="overflow-y-auto">
        <div class="px-4 py-6 space-y-2">
            <!-- Dashboard -->
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'dashboard' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m2 3l2-3m2 3l2-3m2 3l2-3m2 3l2-3M3 20l2-3m2 3l2-3m2 3l2-3m2 3l2-3"></path>
                </svg>
                <span class="font-medium">Dashboard</span>
            </a>

            <!-- MANAP Dropdown -->
            <div x-data="{ manap_open: <?php echo ($currentPage === 'documents' || $currentPage === 'reports' || $currentPage === 'manap_reports') ? 'true' : 'false'; ?> }">
                <button @click="manap_open = !manap_open" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'documents' || $currentPage === 'reports' || $currentPage === 'manap_reports') ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium flex-1 text-left">MANAP</span>
                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': manap_open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>

                <!-- MANAP Submenu -->
                <div x-show="manap_open" class="pl-6 space-y-1 mt-2">
                    <!-- Documents -->
                    <a href="documents.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'documents' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <span class="text-sm font-medium">Documents</span>
                    </a>
                    <!-- Reports -->
                    <a href="manap_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'manap_reports' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                </div>
            </div>

            <!-- PPE Provident Fund Dropdown - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <div x-data="{ ppe_open: <?php echo ($currentPage === 'ppe' || $currentPage === 'ppe_reports' || $currentPage === 'ppe_balance') ? 'true' : 'false'; ?> }">
                <button @click="ppe_open = !ppe_open" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo ($currentPage === 'ppe' || $currentPage === 'ppe_reports' || $currentPage === 'ppe_balance') ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium flex-1 text-left">PPE Provident Fund</span>
                    <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': ppe_open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </button>

                <!-- PPE Submenu -->
                <div x-show="ppe_open" class="pl-6 space-y-1 mt-2">
                    <!-- Remaining Balance -->
                    <a href="ppe_balance.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe_balance' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <span class="text-sm font-medium">Remaining Balance</span>
                    </a>
                    <!-- PPE Provident Fund -->
                    <a href="ppe.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <span class="text-sm font-medium">PPE Provident Fund</span>
                    </a>
                    <!-- Reports -->
                    <a href="ppe_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe_reports' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Divider -->
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Manage EC -->
            <a href="manage_ec.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'manage_ec' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-medium">Manage EC</span>
            </a>

            <!-- Divider -->
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Audit Log - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="audit_logs.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'audit_logs' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-medium">Audit Log</span>
            </a>
            <?php endif; ?>

            <!-- Settings - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'settings' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium">Settings</span>
            </a>
            <?php endif; ?>

            <!-- Divider -->
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Maintenance - Only for Superadmin -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'maintenance' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="font-medium">Maintenance</span>
            </a>
            <?php endif; ?>

            <!-- Logout -->
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-700 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="font-medium text-red-600 dark:text-red-400">Logout</span>
            </a>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <!-- <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div> -->
            <!-- <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($username ?? 'User'); ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">User Account</p>
            </div> -->
        </div>
    </div>
</aside>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-black bg-opacity-50 lg:hidden" @click="document.getElementById('sidebar').classList.toggle('-translate-x-full')"></div>
