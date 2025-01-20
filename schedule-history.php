<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Get all user schedules with gym details
$stmt = $conn->prepare("
    SELECT s.*, g.name as gym_name, g.address,
           g.city, g.state, g.zip_code
    FROM schedules s
    JOIN gyms g ON s.gym_id = g.gym_id
    WHERE s.user_id = ?
    ORDER BY s.start_date ASC, s.start_time ASC LIMIT 10
");
$stmt->execute([$user_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">My Workout Schedule</h1>
            <a href="schedule_workout.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                Schedule New Workout
            </a>
        </div>

        <!-- Schedule Calendar View -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Upcoming Workouts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php 
                $upcoming = array_filter($schedules, function($schedule) {
                    return strtotime($schedule['start_date']) >= strtotime('today');
                });
                
                foreach($upcoming as $schedule): 
                ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-semibold"><?= htmlspecialchars($schedule['gym_name']) ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?= date('M j, Y', strtotime($schedule['start_date'])) ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?= date('g:i A', strtotime($schedule['start_time'])) ?>
                                </p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs <?= 
                                match($schedule['status']) {
                                    'scheduled' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    'missed' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800'
                                }
                            ?>">
                                <?= ucfirst($schedule['status']) ?>
                            </span>
                        </div>

                        <div class="text-sm text-gray-600 mb-2">
                            <p><?= htmlspecialchars($schedule['address']) ?></p>
                            <p><?= htmlspecialchars($schedule['city']) ?>, 
                               <?= htmlspecialchars($schedule['state']) ?> 
                               <?= htmlspecialchars($schedule['zip_code']) ?>
                            </p>
                        </div>

                        <?php if($schedule['recurring'] !== 'none'): ?>
                            <div class="text-sm text-blue-600 mb-2">
                                <?= ucfirst($schedule['recurring']) ?> workout until 
                                <?= date('M j, Y', strtotime($schedule['recurring_until'])) ?>
                                <?php if($schedule['days_of_week']): ?>
                                    <br>Days: <?= implode(', ', array_map('ucfirst', json_decode($schedule['days_of_week'], true))) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($schedule['notes']): ?>
                            <p class="text-sm italic text-gray-500">
                                <?= htmlspecialchars($schedule['notes']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="mt-4 flex gap-2">
                            <?php if($schedule['status'] === 'scheduled'): ?>
                                <button onclick="checkIn(<?= $schedule['id'] ?>)" 
                                        class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                                    Check In
                                </button>
                                <button onclick="cancelSchedule(<?= $schedule['id'] ?>)"
                                        class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                    Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Past Workouts -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Past Workouts</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gym</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $past = array_filter($schedules, function($schedule) {
                            return strtotime($schedule['start_date']) < strtotime('today');
                        });
                        
                        foreach($past as $schedule): 
                        ?>
                            <tr>
                                <td class="px-6 py-4"><?= date('M j, Y', strtotime($schedule['start_date'])) ?></td>
                                <td class="px-6 py-4"><?= date('g:i A', strtotime($schedule['start_time'])) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($schedule['gym_name']) ?></td>
                                <td class="px-6 py-4"><?= ucfirst(str_replace('_', ' ', $schedule['activity_type'])) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs <?= 
                                        match($schedule['status']) {
                                            'completed' => 'bg-green-100 text-green-800',
                                            'missed' => 'bg-red-100 text-red-800',
                                            'cancelled' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        }
                                    ?>">
                                        <?= ucfirst($schedule['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function checkIn(scheduleId) {
            if(confirm('Confirm check-in for this workout?')) {
                window.location.href = `process_checkin.php?schedule_id=${scheduleId}`;
            }
        }

        function cancelSchedule(scheduleId) {
            if(confirm('Are you sure you want to cancel this workout?')) {
                window.location.href = `cancel_schedule.php?schedule_id=${scheduleId}`;
            }
        }
    </script>
</body>
</html>
