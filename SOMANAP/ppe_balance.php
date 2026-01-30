<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';
require_once __DIR__ . '/app/helpers/AuditLogger.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('ppe_balance');

// Initialize audit logger
$auditLogger = new AuditLogger($conn);

// Set current page for sidebar active state
$currentPage = 'ppe_balance';
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
    
    $description = "PPE Provident Fund balance updated via Remaining Balance page | ";
    $description .= "Previous Balance: ₱" . number_format($oldBalance, 2) . " | ";
    $description .= "New Balance: ₱" . number_format($newBalance, 2) . " | ";
    $description .= "{$differenceType} by: ₱" . number_format($absDifference, 2);
    
    // Log the balance update with full details
    $auditLogger->logUpdate('ppe_funds', 1, $description, 
        ['fund_name' => 'PPE Provident Fund', 'remaining_balance' => $oldBalance], 
        ['fund_name' => 'PPE Provident Fund', 'remaining_balance' => $newBalance]
    );
}

// Fetch PPE Remaining Balance - Calculate from actual PPE table data
$stmt = $conn->prepare("SELECT balance FROM ppe ORDER BY id DESC LIMIT 1");
$stmt->execute();
$lastRecord = $stmt->fetch(PDO::FETCH_ASSOC);
$remainingBalance = $lastRecord ? floatval($lastRecord['balance']) : 0;

// Fetch total debit and credit accumulation
$ppeStmt = $conn->prepare("SELECT COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit FROM ppe");
$ppeStmt->execute();
$ppeStats = $ppeStmt->fetch(PDO::FETCH_ASSOC);
$totalDebit = $ppeStats['total_debit'] ?? 0;
$totalCredit = $ppeStats['total_credit'] ?? 0;

