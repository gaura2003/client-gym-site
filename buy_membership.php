<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$plan_id = filter_input(INPUT_GET, 'plan_id', FILTER_VALIDATE_INT);
$gym_id = filter_input(INPUT_GET, 'gym_id', FILTER_VALIDATE_INT);

if (!$plan_id || !$gym_id) {
    header('Location: gyms.php');
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch plan details
$stmt = $conn->prepare("SELECT * FROM gym_membership_plans WHERE plan_id = ? AND gym_id = ?");
$stmt->execute([$plan_id, $gym_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    header('Location: gym_details.php?gym_id=' . $gym_id);
    exit();
}

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Confirm Membership Purchase</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4"><?php echo ucfirst($plan['tier']); ?> Plan</h2>
            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($plan['inclusions']); ?></p>
            <div class="text-2xl font-bold mb-6">
                ₹<?php echo number_format($plan['price'], 2); ?>
                <span class="text-sm text-gray-600 font-normal">
                    /<?php echo strtolower($plan['duration']); ?>
                </span>
            </div>
            
            <form action="process_membership.php" method="POST">
                <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
                <input type="hidden" name="gym_id" value="<?php echo $gym_id; ?>">
                <button type="submit" 
                        class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Proceed to Payment
                </button>
            </form>
        </div>
    </div>
</div>
