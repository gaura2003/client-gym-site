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
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
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

<?php include 'includes/navbar.php'; ?>

<div class="min-h-screen bg-gradient-to-b from-gray-900 to-black py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <?php if ($schedules): ?>
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-10">
                <h1 class="text-3xl font-bold text-white">My Workout Schedule</h1>
                <a href="schedule_workout.php" 
                   class="bg-yellow-400 text-black px-6 py-3 rounded-full font-bold hover:bg-yellow-500 transform hover:scale-105 transition-all duration-300">
                    <i class="fas fa-plus mr-2"></i>Schedule New Workout
                </a>
            </div>

            <!-- Upcoming Workouts -->
            <h2 class="text-3xl font-bold text-white mb-8 text-center">Upcoming Workouts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php
                $upcoming = array_filter($schedules, function ($schedule) {
                    return strtotime($schedule['start_date']) >= strtotime('today');
                });

                foreach ($upcoming as $schedule): ?>
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
                        <!-- Header Section -->
                        <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold text-gray-900">
                                    <?= htmlspecialchars($schedule['gym_name']) ?>
                                </h3>
                                <span class="px-4 py-1 rounded-full text-sm font-medium
                                    <?= match ($schedule['status']) {
                                        'scheduled' => 'bg-green-900 text-green-100',
                                        'completed' => 'bg-blue-900 text-blue-100',
                                        'cancelled' => 'bg-red-900 text-red-100',
                                        'missed' => 'bg-yellow-900 text-yellow-100',
                                        default => 'bg-gray-900 text-gray-100'
                                    } ?>">
                                    <?= ucfirst($schedule['status']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="p-6">
                            <div class="">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-yellow-400 text-sm">Date & Time</label>
                                        <p class="text-white text-lg">
                                            <?= date('M j, Y', strtotime($schedule['start_date'])) ?><br>
                                            <?= date('g:i A', strtotime($schedule['start_time'])) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-yellow-400 text-sm">Location</label>
                                        <p class="text-white">
                                            <?= htmlspecialchars($schedule['address']) ?><br>
                                            <?= htmlspecialchars($schedule['city']) ?>, <?= htmlspecialchars($schedule['state']) ?>
                                            <?= htmlspecialchars($schedule['zip_code']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <?php if ($schedule['recurring'] !== 'none'): ?>
                                        <div>
                                            <label class="text-yellow-400 text-sm">Recurring Schedule</label>
                                            <p class="text-white">
                                                <?= ucfirst($schedule['recurring']) ?> workout until
                                                <?= date('M j, Y', strtotime($schedule['recurring_until'])) ?>
                                                <?php if ($schedule['days_of_week']): ?>
                                                    <br>Days: <?= implode(', ', array_map('ucfirst', json_decode($schedule['days_of_week'], true))) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($schedule['notes']): ?>
                                        <div>
                                            <label class="text-yellow-400 text-sm">Notes</label>
                                            <p class="text-white italic"><?= htmlspecialchars($schedule['notes']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($schedule['status'] === 'scheduled'): ?>
                                <div class="mt-6 flex justify-end space-x-4">
                                    <button onclick="change(<?= $schedule['id'] ?>)"
                                            class="bg-yellow-400 text-black px-6 py-3 rounded-full font-bold hover:bg-yellow-500 transform hover:scale-105 transition-all duration-300">
                                        <i class="fas fa-edit mr-2"></i>Change
                                    </button>
                                    <button onclick="cancelSchedule(<?= $schedule['id'] ?>)"
                                            class="bg-red-700 text-white px-6 py-3 rounded-full font-bold hover:bg-red-600 transform hover:scale-105 transition-all duration-300">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center my-8">
                <nav class="flex items-center space-x-4">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" 
                           class="bg-gray-700 text-white px-6 py-3 rounded-full font-bold hover:bg-gray-600 transform hover:scale-105 transition-all duration-300">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 1); $i <= min($total_pages, $page + 1); $i++): ?>
                        <a href="?page=<?= $i ?>" 
                           class="<?= $i == $page 
                               ? 'bg-yellow-400 text-black' 
                               : 'bg-gray-700 text-white hover:bg-gray-600' ?> 
                               px-6 py-3 rounded-full font-bold transform hover:scale-105 transition-all duration-300">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" 
                           class="bg-gray-700 text-white px-6 py-3 rounded-full font-bold hover:bg-gray-600 transform hover:scale-105 transition-all duration-300">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <!-- Past Workouts -->
            <h2 class="text-3xl font-bold text-white mb-8 text-center">Past Workouts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" >
                <?php
                $past = array_filter($schedules, function ($schedule) {
                    return strtotime($schedule['start_date']) < strtotime('today');
                });

                foreach ($past as $schedule): ?>
                    <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
    <!-- Header Section -->
    <div class="p-6 bg-gradient-to-r from-gray-700 to-gray-600">
        <div class="flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">
                <?= htmlspecialchars($schedule['gym_name']) ?>
            </h3>
            <span class="px-4 py-1 rounded-full text-sm font-medium
                <?= match ($schedule['status']) {
                    'completed' => 'bg-blue-900 text-blue-100',
                    'cancelled' => 'bg-red-900 text-red-100',
                    'missed' => 'bg-yellow-900 text-yellow-100',
                    default => 'bg-gray-900 text-gray-100'
                } ?>">
                <?= ucfirst($schedule['status']) ?>
            </span>
        </div>
    </div>

    <!-- Details Section -->
    <div class="p-6">
        <div class="grid grid-cols gap-6">
            <div class="space-y-4">
                <div>
                    <label class="text-yellow-400 text-sm">Date & Time</label>
                    <p class="text-white text-lg">
                        <?= date('M j, Y', strtotime($schedule['start_date'])) ?><br>
                        <?= date('g:i A', strtotime($schedule['start_time'])) ?>
                    </p>
                </div>
                <div>
                    <label class="text-yellow-400 text-sm">Location</label>
                    <p class="text-white">
                        <?= htmlspecialchars($schedule['address']) ?><br>
                        <?= htmlspecialchars($schedule['city']) ?>, <?= htmlspecialchars($schedule['state']) ?>
                        <?= htmlspecialchars($schedule['zip_code']) ?>
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                <?php if ($schedule['recurring'] !== 'none'): ?>
                    <div>
                        <label class="text-yellow-400 text-sm">Recurring Schedule</label>
                        <p class="text-white">
                            <?= ucfirst($schedule['recurring']) ?> workout until
                            <?= date('M j, Y', strtotime($schedule['recurring_until'])) ?>
                            <?php if ($schedule['days_of_week']): ?>
                                <br>Days: <?= implode(', ', array_map('ucfirst', json_decode($schedule['days_of_week'], true))) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($schedule['notes']): ?>
                    <div>
                        <label class="text-yellow-400 text-sm">Notes</label>
                        <p class="text-white italic"><?= htmlspecialchars($schedule['notes']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl p-8 text-center">
                <h1 class="text-3xl font-bold text-white mb-6">My Workout Schedule</h1>
                <div class="text-yellow-400 text-lg mb-8">
                    No workouts scheduled yet. Start your fitness journey today!
                </div>
                <a href="schedule.php?gym_id=<?php echo $membership['gym_id']; ?>"
                   class="bg-yellow-400 text-black px-8 py-4 rounded-full font-bold hover:bg-yellow-500 transform hover:scale-105 transition-all duration-300 inline-block">
                    Create Schedule
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Popup HTML -->
<div id="cancel-popup" class="hidden fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold mb-4">Cancel Workout</h2>
        <form action="cancel_schedule.php" method="POST">
            <input type="hidden" name="schedule_id" id="cancel-schedule-id" value="">
            <label for="cancel-reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for
                cancellation:</label>
            <select name="cancel_reason" id="cancel-reason" class="w-full px-3 py-2 border rounded-lg mb-4"
                onchange="handleOtherReason(this.value)" required>
                <option value="" disabled selected>Select a reason</option>
                <option value="not feeling well">Not feeling well</option>
                <option value="schedule conflict">Schedule conflict</option>
                <option value="other">Other</option>
            </select>
            <input type="text" name="other_reason" id="other-reason-input"
                class="w-full px-3 py-2 border rounded-lg hidden mb-4" placeholder="Enter your reason">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCancelPopup()"
                    class="bg-gray-500 text-white px-3 py-2 rounded-lg hover:bg-gray-600">Close</button>
                <button type="submit"
                    class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600">Submit</button>
            </div>
        </form>
    </div>
</div>
<script>
    function change(scheduleId) {
        if (confirm('Confirm check-in for this workout?')) {
            window.location.href = `schedule_workout.php?schedule_id=${scheduleId}`;
        }
    }

    function cancelSchedule(scheduleId) {
        if (confirm('Are you sure you want to cancel this workout?')) {
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