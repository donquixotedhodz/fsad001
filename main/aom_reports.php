<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/app/controllers/MainController.php';

MainController::requireAuth();
$controller = new MainController($conn);
$controller->setCurrentPage('aom_reports');

// Set current page for sidebar active state
$currentPage = 'aom_reports';
$username = $_SESSION['username'] ?? 'User';

// Get filter values
$filterDateType = $_GET['date_filter'] ?? '';
$filterDept = $_GET['department_id'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterMonth = $_GET['selected_month'] ?? date('m');
$filterYear = $_GET['selected_year'] ?? date('Y');
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

$conn->exec("\n    CREATE TABLE IF NOT EXISTS aom_departments (\n        id INT AUTO_INCREMENT PRIMARY KEY,\n        aom_id INT NOT NULL,\n        department_id INT NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        UNIQUE KEY uniq_aom_department (aom_id, department_id),\n        INDEX idx_aom_id (aom_id),\n        INDEX idx_department_id (department_id),\n        CONSTRAINT fk_aom_departments_aom FOREIGN KEY (aom_id) REFERENCES aom_table(id) ON DELETE CASCADE ON UPDATE CASCADE,\n        CONSTRAINT fk_aom_departments_department FOREIGN KEY (department_id) REFERENCES neadept_table(id) ON DELETE CASCADE ON UPDATE CASCADE\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci\n");

// Get pagination parameters
$itemsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$currentPageNum = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Start output buffering to capture content
ob_start();
?>

<div class="w-full">
    <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Audit Observation Memorandum - Reports</h1>
            <div class="flex gap-3">
                <button onclick="window.open('aom_print.php' + getQueryString(), '_blank')" class="flex items-center gap-2 px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                    </svg>
                    Print
                </button>
                <button onclick="exportToExcel()" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                    Export to Excel
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg mb-4">
            <form method="GET" class="space-y-4" id="filterForm">
                <div class="flex flex-wrap gap-4 items-end">
                    <!-- Date Filter Dropdown -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Filter</label>
                        <select name="date_filter" id="dateFilterSelect" onchange="handleDateFilterChange()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none bg-white">
                            <option value="">Select Date Range</option>
                            <option value="today" <?php echo $filterDateType === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="weekly" <?php echo $filterDateType === 'weekly' ? 'selected' : ''; ?>>This Week</option>
                            <option value="monthly" <?php echo $filterDateType === 'monthly' ? 'selected' : ''; ?>>Months</option>
                            <option value="annual" <?php echo $filterDateType === 'annual' ? 'selected' : ''; ?>>Year</option>
                            <option value="custom" <?php echo $filterDateType === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                        </select>
                    </div>

                    <!-- Department Filter -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Department</label>
                        <select name="department_id" onchange="submitForm()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none bg-white">
                            <option value="">All Departments</option>
                            <?php
$deptStmt = $conn->prepare("SELECT id, name FROM neadept_table ORDER BY name ASC");
$deptStmt->execute();
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($departments as $dept) {
    $selected = ($filterDept == $dept['id']) ? 'selected' : '';
    echo '<option value="' . $dept['id'] . '" ' . $selected . '>' . htmlspecialchars($dept['name']) . '</option>';
}
?>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($filterSearch); ?>" placeholder="Item, Title, Observation..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                    </div>

                    <!-- Search Button -->
                    <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium">
                        Search
                    </button>

                    <!-- Clear Button -->
                    <a href="?" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 transition font-medium">
                        Clear
                    </a>
                </div>

                <!-- Year Selection (Hidden by default) -->
                <div id="yearSelectionDiv" style="<?php echo $filterDateType === 'annual' ? 'display: block;' : 'display: none;'; ?>" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Year</label>
                    <select name="selected_year" id="yearSelect" onchange="submitForm()" class="w-1/4 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none bg-white">
                        <?php
$currentYear = date('Y');
for ($year = $currentYear; $year >= 2020; $year--) {
    echo '<option value="' . $year . '" ' . ($filterYear == $year ? 'selected' : '') . '>' . $year . '</option>';
}
?>
                    </select>
                </div>

                <!-- Month Selection (Hidden by default) -->
                <div id="monthSelectionDiv" style="<?php echo $filterDateType === 'monthly' ? 'display: block;' : 'display: none;'; ?>" class="mt-4">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Month</label>
                            <select name="selected_month" id="monthSelect" onchange="submitForm()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none bg-white">
                                <?php
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
foreach ($months as $monthNum => $monthName) {
    echo '<option value="' . $monthNum . '" ' . ($filterMonth == $monthNum ? 'selected' : '') . '>' . $monthName . '</option>';
}
?>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Year</label>
                            <select name="selected_year" onchange="submitForm()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none bg-white">
                                <?php
for ($year = $currentYear; $year >= 2020; $year--) {
    echo '<option value="' . $year . '" ' . ($filterYear == $year ? 'selected' : '') . '>' . $year . '</option>';
}
?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Custom Date Range (Hidden by default) -->
                <div id="customDateRange" style="<?php echo $filterDateType === 'custom' ? 'display: grid;' : 'display: none;'; ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                        <input type="date" name="date_from" id="dateFrom" value="<?php echo htmlspecialchars($filterDateFrom); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                        <input type="date" name="date_to" id="dateTo" value="<?php echo htmlspecialchars($filterDateTo); ?>" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-600 dark:text-white focus:outline-none" onchange="submitForm()">
                    </div>
                </div>
            </form>
        </div>

        <script>
            function handleDateFilterChange() {
                const filterType = document.getElementById('dateFilterSelect').value;
                const customDateRange = document.getElementById('customDateRange');
                const yearSelectionDiv = document.getElementById('yearSelectionDiv');
                const monthSelectionDiv = document.getElementById('monthSelectionDiv');

                customDateRange.style.display = 'none';
                yearSelectionDiv.style.display = 'none';
                monthSelectionDiv.style.display = 'none';

                if (filterType === 'custom') {
                    customDateRange.style.display = 'grid';
                } else if (filterType === 'annual') {
                    yearSelectionDiv.style.display = 'block';
                } else if (filterType === 'monthly') {
                    monthSelectionDiv.style.display = 'block';
                } else if (filterType !== '') {
                    submitForm();
                }
            }

            function submitForm() {
                document.getElementById('filterForm').submit();
            }

            function getQueryString() {
                const form = document.getElementById('filterForm');
                const formData = new FormData(form);
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value) params.append(key, value);
                }
                return params.toString() ? '?' + params.toString() : '';
            }

            function exportToExcel() {
                window.location.href = 'aom_export.php' + getQueryString();
            }
            
            function changeLimit() {
                const limit = document.getElementById('limitSelect').value;
                const searchParams = new URLSearchParams(window.location.search);
                searchParams.set('limit', limit);
                searchParams.set('page', '1');
                window.location.href = '?' + searchParams.toString();
            }
        </script>
    </div>

    <!-- Show Entries -->
    <div class="mb-4 flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Show</label>
        <select id="limitSelect" onchange="changeLimit()" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="5" <?php echo($itemsPerPage == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo($itemsPerPage == 10) ? 'selected' : ''; ?>>10</option>
            <option value="25" <?php echo($itemsPerPage == 25) ? 'selected' : ''; ?>>25</option>
            <option value="50" <?php echo($itemsPerPage == 50) ? 'selected' : ''; ?>>50</option>
            <option value="100" <?php echo($itemsPerPage == 100) ? 'selected' : ''; ?>>100</option>
        </select>
        <span class="text-sm text-gray-600 dark:text-gray-400">entries</span>
    </div>

    <!-- AOM Reports Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700">
                    <th style="width: 10%; text-align: center;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-semibold text-gray-900 dark:text-white">Item</th>
                    <th style="width: 7%; text-align: center;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-semibold text-gray-900 dark:text-white">Date</th>
                    <th style="width: 5%; text-align: center;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 font-semibold text-gray-900 dark:text-white">Department</th>
                    <th style="width: 15%;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Title</th>
                    <th style="width: 20%;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">COA Observation</th>
                    <th style="width: 20%;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Recommendations</th>
                    <th style="width: 20%;" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Comments / Justification</th>
                </tr>
            </thead>
            <tbody>
                <?php
// Build filter conditions
$whereConditions = [];
$params = [];

$today = date('Y-m-d');

if ($filterDateType === 'today') {
    $whereConditions[] = "a.date = ?";
    $params[] = $today;
}
elseif ($filterDateType === 'weekly') {
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $whereConditions[] = "a.date >= ?";
    $params[] = $weekStart;
    $whereConditions[] = "a.date <= ?";
    $params[] = $today;
}
elseif ($filterDateType === 'monthly') {
    $monthStart = $filterYear . '-' . $filterMonth . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $whereConditions[] = "a.date >= ?";
    $params[] = $monthStart;
    $whereConditions[] = "a.date <= ?";
    $params[] = $monthEnd;
}
elseif ($filterDateType === 'annual') {
    $whereConditions[] = "YEAR(a.date) = ?";
    $params[] = $filterYear;
}
elseif ($filterDateType === 'custom') {
    if (!empty($filterDateFrom)) {
        $whereConditions[] = "a.date >= ?";
        $params[] = $filterDateFrom;
    }
    if (!empty($filterDateTo)) {
        $whereConditions[] = "a.date <= ?";
        $params[] = $filterDateTo;
    }
}

if (!empty($filterDept)) {
    $whereConditions[] = "(EXISTS (SELECT 1 FROM aom_departments adf WHERE adf.aom_id = a.id AND adf.department_id = ?) OR a.department_id = ?)";
    $params[] = $filterDept;
    $params[] = $filterDept;
}

if (!empty($filterSearch)) {
    $searchTerm = '%' . $filterSearch . '%';
    $whereConditions[] = "(a.item LIKE ? OR a.title LIKE ? OR a.coa_observation LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    // First, get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM aom_table a $whereClause";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetch()['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPageNum = min($currentPageNum, max(1, $totalPages));
    $offset = ($currentPageNum - 1) * $itemsPerPage;

    $sql = "SELECT 
                                a.*,
                                COALESCE(da.department_names, d_fallback.name) as department_name,
                                COALESCE(da.department_acronyms, d_fallback.acronym) as department_acronym
                            FROM aom_table a 
                            LEFT JOIN (
                                SELECT
                                    ad.aom_id,
                                    GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') as department_names,
                                    GROUP_CONCAT(DISTINCT d.acronym ORDER BY d.name SEPARATOR ', ') as department_acronyms
                                FROM aom_departments ad
                                INNER JOIN neadept_table d ON ad.department_id = d.id
                                GROUP BY ad.aom_id
                            ) da ON da.aom_id = a.id
                            LEFT JOIN neadept_table d_fallback ON a.department_id = d_fallback.id
                            $whereClause 
                            ORDER BY a.date DESC 
                            LIMIT $itemsPerPage OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    if (count($records) > 0) {
        foreach ($records as $record) {
            $formattedDate = $record['date'] ? date('M/d/Y', strtotime($record['date'])) : '';

            // Parse recommendations and justifications
            $recs = [];
            $justs = [];
            try {
                $recs = json_decode($record['coa_recommendation'], true);
                if (!is_array($recs))
                    $recs = $record['coa_recommendation'] ? [$record['coa_recommendation']] : [];
            }
            catch (Exception $e) {
                $recs = [];
            }

            try {
                $justs = json_decode($record['comments_justification'], true);
                if (!is_array($justs))
                    $justs = $record['comments_justification'] ? [$record['comments_justification']] : [];
            }
            catch (Exception $e) {
                $justs = [];
            }

            $maxRows = max(count($recs), count($justs));

            for ($i = 0; $i < $maxRows; $i++) {
                echo '<tr class="hover:bg-gray-50 dark:hover:bg-gray-700 align-top">';
                if ($i === 0) {
                    echo '<td rowspan="' . $maxRows . '" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['item'] ?? '') . '</td>';
                    echo '<td rowspan="' . $maxRows . '" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-700 dark:text-gray-300">' . htmlspecialchars($formattedDate) . '</td>';
                    echo '<td rowspan="' . $maxRows . '" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['department_acronym'] ?: ($record['department_name'] ?? '')) . '</td>';
                    echo '<td rowspan="' . $maxRows . '" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300">' . htmlspecialchars($record['title']) . '</td>';
                    echo '<td rowspan="' . $maxRows . '" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-pre-wrap text-sm">' . htmlspecialchars($record['coa_observation'] ?? '') . '</td>';
                }

                $rec = isset($recs[$i]) ? trim($recs[$i]) : '';
                $just = isset($justs[$i]) ? trim($justs[$i]) : '';

                $borderStyle = "";
                if ($i > 0)
                    $borderStyle .= "border-top: none; ";
                if ($i < $maxRows - 1)
                    $borderStyle .= "border-bottom: none; ";

                echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-sm whitespace-pre-wrap" style="' . $borderStyle . '">' . htmlspecialchars($rec) . '</td>';
                echo '<td class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-sm whitespace-pre-wrap" style="' . $borderStyle . '">' . htmlspecialchars($just) . '</td>';
                echo '</tr>';
            }
        }
    }
    else {
        echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-gray-500">No records found</td></tr>';
    }
}
catch (Exception $e) {
    echo '<tr><td colspan="7" class="border border-gray-300 dark:border-gray-600 px-4 py-3 text-center text-red-500">Error loading data: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Showing <?php echo($offset + 1); ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> of <?php echo $totalItems; ?> records
        </div>
        <div class="flex gap-2">
            <?php
    $paginationQuery = '';
    if (!empty($filterDateType))
        $paginationQuery .= '&date_filter=' . urlencode($filterDateType);
    if (!empty($filterDept))
        $paginationQuery .= '&department_id=' . urlencode($filterDept);
    if (!empty($filterSearch))
        $paginationQuery .= '&search=' . urlencode($filterSearch);
    if (!empty($filterMonth))
        $paginationQuery .= '&selected_month=' . urlencode($filterMonth);
    if (!empty($filterYear))
        $paginationQuery .= '&selected_year=' . urlencode($filterYear);
    if (!empty($filterDateFrom))
        $paginationQuery .= '&date_from=' . urlencode($filterDateFrom);
    if (!empty($filterDateTo))
        $paginationQuery .= '&date_to=' . urlencode($filterDateTo);
    if ($itemsPerPage !== 10)
        $paginationQuery .= '&limit=' . $itemsPerPage;
?>
            
            <?php if ($currentPageNum > 1): ?>
            <a href="?page=<?php echo $currentPageNum - 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Previous
            </a>
            <?php
    endif; ?>

            <?php
    $startPage = max(1, $currentPageNum - 2);
    $endPage = min($totalPages, $currentPageNum + 2);

    if ($startPage > 1): ?>
            <a href="?page=1<?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">1</a>
            <?php if ($startPage > 2): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php
        endif; ?>
            <?php
    endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 rounded-lg transition <?php echo($i === $currentPageNum) ? 'bg-blue-600 text-white' : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                <?php echo $i; ?>
            </a>
            <?php
    endfor; ?>

            <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
            <span class="px-4 py-2 text-gray-600 dark:text-gray-400">...</span>
            <?php
        endif; ?>
            <a href="?page=<?php echo $totalPages; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"><?php echo $totalPages; ?></a>
            <?php
    endif; ?>

            <?php if ($currentPageNum < $totalPages): ?>
            <a href="?page=<?php echo $currentPageNum + 1; ?><?php echo $paginationQuery; ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Next
            </a>
            <?php
    endif; ?>
        </div>
    </div>
    <?php
endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'app/views/layouts/master.php';
?>
