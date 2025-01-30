<?php
require_once 'config/database.php';
$user_id = $_SESSION['user_id'] ?? null;

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch user's active membership with completed payment
$stmt = $conn->prepare("
    SELECT um.*, gmp.tier as plan_name, gmp.inclusions, gmp.duration,
           g.name as gym_name, g.address, p.status as payment_status
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

<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <?php if ($membership): ?>
        <div
            class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
            <!-- Header Section -->
            <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">Your Membership</h2>
                    <span class="px-4 py-1 rounded-full text-sm font-medium bg-green-900 text-green-100">
                        Active
                    </span>
                </div>
            </div>

            <!-- Details Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6">
                <div class="space-y-4">
                    <div class="bg-gray-700 rounded-xl p-4">
                        <p class="text-yellow-400 text-sm">Plan</p>
                        <p class="text-lg font-semibold text-white">
                            <?php echo htmlspecialchars($membership['plan_name']); ?>
                        </p>
                    </div>

                    <div class="bg-gray-700 rounded-xl p-4">
                        <p class="text-yellow-400 text-sm">Duration</p>
                        <p class="text-lg font-semibold text-white">
                            <?php echo htmlspecialchars($membership['duration']); ?>
                        </p>
                    </div>

                    <div class="bg-gray-700 rounded-xl p-4">
                        <p class="text-yellow-400 text-sm">Validity</p>
                        <div class="space-y-1">
                            <p class="text-white">
                                <i class="fas fa-calendar-alt text-yellow-400 mr-2"></i>
                                Start: <?php echo date('F j, Y', strtotime($membership['start_date'])); ?>
                            </p>
                            <p class="text-white">
                                <i class="fas fa-calendar-check text-yellow-400 mr-2"></i>
                                End: <?php echo date('F j, Y', strtotime($membership['end_date'])); ?>
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-700 rounded-xl p-4">
                        <p class="text-yellow-400 text-sm">Gym Details</p>
                        <p class="text-lg font-semibold text-white">
                            <?php echo htmlspecialchars($membership['gym_name']); ?>
                        </p>
                        <p class="text-white ">
                            <?php echo htmlspecialchars($membership['address']); ?>
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-gray-700 rounded-xl p-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Plan Inclusions</h3>
                        <ul class="space-y-3">
                            <?php foreach (explode(',', $membership['inclusions']) as $inclusion): ?>
                                <li class="flex items-center text-white">
                                    <i class="fas fa-check-circle text-yellow-400 mr-3"></i>
                                    <?php echo htmlspecialchars(trim($inclusion)); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="p-6 bg-gray-700 flex flex-col sm:flex-row gap-4 justify-center">

                <a href="schedule.php?gym_id=<?php echo $membership['gym_id']; ?>"
                    class="inline-flex items-center justify-center px-6 py-3 bg-yellow-400 hover:bg-yellow-500 text-black rounded-full font-bold transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-plus mr-2"></i>
                    Schedule Workout
                </a>

                <a href="user_schedule.php"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-full font-bold transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    View My Schedule
                </a>
            </div>
        </div>
    <?php else:
        include 'gym.php';
    endif; ?>
</div>