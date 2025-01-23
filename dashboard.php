<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch upcoming classes
$stmt = $conn->prepare("
    SELECT c.*, g.name as gym_name 
    FROM class_bookings cb 
    JOIN gym_classes c ON cb.class_id = c.id 
    JOIN gyms g ON c.gym_id = g.gym_id 
    WHERE cb.user_id = :user_id AND cb.status = 'booked'
    ORDER BY c.schedule ASC LIMIT 5
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$upcoming_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch upcoming schedules
$stmt = $conn->prepare("
    SELECT s.*, g.name as gym_name 
    FROM schedules s 
    JOIN gyms g ON s.gym_id = g.gym_id 
    WHERE s.user_id = :user_id AND s.status = 'scheduled' AND s.start_date >= CURDATE()
    ORDER BY s.start_date ASC, s.start_time ASC LIMIT 5
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$upcoming_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent schedules
$stmt = $conn->prepare("
    SELECT s.*, g.name as gym_name 
    FROM schedules s 
    JOIN gyms g ON s.gym_id = g.gym_id 
    WHERE s.user_id = :user_id AND s.status IN ('completed', 'missed')
    ORDER BY s.start_date DESC, s.start_time DESC LIMIT 5
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Membership Status -->
    <?php include 'membership.php'; ?>

    <!-- Upcoming Classes -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Upcoming Classes</h2>
        <?php if ($upcoming_classes): ?>
            <div class="space-y-4">
                <?php foreach ($upcoming_classes as $class): ?>
                    <div class="flex justify-between items-center border-b pb-4">
                        <div>
                            <p class="font-semibold"><?php echo htmlspecialchars($class['name']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($class['gym_name']); ?></p>
                        </div>
                        <p class="text-gray-600"><?php echo date('M j, g:i A', strtotime($class['schedule'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600">No upcoming classes.</p>
            <a href="book_class.php" class="text-blue-500 hover:underline">Book a class</a>
        <?php endif; ?>
    </div>
<!-- Upcoming Schedules -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-2xl font-bold mb-4">Upcoming Schedules</h2>
    <?php if ($upcoming_schedules): ?>
        <div class="space-y-4">
            <?php foreach ($upcoming_schedules as $schedule): ?>
                <a href="schedule_details.php?schedule_id=<?php echo $schedule['id']; ?>" class="block hover:bg-gray-50 p-4 rounded-lg border">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-semibold"><?php echo htmlspecialchars($schedule['activity_type']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($schedule['gym_name']); ?></p>
                            <?php if (!empty($schedule['notes'])): ?>
                                <p class="text-gray-500 italic text-sm"><?php echo htmlspecialchars($schedule['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-600">
                            <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?>, 
                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">No upcoming schedules.</p>
    <?php endif; ?>
</div>

<!-- Recent Schedules -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Recent Schedules</h2>
    <?php if ($recent_schedules): ?>
        <div class="space-y-4">
            <?php foreach ($recent_schedules as $schedule): ?>
                <a href="schedule_details.php?schedule_id=<?php echo $schedule['id']; ?>" class="block hover:bg-gray-50 p-4 rounded-lg border">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-semibold"><?php echo htmlspecialchars($schedule['activity_type']); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($schedule['gym_name']); ?></p>
                            <?php if ($schedule['cancellation_reason'] && $schedule['status'] === 'missed'): ?>
                                <p class="text-red-500 italic text-sm">Reason: <?php echo htmlspecialchars($schedule['cancellation_reason']); ?></p>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-600">
                            <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?>, 
                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-600">No recent schedules.</p>
    <?php endif; ?>
</div>
</div>


