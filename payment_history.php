<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT 
        p.*,
        um.start_date,
        um.end_date,
        um.status as membership_status,
        um.plan_id as membership_plan_id,
        g.name as gym_name,
        gmp.tier as plan_name,
        gmp.duration,
        gmp.plan_id as gym_plan_id,
        gmp.price as plan_price
    FROM payments p
    JOIN user_memberships um ON p.membership_id = um.id
    JOIN gyms g ON p.gym_id = g.gym_id
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    WHERE p.user_id = :user_id 
    ORDER BY p.payment_date DESC
");

$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Your Payment History</h1>

    <?php if (empty($payments)): ?>
        <div class="text-center text-gray-500 text-lg">
            No payment history found. Start your fitness journey today!
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($payments as $payment): ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <!-- Header Section -->
                    <div class="p-4 bg-blue-600 text-white">
                        <h2 class="text-lg font-semibold">
                            <?php echo htmlspecialchars($payment['gym_name']); ?>
                        </h2>
                        <p class="text-sm">
                            Paid on: <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                        </p>
                    </div>

                    <!-- Details Section -->
                    <div class="p-4 space-y-4">
                        <!-- Plan and Duration -->
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-medium text-gray-600">
                                <span class="font-bold">Plan:</span> <?php echo htmlspecialchars($payment['plan_name']); ?>
                            </p>
                            <p class="text-sm font-medium text-gray-600">
                                <span class="font-bold">Duration:</span> <?php echo htmlspecialchars($payment['duration']); ?>
                            </p>
                        </div>
                        <p class="text-xs text-gray-500">
                            <?php echo date('M j, Y', strtotime($payment['start_date'])); ?> - 
                            <?php echo date('M j, Y', strtotime($payment['end_date'])); ?>
                        </p>

                        <!-- Amount -->
                        <div class="flex justify-between items-center">
                            <p class="text-lg font-bold text-gray-800">
                                ₹<?php echo number_format($payment['amount'], 2); ?>
                            </p>
                            <!-- Status Badge -->
                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                <?php echo $payment['status'] === 'completed' 
                                    ? 'bg-green-100 text-green-800' 
                                    : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
