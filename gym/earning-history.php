<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

$db       = new GymDatabase();
$conn     = $db->getConnection();
$owner_id = $_SESSION['owner_id'];

// Get filter parameters
$filter_plan   = $_GET['plan'] ?? '';
$filter_date   = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Base query to get all required payment information
$query = "
SELECT p.*, g.name as gym_name, mp.name as plan_name, u.username
FROM payments p
JOIN gyms g ON p.gym_id = g.gym_id
JOIN membership_plans mp ON p.membership_id = mp.id
JOIN users u ON p.user_id = u.id
WHERE g.owner_id = ?
";

// Add filters
$params = [$owner_id];

if ($filter_plan) {
    $query .= " AND mp.id = ?";
    $params[] = $filter_plan;
}
if ($filter_date) {
    $query .= " AND DATE(p.payment_date) = ?";
    $params[] = $filter_date;
}
if ($filter_status) {
    $query .= " AND p.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY p.payment_date DESC";

// Execute main query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total earnings - include all completed payments
$total_query = "
SELECT COALESCE(SUM(p.amount), 0) as total
FROM payments p
JOIN gyms g ON p.gym_id = g.gym_id
WHERE g.owner_id = ? AND p.status = 'completed'
";
$total_stmt = $conn->prepare($total_query);
$total_stmt->execute([$owner_id]);
$total_earnings = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get monthly earnings
$monthly_query = "
SELECT COALESCE(SUM(p.amount), 0) as monthly
FROM payments p
JOIN gyms g ON p.gym_id = g.gym_id
WHERE g.owner_id = ?
AND p.status = 'completed'
AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())
";
$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->execute([$owner_id]);
$monthly_earnings = $monthly_stmt->fetch(PDO::FETCH_ASSOC)['monthly'];

// Get pending payments
$pending_query = "
SELECT COALESCE(SUM(p.amount), 0) as pending
FROM payments p
JOIN gyms g ON p.gym_id = g.gym_id
WHERE g.owner_id = ? AND p.status = 'pending'
";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->execute([$owner_id]);
$pending_amount = $pending_stmt->fetch(PDO::FETCH_ASSOC)['pending'];

// Get all membership plans for filter
$plans_query = "
SELECT DISTINCT mp.id, mp.name
FROM membership_plans mp
JOIN payments p ON mp.id = p.membership_id
JOIN gyms g ON p.gym_id = g.gym_id
WHERE g.owner_id = ?
";
$plans_stmt = $conn->prepare($plans_query);
$plans_stmt->execute([$owner_id]);
$plans = $plans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate visit revenue
$visitRevenueQuery = "
SELECT COALESCE(SUM(CASE 
    WHEN source_type = 'visit' THEN amount
    WHEN source_type = 'transfer_deduction' THEN amount
    ELSE 0 
END), 0) as visit_revenue
FROM gym_revenue 
WHERE gym_id IN (SELECT gym_id FROM gyms WHERE owner_id = ?)
AND MONTH(date) = MONTH(CURRENT_DATE())
AND YEAR(date) = YEAR(CURRENT_DATE())
";
$visitStmt = $conn->prepare($visitRevenueQuery);
$visitStmt->execute([$owner_id]);
$visit_revenue = $visitStmt->fetchColumn();

// Get total withdrawals
$withdrawalsQuery = "
SELECT COALESCE(SUM(w.amount), 0) as total_withdrawn
FROM withdrawals w
JOIN gyms g ON w.gym_id = g.gym_id
WHERE g.owner_id = ? 
AND w.status = 'completed'";
$withdrawStmt = $conn->prepare($withdrawalsQuery);
$withdrawStmt->execute([$owner_id]);
$total_withdrawn = $withdrawStmt->fetchColumn();

// Calculate updated balance
$updated_total = $visit_revenue - $total_withdrawn;

include "../includes/navbar.php";
?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Payment History</h1>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form class="flex gap-4">
            <select name="plan" class="rounded border p-2">
                <option value="">All Plans</option>
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo $plan['id'] ?>"<?php echo $filter_plan == $plan['id'] ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($plan['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date" class="rounded border p-2" value="<?php echo $filter_date ?>">

            <select name="status" class="rounded border p-2">
                <option value="">All Status</option>
                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?php echo $filter_status == 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>

            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Filter
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-2">This Month</h3>
            <p class="text-3xl font-bold text-blue-600">
                ₹<?php echo number_format($monthly_earnings, 2) ?>
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-2">Pending Payments</h3>
            <p class="text-3xl font-bold text-yellow-600">
                ₹<?php echo number_format($pending_amount, 2) ?>
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold mb-2">Total Earnings</h3>
                <a href="withdraw.php" class="inline-block mt-4 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                    Withdraw Funds
                </a>
            </div>
            <p class="text-3xl font-bold <?php echo($updated_total < 0) ? 'text-red-600' : 'text-green-600'; ?> mt-2">
                ₹<?php echo number_format(abs($updated_total), 2) ?>
            </p>
            <p class="text-sm text-gray-600">Total Withdrawn: ₹<?php echo number_format($total_withdrawn, 2) ?></p>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No payments found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($payment['username']) ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($payment['plan_name']) ?></td>
                            <td class="px-6 py-4">₹<?php echo number_format($payment['amount'], 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs
                                    <?php echo $payment['status'] === 'completed' ? 'bg-green-100 text-green-800' : '' ?>
                                    <?php echo $payment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' ?>
                                    <?php echo $payment['status'] === 'failed' ? 'bg-red-100 text-red-800' : '' ?>
                                ">
                                    <?php echo ucfirst($payment['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>
</body>
</html>
