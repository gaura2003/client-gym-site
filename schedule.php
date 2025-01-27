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

<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <!-- Membership Selection -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Select Membership to Schedule</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($memberships as $membership): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow cursor-pointer"
                        onclick="selectMembership(<?= $membership['membership_id'] ?>, '<?= htmlspecialchars($membership['gym_name']) ?>')">
                        <div class="h-32 bg-cover bg-center"
                            style="background-image: url('./gym/uploads/gym_images/<?= $membership['cover_photo'] ?? 'assets/default-gym.jpg' ?>')">
                        </div>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">
                                        <?= htmlspecialchars($membership['gym_name']) ?>
                                    </h3>
                                    <p class="text-blue-600"><?= htmlspecialchars($membership['tier']) ?> Plan</p>
                                </div>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Active</span>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p>Valid till: <?= date('d M Y', strtotime($membership['end_date'])) ?></p>
                                <p>Location: <?= htmlspecialchars($membership['city']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-500 text-white p-4 rounded-md mb-4">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); // Clear error message after displaying ?>
        <?php endif; ?>

        <!-- Display success message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-500 text-white p-4 rounded-md mb-4">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); // Clear success message after displaying ?>
        <?php endif; ?>
        <div class="mb-4">
            <h4 class="text-lg font-semibold">Your Balance: ₹<?= number_format($userBalance, 2) ?></h4>
        </div>
        <!-- Schedule Form -->
        <div id="scheduleForm" class="bg-white rounded-xl shadow-lg p-8" style="display: none;">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Create Schedule</h2>
                <span id="selectedGymName" class="text-blue-600 font-semibold"></span>
            </div>

            <form action="process_schedule.php" method="POST" class="space-y-6">
    <input type="hidden" name="membership_id" id="selectedMembershipId">
    <input type="hidden" name="gym_id" id="selectedGymId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                        <select name="time_slot" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Activity Type</label>
                        <select name="activity_type" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="gym_visit">General Workout</option>
                            <option value="class">Class Session</option>
                            <option value="personal_training">Personal Training</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Schedule Type</label>
                    <select name="recurring" id="recurringSelect"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="none">Today Only</option>
                        <option value="daily">Daily</option>
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
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Add any notes or special requests for your workout session"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all">
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