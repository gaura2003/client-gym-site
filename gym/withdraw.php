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

    $stmt = $conn->prepare("SELECT COUNT(*) FROM payment_methods WHERE owner_id = ?");
    $stmt->execute([$_SESSION['owner_id']]);
    $hasPaymentMethods = $stmt->fetchColumn() > 0;

    // Get payment methods
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE owner_id = ? ORDER BY is_primary DESC");
$stmt->execute([$_SESSION['owner_id']]);
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include '../includes/navbar.php';
?><div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Withdraw Funds</h1>
                    <p class="text-gray-300">Available Balance: ₹<?php echo number_format($balance['total_revenue'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$hasPaymentMethods): ?>
    <!-- Payment Method Setup Section -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="h-24 w-24 mx-auto mb-4">
                <i class="fas fa-university text-yellow-500 text-6xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Set Up Payment Method</h2>
            <p class="text-gray-600 mt-2">Add a payment method to start withdrawing your earnings</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Bank Account Form -->
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-landmark text-yellow-500 mr-2"></i>
                    Bank Account
                </h3>
                <form action="add_payment_method.php" method="POST" class="space-y-4">
                    <input type="hidden" name="method_type" value="bank">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Holder Name</label>
                        <input type="text" name="account_name" required 
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Account Number</label>
                        <input type="text" name="account_number" required 
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">IFSC Code</label>
                        <input type="text" name="ifsc_code" required 
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                        <input type="text" name="bank_name" required 
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                    </div>

                    <button type="submit" 
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus-circle mr-2"></i>Add Bank Account
                    </button>
                </form>
            </div>

            <!-- UPI Form -->
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-mobile-alt text-yellow-500 mr-2"></i>
                    UPI ID
                </h3>
                <form action="add_payment_method.php" method="POST" class="space-y-4">
                    <input type="hidden" name="method_type" value="upi">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">UPI ID</label>
                        <input type="text" name="upi_id" required 
                               placeholder="example@upi"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                        <p class="mt-1 text-sm text-gray-500">Enter your UPI ID linked with your bank account</p>
                    </div>

                    <button type="submit" 
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus-circle mr-2"></i>Add UPI ID
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Withdrawal Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Withdrawal Form -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="mb-8">
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Available Balance</h3>
                <div class="flex items-center space-x-4">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-wallet text-green-600"></i>
                    </div>
                    <p class="text-3xl font-bold text-green-600">₹<?php echo number_format($balance['total_revenue'], 2); ?></p>
                </div>
            </div>

            <form action="process_withdrawal.php" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-money-bill-alt mr-2"></i>Withdrawal Amount
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₹</span>
                        <input type="number" name="amount" 
                               min="500" max="<?php echo $balance['total_revenue']; ?>"
                               step="0.01" required
                               class="pl-8 w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Minimum withdrawal: ₹1,000</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-university mr-2"></i>Select Payment Method
                    </label>
                    <select name="payment_method" required 
                            class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                        <option value="">Choose a payment method</option>
                        <?php foreach ($payment_methods as $method): ?>
                            <option value="<?php echo $method['id']; ?>">
                                <?php if ($method['method_type'] === 'bank'): ?>
                                    <?php echo htmlspecialchars($method['bank_name']); ?> (**** <?php echo substr($method['account_number'], -4); ?>)
                                <?php else: ?>
                                    UPI: <?php echo htmlspecialchars($method['upi_id']); ?>
                                <?php endif; ?>
                                <?php echo $method['is_primary'] ? ' (Primary)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit"
                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Request Withdrawal
                </button>
            </form>
        </div>

        <!-- Withdrawal History -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-6 flex items-center">
                <i class="fas fa-history text-yellow-500 mr-2"></i>
                Withdrawal History
            </h2>

            <div class="space-y-4">
                <?php if (count($withdrawals) > 0): ?>
                    <?php foreach ($withdrawals as $withdrawal): ?>
                        <div class="p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-lg font-semibold text-gray-900">
                                        ₹<?php echo number_format($withdrawal['amount'], 2); ?>
                                    </p>
                                    <p class="text-sm text-gray-600"><?php echo $withdrawal['gym_name']; ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    <?php echo $withdrawal['status'] === 'completed' 
                                        ? 'bg-green-100 text-green-800' 
                                        : ($withdrawal['status'] === 'pending' 
                                            ? 'bg-yellow-100 text-yellow-800' 
                                            : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($withdrawal['status']); ?>
                                </span>
                            </div>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <i class="fas fa-clock mr-2"></i>
                                <?php echo date('M j, Y g:i A', strtotime($withdrawal['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="h-24 w-24 mx-auto mb-4 text-gray-300">
                            <i class="fas fa-file-invoice-dollar text-6xl"></i>
                        </div>
                        <p class="text-gray-500">No withdrawal history available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Methods Management -->
    <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
        <h2 class="text-xl font-semibold text-gray-700 mb-6 flex items-center">
            <i class="fas fa-university text-yellow-500 mr-2"></i>
            Manage Payment Methods
        </h2>

        <div class="space-y-4">
            <?php foreach ($payment_methods as $method): ?>
                <div class="flex justify-between items-center p-4 border rounded-lg hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-center space-x-4">
                        <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="<?php echo $method['method_type'] === 'bank' ? 'fas fa-university' : 'fas fa-mobile-alt'; ?> text-gray-600"></i>
                        </div>
                        <div>
                            <?php if ($method['method_type'] === 'bank'): ?>
                                <p class="font-medium"><?= htmlspecialchars($method['bank_name']) ?></p>
                                <p class="text-sm text-gray-600">
                                    **** <?= substr($method['account_number'], -4) ?>
                                    <?php echo $method['is_primary'] ? ' (Primary)' : ''; ?>
                                </p>
                            <?php else: ?>
                                <p class="font-medium">UPI ID</p>
                                <p class="text-sm text-gray-600">
                                    <?= htmlspecialchars($method['upi_id']) ?>
                                    <?php echo $method['is_primary'] ? ' (Primary)' : ''; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <?php if (!$method['is_primary']): ?>
                            <button onclick="setAsPrimary(<?= $method['id'] ?>)" 
                                    class="text-blue-600 hover:text-blue-800 px-3 py-1 rounded-lg hover:bg-blue-50 transition-colors duration-200">
                                Set as Primary
                            </button>
                        <?php endif; ?>
                        <button onclick="deletePaymentMethod(<?= $method['id'] ?>)" 
                                class="text-red-600 hover:text-red-800 px-3 py-1 rounded-lg hover:bg-red-50 transition-colors duration-200">
                            Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function setAsPrimary(methodId) {
    fetch('update_payment_method.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            method_id: methodId,
            action: 'set_primary'
        })
    }).then(() => window.location.reload());
}

function deletePaymentMethod(methodId) {
    if (confirm('Are you sure you want to delete this payment method?')) {
        fetch('update_payment_method.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                method_id: methodId,
                action: 'delete'
            })
        }).then(() => window.location.reload());
    }
}
</script>
