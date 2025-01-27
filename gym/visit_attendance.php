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
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Visit Attendance & Earnings</h1>
        <div class="flex items-center space-x-4">
            <div class="text-lg">
                Current Balance: ₹<?php echo number_format($gym['balance'], 2); ?>
            </div>
            <button onclick="initiateWithdrawal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Withdraw Funds
            </button>
        </div>
    </div>

    <!-- Today's Visits -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Today's Visits</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($today_visits as $visit): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo date('h:i A', strtotime($visit['start_time'])); ?></td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium"><?php echo htmlspecialchars($visit['username']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($visit['email']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $visit['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo ucfirst($visit['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">₹<?php echo number_format($visit['daily_rate'], 2); ?></td>
                        <td class="px-6 py-4">
                            <?php if ($visit['status'] === 'scheduled'): ?>
                            <button onclick="markAttendance(<?php echo $visit['id']; ?>)" 
                                class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                Mark Present
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Earnings History -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Earnings History</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visits</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Earnings</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($earnings_history as $earning): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($earning['date'])); ?></td>
                        <td class="px-6 py-4"><?php echo $earning['visit_count']; ?></td>
                        <td class="px-6 py-4">₹<?php echo number_format($earning['total_earnings'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
