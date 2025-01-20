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

// Get the current page from the URL (default is page 1)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;  // Number of results per page
$offset = ($page - 1) * $limit;

// Get total number of schedules
$stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_schedules = $stmt->fetchColumn();
$total_pages = ceil($total_schedules / $limit);

// Get schedules for the current page
$stmt = $conn->prepare("
    SELECT s.*, g.name as gym_name, g.address, g.city, g.state, g.zip_code
    FROM schedules s
    JOIN gyms g ON s.gym_id = g.gym_id
    WHERE s.user_id = :user_id
    ORDER BY s.start_date ASC, s.start_time ASC
    LIMIT :limit OFFSET :offset
");

$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

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

    <!-- Upcoming Workouts -->
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
                            <p class="text-sm text-gray-600"><?= date('M j, Y', strtotime($schedule['start_date'])) ?></p>
                            <p class="text-sm text-gray-600"><?= date('g:i A', strtotime($schedule['start_time'])) ?></p>
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
                        <p><?= htmlspecialchars($schedule['city']) ?>, <?= htmlspecialchars($schedule['state']) ?> <?= htmlspecialchars($schedule['zip_code']) ?></p>
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
                        <p class="text-sm italic text-gray-500"><?= htmlspecialchars($schedule['notes']) ?></p>
                    <?php endif; ?>

                    <div class="mt-4 flex gap-2">
                        <?php if($schedule['status'] === 'scheduled'): ?>
                            <button onclick="change(<?= $schedule['id'] ?>)" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">Change</button>
                            <button onclick="cancelSchedule(<?= $schedule['id'] ?>)" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">Cancel</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-6">
        <nav class="flex items-center space-x-4">
            <!-- Previous Button -->
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">&laquo; Previous</a>
            <?php else: ?>
                <span class="px-4 py-2 bg-gray-200 text-gray-400 rounded">Previous</span>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++): ?>
                <a href="?page=<?= $i ?>" class="px-4 py-2 <?= $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-800' ?> rounded hover:bg-gray-300"><?= $i ?></a>
            <?php endfor; ?>

            <!-- Next Button -->
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Next &raquo;</a>
            <?php else: ?>
                <span class="px-4 py-2 bg-gray-200 text-gray-400 rounded">Next</span>
            <?php endif; ?>
        </nav>
    </div>
    <!-- Past Workouts -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Past Workouts</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
            $past = array_filter($schedules, function($schedule) {
                return strtotime($schedule['start_date']) < strtotime('today');
            });
            
            foreach($past as $schedule): 
            ?>
                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-semibold"><?= htmlspecialchars($schedule['gym_name']) ?></h3>
                            <p class="text-sm text-gray-600"><?= date('M j, Y', strtotime($schedule['start_date'])) ?></p>
                            <p class="text-sm text-gray-600"><?= date('g:i A', strtotime($schedule['start_time'])) ?></p>
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
                        <p><?= htmlspecialchars($schedule['city']) ?>, <?= htmlspecialchars($schedule['state']) ?> <?= htmlspecialchars($schedule['zip_code']) ?></p>
                    </div>

                    <?php if($schedule['notes']): ?>
                        <p class="text-sm italic text-gray-500"><?= htmlspecialchars($schedule['notes']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<!-- Popup HTML -->
<div id="cancel-popup" class="hidden fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Cancel Workout</h2>
        <form action="cancel_schedule.php" method="POST">
            <input type="hidden" name="schedule_id" id="cancel-schedule-id" value="">
            <label for="cancel-reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for cancellation:</label>
            <select name="cancel_reason" id="cancel-reason" class="w-full px-3 py-2 border rounded-lg mb-4" onchange="handleOtherReason(this.value)" required>
                <option value="" disabled selected>Select a reason</option>
                <option value="not feeling well">Not feeling well</option>
                <option value="schedule conflict">Schedule conflict</option>
                <option value="other">Other</option>
            </select>
            <input type="text" name="other_reason" id="other-reason-input" class="w-full px-3 py-2 border rounded-lg hidden mb-4" placeholder="Enter your reason">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCancelPopup()" class="bg-gray-500 text-white px-3 py-2 rounded-lg hover:bg-gray-600">Close</button>
                <button type="submit" class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600">Submit</button>
            </div>
        </form>
    </div>
</div>
<script>
    function change(scheduleId) {
        if(confirm('Confirm check-in for this workout?')) {
            window.location.href = `schedule_workout.php?schedule_id=${scheduleId}`;
        }
    }

    function cancelSchedule(scheduleId) {
        if(confirm('Are you sure you want to cancel this workout?')) {
            window.location.href = `cancel_schedule.php?schedule_id=${scheduleId}`;
        }
    }

function cancelSchedule(scheduleId) {
    // Display the cancellation popup
    const popup = document.getElementById('cancel-popup');
    const scheduleInput = document.getElementById('cancel-schedule-id');
    scheduleInput.value = scheduleId; // Set the schedule ID in the hidden input
    popup.classList.remove('hidden'); // Show the popup
}

function closeCancelPopup() {
    const popup = document.getElementById('cancel-popup');
    popup.classList.add('hidden'); // Hide the popup
}

function handleOtherReason(value) {
    const otherReasonInput = document.getElementById('other-reason-input');
    if (value === 'other') {
        otherReasonInput.classList.remove('hidden'); // Show the text input
    } else {
        otherReasonInput.classList.add('hidden'); // Hide the text input
    }
}
</script>

