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

// Fetch payment history
$stmt = $conn->prepare("
    SELECT p.*, um.plan_id, mp.name as plan_name 
    FROM payments p 
    LEFT JOIN user_memberships um ON p.membership_id = um.id 
    LEFT JOIN membership_plans mp ON um.plan_id = mp.id 
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php 
                            if ($payment['plan_name']) {
                                echo htmlspecialchars($payment['plan_name']) . ' Membership';
                            } else {
                                echo 'Gym Visit';
                            }
                            ?>
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
            </tbody>
        </table>
    </div>
</div>
