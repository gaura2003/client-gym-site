<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch current membership
$stmt = $conn->prepare("
    SELECT um.*, mp.name, mp.price, mp.features 
    FROM user_memberships um 
    JOIN membership_plans mp ON um.plan_id = mp.id 
    WHERE um.user_id = :user_id 
    ORDER BY um.start_date DESC LIMIT 1
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$current_membership = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch available plans
$stmt = $conn->prepare("SELECT * FROM membership_plans WHERE status = 'active'");
$stmt->execute();
$available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Current Membership -->
    <?php if ($current_membership): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">Current Membership</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="font-semibold"><?php echo htmlspecialchars($current_membership['name']); ?></p>
                    <p class="text-gray-600">Valid until: <?php echo date('F j, Y', strtotime($current_membership['end_date'])); ?></p>
                    <p class="text-gray-600">Status: <?php echo ucfirst($current_membership['status']); ?></p>
                </div>
                <div>
                    <p class="font-semibold">Features:</p>
                    <?php 
                    $features = json_decode($current_membership['features'], true);
                    foreach ($features as $feature): ?>
                        <p class="text-gray-600">• <?php echo htmlspecialchars($feature); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Available Plans -->
    <h2 class="text-2xl font-bold mb-6">Available Membership Plans</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($available_plans as $plan): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                <p class="text-3xl font-bold mb-4">₹<?php echo number_format($plan['price'], 2); ?></p>
                <p class="text-gray-600 mb-4">Duration: <?php echo $plan['duration_days']; ?> days</p>
                
                <?php if ($plan['visit_limit']): ?>
                    <p class="text-gray-600 mb-4">Visits: <?php echo $plan['visit_limit']; ?></p>
                <?php endif; ?>
                <div class="mb-6">
                    <?php 
                    $features = json_decode($plan['features'], true);
                    foreach ($features as $feature): ?>
                        <p class="text-gray-600">• <?php echo htmlspecialchars($feature); ?></p>
                    <?php endforeach; ?>
                </div>

                <form action="./gyms.php" method="POST">
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                    <button type="submit" 
                            class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        Select Plan
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
