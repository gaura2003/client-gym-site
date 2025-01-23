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
    <h1 class="text-3xl font-bold mb-8">Payment History</h1>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gym</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No payment history found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($payment['gym_name']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($payment['plan_name']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($payment['duration']); ?>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('M j, Y', strtotime($payment['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($payment['end_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                ₹<?php echo number_format($payment['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $payment['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
