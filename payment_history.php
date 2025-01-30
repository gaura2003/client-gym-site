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
<div class="min-h-screen bg-gradient-to-b from-gray-900 to-black py-12 sm:py-16 lg:py-20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-white mb-8 text-center">Payment History</h1>

        <?php if (empty($payments)): ?>
            <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl p-8 text-center">
                <div class="text-yellow-400 text-lg">
                    No payment history found. Start your fitness journey today!
                </div>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($payments as $payment): ?>
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
                        <!-- Header Section -->
                        <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                            <div class="flex justify-between items-center">
                                <h2 class="text-xl font-bold text-gray-900">
                                    <?php echo htmlspecialchars($payment['gym_name']); ?>
                                </h2>
                                <span class="px-4 py-1 rounded-full text-sm font-medium
                                    <?php echo $payment['status'] === 'completed' 
                                        ? 'bg-green-900 text-green-100' 
                                        : 'bg-red-900 text-red-100'; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-yellow-400 text-sm">Plan Details</label>
                                        <p class="text-white text-lg font-medium">
                                            <?php echo htmlspecialchars($payment['plan_name']); ?> - 
                                            <?php echo htmlspecialchars($payment['duration']); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-yellow-400 text-sm">Membership Period</label>
                                        <p class="text-white">
                                            <?php echo date('d M Y', strtotime($payment['start_date'])); ?> - 
                                            <?php echo date('d M Y', strtotime($payment['end_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-yellow-400 text-sm">Payment Date</label>
                                        <p class="text-white">
                                            <?php echo date('d M Y', strtotime($payment['payment_date'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-yellow-400 text-sm">Amount Paid</label>
                                        <p class="text-2xl font-bold text-white">
                                            ₹<?php echo number_format($payment['amount'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
