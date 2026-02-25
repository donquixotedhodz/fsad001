<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
if ($_SESSION['role'] !== 'superadmin') {
    die("Access Denied: Only Super Admin can print reports.");
}
$controller = new MainController($conn);
$controller->setCurrentPage('ppe_print');


// Set current page for sidebar active state
$currentPage = 'ppe_print';
$username = $_SESSION['username'] ?? 'User';

// Start output buffering to capture content
ob_start();
?>

<div class="w-full">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Print PPE Reports</h1>
    </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 w-full max-w-[1600px] mx-auto overflow-hidden">
            <form id="printForm" action="ppe_check_issued_receiving_print.php" method="GET" target="_blank">
                <div class="flex flex-col lg:flex-row min-h-[600px]">
                    
                    <!-- LEFT PANEL: Report Type Selection (Narrower) -->
                    <div class="lg:w-[400px] p-6 lg:p-8 border-b lg:border-b-0 lg:border-r border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-3 mb-6">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            Select Report
                        </h2>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Option 1: Checks Issued - Receiving -->
                            <div class="relative group">
                                <input class="sr-only peer" type="radio" value="ppe_check_issued_receiving_print.php" name="report_type_radio" id="rt_receiving" checked onchange="setReportType(this)">
                                <label class="flex items-start p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-100 dark:peer-checked:bg-blue-900/20 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="rt_receiving">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg text-blue-600 shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white block">Check Issued - Receiving</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1 leading-tight">Checks issued with receiving info.</span>
                                    </div>
                                </label>
                            </div>

                             <!-- Option 2: Checks Issued -->
                             <div class="relative group">
                                <input class="sr-only peer" type="radio" value="ppe_check_issued_print.php" name="report_type_radio" id="rt_check_issued" onchange="setReportType(this)">
                                <label class="flex items-start p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-indigo-500 dark:hover:border-indigo-400 peer-checked:border-indigo-600 peer-checked:bg-indigo-100 dark:peer-checked:bg-indigo-900/20 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="rt_check_issued">
                                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg text-indigo-600 shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white block">Checks Issued</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1 leading-tight">Summary of issued checks.</span>
                                    </div>
                                </label>
                            </div>

                             <!-- Option 3: Cash Balance -->
                             <div class="relative group">
                                <input class="sr-only peer" type="radio" value="ppe_table_print.php" name="report_type_radio" id="rt_cash_balance" onchange="setReportType(this)">
                                <label class="flex items-start p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-green-500 dark:hover:border-green-400 peer-checked:border-green-600 peer-checked:bg-green-100 dark:peer-checked:bg-green-900/20 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="rt_cash_balance">
                                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-green-600 shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white block">Cash Balance</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1 leading-tight">Current balance overview.</span>
                                    </div>
                                </label>
                            </div>

                             <!-- Option 4: Remittance -->
                             <div class="relative group">
                                <input class="sr-only peer" type="radio" value="ppe_remittance_print.php" name="report_type_radio" id="rt_remittance" onchange="setReportType(this)">
                                <label class="flex items-start p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-purple-500 dark:hover:border-purple-400 peer-checked:border-purple-600 peer-checked:bg-purple-100 dark:peer-checked:bg-purple-900/20 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="rt_remittance">
                                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg text-purple-600 shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </div>
                                    <div class="ml-3">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white block">Remittance</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mt-1 leading-tight">Remittance transactions.</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT PANEL: Settings & Actions (Wider) -->
                    <div class="flex-1 bg-gray-50 dark:bg-gray-800/50 p-8 lg:p-10 flex flex-col border-l border-gray-100 dark:border-gray-700">
                        
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-12 flex-1">
                            
                            <!-- Period Selection Column -->
                            <div class="space-y-6" x-data="{
                                filter: 'monthly',
                                day: <?php echo date('j'); ?>,
                                month: '<?php echo date('m'); ?>',
                                year: '<?php echo date('Y'); ?>',
                                dateFrom: '<?php echo date('Y-m-d'); ?>',
                                dateTo: '<?php echo date('Y-m-d'); ?>',
                                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                                get monthName() { return this.months[parseInt(this.month) - 1]; },
                                get lastDay() { return new Date(this.year, this.month, 0).getDate(); },
                                get asOfLabel() { return `As of ${this.monthName} ${this.day}, ${this.year} (Balance Forward)`; },
                                get periodLabel() { return `For the Month of ${this.monthName} ${this.lastDay}, ${this.year}`; },
                                updateDay() { if (this.day > this.lastDay) this.day = this.lastDay; }
                            }">
                                <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    Report Period
                                </h2>
                                
                                <!-- Hidden Inputs -->
                                <input type="hidden" name="selected_month" :value="month">
                                <input type="hidden" name="selected_year" :value="year">
                                <!-- Date To Sync -->
                                <input type="hidden" name="date_from" :value="dateFrom">
                                <input type="hidden" name="date_to" :value="dateTo">
                                <input type="hidden" name="selected_day" :value="day">
                                
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Select Period</label>
                                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden shadow-sm">
                                    
                                    
                                    <div class="border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" @click="filter = 'monthly'">
                                            <input type="radio" value="monthly" name="date_filter" x-model="filter" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <label class="ml-3 block text-base font-medium text-gray-900 dark:text-white cursor-pointer flex-1" x-text="asOfLabel">
                                                End of the Month
                                            </label>
                                        </div>
                                        
                                        <div x-show="filter == 'monthly'" x-collapse class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 grid grid-cols-3 gap-3 transition-all">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Month</label>
                                                <select x-model="month" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                                    <?php
