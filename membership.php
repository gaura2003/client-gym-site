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

<div class="container mx-auto px-4 py-8">
    <?php if ($membership): ?>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Your Membership</h2>
                <span class="px-4 py-2 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                    Active
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="space-y-3">
                        <p class="text-gray-700"><span class="font-medium">Plan:</span> <?php echo htmlspecialchars($membership['plan_name']); ?></p>
                        <p class="text-gray-700"><span class="font-medium">Duration:</span> <?php echo htmlspecialchars($membership['duration']); ?></p>
                        <p class="text-gray-700"><span class="font-medium">Start Date:</span> <?php echo date('F j, Y', strtotime($membership['start_date'])); ?></p>
                        <p class="text-gray-700"><span class="font-medium">End Date:</span> <?php echo date('F j, Y', strtotime($membership['end_date'])); ?></p>
                        <p class="text-gray-700"><span class="font-medium">Gym:</span> <?php echo htmlspecialchars($membership['gym_name']); ?></p>
                        <p class="text-gray-700"><span class="font-medium">Location:</span> <?php echo htmlspecialchars($membership['address']); ?></p>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Inclusions</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <?php
                        $inclusions = explode(',', $membership['inclusions']);
                        foreach ($inclusions as $inclusion): ?>
                            <li><?php echo htmlspecialchars(trim($inclusion)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <?php
            // Check for existing schedule
            $scheduleStmt = $conn->prepare("
                SELECT id FROM schedules 
                WHERE user_id = ? AND membership_id = ? AND status = 'scheduled'
            ");
            $scheduleStmt->execute([$user_id, $membership['id']]);
            $existingSchedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
            ?>

            <div class="col-span-2 mt-6 flex flex-col sm:flex-row gap-4 justify-center">
                <?php if ($existingSchedule): ?>
                    <a href="schedule.php?gym_id=<?php echo $membership['gym_id']; ?>&edit_id=<?php echo $existingSchedule['id']; ?>"
                       class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center shadow-md transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Update Schedule
                    </a>
                <?php else: ?>
                    <a href="schedule.php?gym_id=<?php echo $membership['gym_id']; ?>"
                       class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center shadow-md transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Schedule Workout
                    </a>
                <?php endif; ?>

                <a href="user_schedule.php"
                   class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 flex items-center shadow-md transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    View My Schedule
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center text-gray-600">
            <h2 class="text-xl font-semibold">No Active Membership Found</h2>
            <p class="mt-4">Explore our <a href="gym.php" class="text-blue-600 underline">membership plans</a>.</p>
        </div>
    <?php endif; ?>
</div>
