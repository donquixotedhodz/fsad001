<?php
/**
 * PPE Balance Recalculation Script
 * This script recalculates all PPE balances using the correct formula:
 * Balance = Previous Balance + Credit - Debit
 */

session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in and is superadmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    die("Access denied. Only superadmin can run this script.");
}

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n<title>PPE Balance Recalculation</title>\n";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>\n";
echo "</head>\n<body>\n";
echo "<h1>PPE Balance Recalculation</h1>\n";

try {
    // Start transaction
    $conn->beginTransaction();

    echo "<p class='info'>Starting recalculation...</p>\n";

    // Get all PPE records ordered by ID (chronological order)
    $stmt = $conn->prepare("SELECT id, debit, credit FROM ppe ORDER BY id ASC");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records)) {
        echo "<p class='info'>No PPE records found.</p>\n";
        $conn->rollBack();
        echo "</body></html>";
        exit;
    }

    echo "<p class='info'>Found " . count($records) . " records to recalculate.</p>\n";
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>ID</th><th>Debit</th><th>Credit</th><th>Old Balance</th><th>New Balance</th><th>Difference</th></tr>\n";

    $STARTING_BALANCE = 0.00;
    $currentBalance = $STARTING_BALANCE;
    $totalDifferences = 0;

    foreach ($records as $record) {
        // Get old balance
        $oldBalanceStmt = $conn->prepare("SELECT balance FROM ppe WHERE id = ?");
        $oldBalanceStmt->execute([$record['id']]);
        $oldBalanceRecord = $oldBalanceStmt->fetch(PDO::FETCH_ASSOC);
        $oldBalance = $oldBalanceRecord ? floatval($oldBalanceRecord['balance']) : 0;

        // Calculate new balance with CORRECT formula: Balance = Previous + Credit - Debit
        $currentBalance = $currentBalance + floatval($record['credit']) - floatval($record['debit']);

        // Update the record
        $updateStmt = $conn->prepare("UPDATE ppe SET balance = ? WHERE id = ?");
        $updateStmt->execute([$currentBalance, $record['id']]);

        $difference = $currentBalance - $oldBalance;
        if (abs($difference) > 0.01) {
            $totalDifferences++;
        }

        $diffClass = abs($difference) > 0.01 ? 'error' : 'success';

        echo "<tr>";
        echo "<td>" . $record['id'] . "</td>";
        echo "<td>" . number_format($record['debit'], 2) . "</td>";
        echo "<td>" . number_format($record['credit'], 2) . "</td>";
        echo "<td>" . number_format($oldBalance, 2) . "</td>";
        echo "<td>" . number_format($currentBalance, 2) . "</td>";
        echo "<td class='$diffClass'>" . number_format($difference, 2) . "</td>";
        echo "</tr>\n";
    }

    echo "</table>\n";

    // Update PPE Provident Fund balance
    $updateFundStmt = $conn->prepare("UPDATE ppe_funds SET remaining_balance = ? WHERE fund_name = 'PPE Provident Fund'");
    $updateFundStmt->execute([$currentBalance]);

    // Commit transaction
    $conn->commit();

    echo "<p class='success'><strong>Recalculation completed successfully!</strong></p>\n";
    echo "<p class='info'>Final Balance: <strong>" . number_format($currentBalance, 2) . "</strong></p>\n";
    echo "<p class='info'>Records with differences: <strong>" . $totalDifferences . "</strong></p>\n";
    echo "<p class='info'>PPE Provident Fund balance updated.</p>\n";
    echo "<p><a href='ppe.php'>← Back to PPE</a></p>\n";


}
catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "</body>\n</html>";
?>
