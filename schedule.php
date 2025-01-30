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

// Fetch user balance
$balanceStmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$balanceStmt->execute([$user_id]);
$userBalance = $balanceStmt->fetchColumn();

// Fetch all active memberships
$membershipsStmt = $conn->prepare("
    SELECT 
        um.id as membership_id,
        um.start_date,
        um.end_date,
        um.status,
        um.payment_status,
        gmp.tier,
        gmp.duration,
        gmp.price,
        gmp.inclusions,
        g.name as gym_name,
        g.gym_id,
        g.address,
        g.city,
        g.cover_photo
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    JOIN gyms g ON um.gym_id = g.gym_id
    WHERE um.user_id = ?
    AND um.status = 'active'
    AND um.payment_status = 'paid'
    AND CURRENT_DATE BETWEEN um.start_date AND um.end_date
    ORDER BY um.start_date DESC
");
$membershipsStmt->execute([$user_id]);
$memberships = $membershipsStmt->fetchAll(PDO::FETCH_ASSOC);

// After fetching memberships, add this code to get gym operating hours
if ($memberships) {
    $selectedMembership = $memberships[0];
    $gym_id = $selectedMembership['gym_id'];

    // Get gym operating hours
    $hoursStmt = $conn->prepare("
        SELECT 
            morning_open_time, 
            morning_close_time, 
            evening_open_time, 
            evening_close_time
        FROM gym_operating_hours 
        WHERE gym_id = ? AND day = 'Daily'
    ");
    $hoursStmt->execute([$gym_id]);
    $hours = $hoursStmt->fetch(PDO::FETCH_ASSOC);

    // Generate time slots
    $timeSlots = [];
    if ($hours) {
        // Morning slots
        $morning_start = strtotime($hours['morning_open_time']);
        $morning_end = strtotime($hours['morning_close_time']);
        for ($time = $morning_start; $time <= $morning_end; $time += 3600) {
            $timeSlots[] = date('H:i:s', $time);
        }

        // Evening slots
        $evening_start = strtotime($hours['evening_open_time']);
        $evening_end = strtotime($hours['evening_close_time']);
        for ($time = $evening_start; $time <= $evening_end; $time += 3600) {
            $timeSlots[] = date('H:i:s', $time);
        }
    }

    // Get current occupancy for each time slot
    $occupancyStmt = $conn->prepare("
        SELECT start_time, COUNT(*) as current_occupancy 
        FROM schedules 
        WHERE gym_id = ? 
        AND start_date = CURRENT_DATE
        GROUP BY start_time
    ");
    $occupancyStmt->execute([$gym_id]);
    $occupancyByTime = $occupancyStmt->fetchAll(PDO::FETCH_KEY_PAIR);
}



include 'includes/navbar.php';
?>
<div class="min-h-screen bg-gradient-to-b from-gray-900 to-black py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Balance Display -->
        <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden mb-8">
            <div class="p-6">
                <h4 class="text-2xl font-bold text-white flex items-center">
                    <i class="fas fa-wallet text-yellow-400 mr-2"></i>
                    Your Balance: ₹<?= number_format($userBalance, 2) ?>
                </h4>
            </div>
        </div>

        <!-- Membership Selection -->
        <h2 class="text-3xl font-bold text-white mb-8 text-center">Select Membership to Schedule</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <?php foreach ($memberships as $membership): ?>
                <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300 cursor-pointer"
                    onclick="selectMembership(<?= $membership['membership_id'] ?>, '<?= htmlspecialchars($membership['gym_name']) ?>')">
                    <!-- Header Section -->
                    <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-bold text-gray-900">
                                <?= htmlspecialchars($membership['gym_name']) ?>
                            </h3>
                            <span class="px-4 py-1 rounded-full text-sm font-medium bg-green-900 text-green-100">
                                Active
                            </span>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="p-6">
                        <p class="text-yellow-400 text-lg mb-4"><?= htmlspecialchars($membership['tier']) ?> Plan</p>
                        <div class="space-y-3 text-white">
                            <p class="flex items-center">
                                <i class="far fa-calendar-alt text-yellow-400 mr-2"></i>
                                Valid till: <?= date('d M Y', strtotime($membership['end_date'])) ?>
                            </p>
                            <p class="flex items-center">
                                <i class="fas fa-map-marker-alt text-yellow-400 mr-2"></i>
                                Location: <?= htmlspecialchars($membership['city']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-900 text-red-100 p-6 rounded-3xl mb-6">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-900 text-green-100 p-6 rounded-3xl mb-6">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Schedule Form -->
        <div id="scheduleForm" class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden hidden">
            <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">Create Schedule</h2>
                    <span id="selectedGymName" class="text-gray-900 font-semibold"></span>
                </div>
            </div>

            <form action="process_schedule.php" method="POST" class="p-6 space-y-6">
                <input type="hidden" name="membership_id" id="selectedMembershipId">
                <input type="hidden" name="gym_id" id="selectedGymId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">Start Date</label>
        <input type="date" name="start_date" required
            class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-yellow-400">
    </div>

    <div class="space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">End Date</label>
        <input type="date" name="end_date" required
            class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-yellow-400">
    </div>

    <div class="space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">Time Slot</label>
        <select name="time_slot" required
            class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-xl 
                   text-gray-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-yellow-400">
            <?php foreach ($timeSlots as $time):
                $currentOccupancy = $occupancyByTime[$time] ?? 0;
                $available = 50 - $currentOccupancy;
                $isSlotFull = $currentOccupancy >= 50;
                $formattedTime = date('g:i A', strtotime($time));
            ?>
                <option value="<?= $time ?>" <?= $isSlotFull ? 'disabled' : '' ?>>
                    <?= $formattedTime ?> (<?= $available ?> spots available)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">Activity Type</label>
        <select name="activity_type" required
            class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-xl 
                   text-gray-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-yellow-400">
            <option value="gym_visit">General Workout</option>
            <option value="class">Class Session</option>
            <option value="personal_training">Personal Training</option>
        </select>
    </div>

    <div class="space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">Schedule Type</label>
        <select name="recurring" id="recurringSelect"
            class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-xl 
                   text-gray-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-yellow-400">
            <option value="daily">Daily</option>
            <option value="none">Today Only</option>
            <option value="weekly">Weekly</option>
        </select>
    </div>

    <div id="daysSelection" class="hidden space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">Select Days</label>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="days[]" value="<?= strtolower($day) ?>"
                        class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 
                               text-yellow-400 focus:ring-yellow-400">
                    <span class="ml-2 text-gray-700 dark:text-white"><?= $day ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="space-y-2">
        <label class="text-white  dark:text-yellow-400 text-sm">Notes</label>
        <textarea name="notes" rows="3"
            class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded-xl 
                   text-gray-900 dark:text-white px-4 py-3 focus:ring-2 focus:ring-yellow-400"
            placeholder="Add any notes or special requests for your workout session"></textarea>
    </div>
</div>


                <button type="submit"
                    class="w-full bg-yellow-400 text-black px-6 py-3 rounded-full font-bold hover:bg-yellow-500 transform hover:scale-105 transition-all duration-300">
                    Create Schedule
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

    function selectMembership(membershipId, gymName) {
        const memberships = <?php echo json_encode($memberships); ?>;
        const selectedMembership = memberships.find(m => m.membership_id == membershipId);

        // Set form values
        document.getElementById('selectedMembershipId').value = membershipId;
        document.getElementById('selectedGymId').value = selectedMembership.gym_id;
        document.getElementById('selectedGymName').textContent = gymName;

        // Set date inputs with membership dates
        document.querySelector('input[name="start_date"]').value = selectedMembership.start_date;
        document.querySelector('input[name="start_date"]').min = selectedMembership.start_date;
        document.querySelector('input[name="end_date"]').value = selectedMembership.end_date;
        document.querySelector('input[name="end_date"]').max = selectedMembership.end_date;

        // Show form and scroll
        document.getElementById('scheduleForm').style.display = 'block';
        document.getElementById('scheduleForm').scrollIntoView({ behavior: 'smooth' });


    }

    function updateTimeSlots(membershipId) {
        const memberships = <?php echo json_encode($memberships); ?>;
        const selectedMembership = memberships.find(m => m.membership_id == membershipId);
        const timeSlots = <?php echo json_encode($timeSlots); ?>;
        const occupancy = <?php echo json_encode($occupancyByTime); ?>;

        const select = document.querySelector('select[name="start_time"]');
        select.innerHTML = timeSlots.map(time => {
            const currentOccupancy = occupancy[time] || 0;
            const available = 50 - currentOccupancy;
            const formattedTime = new Date(`2000-01-01 ${time}`).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });

            return `
            <option value="${time}" ${available <= 0 ? 'disabled' : ''}>
                ${formattedTime} (${available} spots available)
            </option>
        `;
        }).join('');
    }


    document.querySelector('input[name="visit_date"]').addEventListener('change', (e) => {
        const selectedGymId = document.querySelector('#selectedMembership').value;
        updateTimeSlots(e.target.value, selectedGymId);
    });

</script>