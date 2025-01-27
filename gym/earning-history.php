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
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700">Total Balance</h3>
            <p class="text-3xl font-bold text-green-600">₹<?= number_format($gym['balance'], 2) ?></p>
            <button onclick="window.location.href='withdraw.php'" 
                class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Withdraw Funds
            </button>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700">Total Memberships Sold</h3>
            <p class="text-3xl font-bold text-blue-600"><?= $total_memberships ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700">Total Earnings</h3>
            <p class="text-3xl font-bold text-purple-600">₹<?= number_format($total_earnings, 2) ?></p>
        </div>
    </div>

    <!-- Membership Distribution -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Distribution by Tier</h3>
            <div class="space-y-4">
                <?php foreach($membership_by_tier as $tier => $count): ?>
                    <div class="flex justify-between items-center">
                        <span class="font-medium"><?= $tier ?></span>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                            <?= $count ?> members
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Distribution by Duration</h3>
            <div class="space-y-4">
                <?php foreach($membership_by_duration as $duration => $count): ?>
                    <div class="flex justify-between items-center">
                        <span class="font-medium"><?= $duration ?></span>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full">
                            <?= $count ?> members
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Monthly Sales Trends</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Memberships</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($monthly_sales as $month => $data): ?>
                        <tr>
                            <td class="px-6 py-4"><?= date('F Y', strtotime($month)) ?></td>
                            <td class="px-6 py-4"><?= $data['count'] ?></td>
                            <td class="px-6 py-4">₹<?= number_format($data['earnings'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Memberships -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Recent Membership Sales</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Your Cut</th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach(array_slice($memberships, 0, 10) as $membership): ?>
                    <tr>
                        <td class="px-6 py-4"><?= htmlspecialchars($membership['username']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($membership['plan_name']) ?></td>
                        <td class="px-6 py-4">₹<?= number_format($membership['plan_price'], 2) ?></td>
                        <td class="px-6 py-4">₹<?= number_format($membership['gym_earnings'], 2) ?></td>
                        <td class="px-6 py-4"><?= date('M d, Y', strtotime($membership['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
