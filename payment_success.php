<?php
include 'includes/navbar.php';

session_start();
require_once 'config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'] ?? null; // Ensure user_id is set from session

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $data = json_decode(file_get_contents('php://input'), true);

    // Get the membership details to calculate the amount
    $stmt = $conn->prepare("SELECT gmp.price, um.id, um.plan_id
                            FROM user_memberships um
                            JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
                            WHERE um.id = ?");
    $stmt->execute([$data['membership_id']]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($membership) {
        // Get the membership price
        $membership_price = $membership['price'];

        // Check if user has enough balance
        $stmt = $conn->prepare("SELECT balance FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['balance'] >= $membership_price) {
            // Deduct the membership price from the user's balance
            $new_balance = $user['balance'] - $membership_price;
            $update_balance_stmt = $conn->prepare("UPDATE users SET balance = ? WHERE user_id = ?");
            $update_balance_stmt->execute([$new_balance, $user_id]);

            // Record the transaction for the user
            $transaction_stmt = $conn->prepare("INSERT INTO transactions (user_id, gym_id, amount, transaction_type) 
                                                VALUES (?, ?, ?, 'debit')");
            $transaction_stmt->execute([$user_id, $membership['gym_id'], $membership_price]);

            // Credit the gym's balance
            $update_gym_balance_stmt = $conn->prepare("UPDATE gyms SET balance = balance + ? WHERE gym_id = ?");
            $update_gym_balance_stmt->execute([$membership_price, $membership['gym_id']]);

            // Record the transaction for the gym
            $gym_transaction_stmt = $conn->prepare("INSERT INTO transactions (user_id, gym_id, amount, transaction_type) 
                                                    VALUES (?, ?, ?, 'credit')");
            $gym_transaction_stmt->execute([$user_id, $membership['gym_id'], $membership_price]);

            // Update payment status
            $stmt = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ? WHERE membership_id = ?");
            $stmt->execute([$data['razorpay_payment_id'], $data['membership_id']]);

            // Update membership status
            $stmt = $conn->prepare("
            UPDATE user_memberships 
            SET payment_status = 'paid', amount = ? 
            WHERE id = ?");
        $stmt->execute([$membership_price, $data['membership_id']]);
        } else {
            // Handle case where user balance is not enough
            echo "Insufficient balance.";
        }
    }
}

// Fetch gym_id for the purchased membership
$stmt = $conn->prepare("
  SELECT um.*, gmp.tier as plan_name, gmp.inclusions, gmp.duration,
         g.name as gym_name, g.address, p.status as payment_status, g.gym_id
  FROM user_memberships um
  JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
  JOIN gyms g ON gmp.gym_id = g.gym_id
  JOIN payments p ON um.id = p.membership_id
  WHERE um.user_id = ?
  AND um.status = 'active'
  AND p.status = 'completed'
  ORDER BY um.start_date DESC
");
$stmt->execute([$user_id]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 text-center">
        <div class="text-green-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4">Payment Successful!</h1>
        <p class="text-gray-600 mb-6">Your membership has been activated successfully.</p>

        <?php if ($membership): ?>
            <div class="space-y-4">
                <a href="schedule.php?gym_id=<?php echo $membership['gym_id']; ?>"
                    class="block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 text-center">
                    Schedule Workout
                </a>
                <a href="dashboard.php"
                    class="block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 text-center">
                    Go to Dashboard
                </a>
            </div>
        <?php else: ?>
            <p class="text-red-500">Sorry, we couldn't fetch your membership details. Please try again later.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    setTimeout(function () {
        <?php if ($membership): ?>
            window.location.href = 'schedule.php?gym_id=<?php echo $membership['gym_id']; ?>'; // Redirect after 3 seconds
        <?php else: ?>
            window.location.href = 'dashboard.php'; // Redirect to dashboard if no membership is found
        <?php endif; ?>
    }, 3000); // 3000 milliseconds = 3 seconds
</script>
