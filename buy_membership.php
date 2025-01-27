<?php 
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$plan_id = filter_input(INPUT_GET, 'plan_id', FILTER_VALIDATE_INT);
$gym_id = filter_input(INPUT_GET, 'gym_id', FILTER_VALIDATE_INT);

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch plan and gym details
$stmt = $conn->prepare("
    SELECT 
        gmp.*,
        g.name as gym_name,
        g.address,
        g.city,
        g.cover_photo
    FROM gym_membership_plans gmp
    JOIN gyms g ON gmp.gym_id = g.gym_id
    WHERE gmp.plan_id = ? AND g.gym_id = ?
");
$stmt->execute([$plan_id, $gym_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header with Gym Image -->
            <div class="relative h-48 bg-gradient-to-r from-blue-600 to-indigo-600">
                <?php if ($plan['cover_photo']): ?>
                    <img src="./gym/uploads/gym_images/<?php echo htmlspecialchars($plan['cover_photo']); ?>" 
                         class="w-full h-full object-cover opacity-50" alt="Gym Cover">
                <?php endif; ?>
                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <h1 class="text-3xl font-bold text-white text-center">
                        Membership Confirmation
                    </h1>
                </div>
            </div>

            <div class="p-8">
                <!-- Gym Details -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">
                        <?php echo htmlspecialchars($plan['gym_name']); ?>
                    </h2>
                    <p class="text-gray-600">
                        <?php echo htmlspecialchars($plan['address'] . ', ' . $plan['city']); ?>
                    </p>
                </div>

                <!-- Plan Details Card -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">
                                <?php echo htmlspecialchars($plan['tier']); ?> Plan
                            </h3>
                            <p class="text-gray-600">
                                <?php echo htmlspecialchars($plan['duration']); ?> Membership
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold text-blue-600">
                                ₹<?php echo number_format($plan['price'], 2); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo strtolower($plan['duration']); ?> billing
                            </div>
                        </div>
                    </div>

                    <!-- Plan Features -->
                    <div class="space-y-3">
                        <?php 
                        $inclusions = explode(',', $plan['inclusions']);
                        foreach ($inclusions as $inclusion): ?>
                            <div class="flex items-center text-gray-700">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?php echo htmlspecialchars(trim($inclusion)); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Membership Duration -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <h4 class="font-semibold text-gray-800 mb-4">Membership Duration</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">Start Date</div>
                            <div class="font-semibold"><?php echo date('d M Y'); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">End Date</div>
                            <div class="font-semibold">
                                <?php 
                                $duration_days = [
                                    'Daily' => 1,
                                    'Weekly' => 7,
                                    'Monthly' => 30,
                                    'Quartrly' => 90,
                                    'Half Yearly' => 180,
                                    'Yearly' => 365
                                ];
                                $end_date = date('d M Y', strtotime('+' . $duration_days[$plan['duration']] . ' days'));
                                echo $end_date;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Action -->
                <form action="process_membership.php" method="POST" class="space-y-4">
                    <input type="hidden" name="plan_id" value="<?php echo $plan_id; ?>">
                    <input type="hidden" name="gym_id" value="<?php echo $gym_id; ?>">
                    <input type="hidden" name="end_date" value="<?php echo date('Y-m-d', strtotime($end_date)); ?>">
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 px-8 rounded-xl font-semibold text-lg hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Proceed to Payment
                    </button>
                    
                    <p class="text-center text-sm text-gray-600">
                        By proceeding, you agree to our terms and conditions
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