// Fetch daily debit and credit data for chart
$chartStmt = $conn->prepare("
    SELECT DATE(date) as chart_date, 
           COALESCE(SUM(debit), 0) as daily_debit, 
           COALESCE(SUM(credit), 0) as daily_credit,
           MAX(balance) as daily_balance
    FROM ppe 
    GROUP BY DATE(date) 
    ORDER BY DATE(date) ASC 
    LIMIT 30
");
$chartStmt->execute();
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

// Process chart data
$chartDates = [];
$chartDebits = [];
$chartCredits = [];
foreach ($chartData as $row) {
    $chartDates[] = date('M d', strtotime($row['chart_date']));
    $chartDebits[] = floatval($row['daily_debit']);
    $chartCredits[] = floatval($row['daily_credit']);
}

// Start output buffering to capture content
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">PPE Provident Fund Balance</h1>
        <p class="text-gray-600 dark:text-gray-400">Track the remaining balance and accumulations</p>
    </div>

    <!-- Balance Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Remaining Balance Card -->
        <div class="rounded-lg shadow-sm border p-8 transition-all duration-300" id="balanceCard" style="background: linear-gradient(to bottom right, rgba(var(--theme-primary-rgb), 0.1), rgba(var(--theme-primary-rgb), 0.05)); border-color: var(--theme-primary); border-width: 1px;">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium" style="color: var(--theme-primary);">Remaining Balance</p>
                <div class="flex gap-2">
                    <button onclick="refreshBalance()" title="Refresh balance" class="p-3 rounded-lg hover:opacity-80 transition" style="background-color: rgba(var(--theme-primary-rgb), 0.2);">
                        <svg id="refreshIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-primary);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <p id="balanceDisplay" class="text-4xl font-bold mb-4" style="color: var(--theme-primary);">₱<?php echo number_format($remainingBalance, 2); ?></p>
            <button onclick="document.getElementById('editBalanceModal').classList.remove('hidden')" class="w-full px-4 py-2 text-white text-sm rounded-lg transition font-medium hover:opacity-90" style="background-color: var(--theme-primary);">
                Edit Balance
            </button>
        </div>

        <!-- Total Debit Card -->
        <div class="rounded-lg shadow-sm border p-8 transition-all duration-300" id="debitCard" style="background: linear-gradient(to bottom right, rgba(var(--theme-danger-rgb), 0.1), rgba(var(--theme-danger-rgb), 0.05)); border-color: var(--theme-danger); border-width: 1px;">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium" style="color: var(--theme-danger);">Total Debit Accumulation</p>
                <div class="p-3 rounded-lg" style="background-color: rgba(var(--theme-danger-rgb), 0.2);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-danger);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4m0 0L3 5m0 0v8m0-8l4 4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-4xl font-bold" style="color: var(--theme-danger);">₱<?php echo number_format($totalDebit, 2); ?></p>
            <p class="text-xs mt-4" style="color: var(--theme-danger);">Total amount debited from the fund</p>
        </div>

        <!-- Total Credit Card -->
        <div class="rounded-lg shadow-sm border p-8 transition-all duration-300" id="creditCard" style="background: linear-gradient(to bottom right, rgba(var(--theme-success-rgb), 0.1), rgba(var(--theme-success-rgb), 0.05)); border-color: var(--theme-success); border-width: 1px;">
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-medium" style="color: var(--theme-success);">Total Credit Accumulation</p>
                <div class="p-3 rounded-lg" style="background-color: rgba(var(--theme-success-rgb), 0.2);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--theme-success);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7H5v12a1 1 0 001 1h12a1 1 0 001-1V9.5M13 5h8m0 0v8m0-8l-8 8-4-4m0 0L3 19m0 0v-8m0 8l4-4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-4xl font-bold" style="color: var(--theme-success);">₱<?php echo number_format($totalCredit, 2); ?></p>
            <p class="text-xs mt-4" style="color: var(--theme-success);">Total amount credited to the fund</p>
        </div>
    </div>

    <!-- Summary Section - Line Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Debit & Credit Trend</h2>
        <div class="overflow-x-auto">
            <div id="ppeLineChart" style="min-height: 350px;"></div>
        </div>
    </div>

    <!-- Detailed Records -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Detailed Records</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600">Date</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600">Particulars</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600">Debit</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600">Credit</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-600">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $conn->prepare("SELECT date, particulars, debit, credit, balance FROM ppe ORDER BY date DESC LIMIT 10");
                        $stmt->execute();
                        $recentRecords = $stmt->fetchAll();
                        
                        if (count($recentRecords) > 0) {
                            foreach ($recentRecords as $record) {
                                echo '<tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">';
                                echo '<td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['date']) . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['particulars']) . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-right text-red-600 dark:text-red-400">' . ($record['debit'] > 0 ? '₱' . number_format($record['debit'], 2) : '-') . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-right text-green-600 dark:text-green-400">' . ($record['credit'] > 0 ? '₱' . number_format($record['credit'], 2) : '-') . '</td>';
                                echo '<td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">₱' . number_format($record['balance'], 2) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No records found</td></tr>';
                        }
                    } catch (Exception $e) {
                        echo '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error loading data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Balance Modal -->
<div id="editBalanceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md p-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Remaining Balance</h2>
            <button onclick="document.getElementById('editBalanceModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_ppe_balance">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Balance</label>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mb-4">₱<?php echo number_format($remainingBalance, 2); ?></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">New Balance</label>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-medium text-gray-700 dark:text-gray-300">₱</span>
                    <input type="number" name="ppe_balance" value="<?php echo $remainingBalance; ?>" step="0.01" min="0" required class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Enter amount">
                </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="document.getElementById('editBalanceModal').classList.add('hidden')" class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition font-medium">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition font-medium">
                    Update Balance
                </button>
            </div>
        </form>
    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
<script src="../SOMANAP/app/js/ChartColorHelper.js"></script>
<script>
function refreshBalance() {
    const refreshIcon = document.getElementById('refreshIcon');
    refreshIcon.style.animation = 'spin 0.6s linear';
    
    fetch('get_ppe_balance.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('balanceDisplay').textContent = '₱' + parseFloat(data.balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                // Reload page after short delay to update all cards
                setTimeout(() => location.reload(), 500);
            }
        })
        .catch(error => {
            console.error('Error refreshing balance:', error);
            refreshIcon.style.animation = 'none';
        });
}

