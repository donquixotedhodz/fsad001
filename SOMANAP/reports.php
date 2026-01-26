<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('reports');
$username = $_SESSION['username'] ?? 'User';

ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Reports</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage your generated reports</p>
        </div>
        <button class="mt-4 md:mt-0 inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Generate Report
        </button>
    </div>

    <!-- Filters and Search -->
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <input 
            type="text" 
            placeholder="Search reports..." 
            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        <select class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option>All Periods</option>
            <option>This Month</option>
            <option>Last Month</option>
            <option>This Quarter</option>
            <option>This Year</option>
        </select>
    </div>

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Report Card 1 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Sales Performance Report</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Generated on Jan 20, 2026</p>
                </div>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">Complete</span>
            </div>
            <div class="mb-4">
                <div class="flex justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Progress</span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">100%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                A comprehensive analysis of sales performance across all regions for Q4 2024. Includes revenue trends, customer acquisition, and market insights.
            </p>
            <div class="flex gap-2">
                <button class="flex-1 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 text-sm font-medium transition">
                    View Report
                </button>
                <button class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition">
                    Download
                </button>
            </div>
        </div>

        <!-- Report Card 2 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Customer Feedback Analysis</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Generated on Jan 18, 2026</p>
                </div>
                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 text-xs font-medium rounded-full">In Progress</span>
            </div>
            <div class="mb-4">
                <div class="flex justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Progress</span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">75%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: 75%"></div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Analysis of customer feedback from surveys and support tickets. Sentiment analysis and recommendations for improvements.
            </p>
            <div class="flex gap-2">
                <button class="flex-1 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 text-sm font-medium transition">
                    View Report
                </button>
                <button class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition">
                    Download
                </button>
            </div>
        </div>

        <!-- Report Card 3 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Operational Efficiency Report</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Generated on Jan 15, 2026</p>
                </div>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">Complete</span>
            </div>
            <div class="mb-4">
                <div class="flex justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Progress</span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">100%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Detailed analysis of operational metrics including productivity, resource utilization, and process optimization opportunities.
            </p>
            <div class="flex gap-2">
                <button class="flex-1 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 text-sm font-medium transition">
                    View Report
                </button>
                <button class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition">
                    Download
                </button>
            </div>
        </div>

        <!-- Report Card 4 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Budget Variance Analysis</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Generated on Jan 10, 2026</p>
                </div>
                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 text-xs font-medium rounded-full">Pending</span>
            </div>
            <div class="mb-4">
                <div class="flex justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Progress</span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">50%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-yellow-500 h-2 rounded-full" style="width: 50%"></div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Analysis of budget vs. actual spending across all departments. Includes variance explanations and financial forecasts.
            </p>
            <div class="flex gap-2">
                <button class="flex-1 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 text-sm font-medium transition">
                    View Report
                </button>
                <button class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition">
                    Download
                </button>
            </div>
        </div>
    </div>

    <!-- Reports Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Reports</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">12</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Completed</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">8</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">In Progress</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">3</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">1</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Reports';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
