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

include '../includes/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Today's Scheduled Visits</h2>
        </div>
        
        <div class="divide-y divide-gray-200">
            <?php if (count($todayBookings) > 0): ?>
                <?php foreach ($todayBookings as $booking): ?>
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?= htmlspecialchars($booking['username']) ?>
                                </h3>
                                <div class="mt-1 text-sm text-gray-500">
                                    <p>Time: <?= date('h:i A', strtotime($booking['start_time'])) ?></p>
                                    <p>Phone: <?= htmlspecialchars($booking['phone']) ?></p>
                                    <p>Email: <?= htmlspecialchars($booking['email']) ?></p>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                <button class="px-4 py-2 bg-green-500 text-white rounded-md">
                                    Check In
                                </button>
                                <button class="px-4 py-2 bg-red-500 text-white rounded-md">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No scheduled visits for today
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