// PPE Line Chart with dynamic colors based on theme
const chartDates = <?php echo json_encode($chartDates); ?>;
const chartDebits = <?php echo json_encode($chartDebits); ?>;
const chartCredits = <?php echo json_encode($chartCredits); ?>;

// Get colors from ChartColorHelper based on current theme
const lineColors = ChartColorHelper.getLineChartColors();
const markerColors = ChartColorHelper.getBorderColors(null, 2);

const ppeLineChartOptions = {
    series: [
        {
            name: "Debit",
            data: chartDebits,
        },
        {
            name: "Credit",
            data: chartCredits,
        }
    ],
    legend: {
        show: true,
        position: "top",
        horizontalAlign: "right",
    },
    colors: [ChartColorHelper.rgbaToHex(lineColors[2]), ChartColorHelper.rgbaToHex(lineColors[1])],
    chart: {
        fontFamily: "Outfit, sans-serif",
        height: 350,
        type: "line",
        toolbar: {
            show: false,
        },
    },
    fill: {
        gradient: {
            enabled: true,
            opacityFrom: 0.55,
            opacityTo: 0,
        },
    },
    stroke: {
        curve: "smooth",
        width: [2, 2],
    },
    markers: {
        size: 4,
        colors: [ChartColorHelper.rgbaToHex(lineColors[2]), ChartColorHelper.rgbaToHex(lineColors[1])],
        strokeColors: "#fff",
        strokeWidth: 2,
        hover: {
            size: 6,
        }
    },
    grid: {
        xaxis: {
            lines: {
                show: false,
            },
        },
        yaxis: {
            lines: {
                show: true,
            },
        },
    },
    dataLabels: {
        enabled: false,
    },
    tooltip: {
        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        x: {
            format: "dd MMM",
        },
        y: {
            formatter: function(value) {
                return '₱' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    },
    xaxis: {
        type: "category",
        categories: chartDates,
        axisBorder: {
            show: false,
        },
        axisTicks: {
            show: false,
        },
    },
    yaxis: {
        title: {
            style: {
                fontSize: "0px",
            },
        },
    },
    responsive: [
        {
            breakpoint: 1024,
            options: {
                chart: {
                    height: 300,
                },
            },
        },
    ],
};

const ppeLineChart = new ApexCharts(
    document.querySelector("#ppeLineChart"),
    ppeLineChartOptions
);
ppeLineChart.render();

// Listen for theme changes to update chart colors
ChartColorHelper.onThemeChange(function(event) {
    if (ppeLineChart) {
        const newLineColors = ChartColorHelper.getLineChartColors();
        ppeLineChart.updateOptions({
            colors: [ChartColorHelper.rgbaToHex(newLineColors[2]), ChartColorHelper.rgbaToHex(newLineColors[1])],
            markers: {
                colors: [ChartColorHelper.rgbaToHex(newLineColors[2]), ChartColorHelper.rgbaToHex(newLineColors[1])]
            }
        });
    }
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

#balanceCard, #debitCard, #creditCard {
    background: white;
    border: 1px solid;
}

.dark #balanceCard, .dark #debitCard, .dark #creditCard {
    background-color: rgba(0, 0, 0, 0.2);
}

#balanceCard .text-sm, #debitCard .text-sm, #creditCard .text-sm {
    opacity: 0.9;
}

.dark #balanceCard, .dark #debitCard, .dark #creditCard {
    opacity: 0.95;
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'PPE Provident Fund Balance';
require_once __DIR__ . '/app/views/layouts/master.php';
?>
