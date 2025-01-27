<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$owner_id = $_SESSION['owner_id'];

// Get gym details
$stmt = $conn->prepare("SELECT gym_id, name, balance FROM gyms WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$gym = $stmt->fetch(PDO::FETCH_ASSOC);
$gym_id = $gym['gym_id'];

// Get today's visits
$stmt = $conn->prepare("
    SELECT s.*, u.username, u.email, u.phone
    FROM schedules s 
    JOIN users u ON s.user_id = u.id
    WHERE s.gym_id = ? 
    AND DATE(s.start_date) = CURRENT_DATE
    ORDER BY s.start_time ASC
");
$stmt->execute([$gym_id]);
$today_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get earnings history
$stmt = $conn->prepare("
    SELECT DATE(s.start_date) as date,
           COUNT(*) as visit_count,
           SUM(s.daily_rate) as total_earnings
    FROM schedules s
    WHERE s.gym_id = ?
    GROUP BY DATE(s.start_date)
    ORDER BY date ASC
    LIMIT 30
");
$stmt->execute([$gym_id]);
$earnings_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                        <i class="fas fa-clipboard-check text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Visit Attendance</h1>
                        <p class="text-gray-300"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-white text-right">
                        <p class="text-sm">Current Balance</p>
                        <p class="text-xl font-bold">₹<?php echo number_format($gym['balance'], 2); ?></p>
                    </div>
                    <button onclick="initiateWithdrawal()" 
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                        <i class="fas fa-money-bill-wave mr-2"></i>Withdraw
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Visits -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-6 flex items-center">
            <i class="fas fa-calendar-day text-yellow-500 mr-2"></i>
            Today's Visits
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($today_visits) > 0): ?>
                        <?php foreach ($today_visits as $visit): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo date('h:i A', strtotime($visit['start_time'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($visit['username']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($visit['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $visit['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($visit['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    ₹<?php echo number_format($visit['daily_rate'], 2); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($visit['status'] === 'scheduled'): ?>
                                        <button onclick="markAttendance(<?php echo $visit['id']; ?>)" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                            <i class="fas fa-check mr-2"></i>Mark Present
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500">No visits scheduled for today</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Earnings History -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-semibold mb-6 flex items-center">
            <i class="fas fa-chart-line text-yellow-500 mr-2"></i>
            Earnings History
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Earnings</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($earnings_history as $earning): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo date('M d, Y', strtotime($earning['date'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                    <?php echo $earning['visit_count']; ?> visits
                                </span>
                            </td>
                            <td class="px-6 py-4 text-green-600 font-medium">
                                ₹<?php echo number_format($earning['total_earnings'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>


<script>
function markAttendance(visitId) {
    fetch('mark_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ visit_id: visitId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function initiateWithdrawal() {
    window.location.href = 'withdraw.php';
}
</script>
