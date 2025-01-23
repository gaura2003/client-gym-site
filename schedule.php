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

// Get all active memberships for the user across different gyms
// Get all active memberships for the user
$membershipsStmt = $conn->prepare("
    SELECT 
        um.id as membership_id,
        um.start_date,
        um.end_date,
        um.status,
        um.payment_status,
        gmp.tier as plan_name,
        gmp.duration,
        gmp.price,
        g.name as gym_name,
        g.gym_id,
        coc.admin_cut_percentage,
        coc.gym_owner_cut_percentage
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    JOIN gyms g ON um.gym_id = g.gym_id
    JOIN cut_off_chart coc ON gmp.tier = coc.tier AND gmp.duration = coc.duration
    WHERE um.user_id = ?
    AND um.status = 'active'
    AND um.payment_status = 'paid'
    AND CURRENT_DATE BETWEEN um.start_date AND um.end_date
    ORDER BY um.start_date DESC
");
$membershipsStmt->execute([$user_id]);
$memberships = $membershipsStmt->fetchAll(PDO::FETCH_ASSOC);

// Set default start and end dates
$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

if ($memberships) {
    $selectedMembership = $memberships[0]; // Default to the first membership
    $start_date = $selectedMembership['start_date'];
    $end_date = $selectedMembership['end_date'];
    $gym_id = $selectedMembership['gym_id'];
}

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
        <?php if (!$memberships): ?>
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
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Create Schedule</h2>
            <form action="process_schedule.php" method="POST" id="scheduleForm" class="space-y-6">
            <select name="membership_id" id="membershipSelect" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    <?php foreach ($memberships as $membership): ?>
        <option value="<?= $membership['membership_id'] ?>" 
                data-start="<?= $membership['start_date'] ?>"
                data-end="<?= $membership['end_date'] ?>"
                data-gym-id="<?= $membership['gym_id'] ?>">
            <?= htmlspecialchars($membership['gym_name']) ?> - 
            <?= htmlspecialchars($membership['plan_name']) ?> 
            (<?= date('d M Y', strtotime($membership['start_date'])) ?> - 
             <?= date('d M Y', strtotime($membership['end_date'])) ?>)
        </option>
    <?php endforeach; ?>
</select>


                <input type="hidden" name="price" id="priceInput">

                <input type="hidden" name="gym_id" value="<?= htmlspecialchars($gym_id) ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" required min="<?= $start_date ?>" max="<?= $end_date ?>"
                            value="<?= $start_date ?>"
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" required min="<?= $start_date ?>" max="<?= $end_date ?>"
                            value="<?= $end_date ?>"
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                        <select name="start_time" required
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php foreach ($timeSlots as $time): ?>
                                <option value="<?= $time ?>"><?= date('g:i A', strtotime($time)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Activity Type</label>
                        <select name="activity_type" required
                            class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="gym_visit">General Workout</option>
                            <option value="class">Class Session</option>
                            <option value="personal_training">Personal Training</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Schedule Type</label>
                    <select name="recurring" id="recurringSelect"
                        class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="daily">Daily</option>
                        <option value="none">Today</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>

                <div id="daysSelection" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Days</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
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
                    <textarea name="notes" rows="3"
                        class="mt-1 p-2 block w-full border rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <!-- Hidden schedule ID input for editing -->
                <input type="hidden" name="schedule_id" value="<?php echo $_GET['edit_id'] ?? ''; ?>">
                <button type="submit" <?= !$memberships ? 'disabled' : '' ?>
                    class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <?php echo isset($_GET['edit_id']) ? 'Update Schedule' : 'Create Schedule'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('recurringSelect').addEventListener('change', function () {
        const daysSelection = document.getElementById('daysSelection');
        daysSelection.classList.toggle('hidden', this.value !== 'weekly');
    });

    document.getElementById('scheduleForm').addEventListener('submit', function (e) {
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

    document.getElementById('membershipSelect').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const startDate = selectedOption.getAttribute('data-start');
        const endDate = selectedOption.getAttribute('data-end');
        const price = selectedOption.getAttribute('data-price');

        document.querySelector('input[name="start_date"]').value = startDate;
        document.querySelector('input[name="start_date"]').min = startDate;
        document.querySelector('input[name="end_date"]').value = endDate;
        document.querySelector('input[name="end_date"]').max = endDate;
        document.getElementById('priceInput').value = price;
    });
</script>

</body>

</html>