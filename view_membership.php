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

$stmt = $conn->prepare("
    SELECT um.*, gmp.tier AS plan_name, gmp.duration, gmp.price, gmp.inclusions,
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
$stmt = $conn->prepare("SELECT * FROM membership_plans");
$stmt->execute();
$available_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>
<div class="container mx-auto px-4 py-8">
    <!-- User Memberships -->
    <?php if ($memberships): ?>
        <h2 class="text-3xl font-extrabold mb-10 text-center md:text-left text-gray-800">Your Active Memberships</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php foreach ($memberships as $membership): ?>
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl shadow-lg p-6">
                    <h3 class="text-2xl font-semibold mb-4 text-blue-800">
                        <?php echo htmlspecialchars($membership['plan_name']); ?> Plan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-700">
                        <div>
                            <p><span class="font-semibold">Valid until:</span> <?php echo date('F j, Y', strtotime($membership['end_date'])); ?></p>
                            <p><span class="font-semibold">Status:</span> <?php echo ucfirst($membership['status']); ?></p>
                            <p><span class="font-semibold">Price:</span> ₹<?php echo number_format($membership['price'], 2); ?></p>
                        </div>
                        <div>
                            <p><span class="font-semibold">Gym:</span> <?php echo htmlspecialchars($membership['gym_name']); ?></p>
                            <p><span class="font-semibold">Address:</span> <?php echo htmlspecialchars($membership['address']); ?></p>
                            <p><span class="font-semibold">Payment Status:</span> <?php echo ucfirst($membership['payment_status']); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h4 class="text-lg font-semibold text-blue-700 mb-2">Inclusions</h4>
                        <ul class="list-disc list-inside space-y-1 text-gray-700">
                            <?php
                            $inclusions = explode(',', $membership['inclusions']);
                            foreach ($inclusions as $inclusion): ?>
                                <li><?php echo htmlspecialchars(trim($inclusion)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600 text-center text-lg">You do not have any active memberships.</p>
    <?php endif; ?>

    <!-- Available Plans -->
    <h2 class="text-3xl font-extrabold my-12 text-center md:text-left text-gray-800">Available Membership Plans</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($available_plans as $plan): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 hover:scale-105 transform transition">
                <h3 class="text-2xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                <p class="text-4xl font-extrabold text-blue-600 mb-4">₹<?php echo number_format($plan['price'], 2); ?></p>
                <p class="text-gray-600 mb-4"><span class="font-semibold">Duration:</span> <?php echo $plan['duration_days']; ?> days</p>
                <?php if ($plan['visit_limit']): ?>
                    <p class="text-gray-600 mb-4"><span class="font-semibold">Visits:</span> <?php echo $plan['visit_limit']; ?></p>
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
                    <input type="hidden" name="min_price" value="<?php echo $plan['price']; ?>">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-shadow shadow-md hover:shadow-lg">
                        Select Plan
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
