<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('dashboard');
$auditLogger = new AuditLogger($conn);
$username = $_SESSION['username'] ?? 'User';
$isSuperadmin = isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ppe_balance' && !$isSuperadmin) {
    $_SESSION['errorMessage'] = "Only Superadmin can update PPE balance.";
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle PPE Fund Balance Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ppe_balance' && $isSuperadmin) {
    $newBalance = isset($_POST['ppe_balance']) ? floatval($_POST['ppe_balance']) : 0;

    // Fetch old balance for audit log
    $oldBalanceStmt = $conn->prepare("SELECT remaining_balance FROM ppe_funds WHERE fund_name = 'PPE Provident Fund' LIMIT 1");
    $oldBalanceStmt->execute();
    $oldBalanceResult = $oldBalanceStmt->fetch(PDO::FETCH_ASSOC);
    $oldBalance = $oldBalanceResult ? $oldBalanceResult['remaining_balance'] : 0;

    $updateStmt = $conn->prepare("UPDATE ppe_funds SET remaining_balance = ? WHERE fund_name = 'PPE Provident Fund'");
    $updateStmt->execute([$newBalance]);

    // Create comprehensive description
    $difference = $newBalance - $oldBalance;
    $differenceType = $difference > 0 ? 'Increased' : ($difference < 0 ? 'Decreased' : 'No change');
    $absDifference = abs($difference);

    $description = "PPE Provident Fund balance updated from Dashboard | ";
    $description .= "Previous Balance: ₱" . number_format($oldBalance, 2) . " | ";
    $description .= "New Balance: ₱" . number_format($newBalance, 2) . " | ";
    $description .= "{$differenceType} by: ₱" . number_format($absDifference, 2) . " | ";
    $description .= "Updated by: " . htmlspecialchars($_SESSION['username']);

    // Log the balance update with full details
    $auditLogger->logUpdate('ppe_funds', 1, $description,
    ['fund_name' => 'PPE Provident Fund', 'remaining_balance' => $oldBalance],
    ['fund_name' => 'PPE Provident Fund', 'remaining_balance' => $newBalance]
    );

    // Refresh the balance
    $ppeStmt = $conn->prepare("SELECT remaining_balance FROM ppe_funds WHERE fund_name = 'PPE Provident Fund' LIMIT 1");
    $ppeStmt->execute();
    $ppeResult = $ppeStmt->fetch(PDO::FETCH_ASSOC);
    $ppeBalance = $ppeResult ? $ppeResult['remaining_balance'] : 0;
}