$currentMonth = date('m');
for ($m = 1; $m <= 12; $m++) {
    $monthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
    $monthName = date('F', mktime(0, 0, 0, $m, 1));
    echo '<option value="' . $monthNum . '">' . $monthName . '</option>';
}
?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Day</label>
                                                <select x-model="day" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                                    <template x-for="d in lastDay" :key="d">
                                                        <option :value="d" x-text="d"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Year</label>
                                                <select x-model="year" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                                    <?php
$currentYear = date('Y');
for ($y = $currentYear; $y >= 2020; $y--) {
    echo '<option value="' . $y . '">' . $y . '</option>';
}
?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Option 2: For the Month (Period) -->
                                    <div class="border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" @click="filter = 'monthly_period'">
                                            <input type="radio" value="monthly_period" name="date_filter" x-model="filter" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <label class="ml-3 block text-base font-medium text-gray-900 dark:text-white cursor-pointer flex-1" x-text="periodLabel">
                                                For the Month
                                            </label>
                                        </div>
                                        
                                        <div x-show="filter == 'monthly_period'" x-collapse class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 gap-3 transition-all">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Month</label>
                                                <select x-model="month" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                                    <?php
for ($m = 1; $m <= 12; $m++) {
    $monthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
    $monthName = date('F', mktime(0, 0, 0, $m, 1));
    echo '<option value="' . $monthNum . '">' . $monthName . '</option>';
}
?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Year</label>
                                                <select x-model="year" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                                    <?php
for ($y = $currentYear; $y >= 2020; $y--) {
    echo '<option value="' . $y . '">' . $y . '</option>';
}
?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Option 3: As Of Year -->
                                    <div class="border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" @click="filter = 'annual'">
                                            <input type="radio" value="annual" name="date_filter" x-model="filter" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <label class="ml-3 block text-base font-medium text-gray-900 dark:text-white cursor-pointer flex-1">
                                                As of (Year)
                                            </label>
                                        </div>
                                        <div x-show="filter == 'annual'" x-collapse class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 transition-all">
                                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Select Year</label>
                                            <select x-model="year" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                                <?php
