<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-300 bg-white border-r border-gray-200 lg:translate-x-0 dark:bg-gray-900 dark:border-gray-700 -translate-x-full lg:-translate-x-0">
    <!-- Sidebar Header -->
    <div class="h-20 flex items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <img src="app/views/partials/nealogo.png" alt="NEA" class="w-10 h-10 object-contain">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">IAQSMO</h2>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-900 dark:hover:text-white">
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                </svg>
                <span class="font-medium">Dashboard</span>
            </a>

            <!-- MANAP Dropdown -->
            <div x-data="{ manap_open: <?php echo($currentPage === 'documents' || $currentPage === 'favorites' || $currentPage === 'manap_reports') ? 'true' : 'false'; ?> }">
                <button @click="if (sidebarCollapsed) { sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', false); setTimeout(() => { manap_open = !manap_open }, 300); } else { manap_open = !manap_open }" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo($currentPage === 'documents' || $currentPage === 'favorites' || $currentPage === 'manap_reports') ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium flex-1 text-left">MANAP</span>
                    <svg x-show="!sidebarCollapsed" class="w-5 h-5 transition-transform" :class="{ 'rotate-180': manap_open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <!-- MANAP Submenu -->
                <div x-cloak x-show="manap_open && !sidebarCollapsed" x-collapse class="pl-6 space-y-1 mt-2">
                    <!-- Documents -->
                    <a href="documents.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'documents' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Documents</span>
                    </a>
                    <!-- Favorites -->
                    <a href="favorites.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'favorites' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        <span class="text-sm font-medium">Favorites</span>
                    </a>
                    <!-- Reports -->
                    <a href="manap_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'manap_reports' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                </div>
            </div>

            <!-- PPE Provident Fund Dropdown - Only for Superadmin -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
            <div x-data="{ ppe_open: <?php echo($currentPage === 'ppe' || $currentPage === 'ppe_reports' || $currentPage === 'ppe_balance' || $currentPage === 'ppe_print') ? 'true' : 'false'; ?> }">
                <button @click="if (sidebarCollapsed) { sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', false); setTimeout(() => { ppe_open = !ppe_open }, 300); } else { ppe_open = !ppe_open }" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo($currentPage === 'ppe' || $currentPage === 'ppe_reports' || $currentPage === 'ppe_balance' || $currentPage === 'ppe_print') ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                    <span class="font-medium flex-1 text-left">PPE Provident Fund</span>
                    <svg x-show="!sidebarCollapsed" class="w-5 h-5 transition-transform" :class="{ 'rotate-180': ppe_open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <!-- PPE Submenu -->
                <div x-cloak x-show="ppe_open && !sidebarCollapsed" x-collapse class="pl-6 space-y-1 mt-2">
                    <!-- Remaining Balance -->
                    <a href="ppe_balance.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe_balance' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="text-sm font-medium">Remaining Balance</span>
                    </a>
                    <!-- PPE Provident Fund -->
                    <a href="ppe.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <span class="text-sm font-medium">PPE Provident Fund</span>
                    </a>
                    <!-- Reports -->
                    <a href="ppe_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe_reports' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                    <!-- Print PPE -->
                    <a href="ppe_print.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ppe_print' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span class="text-sm font-medium">Print PPE</span>
                    </a>
                </div>
            </div>
            <?php
endif; ?>

            <!-- AD Scorecard Dropdown -->
            <div x-data="{ ads_open: <?php echo($currentPage === 'ads' || $currentPage === 'ad_scorecard_reports') ? 'true' : 'false'; ?> }">
                <button @click="if (sidebarCollapsed) { sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', false); setTimeout(() => { ads_open = !ads_open }, 300); } else { ads_open = !ads_open }" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo($currentPage === 'ads' || $currentPage === 'ad_scorecard_reports') ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="font-medium flex-1 text-left">AD Scorecard</span>
                    <svg x-show="!sidebarCollapsed" class="w-5 h-5 transition-transform" :class="{ 'rotate-180': ads_open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <!-- AD Scorecard Submenu -->
                <div x-cloak x-show="ads_open && !sidebarCollapsed" x-collapse class="pl-6 space-y-1 mt-2">
                    <!-- Documents -->
                    <a href="ad_scorecard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ads' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Documents</span>
                    </a>
                    <!-- Reports -->
                    <a href="ad_scorecard_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'ad_scorecard_reports' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                </div>
            </div>

            <!-- AOM Dropdown -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <div x-data="{ aom_open: <?php echo($currentPage === 'aom' || $currentPage === 'aom_reports') ? 'true' : 'false'; ?> }">
                <button @click="if (sidebarCollapsed) { sidebarCollapsed = false; localStorage.setItem('sidebarCollapsed', false); setTimeout(() => { aom_open = !aom_open }, 300); } else { aom_open = !aom_open }" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo($currentPage === 'aom' || $currentPage === 'aom_reports') ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                    <span class="font-medium flex-1 text-left">AOM</span>
                    <svg x-show="!sidebarCollapsed" class="w-5 h-5 transition-transform" :class="{ 'rotate-180': aom_open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <!-- AOM Submenu -->
                <div x-cloak x-show="aom_open && !sidebarCollapsed" x-collapse class="pl-6 space-y-1 mt-2">
                    <!-- Documents -->
                    <a href="aom.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'aom' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Documents</span>
                    </a>
                    <!-- Reports -->
                    <a href="aom_reports.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'aom_reports' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                </div>
            </div>
            <?php
endif; ?>

            <!-- Divider -->
            <!-- Divider -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>
            <?php
endif; ?>

            <!-- Manage EC -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="manage_ec.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'manage_ec' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="font-medium">Manage EC</span>
            </a>
            <?php
endif; ?>

            <!-- Manage Users - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="manage_users.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'manage_users' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span class="font-medium">Manage Users</span>
            </a>
            <?php
endif; ?>

            <!-- Manage Departments - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="manage_departments.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'manage_departments' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                <span class="font-medium">Manage Departments</span>
            </a>
            <?php
endif; ?>

            <!-- Divider -->
            <!-- Divider -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>
            <?php
endif; ?>

            <!-- Audit Log - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="audit_logs.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'audit_logs' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <span class="font-medium">Audit Log</span>
            </a>
            <?php
endif; ?>

            <!-- Settings - Only for Administrator and Superadmin -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'settings' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium">Settings</span>
            </a>
            <?php
endif; ?>

            <!-- Divider -->
            <!-- Divider -->
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'administrator' || $_SESSION['role'] === 'superadmin')): ?>
            <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>
            <?php
endif; ?>

                        <!-- Support / Maintenance -->
            <a href="maintenance.php" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentPage === 'maintenance' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span class="font-medium">Backup &amp; Restore</span>
            </a>

            <!-- Logout -->
            <!-- <button onclick="confirmLogout()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-700 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="font-medium text-red-600 dark:text-red-400">Logout</span>
            </button> -->
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
