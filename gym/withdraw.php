<?php
    session_start();
    require_once '../config/database.php';

    if (! isset($_SESSION['owner_id'])) {
        header('Location: login.php');
        exit;
    }

    $db       = new GymDatabase();
    $conn     = $db->getConnection();
    $owner_id = $_SESSION['owner_id'];

    // Get total available balance
    $balanceQuery = "
    SELECT
        COALESCE(SUM(CASE
            WHEN source_type = 'visit' THEN amount
            ELSE 0
        END), 0) as visit_revenue,
        COALESCE(SUM(amount), 0) as total_revenue
    FROM gym_revenue gr
    JOIN gyms g ON gr.gym_id = g.gym_id
    WHERE g.owner_id = ?
    AND gr.date <= CURRENT_DATE()
";
    $balanceStmt = $conn->prepare($balanceQuery);
    $balanceStmt->execute([$owner_id]);
    $balance = $balanceStmt->fetch(PDO::FETCH_ASSOC);

    // Get withdrawal history
    $historyQuery = "
    SELECT w.*, g.name as gym_name
    FROM withdrawals w
    JOIN gyms g ON w.gym_id = g.gym_id
    WHERE g.owner_id = ?
    ORDER BY w.created_at DESC
";
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->execute([$owner_id]);
    $withdrawals = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Withdrawal Form -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Withdraw Funds</h2>

            <div class="mb-6">
                <h3 class="text-lg font-semibold">Available Balance</h3>
                <p class="text-3xl font-bold <?php echo ($balance['total_revenue'] >= 0) ? 'text-green-600' : 'text-red-600'; ?>">
    ₹<?php echo number_format($balance['total_revenue'], 2); ?></p>

            </div>

            <form action="process_withdrawal.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input type="number" name="amount" min="500" max="<?php echo $balance['total_revenue']; ?>"
                           step="0.01" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <p class="text-sm text-gray-500 mt-1">Minimum withdrawal: ₹1,000</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Account</label>
                    <select name="bank_account" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="account1">Primary Account (**** 1234)</option>
                        <option value="account2">Secondary Account (**** 5678)</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Request Withdrawal
                </button>
            </form>
        </div>

        <!-- Withdrawal History -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Withdrawal History</h2>

            <div class="space-y-4">
                <?php foreach ($withdrawals as $withdrawal): ?>
                    <div class="border-b pb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold">₹<?php echo number_format($withdrawal['amount'], 2); ?></p>
                                <p class="text-sm text-gray-600"><?php echo $withdrawal['gym_name']; ?></p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs
                                <?php echo $withdrawal['status'] === 'completed' ? 'bg-green-100 text-green-800' :
                                ($withdrawal['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($withdrawal['status']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php echo date('M j, Y g:i A', strtotime($withdrawal['created_at'])); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