// Fetch total documents count efficiently
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM manap");
$stmt->execute();
$totalDocuments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Fetch recent documents (limit to 5)
$stmt = $conn->prepare("SELECT * FROM manap ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count documents by EC - Optimized
// First get raw counts by EC string
$stmt = $conn->prepare("SELECT ec, COUNT(*) as count FROM manap GROUP BY ec");
$stmt->execute();
$rawEcStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all electric cooperatives for matching
$ecStmt = $conn->prepare("SELECT code, name FROM electric_cooperatives");
$ecStmt->execute();
$elecCoops = $ecStmt->fetchAll(PDO::FETCH_ASSOC);

// Process and map ECs in PHP
$ecCounts = [];
foreach ($rawEcStats as $row) {
    if (empty($row['ec']))
        continue;

    $rawEc = trim($row['ec']);
    $count = $row['count'];
    $matchedCode = null;

    // Try to match with known cooperatives
    foreach ($elecCoops as $coop) {
        if (stripos($rawEc, trim($coop['name'])) !== false) {
            $matchedCode = $coop['code'];
            break;
        }
    }

    // Use matched code or raw string if no match
    $key = $matchedCode ?? $rawEc;

    if (!isset($ecCounts[$key])) {
        $ecCounts[$key] = 0;
    }
    $ecCounts[$key] += $count;
}

// Convert back to array format for chart
$ecStats = [];
foreach ($ecCounts as $ec => $count) {
    $ecStats[] = ['ec' => $ec, 'count' => $count];
}
// Sort by count descending
usort($ecStats, function ($a, $b) {
    return $b['count'] - $a['count'];
});


// Count total electric cooperatives
$ecStmt = $conn->prepare("SELECT COUNT(*) as total FROM electric_cooperatives");
$ecStmt->execute();
$totalECs = $ecStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Count total AD Scorecard documents
$adsStmt = $conn->prepare("SELECT COUNT(*) as total FROM ads");
$adsStmt->execute();
$totalADS = $adsStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Count documents by Team - Optimized
$teamCount = [];
$stmt = $conn->prepare("SELECT team, COUNT(*) as count FROM manap WHERE team IS NOT NULL AND team != '' GROUP BY team");
$stmt->execute();
$teamRawStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process team data to remove numbering and aggregate counts
$teamStatsMap = [];
foreach ($teamRawStats as $record) {
    if (!empty($record['team'])) {
        $count = $record['count'];
        $teams = array_filter(array_map('trim', explode("\n", $record['team'])));

        $uniqueTeamsInRecord = [];
        foreach ($teams as $team) {
            $teamName = preg_replace('/^\d+\.\s+/', '', $team);
            if (!in_array($teamName, $uniqueTeamsInRecord)) {
                $uniqueTeamsInRecord[] = $teamName;
            }
        }

        foreach ($uniqueTeamsInRecord as $teamName) {
            if (!isset($teamStatsMap[$teamName])) {
                $teamStatsMap[$teamName] = 0;
            }
            $teamStatsMap[$teamName] += $count;
        }
    }
}

// Convert to array
$teamStats = [];
foreach ($teamStatsMap as $team => $count) {
    $teamStats[] = ['team' => $team, 'count' => $count];
}
usort($teamStats, function ($a, $b) {
    return $b['count'] - $a['count'];
});

// Get PPE Provident Fund remaining balance - Calculate from actual PPE table data
$ppeStmt = $conn->prepare("SELECT balance FROM ppe ORDER BY id DESC LIMIT 1");
$ppeStmt->execute();
$ppeResult = $ppeStmt->fetch(PDO::FETCH_ASSOC);
$ppeBalance = $ppeResult ? floatval($ppeResult['balance']) : 0;

// Fetch latest non-expired AOM records for countdown (15-day window)
$stmt = $conn->prepare("SELECT item, date, title FROM aom_table WHERE DATE_ADD(date, INTERVAL 15 DAY) >= CURDATE() ORDER BY date DESC LIMIT 5");
$stmt->execute();
$aomRecentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="w-full">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Card 1 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-6 transition-all duration-300" id="statCard1" style="border-color: var(--theme-primary);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Documents</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalDocuments; ?></p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(var(--theme-primary-rgb), 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-6 transition-all duration-300" id="statCard2" style="border-color: var(--theme-secondary);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Electric Cooperatives</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalECs; ?></p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(var(--theme-secondary-rgb), 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-secondary);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-6 transition-all duration-300" id="statCard3" style="border-color: var(--theme-accent);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PPE Provident Fund</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        <?php
if ($isSuperadmin) {
    echo '₱' . number_format($ppeBalance, 2);
}
else {
    echo '***';
}
?>
                    </p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(var(--theme-accent-rgb), 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-accent);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-6 transition-all duration-300" id="statCard4" style="border-color: var(--theme-danger);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">AD Scorecard Documents</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalADS; ?></p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(var(--theme-danger-rgb), 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-danger);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- EC Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Documents by Electric Cooperative</h2>
            <div style="height: 300px;">
                <canvas id="ecChart"></canvas>
            </div>
        </div>

        <!-- Team Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Documents by Team</h2>
            <div style="height: 300px;">
                <canvas id="teamChart"></canvas>
            </div>
        </div>
    </div>

    <!-- AOM Notification Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">AOM Notification (15-Day Countdown)</h2>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b dark:border-gray-600">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b dark:border-gray-600">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b dark:border-gray-600">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b dark:border-gray-600">Countdown</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($aomRecentRecords)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No AOM records found</td>
                        </tr>
                    <?php
else: ?>
                        <?php foreach ($aomRecentRecords as $record):
        $dateObj = new DateTime($record['date']);
        $targetDate = clone $dateObj;
        $targetDate->modify('+15 days');
        $today = new DateTime('today');

        $daysLeft = (int) $today->diff($targetDate)->format('%r%a');

        // Safety check: hide expired rows even if they somehow pass query filtering
        if ($daysLeft < 0) {
            continue;
        }

        $statusClass = 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        if ($daysLeft <= 5) {
            $statusClass = 'text-orange-700 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
        }
        $displayDiff = $daysLeft . ' days left';
?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($record['item']); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo $dateObj->format('M d, Y'); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate"><?php echo htmlspecialchars($record['title']); ?></td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo $displayDiff; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Documents -->
        <!-- <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Recent Documents</h2>
                <div class="space-y-3">
                    <?php if (!empty($recentDocuments)): ?>
                        <?php foreach ($recentDocuments as $doc): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition cursor-pointer">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 012-2h6a1 1 0 00-.707.293l-7 7A1 1 0 004 13H4a2 2 0 01-2-2V4zm12 12a2 2 0 01-2 2h-2.5a.5.5 0 00-.5.5v.5H6V4a2 2 0 012-2h5.5a.5.5 0 00.5.5v.5h1a2 2 0 012 2v8z"></path>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo htmlspecialchars($doc['ec']); ?> - <?php echo htmlspecialchars($doc['item']); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                <?php echo date('M d', strtotime($doc['created_at'])); ?>
                            </span>
                        </div>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No documents available yet</p>
                        </div>
                    <?php
endif; ?>
                </div>
                <a href="documents.php" class="mt-4 inline-block text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium text-sm">
                    View all documents →
                </a>
            </div>
        </div> -->

        <!-- Quick Actions -->
        <!-- <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="documents.php" class="flex items-center gap-3 p-3 rounded-lg hover:opacity-90 transition" style="background-color: rgba(var(--theme-primary-rgb), 0.1); color: var(--theme-primary);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="text-sm font-medium">Add Document</span>
                </a>

                <a href="reports.php" class="flex items-center gap-3 p-3 rounded-lg hover:opacity-90 transition" style="background-color: rgba(var(--theme-success-rgb), 0.1); color: var(--theme-success);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-sm font-medium">Generate Report</span>
                </a>

                <a href="settings.php" class="flex items-center gap-3 p-3 rounded-lg hover:opacity-90 transition" style="background-color: rgba(var(--theme-accent-rgb), 0.1); color: var(--theme-accent);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="text-sm font-medium">Settings</span>
                </a>
            </div>
        </div> -->
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="../main/app/js/ChartColorHelper.js"></script>
    <script>
        // Helper function to get chart config with colors
        function getChartConfig(type, labels, data, label, hideXLabels = false) {
            const isDark = document.documentElement.classList.contains('dark');
            const chartColors = ChartColorHelper.getChartColorsByCount(labels.length);
            const borderColors = ChartColorHelper.getBorderColors(null, labels.length);
            
            const config = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: isDark ? '#e5e7eb' : '#374151'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: isDark ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: isDark ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            display: hideXLabels ? false : true,
                            color: isDark ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: isDark ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            };

            return {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: label || 'Number of Documents',
                        data: data,
                        backgroundColor: chartColors,
                        borderColor: borderColors,
                        borderWidth: 1
                    }]
                },
                options: config
            };
        }

        // EC Distribution Chart
        const ecData = <?php echo json_encode($ecStats); ?>;
        const ecLabels = ecData.map(item => item.ec);
        const ecValues = ecData.map(item => item.count);

        const ecCtx = document.getElementById('ecChart').getContext('2d');
        new Chart(ecCtx, getChartConfig('bar', ecLabels, ecValues, 'Number of Documents'));



        // Team Distribution Chart
        const teamData = <?php echo json_encode($teamStats); ?>;
        const teamLabels = teamData.map(item => item.team);
        const teamValues = teamData.map(item => item.count);

        const teamCtx = document.getElementById('teamChart').getContext('2d');
        new Chart(teamCtx, getChartConfig('bar', teamLabels, teamValues, 'Number of Documents'));

        // Listen for theme changes
        ChartColorHelper.onThemeChange(function(event) {
            // Reload page to refresh all charts with new colors
            setTimeout(() => location.reload(), 200);
        });
    </script>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
