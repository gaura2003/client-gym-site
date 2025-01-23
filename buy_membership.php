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

$duration_mapping = [
    'Daily' => 1,
    'Weekly' => 7,
    'Bi-Weekly' => 14,
    'Semi-Monthly' => 15,
    'Monthly' => 30,
    'Quarterly' => 90,
    'Half Yearly' => 180,
    'Yearly' => 365
];

$duration_days = $duration_mapping[$plan['duration']];

// Calculate dates using DateTime for accuracy
$start_date = new DateTime(date('Y-m-d'));
$end_date = clone $start_date;
$end_date->modify("+$duration_days days");

// Format dates for display
$start_date = $start_date->format('Y-m-d');
$end_date = $end_date->format('Y-m-d');
$total_days = $duration_days;

// Calculate the membership period in a human-readable format
$membership_period = "$start_date to $end_date";


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
            
            <div class="mb-4">
                <p><strong>Membership Period:</strong> <?php echo $membership_period; ?></p>
                <p><strong>Total Membership Days:</strong> <?php echo $total_days; ?> days</p>
            </div>
            
            <form action="process_membership.php" method="POST">
    <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
    <input type="hidden" name="gym_id" value="<?php echo $gym_id; ?>">
    <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
    <button type="submit" 
            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
        Proceed to Payment
    </button>
</form>

        </div>
    </div>
</div>
