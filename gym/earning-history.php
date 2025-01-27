<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: login.php");
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();
$owner_id = $_SESSION['owner_id'];

// Get gym details and balance
$stmt = $conn->prepare("
    SELECT gym_id, name, balance, last_payout_date 
    FROM gyms 
    WHERE owner_id = ?
");
$stmt->execute([$owner_id]);
$gym = $stmt->fetch(PDO::FETCH_ASSOC);
$gym_id = $gym['gym_id'];

// Fetch membership sales with revenue calculations
$stmt = $conn->prepare("
    SELECT 
        um.*,
        gmp.plan_name as plan_name,
        gmp.tier,
        gmp.duration,
        gmp.price as plan_price,
        u.username,
        CASE 
            WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end 
            THEN fbc.gym_cut_percentage
            ELSE coc.gym_owner_cut_percentage
        END as gym_cut_percentage,
        CASE 
            WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end 
            THEN (gmp.price * fbc.gym_cut_percentage / 100)
            ELSE (gmp.price * coc.gym_owner_cut_percentage / 100)
        END as gym_earnings
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    JOIN users u ON um.user_id = u.id
    LEFT JOIN cut_off_chart coc ON gmp.tier = coc.tier AND gmp.duration = coc.duration
    LEFT JOIN fee_based_cuts fbc ON gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end
    WHERE um.gym_id = ? AND um.payment_status = 'paid'
    ORDER BY um.created_at DESC
");
$stmt->execute([$gym_id]);
$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate analytics
$total_earnings = array_sum(array_column($memberships, 'gym_earnings'));
$total_memberships = count($memberships);
$membership_by_tier = array_count_values(array_column($memberships, 'tier'));
$membership_by_duration = array_count_values(array_column($memberships, 'duration'));

// Monthly trends
$monthly_sales = [];
foreach($memberships as $membership) {
    $month = date('Y-m', strtotime($membership['created_at']));
    if(!isset($monthly_sales[$month])) {
        $monthly_sales[$month] = [
            'count' => 0,
            'earnings' => 0
        ];
    }
    $monthly_sales[$month]['count']++;
    $monthly_sales[$month]['earnings'] += $membership['gym_earnings'];
}

include '../includes/navbar.php';
?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Earnings Dashboard</h1>
                        <p class="text-gray-300"><?= htmlspecialchars($gym['name']) ?></p>
                    </div>
                </div>
                <div class="text-white">
                    <p class="text-sm">Last Payout</p>
                    <p class="font-semibold"><?= $gym['last_payout_date'] ? date('M d, Y', strtotime($gym['last_payout_date'])) : 'No payouts yet' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Available Balance</h3>
                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                    <i class="fas fa-wallet text-green-600"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-green-600">₹<?= number_format($gym['balance'], 2) ?></p>
            <button onclick="window.location.href='withdraw.php'" 
                    class="mt-4 w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                <i class="fas fa-money-bill-wave mr-2"></i>Withdraw Funds
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Total Memberships</h3>
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-blue-600"><?= $total_memberships ?></p>
            <p class="mt-2 text-gray-600">Active members this month</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Total Earnings</h3>
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-chart-line text-purple-600"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-purple-600">₹<?= number_format($total_earnings, 2) ?></p>
            <p class="mt-2 text-gray-600">Lifetime earnings</p>
        </div>
    </div>

    <!-- Distribution Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-6 flex items-center">
                <i class="fas fa-layer-group text-yellow-500 mr-2"></i>
                Distribution by Tier
            </h3>
            <div class="space-y-4">
                <?php foreach($membership_by_tier as $tier => $count): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700"><?= $tier ?></span>
                        <span class="px-4 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                            <?= $count ?> members
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-6 flex items-center">
                <i class="fas fa-clock text-yellow-500 mr-2"></i>
                Distribution by Duration
            </h3>
            <div class="space-y-4">
                <?php foreach($membership_by_duration as $duration => $count): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-700"><?= $duration ?></span>
                        <span class="px-4 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                            <?= $count ?> members
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-700 mb-6 flex items-center">
            <i class="fas fa-chart-bar text-yellow-500 mr-2"></i>
            Monthly Sales Trends
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Memberships</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($monthly_sales as $month => $data): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('F Y', strtotime($month)) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    <?= $data['count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-green-600 font-medium">
                                ₹<?= number_format($data['earnings'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-700 flex items-center">
                <i class="fas fa-receipt text-yellow-500 mr-2"></i>
                Recent Membership Sales
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Your Cut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach(array_slice($memberships, 0, 10) as $membership): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4"><?= htmlspecialchars($membership['username']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                    <?= htmlspecialchars($membership['plan_name']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">₹<?= number_format($membership['plan_price'], 2) ?></td>
                            <td class="px-6 py-4 text-green-600 font-medium">₹<?= number_format($membership['gym_earnings'], 2) ?></td>
                            <td class="px-6 py-4 text-gray-500"><?= date('M d, Y', strtotime($membership['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
