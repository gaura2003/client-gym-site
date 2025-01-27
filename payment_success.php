<?php
session_start();
include 'includes/navbar.php';
require_once 'config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
$membership_id = $_GET['membership_id'] ?? null;
$payment_id = $_GET['payment_id'] ?? null;

$db = new GymDatabase();
$conn = $db->getConnection();

$conn->beginTransaction();
try {
   // Update payment status
$stmt = $conn->prepare("
UPDATE payments 
SET status = 'completed',
    transaction_id = ?,
    payment_date = CURRENT_TIMESTAMP
WHERE membership_id = ? AND user_id = ?
");
$stmt->execute([$payment_id, $membership_id, $user_id]);

    // Update membership status with end date
    $stmt = $conn->prepare("
        UPDATE user_memberships 
        SET payment_status = 'paid',
            status = 'active',
            start_date = CURRENT_DATE,
            end_date = DATE_ADD(CURRENT_DATE, INTERVAL 
                (SELECT CASE duration
                    WHEN 'Daily' THEN 1
                    WHEN 'Weekly' THEN 7
                    WHEN 'Bi-Weekly' THEN 14
                    WHEN 'Semi-Monthly' THEN 15
                    WHEN 'Monthly' THEN 30
                    WHEN 'Quarterly' THEN 90
                    WHEN 'Half Yearly' THEN 180
                    WHEN 'Yearly' THEN 365
                END FROM gym_membership_plans WHERE plan_id = user_memberships.plan_id) DAY)
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$membership_id, $user_id]);

    // Update gym revenue
    $stmt = $conn->prepare("
        UPDATE gyms g
        JOIN user_memberships um ON g.gym_id = um.gym_id
        JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
        SET g.balance = g.balance + gmp.price,
            g.current_occupancy = g.current_occupancy + 1
        WHERE um.id = ?
    ");
    $stmt->execute([$membership_id]);
    // Update user balance
    $stmt = $conn->prepare("
UPDATE users u
JOIN user_memberships um ON u.id = um.user_id
JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
SET u.balance = u.balance + gmp.price
WHERE um.id = ? AND u.id = ?
");
    $stmt->execute([$membership_id, $user_id]);

    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    header('Location: dashboard.php?status=error');
    exit();
}

// Fetch updated membership details for display
$stmt = $conn->prepare("
    SELECT 
        um.*,
        gmp.tier as plan_name,
        gmp.inclusions,
        gmp.duration,
        g.name as gym_name,
        g.address,
        g.gym_id
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    JOIN gyms g ON um.gym_id = g.gym_id
    WHERE um.id = ? AND um.user_id = ?
    AND um.status = 'active'
");
$stmt->execute([$membership_id, $user_id]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if ($membership): ?>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-12">
        <div class="max-w-xl mx-auto px-4">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <!-- Success Header -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">Welcome Aboard!</h1>
                    <p class="text-blue-100">Your membership has been successfully activated</p>
                </div>
    
                <!-- Membership Details -->
                <div class="p-8">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-8">
                        <h2 class="font-semibold text-xl text-gray-800 mb-6">Membership Details</h2>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500">Gym Name</p>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($membership['gym_name'] ?? 'N/A') ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Plan Type</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($membership['plan_name'] ?? 'N/A') ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Duration</p>
                                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($membership['duration'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500">Start Date</p>
                                    <p class="font-semibold text-gray-800">
                                        <?= isset($membership['start_date']) ? date('d M Y', strtotime($membership['start_date'])) : 'N/A' ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">End Date</p>
                                    <p class="font-semibold text-gray-800">
                                        <?= isset($membership['end_date']) ? date('d M Y', strtotime($membership['end_date'])) : 'N/A' ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>
    
                        <!-- Inclusions -->
                        <div class="mt-6">
                            <p class="text-sm text-gray-500 mb-3">Membership Inclusions</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (explode(',', $membership['inclusions'] ?? '') as $inclusion): ?>
                                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">
                                    <?= trim(htmlspecialchars($inclusion)) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
    
                    <!-- Action Buttons -->
                    <div class="space-y-4">
                        <a href="schedule.php?gym_id=<?= htmlspecialchars($membership['gym_id'] ?? '') ?>"
                            class="block w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white text-center py-4 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition duration-300 transform hover:scale-[1.02]">
                            Schedule Your First Workout
                        </a>
                        <a href="dashboard.php"
                            class="block w-full bg-gray-100 text-gray-700 text-center py-4 rounded-xl font-semibold hover:bg-gray-200 transition duration-300">
                            View Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Payment Successful!</h1>
            <p class="text-gray-600 mb-8">Your payment has been processed successfully. View your membership details on the dashboard.</p>
            <a href="dashboard.php" 
               class="inline-block w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-4 rounded-xl font-semibold hover:from-blue-600 hover:to-indigo-700 transition duration-300">
                Go to Dashboard
            </a>
        </div>
    </div>
    <?php endif; ?>
    
<!-- <script>
    setTimeout(function() {
        window.location.href = 'schedule.php?gym_id=<?php echo htmlspecialchars($membership['gym_id']); ?>';
    }, 3000);
</script> -->