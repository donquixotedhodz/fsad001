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

// Handle PPE Fund Balance Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_ppe_balance') {
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

// Fetch documents data
$stmt = $conn->prepare("SELECT * FROM manap ORDER BY created_at DESC");
$stmt->execute();
$allDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalDocuments = count($allDocuments);

// Count documents by EC
$ecCount = [];
$stmt = $conn->prepare("SELECT ec.code as ec, COUNT(*) as count FROM manap m INNER JOIN electric_cooperatives ec ON m.ec = ec.name GROUP BY ec.code ORDER BY count DESC");
$stmt->execute();
$ecStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total electric cooperatives
$ecStmt = $conn->prepare("SELECT COUNT(*) as total FROM electric_cooperatives");
$ecStmt->execute();
$totalECs = $ecStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Count documents by Item
$itemCount = [];
$stmt = $conn->prepare("SELECT item, COUNT(*) as count FROM manap GROUP BY item ORDER BY count DESC");
$stmt->execute();
$itemStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count documents by Department
$deptCount = [];
$stmt = $conn->prepare("SELECT department, COUNT(*) as count FROM manap WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY count DESC");
$stmt->execute();
$deptRawStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process department data to remove numbering
$deptStats = [];
$processedDepts = [];
foreach ($deptRawStats as $record) {
    if (!empty($record['department'])) {
        $depts = array_filter(array_map('trim', explode("\n", $record['department'])));
        foreach ($depts as $dept) {
            $deptName = preg_replace('/^\d+\.\s+/', '', $dept);
            if (!in_array($deptName, $processedDepts)) {
                $processedDepts[] = $deptName;
            }
        }
    }
}

// Count actual department occurrences
foreach ($processedDepts as $deptName) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM manap WHERE department LIKE ?");
    $searchTerm = '%' . $deptName . '%';
    $countStmt->execute([$searchTerm]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $deptStats[] = ['department' => $deptName, 'count' => $countResult['count']];
}
usort($deptStats, function($a, $b) { return $b['count'] - $a['count']; });

// Count documents by Team
$teamCount = [];
$stmt = $conn->prepare("SELECT team, COUNT(*) as count FROM manap WHERE team IS NOT NULL AND team != '' GROUP BY team ORDER BY count DESC");
$stmt->execute();
$teamRawStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process team data to remove numbering
$teamStats = [];
$processedTeams = [];
foreach ($teamRawStats as $record) {
    if (!empty($record['team'])) {
        $teams = array_filter(array_map('trim', explode("\n", $record['team'])));
        foreach ($teams as $team) {
            $teamName = preg_replace('/^\d+\.\s+/', '', $team);
            if (!in_array($teamName, $processedTeams)) {
                $processedTeams[] = $teamName;
            }
        }
    }
}

// Count actual team occurrences
foreach ($processedTeams as $teamName) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM manap WHERE team LIKE ?");
    $searchTerm = '%' . $teamName . '%';
    $countStmt->execute([$searchTerm]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $teamStats[] = ['team' => $teamName, 'count' => $countResult['count']];
}
usort($teamStats, function($a, $b) { return $b['count'] - $a['count']; });

// Get PPE Provident Fund remaining balance - Calculate from actual PPE table data
$ppeStmt = $conn->prepare("SELECT balance FROM ppe ORDER BY id DESC LIMIT 1");
$ppeStmt->execute();
$ppeResult = $ppeStmt->fetch(PDO::FETCH_ASSOC);
$ppeBalance = $ppeResult ? floatval($ppeResult['balance']) : 0;