for ($y = $currentYear; $y >= 2020; $y--) {
    echo '<option value="' . $y . '">' . $y . '</option>';
}
?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Option 4: All Time -->
                                    <div class="border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" @click="filter = 'all_time'">
                                            <input type="radio" value="all_time" name="date_filter" x-model="filter" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <label class="ml-3 block text-base font-medium text-gray-900 dark:text-white cursor-pointer flex-1">
                                                All Time
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Option 5: Date Printed -->
                                    <div class="border-b border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" @click="filter = 'date_printed'; dateTo = dateFrom">
                                            <input type="radio" value="date_printed" name="date_filter" x-model="filter" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <label class="ml-3 block text-base font-medium text-gray-900 dark:text-white cursor-pointer flex-1">
                                                Today
                                            </label>
                                        </div>
                                        <div x-show="filter == 'date_printed'" x-collapse class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 transition-all">
                                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Pick Date</label>
                                            <input type="date" x-model="dateFrom" @change="dateTo = dateFrom" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                        </div>
                                    </div>
                                    
                                    <!-- Option 6: Custom Date -->
                                    <div>
                                        <div class="flex items-center p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer" @click="filter = 'custom'">
                                            <input type="radio" value="custom" name="date_filter" x-model="filter" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                            <label class="ml-3 block text-base font-medium text-gray-900 dark:text-white cursor-pointer flex-1">
                                                Custom Date
                                            </label>
                                        </div>
                                        <div x-show="filter == 'custom'" x-collapse class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 gap-3 transition-all">
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">From</label>
                                                <input type="date" x-model="dateFrom" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">To</label>
                                                <input type="date" x-model="dateTo" class="w-full text-sm px-3 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Output Format Column -->
                            <div class="flex flex-col relative">
                                <!-- Vertical Divider -->
                                <div class="hidden xl:block absolute -left-6 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                                <div class="space-y-6">
                                    <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-3 mb-6">
                                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </div>
                                        Output Format
                                    </h2>

                                    <div class="grid grid-cols-1 gap-3">
                                        <!-- Print -->
                                        <div class="relative">
                                            <input class="sr-only peer" type="radio" value="html" name="format" id="fmt_print" checked>
                                            <label class="flex items-center p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-100 dark:peer-checked:bg-blue-900/30 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="fmt_print">
                                                <div class="p-2 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <span class="block text-base font-bold text-gray-800 dark:text-white">Print Preview</span>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">View in browser</span>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <!-- PDF -->
                                        <div class="relative">
                                            <input class="sr-only peer" type="radio" value="pdf" name="format" id="fmt_pdf">
                                            <label class="flex items-center p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-red-500 peer-checked:border-red-600 peer-checked:bg-red-100 dark:peer-checked:bg-red-900/30 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="fmt_pdf">
                                                <div class="p-2 rounded-full bg-red-50 dark:bg-red-900/30 text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <span class="block text-base font-bold text-gray-800 dark:text-white">PDF Document</span>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Download file</span>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <!-- Excel -->
                                        <div class="relative">
                                            <input class="sr-only peer" type="radio" value="excel" name="format" id="fmt_excel">
                                            <label class="flex items-center p-4 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:border-green-500 peer-checked:border-green-600 peer-checked:bg-green-100 dark:peer-checked:bg-green-900/30 transition-all shadow-sm hover:shadow-md transform peer-checked:scale-105 peer-checked:shadow-lg" for="fmt_excel">
                                                <div class="p-2 rounded-full bg-green-50 dark:bg-green-900/30 text-green-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <span class="block text-base font-bold text-gray-800 dark:text-white">Excel Spreadsheet</span>
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Download file</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 pt-6"> <!-- Pushed to bottom -->
                                    <button type="submit" class="w-full group relative flex items-center justify-center gap-2 px-8 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-500/30 active:scale-95 text-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 transition-transform group-hover:-translate-y-1 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        GENERATE REPORT
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function setReportType(radio) {
            const form = document.getElementById('printForm');
            form.action = radio.value;
        }
    </script>
</div>

<?php
// Capture content and include master layout
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
