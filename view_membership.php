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

<div class="min-h-screen bg-gradient-to-b from-gray-900 to-black py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Active Memberships Section -->
        <?php if ($memberships): ?>
            <h2 class="text-3xl font-extrabold text-white my-10 text-center">Your Active Memberships</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
                <?php foreach ($memberships as $membership): ?>
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                        <!-- Membership Header -->
                        <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 p-6">
                            <h3 class="text-2xl font-bold text-gray-900">
                                <?php echo htmlspecialchars($membership['plan_name']); ?> Plan
                            </h3>
                            <p class="text-gray-800 mt-1">
                                <?php echo htmlspecialchars($membership['gym_name']); ?>
                            </p>
                        </div>

                        <!-- Membership Details -->
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-yellow-400 text-sm">Valid Until</p>
                                    <p class="text-white text-lg">
                                        <?php echo date('F j, Y', strtotime($membership['end_date'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-yellow-400 text-sm">Status</p>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        <?php echo $membership['status'] === 'active' 
                                            ? 'bg-green-900 text-green-100' 
                                            : 'bg-red-900 text-red-100'; ?>">
                                        <?php echo ucfirst($membership['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Inclusions -->
                            <div>
                                <p class="text-yellow-400 text-sm mb-3">Plan Includes</p>
                                <ul class="space-y-2">
                                    <?php foreach (explode(',', $membership['inclusions']) as $inclusion): ?>
                                        <li class="text-white flex items-center">
                                            <i class="fas fa-check text-yellow-400 mr-2"></i>
                                            <?php echo htmlspecialchars(trim($inclusion)); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Price Info -->
                            <div class="flex justify-between items-center pt-4 border-t border-gray-700">
                                <div>
                                    <p class="text-yellow-400 text-sm">Price</p>
                                    <p class="text-white text-2xl font-bold">
                                        ₹<?php echo number_format($membership['price'], 2); ?>
                                    </p>
                                </div>
                                <span class="text-white">
                                    <?php echo $membership['duration']; ?> Days
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Available Plans Section -->
        <h2 class="text-3xl font-extrabold text-white mb-10 text-center">Available Membership Plans</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($available_plans as $plan): ?>
                <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-white mb-2">
                            <?php echo htmlspecialchars($plan['name']); ?>
                        </h3>
                        <p class="text-4xl font-extrabold text-yellow-400 mb-6">
                            ₹<?php echo number_format($plan['price'], 2); ?>
                        </p>
                        
                        <div class="space-y-4 mb-8">
                            <p class="text-white">
                                <i class="fas fa-clock text-yellow-400 mr-2"></i>
                                <?php echo $plan['duration_days']; ?> Days
                            </p>
                            <?php if ($plan['visit_limit']): ?>
                                <p class="text-white">
                                    <i class="fas fa-walking text-yellow-400 mr-2"></i>
                                    <?php echo $plan['visit_limit']; ?> Visits
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-3 mb-8">
                            <?php foreach (json_decode($plan['features'], true) as $feature): ?>
                                <p class="text-white flex items-start">
                                    <i class="fas fa-check-circle text-yellow-400 mr-2 mt-1"></i>
                                    <?php echo htmlspecialchars($feature); ?>
                                </p>
                            <?php endforeach; ?>
                        </div>

                        <form action="./gyms.php" method="POST">
                            <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                            <input type="hidden" name="min_price" value="<?php echo $plan['price']; ?>">
                            <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold py-3 px-6 rounded-xl transition-colors duration-200">
                                Select Plan
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