// Recent documents
$recentDocuments = array_slice($allDocuments, 0, 3);

ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">Welcome back, <?php echo htmlspecialchars($username); ?>!</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Card 1 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Documents</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalDocuments; ?></p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(184, 134, 11, 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgba(184, 134, 11, 1);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Electric Cooperatives</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2"><?php echo $totalECs; ?></p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(255, 140, 0, 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgba(255, 140, 0, 1);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PPE Provident Fund</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        <?php 
                            if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
                                echo '***';
                            } else {
                                echo '₱' . number_format($ppeBalance, 2);
                            }
                        ?>
                    </p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(210, 105, 30, 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgba(210, 105, 30, 1);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">
                        <?php 
                        if (!empty($allDocuments)) {
                            echo date('M d, Y', strtotime($allDocuments[0]['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </p>
                </div>
                <div class="p-3 rounded-lg" style="background-color: rgba(205, 92, 92, 0.15);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: rgba(205, 92, 92, 1);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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

        <!-- Item Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Documents by Item</h2>
            <div style="height: 300px;">
                <canvas id="itemChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Additional Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Department Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Documents by Department</h2>
            <div style="height: 300px;">
                <canvas id="deptChart"></canvas>
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
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">No documents available yet</p>
                        </div>
                    <?php endif; ?>
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
    <script>
        // EC Distribution Chart
        const ecData = <?php echo json_encode($ecStats); ?>;
        const ecLabels = ecData.map(item => item.ec);
        const ecValues = ecData.map(item => item.count);

        // Autumn colors for each EC
        const autumnColors = [
            'rgba(220, 20, 60, 0.8)',       // Crimson
            'rgba(255, 69, 0, 0.8)',        // Red-Orange
            'rgba(255, 140, 0, 0.8)',       // Dark Orange
            'rgba(255, 165, 0, 0.8)',       // Orange
            'rgba(218, 165, 32, 0.8)',      // Goldenrod
            'rgba(184, 134, 11, 0.8)',      // Dark Goldenrod
            'rgba(139, 69, 19, 0.8)',       // Saddle Brown
            'rgba(210, 105, 30, 0.8)',      // Chocolate
            'rgba(205, 92, 92, 0.8)',       // Indian Red
            'rgba(178, 34, 34, 0.8)',       // Firebrick
            'rgba(160, 82, 45, 0.8)',       // Sienna
            'rgba(165, 42, 42, 0.8)'        // Brown
        ];

        const ecBackgroundColors = ecLabels.map((_, index) => autumnColors[index % autumnColors.length]);
        const ecBorderColors = ecBackgroundColors.map(color => color.replace('0.8', '1'));

        const ecCtx = document.getElementById('ecChart').getContext('2d');
        new Chart(ecCtx, {
            type: 'bar',
            data: {
                labels: ecLabels,
                datasets: [{
                    label: 'Number of Documents',
                    data: ecValues,
                    backgroundColor: ecBackgroundColors,
                    borderColor: ecBorderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });

        // Item Distribution Chart
        const itemData = <?php echo json_encode($itemStats); ?>;
        const itemLabels = itemData.map(item => item.item).slice(0, 8);
        const itemValues = itemData.map(item => item.count).slice(0, 8);

        const colors = [
            'rgba(220, 20, 60, 0.8)',       // Crimson
            'rgba(255, 69, 0, 0.8)',        // Red-Orange
            'rgba(255, 140, 0, 0.8)',       // Dark Orange
            'rgba(255, 165, 0, 0.8)',       // Orange
            'rgba(218, 165, 32, 0.8)',      // Goldenrod
            'rgba(184, 134, 11, 0.8)',      // Dark Goldenrod
            'rgba(139, 69, 19, 0.8)',       // Saddle Brown
            'rgba(210, 105, 30, 0.8)'       // Chocolate
        ];

        const itemCtx = document.getElementById('itemChart').getContext('2d');
        new Chart(itemCtx, {
            type: 'doughnut',
            data: {
                labels: itemLabels,
                datasets: [{
                    data: itemValues,
                    backgroundColor: colors.slice(0, itemLabels.length),
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Department Distribution Chart
        const deptData = <?php echo json_encode($deptStats); ?>;
        const deptLabels = deptData.map(item => item.department);
        const deptValues = deptData.map(item => item.count);

        const deptBackgroundColors = deptLabels.map((_, index) => autumnColors[index % autumnColors.length]);
        const deptBorderColors = deptBackgroundColors.map(color => color.replace('0.8', '1'));

        const deptCtx = document.getElementById('deptChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [{
                    label: 'Number of Documents',
                    data: deptValues,
                    backgroundColor: deptBackgroundColors,
                    borderColor: deptBorderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            display: false
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });

        // Team Distribution Chart
        const teamData = <?php echo json_encode($teamStats); ?>;
        const teamLabels = teamData.map(item => item.team);
        const teamValues = teamData.map(item => item.count);

        const teamBackgroundColors = teamLabels.map((_, index) => autumnColors[index % autumnColors.length]);
        const teamBorderColors = teamBackgroundColors.map(color => color.replace('0.8', '1'));

        const teamCtx = document.getElementById('teamChart').getContext('2d');
        new Chart(teamCtx, {
            type: 'bar',
            data: {
                labels: teamLabels,
                datasets: [{
                    label: 'Number of Documents',
                    data: teamValues,
                    backgroundColor: teamBackgroundColors,
                    borderColor: teamBorderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#374151'
                        },
                        grid: {
                            color: document.documentElement.classList.contains('dark') ? 'rgba(55, 65, 81, 0.3)' : 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
