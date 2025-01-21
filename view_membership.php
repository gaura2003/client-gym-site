<?php
session_start();
require 'config/database.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch all active memberships of the user (including those without completed payments)
$stmt = $conn->prepare("
    SELECT um.*, gmp.tier AS plan_name, gmp.inclusions, gmp.duration,
           g.name AS gym_name, g.address, p.status AS payment_status
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    JOIN gyms g ON gmp.gym_id = g.gym_id
    LEFT JOIN payments p ON um.id = p.membership_id
    WHERE um.user_id = ?
    AND um.status = 'active'
    ORDER BY um.start_date DESC
");
$stmt->execute([$user_id]);
$memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available plans (those that are active)
$stmt = $conn->prepare("SELECT * FROM membership_plans WHERE status='active' ");
$stmt->execute();
$available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>
<div class="container mx-auto px-4 py-8">
    <!-- User Memberships -->
    <?php if ($memberships): ?>
        <h2 class="text-2xl font-bold mb-6">Your Active Memberships</h2>
        <?php foreach ($memberships as $membership): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-xl font-bold mb-4"><?php echo htmlspecialchars($membership['plan_name']); ?></h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600">Valid until: <?php echo date('F j, Y', strtotime($membership['end_date'])); ?></p>
                        <p class="text-gray-600">Status: <?php echo ucfirst($membership['status']); ?></p>
                    </div>
                    <div>
                        <h2 class="font-bold text-2xl">Gym: <?php echo htmlspecialchars($membership['gym_name']); ?></h2>
                        <p class="text-gray-600">Address: <?php echo htmlspecialchars($membership['address']); ?></p>
                        <p class="text-gray-600">Payment Status: <?php echo ucfirst($membership['payment_status']); ?></p>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Inclusions</h3>
                    <ul class="list-disc list-inside space-y-2">
                        <?php
                            $inclusions = explode(',', $membership['inclusions']);
                        foreach ($inclusions as $inclusion): ?>
                            <li><?php echo htmlspecialchars(trim($inclusion)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-gray-600">You do not have any active memberships.</p>
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
    <!-- Hidden input to send the plan ID -->
    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">

    <!-- Hidden input to send the min_price -->
    <input type="hidden" name="min_price" value="<?php echo $plan['price']; ?>">

    <button type="submit" 
            class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
        Select Plan
    </button>
</form>

            </div>
        <?php endforeach; ?>
    </div>
</div>
