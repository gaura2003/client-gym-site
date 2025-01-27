<?php
    session_start();
    require 'config/database.php';

    if (! isset($_SESSION['user_id'])) {
        header(header: 'Location: login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $db      = new GymDatabase();
    $conn    = $db->getConnection();
    // Default time slots if gym hours not set
    $timeSlots = [
        '06:00:00',
        '07:00:00',
        '08:00:00',
        '09:00:00',
        '10:00:00',
        '11:00:00',
        '12:00:00',
        '13:00:00',
        '14:00:00',
        '15:00:00',
        '16:00:00',
        '17:00:00',
        '18:00:00',
        '19:00:00',
        '20:00:00',
    ];

    // Get gym operating hours if available
    if (isset($_GET['gym_id'])) {
        $hoursStmt = $conn->prepare("
        SELECT morning_open_time, morning_close_time,
               evening_open_time, evening_close_time
        FROM gym_operating_hours
        WHERE gym_id = ? AND day = 'Daily'
    ");
        $hoursStmt->execute([$gym_id]);
        $hours = $hoursStmt->fetch(PDO::FETCH_ASSOC);

        if ($hours) {
            $timeSlots     = [];
            $morning_start = strtotime($hours['morning_open_time']);
            $morning_end   = strtotime($hours['morning_close_time']);
            $evening_start = strtotime($hours['evening_open_time']);
            $evening_end   = strtotime($hours['evening_close_time']);

            for ($time = $morning_start; $time <= $morning_end; $time += 3600) {
                $timeSlots[] = date('H:i:s', $time);
            }
            for ($time = $evening_start; $time <= $evening_end; $time += 3600) {
                $timeSlots[] = date('H:i:s', $time);
            }
        }
    }

    $search      = $_GET['search'] ?? '';
    $searchCity  = $_GET['city'] ?? '';
    $searchState = $_GET['state'] ?? '';

    // Get user's active membership with proper table joins
    $membershipStmt = $conn->prepare(query: "
    SELECT um.*, gmp.tier as plan_name, gmp.inclusions, gmp.duration, gmp.price,
           g.name as gym_name, g.address, p.status as payment_status
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    JOIN gyms g ON gmp.gym_id = g.gym_id
    JOIN payments p ON um.id = p.membership_id
    WHERE um.user_id = ?
    AND um.status = 'active'
    AND p.status = 'completed'
    AND um.end_date >= CURRENT_DATE()

");
    $membershipStmt->execute(params: [$_SESSION['user_id']]);
    $activeMembership = $membershipStmt->fetch(mode: PDO::FETCH_ASSOC);

    $monthlyPriceActiveMembership = null;

if ($activeMembership) {
    $durationMonths = 1; // Default to 1 month if duration isn't specified
    switch (strtolower($activeMembership['duration'])) {
        case 'monthly':
            $durationMonths = 1;
            break;
        case 'quarterly':
            $durationMonths = 3;
            break;
        case 'half-yearly':
            $durationMonths = 6;
            break;
        case 'yearly':
            $durationMonths = 12;
            break;
    }
    $monthlyPriceActiveMembership = $activeMembership['price'] / $durationMonths;
}

// Fetch distinct cities
$citiesQuery = "SELECT DISTINCT city FROM gyms WHERE status = 'active' ORDER BY city ASC";
$citiesStmt = $conn->prepare($citiesQuery);
$citiesStmt->execute();
$cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Modified gym search query to include price filtering
    $searchQuery = "
    SELECT DISTINCT g.*, gmp.price as monthly_price
    FROM gyms g
    JOIN gym_membership_plans gmp ON g.gym_id = gmp.gym_id
    WHERE g.status = 'active'
    AND gmp.duration = 'Monthly'
    AND (
        (:search = '' OR g.name LIKE :search)
        AND (:city = '' OR g.city = :city)
        AND (:state = '' OR g.state LIKE :state)
    )
    ORDER BY g.rating DESC, g.name ASC
";

$stmt = $conn->prepare($searchQuery);
$stmt->execute([
    ':search' => $search ? "%{$search}%" : '',
    ':city'   => $searchCity ?: '',
    ':state'  => $searchState ? "%{$searchState}%" : '',
]);

  $gyms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Fetching last schedule data to display and update
$scheduleStmt = $conn->prepare("
SELECT s.id, s.activity_type, s.gym_id, s.start_date, s.end_date, s.start_time, s.notes
FROM schedules s
WHERE s.user_id = ?
ORDER BY s.start_date DESC
LIMIT 1
");
$scheduleStmt->execute([$_SESSION['user_id']]);
$lastSchedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

// Check if the selected date and time slot is available
if (isset($_POST['start_date']) && isset($_POST['start_time'])) {
$startDate = $_POST['start_date'];
$startTime = $_POST['start_time'];

$checkScheduleStmt = $conn->prepare("
    SELECT COUNT(*) FROM schedules
    WHERE gym_id = ? AND start_date = ? AND start_time = ?
");
$checkScheduleStmt->execute([$gym_id, $startDate, $startTime]);
$isSlotAvailable = $checkScheduleStmt->fetchColumn() == 0;

$occupancyStmt = $conn->prepare("
    SELECT start_time, COUNT(*) as current_occupancy 
    FROM schedules 
    WHERE gym_id = ? 
    AND start_date = ? 
    GROUP BY start_time
");
$occupancyStmt->execute([$gym_id, $start_date]);
$occupancyByTime = $occupancyStmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($isSlotAvailable) {
    // Update the user's schedule
    $updateScheduleStmt = $conn->prepare("
        UPDATE schedules
        SET gym_id = ?, start_date = ?, start_time = ?, notes = ?
        WHERE user_id = ?
    ");
    $updateScheduleStmt->execute([$_POST['gym_id'], $startDate, $startTime, $_POST['notes'], $_SESSION['user_id']]);
    
    echo "Schedule updated successfully!";
} else {
    echo "Selected time slot is not available.";
}
}
    include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Membership Status -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Your Membership</h2>
        <?php if ($activeMembership): ?>
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold"><?php echo htmlspecialchars($activeMembership['plan_name']); ?></p>
                    <p class="text-sm text-gray-600">Valid until:                                                                                                                                                                                                    <?php echo date('M j, Y', strtotime($activeMembership['end_date'])); ?></p>
                    <?php if ($activeMembership['inclusions']): ?>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600">Features:</p>
                            <?php
                                $features = json_decode($activeMembership['inclusions'], true);
                                if ($features && is_array($features)):
                            ?>
                                <ul class="list-disc list-inside space-y-2">
                                    <?php foreach ($features as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">Active</span>
            </div>
        <?php else: ?>
            <p class="text-red-600">No active membership found. <a href="view_membership.php" class="underline">Get a membership</a></p>
        <?php endif; ?>
    </div>

    <!-- Gym Search -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Find a Gym</h2>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Gym Name</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div>
    <label class="block text-sm font-medium text-gray-700">City</label>
    <select name="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        <option value="">All Cities</option>
        <?php foreach ($cities as $city): ?>
            <option value="<?php echo htmlspecialchars($city['city']); ?>" 
                <?php echo $searchCity === $city['city'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($city['city']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

            <div>
                <label class="block text-sm font-medium text-gray-700">State</label>
                <input type="text" name="state" value="<?php echo htmlspecialchars($searchState); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <button type="submit" class="md:col-span-3 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Search Gyms
            </button>
        </form>
    </div>

    <!-- Gym Results -->
    <?php if ($activeMembership): ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($gyms as $gym): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 id="selectedGymName" class="text-lg font-semibold"><?php echo htmlspecialchars($gym['name']); ?></h3>
                    <span class="px-2 py-1 rounded-full text-xs                                                                                                                                 <?php echo $gym['current_occupancy'] < $gym['max_capacity'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $gym['current_occupancy']; ?>/<?php echo $gym['max_capacity']; ?> capacity
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($gym['address']); ?></p>
                <p class="text-sm text-gray-600 mb-4">
                    <?php echo htmlspecialchars($gym['city']); ?>,
                    <?php echo htmlspecialchars($gym['state']); ?>
<?php echo htmlspecialchars($gym['zip_code']); ?>
                </p>

                <?php if ($gym['amenities']): ?>
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 mb-1">Amenities:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (json_decode($gym['amenities']) as $amenity): ?>
                                <span class="px-2 py-1 bg-gray-100 rounded-full text-xs text-gray-700">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $amenity)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
<?php if ($activeMembership && $gym['monthly_price'] <=  $monthlyPriceActiveMembership): ?>
                    <div class="flex space-x-2 mt-4">
    <button onclick="selectGymForUpdate('<?php echo $gym['gym_id']; ?>', '<?php echo htmlspecialchars($gym['name']); ?>')"
            class="flex-1 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
        Select For Update
    </button>
</div>
                <?php else: ?>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600">Monthly Price: ₹<?php echo number_format($gym['monthly_price'], 2); ?></p>
                        <a href="gym_details.php?gym_id=<?php echo $gym['gym_id']; ?>"
                            class="block w-full text-center bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Get Membership
                        </a>
                    </div>

                <?php endif; ?>
            </div>

        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<!-- Replace the existing modal form with this -->
<div id="updateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
    <div id="errorMessage" class="hidden mb-4 p-3 rounded bg-red-100 text-red-700"></div>

        <h3 class="text-lg font-bold mb-4">Update Schedule</h3>

        <form action="process_schedule_update.php" method="POST" class="space-y-4">
    <!-- Hidden Fields -->
    <input type="hidden" id="selectedGymId" name="new_gym_id" required>
    <input type="hidden" name="old_gym_id" value="<?php echo $lastSchedule['gym_id']; ?>" required>
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>" required>
    <input type="hidden" name="membership_id" value="<?php echo $activeMembership['id']; ?>" required>
    <input type="hidden" name="start_time" id="selectedTimeSlot" required>
    
    <!-- check message -->
    <div id="errorMessage" class="hidden mb-4 p-3 rounded bg-red-100 text-red-700"></div>

    <!-- Date Range -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Date Range</label>
        <div class="space-y-2">
            <input type="date" name="start_date" required
                   value="<?php echo date('Y-m-d'); ?>"
                   min="<?php echo date('Y-m-d'); ?>"
                   max="<?php echo $activeMembership['end_date']; ?>">

            <input type="date" name="end_date" required
                   value="<?php echo date('Y-m-d'); ?>"
                   min="<?php echo date('Y-m-d'); ?>"
                   max="<?php echo $activeMembership['end_date']; ?>">
        </div>
    </div>

    <div>
    <label class="block text-sm font-medium text-gray-700">Time Slot</label>
    <select name="start_time" required
        class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <?php foreach ($timeSlots as $time):
            $currentOccupancy = isset($occupancyByTime[$time]) ? $occupancyByTime[$time] : 0;
            $isSlotFull = $currentOccupancy >= 50;
            ?>
            <option value="<?= $time ?>" <?= $isSlotFull ? 'disabled' : '' ?>>
                <?= date('g:i A', strtotime($time)) ?>
                (<?= $currentOccupancy ?>/50 members)
            </option>
        <?php endforeach; ?>
    </select>
    <!-- Add validation message display -->
    <div id="timeValidationMessage" class="mt-2 text-sm text-red-600 hidden">
        Cannot update past schedules. Please select a future time slot.
    </div>
</div>



    <!-- Notes -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3" value="<?php echo $lastSchedule['notes']; ?>"></textarea>
    </div>

    <div class="flex justify-end space-x-2">
        <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
        <button type="submit" name="update_schedule" value="<?php echo $lastSchedule['id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update Schedule</button>
    </div>
</form>

    </div>
</div>

<script>
function selectGymForUpdate(gymId, gymName) {
    document.getElementById('selectedGymId').value = gymId;
    document.getElementById('selectedGymName').textContent = gymName;
    document.getElementById('updateModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('updateModal').classList.add('hidden');
}
function checkExistingSchedule(gymId, startDate) {
    fetch(`check_schedule.php?gym_id=${gymId}&start_date=${startDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                showSchedulePopup(data.end_date);
            }
        });
}

function showSchedulePopup(endDate) {
    const formattedDate = new Date(endDate).toLocaleDateString();
    const popup = `
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded-lg">
                <h3 class="text-lg font-bold">Existing Schedule Found</h3>
                <p>You already have a schedule at this gym until ${formattedDate}</p>
                <p>Would you like to extend your schedule?</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button onclick="closePopup()" class="px-4 py-2 bg-gray-500 text-white rounded">Cancel</button>
                    <button onclick="extendSchedule('${endDate}')" class="px-4 py-2 bg-blue-500 text-white rounded">Extend</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', popup);
}
function validateTimeSlot() {
    const selectedTime = document.querySelector('select[name="start_time"]').value;
    const currentTime = new Date();
    const selectedDateTime = new Date(document.querySelector('input[name="start_date"]').value + ' ' + selectedTime);
    
    const messageElement = document.getElementById('timeValidationMessage');
    
    if (selectedDateTime <= currentTime) {
        messageElement.classList.remove('hidden');
        return false;
    }
    
    messageElement.classList.add('hidden');
    return true;
}


function extendSchedule(endDate) {
    const newEndDate = new Date(endDate);
    newEndDate.setDate(newEndDate.getDate() + 1);
    const formattedEndDate = newEndDate.toISOString().split('T')[0];
    document.querySelector('input[name="end_date"]').value = formattedEndDate;
    closePopup();
}

// Add comprehensive validation
function validateScheduleUpdate() {
    const selectedDate = new Date(document.querySelector('input[name="start_date"]').value);
    const selectedTime = document.querySelector('select[name="start_time"]').value;
    const selectedDateTime = new Date(selectedDate.toDateString() + ' ' + selectedTime);
    const currentDateTime = new Date();
    const timeBuffer = new Date(currentDateTime.getTime() + (15 * 60000)); // 15 minutes buffer
    
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.classList.remove('hidden');

    // Past date check
    if (selectedDate < new Date().setHours(0,0,0,0)) {
        errorDiv.textContent = "Cannot update past schedules";
        return false;
    }

    // Same day checks
    if (selectedDate.toDateString() === currentDateTime.toDateString()) {
        if (selectedDateTime <= currentDateTime) {
            errorDiv.textContent = "Cannot update schedule for past time slots";
            return false;
        }
        
        if (selectedDateTime <= timeBuffer) {
            errorDiv.textContent = "Schedule can only be updated at least 15 minutes before the slot time";
            return false;
        }
    }

    errorDiv.classList.add('hidden');
    return true;
}

// Update form submission handler
document.getElementById('updateScheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!validateScheduleUpdate()) {
        return false;
    }

    fetch('process_schedule_update.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => response.json())
    .then(data => {
        const errorDiv = document.getElementById('errorMessage');
        
        if (!data.success) {
            // Show error message
            errorDiv.textContent = data.error;
            errorDiv.classList.remove('hidden');
        } else {
            // Success - redirect to schedule page
            window.location.href = 'user_schedule.php';
        }
    })
    .catch(error => {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = 'An error occurred while processing your request';
        errorDiv.classList.remove('hidden');
    });
});

// Add event listeners for real-time validation
document.querySelector('input[name="start_date"]').addEventListener('change', validateScheduleUpdate);
document.querySelector('select[name="start_time"]').addEventListener('change', validateScheduleUpdate);

function closeModal() {
    document.getElementById('updateModal').classList.add('hidden');
    // Clear any error messages when modal is closed
    document.getElementById('errorMessage').classList.add('hidden');
}
</script>
