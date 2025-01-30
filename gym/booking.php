<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();


// Get today's scheduled visits
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.email, u.phone
    FROM schedules s 
    JOIN users u ON s.user_id = u.id
    WHERE s.gym_id = (SELECT gym_id FROM gyms WHERE owner_id = ?)
    AND DATE(s.start_date) = CURRENT_DATE
    AND s.status = 'scheduled'
    ORDER BY s.start_time ASC
");
$stmt->execute([$_SESSION['owner_id']]);
$todayBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Get total count for today's bookings
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM schedules s 
    WHERE s.gym_id = (SELECT gym_id FROM gyms WHERE owner_id = :owner_id)
    AND DATE(s.start_date) = CURRENT_DATE
    AND s.status = 'scheduled'
");
$stmt->execute([':owner_id' => $_SESSION['owner_id']]);
$totalTodayBookings = $stmt->fetchColumn();

// Get total count for tomorrow's bookings
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM schedules s 
    WHERE s.gym_id = (SELECT gym_id FROM gyms WHERE owner_id = :owner_id)
    AND DATE(s.start_date) = DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
    AND s.status = 'scheduled'
");
$stmt->execute([':owner_id' => $_SESSION['owner_id']]);
$totalTomorrowBookings = $stmt->fetchColumn();

// Calculate total pages
$totalPages = ceil(max($totalTodayBookings, $totalTomorrowBookings) / $limit);

// Get today's scheduled visits with pagination
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.email, u.phone
    FROM schedules s 
    JOIN users u ON s.user_id = u.id
    WHERE s.gym_id = (SELECT gym_id FROM gyms WHERE owner_id = :owner_id)
    AND DATE(s.start_date) = CURRENT_DATE
    AND s.status = 'scheduled'
    ORDER BY s.start_time ASC
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset
);
$stmt->execute([':owner_id' => $_SESSION['owner_id']]);
$todayBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get tomorrow's scheduled visits
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.email, u.phone
    FROM schedules s 
    JOIN users u ON s.user_id = u.id
    WHERE s.gym_id = (SELECT gym_id FROM gyms WHERE owner_id = :owner_id)
    AND DATE(s.start_date) = DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
    AND s.status = 'scheduled'
    ORDER BY s.start_time ASC
    LIMIT " . (int)$limit . " OFFSET " . (int)$offset
);
$stmt->execute([':owner_id' => $_SESSION['owner_id']]);
$tomorrowBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = ceil(max($totalTodayBookings, $totalTomorrowBookings) / $limit);

include '../includes/navbar.php';
?><div class="container mx-auto px-4 py-8">
<!-- Header -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
    <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-calendar-check text-2xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Schedules</h1>
                    <p class="text-white "><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Tabs -->
<div class="mb-6">
    <div class="sm:hidden">
        <select id="tabs" class="tab-btn block w-full rounded-md border-gray-300 ">
            <option value="today">Today's Schedule (<?php echo $totalTodayBookings; ?>)</option>
            <option value="tomorrow">Tomorrow's Schedule (<?php echo $totalTomorrowBookings; ?>)</option>
        </select>
    </div>
    <div class="hidden sm:block">
        <nav class="flex space-x-4" aria-label="Tabs">
            <button onclick="showTab('today')" class="tab-btn active px-4 py-2 rounded-lg font-medium">
                Today's Schedule (<?php echo $totalTodayBookings; ?>)
            </button>
            <button onclick="showTab('tomorrow')" class="tab-btn px-4 py-2 rounded-lg font-medium">
                Tomorrow's Schedule (<?php echo $totalTomorrowBookings; ?>)
            </button>
        </nav>
    </div>
</div>

<!-- Today's Schedule -->
<div id="today-tab" class="tab-content">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <?php if (count($todayBookings) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($todayBookings as $booking): ?>
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <div class="flex-shrink-0 w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-2xl text-blue-500"></i>
                        </div>
                        <div class="ml-6 flex-grow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($booking['username']) ?></h3>
                                    <div class="mt-1 text-sm text-gray-600">
                                        <p><i class="fas fa-clock mr-2"></i><?= date('h:i A', strtotime($booking['start_time'])) ?></p>
                                        <p><i class="fas fa-phone mr-2"></i><?= htmlspecialchars($booking['phone']) ?></p>
                                        <p><i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($booking['email']) ?></p>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="checkIn(<?= $booking['id'] ?>)" class="btn-checkin">
                                        <i class="fas fa-check-circle mr-2"></i>Check In
                                    </button>
                                    <button onclick="cancelBooking(<?= $booking['id'] ?>)" class="btn-cancel">
                                        <i class="fas fa-times-circle mr-2"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="h-24 w-24 mx-auto mb-6 text-white ">
                    <i class="fas fa-calendar-times text-6xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No Schedules Today</h3>
                <p class="text-gray-500">There are no scheduled visits for today.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tomorrow's Schedule -->
<div id="tomorrow-tab" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <?php if (count($tomorrowBookings) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($tomorrowBookings as $booking): ?>
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <!-- Same structure as today's bookings -->
                        <div class="flex-shrink-0 w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-2xl text-blue-500"></i>
                        </div>
                        <div class="ml-6 flex-grow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($booking['username']) ?></h3>
                                    <div class="mt-1 text-sm text-gray-600">
                                        <p><i class="fas fa-clock mr-2"></i><?= date('h:i A', strtotime($booking['start_time'])) ?></p>
                                        <p><i class="fas fa-phone mr-2"></i><?= htmlspecialchars($booking['phone']) ?></p>
                                        <p><i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($booking['email']) ?></p>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <button onclick="cancelBooking(<?= $booking['id'] ?>)" class="btn-cancel">
                                        <i class="fas fa-times-circle mr-2"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <div class="h-24 w-24 mx-auto mb-6 text-white ">
                    <i class="fas fa-calendar-times text-6xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No Schedules Tomorrow</h3>
                <p class="text-gray-500">There are no scheduled visits for tomorrow.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<div class="mt-6 flex justify-between items-center">
    <p class="text-sm text-gray-700">
        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
        <span class="font-medium"><?php echo min($offset + $limit, $totalTodayBookings); ?></span> of 
        <span class="font-medium"><?php echo $totalTodayBookings; ?></span> results
    </p>
    <div class="flex space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" 
               class="px-3 py-1 rounded-md <?php echo $page == $i ? 'bg-yellow-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
</div>

<style>
.btn-checkin {
    padding: 1rem 1.5rem;
    background-color: #10B981;
    color: white;
    border-radius: 0.5rem;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
}
.btn-checkin:hover {
    background-color: #059669;
}

.btn-cancel {
    padding: 1rem 1.5rem;
    background-color: #EF4444;
    color: white;
    border-radius: 0.5rem;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
}
.btn-cancel:hover {
    background-color: #DC2626;
}

.tab-btn {
    color: #6B7280;
}
.tab-btn:hover {
    color: #374151;
    background-color: #F3F4F6;
}

.tab-btn.active {
    background-color: #F59E0B;
    color: white;
}
.tab-btn.active:hover {
    background-color: #D97706;
}
</style>


<script>
function showTab(tabName) {
document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

document.getElementById(`${tabName}-tab`).classList.remove('hidden');
event.currentTarget.classList.add('active');
}

// Mobile tab selector
document.getElementById('tabs')?.addEventListener('change', (e) => {
showTab(e.target.value);
});
</script>
