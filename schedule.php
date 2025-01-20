<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$gym_id = $_GET['gym_id'] ?? null;

// Get user's active membership
$membershipStmt = $conn->prepare("
    SELECT um.*, gmp.tier as plan_name, gmp.inclusions, 
           um.start_date as membership_start, 
           um.end_date as membership_end
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    WHERE um.user_id = ? 
    AND um.status = 'active' 
    AND um.end_date >= CURRENT_DATE()
    AND gmp.gym_id = ?
");
$membershipStmt->execute([$user_id, $gym_id]);
$membership = $membershipStmt->fetch(PDO::FETCH_ASSOC);

// Set the date variables
$start_date = $membership ? $membership['membership_start'] : date('Y-m-d');
$end_date = $membership ? $membership['membership_end'] : date('Y-m-d');

// Get gym operating hours
$hoursStmt = $conn->prepare("
    SELECT morning_open_time, morning_close_time, 
           evening_open_time, evening_close_time
    FROM gym_operating_hours 
    WHERE gym_id = ? AND day = 'Daily'
");
$hoursStmt->execute([$gym_id]);
$hours = $hoursStmt->fetch(PDO::FETCH_ASSOC);

// Generate time slots
$timeSlots = [];
if ($hours) {
    $morning_start = strtotime($hours['morning_open_time']);
    $morning_end = strtotime($hours['morning_close_time']);
    $evening_start = strtotime($hours['evening_open_time']);
    $evening_end = strtotime($hours['evening_close_time']);

    for ($time = $morning_start; $time <= $morning_end; $time += 3600) {
        $timeSlots[] = date('H:i:s', $time);
    }
    for ($time = $evening_start; $time <= $evening_end; $time += 3600) {
        $timeSlots[] = date('H:i:s', $time);
    }
}

include 'includes/navbar.php';
?>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <?php if (!$membership): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
                    <p>Active membership required. <a href="membership-plans.php" class="underline">Get membership</a></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Create Schedule</h2>
                
                <form action="process_schedule.php" method="POST" id="scheduleForm" class="space-y-6">
                    <input type="hidden" name="gym_id" value="<?= htmlspecialchars($gym_id) ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" 
                                   name="start_date" 
                                   required 
                                   min="<?= $start_date ?>" 
                                   max="<?= $end_date ?>"
                                   value="<?= $start_date ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" 
                                   name="end_date" 
                                   required 
                                   min="<?= $start_date ?>" 
                                   max="<?= $end_date ?>"
                                   value="<?= $end_date ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                            <select name="start_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php foreach ($timeSlots as $time): ?>
                                    <option value="<?= $time ?>"><?= date('g:i A', strtotime($time)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Activity Type</label>
                            <select name="activity_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="gym_visit">General Workout</option>
                                <option value="class">Class Session</option>
                                <option value="personal_training">Personal Training</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Schedule Type</label>
                        <select name="recurring" id="recurringSelect" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="daily">Daily</option>
                            <option value="none">Today</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>

                    <div id="daysSelection" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Days</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <?php foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="days[]" value="<?= strtolower($day) ?>"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2"><?= $day ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <!-- Add to the form -->
<input type="hidden" name="schedule_id" value="<?php echo $_GET['edit_id'] ?? ''; ?>">
<button type="submit" <?= !$membership ? 'disabled' : '' ?>
        class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed">
    <?php echo isset($_GET['edit_id']) ? 'Update Schedule' : 'Create Schedule'; ?>
</button>

                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('recurringSelect').addEventListener('change', function() {
            const daysSelection = document.getElementById('daysSelection');
            daysSelection.classList.toggle('hidden', this.value !== 'weekly');
        });

        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            const recurring = document.getElementById('recurringSelect').value;
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            const membershipEndDate = '<?= $end_date ?>';

            if (new Date(endDate) < new Date(startDate)) {
                e.preventDefault();
                alert('End date cannot be earlier than start date');
                return;
            }

            if (new Date(endDate) > new Date(membershipEndDate)) {
                e.preventDefault();
                alert('Schedule cannot extend beyond your membership end date');
                return;
            }

            if (recurring === 'weekly') {
                const selectedDays = document.querySelectorAll('input[name="days[]"]:checked');
                if (selectedDays.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one day for weekly schedule');
                }
            }
        });

        // Add date input listeners
        document.querySelector('input[name="start_date"]').addEventListener('change', function() {
            document.querySelector('input[name="end_date"]').min = this.value;
        });

        document.querySelector('input[name="end_date"]').addEventListener('change', function() {
            document.querySelector('input[name="start_date"]').max = this.value;
        });
    </script>
</body>
</html>
