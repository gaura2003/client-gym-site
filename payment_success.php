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


if ($membership) {
    ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="text-green-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold mb-4">Membership Activated!</h1>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h2 class="font-semibold text-lg mb-4">Membership Details</h2>
                <div class="space-y-3 text-left">
                    <div>
                        <span class="font-medium">Gym:</span>
                        <span class="text-gray-700"><?php echo htmlspecialchars($membership['gym_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Plan:</span>
                        <span
                            class="text-gray-700"><?php echo htmlspecialchars($membership['plan_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Duration:</span>
                        <span class="text-gray-700"><?php echo htmlspecialchars($membership['duration'] ?? 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Start Date:</span>
                        <span
                            class="text-gray-700"><?php echo isset($membership['start_date']) ? date('d M Y', strtotime($membership['start_date'])) : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="font-medium">End Date:</span>
                        <span
                            class="text-gray-700"><?php echo isset($membership['end_date']) ? date('d M Y', strtotime($membership['end_date'])) : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Status:</span>
                        <span
                            class="text-green-600 font-semibold"><?php echo ucfirst($membership['payment_status'] ?? 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Location:</span>
                        <span class="text-gray-700"><?php echo htmlspecialchars($membership['address'] ?? 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="font-medium">Inclusions:</span>
                        <span
                            class="text-gray-700"><?php echo htmlspecialchars($membership['inclusions'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <a href="schedule.php?gym_id=<?php echo htmlspecialchars($membership['gym_id'] ?? ''); ?>"
                    class="block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 text-center">
                    Schedule Your First Workout
                </a>
                <a href="dashboard.php"
                    class="block bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 text-center">
                    View Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php
} else {
    // Display a message when no membership data is found
    ?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 text-center">
            <h1 class="text-2xl font-bold mb-4">Payment Processed</h1>
            <p class="text-gray-600 mb-6">Your payment has been processed successfully. View your membership details on the
                dashboard.</p>
            <a href="dashboard.php" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                Go to Dashboard
            </a>
        </div>
    </div>
    <?php
}
?>

<!-- <script>
    setTimeout(function() {
        window.location.href = 'schedule.php?gym_id=<?php echo htmlspecialchars($membership['gym_id']); ?>';
    }, 3000);
</script> -->