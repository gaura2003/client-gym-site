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
<div class="min-h-screen bg-gradient-to-b from-gray-900 to-black py-12">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden mb-8">
            <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                <h1 class="text-4xl font-bold text-gray-900 text-center">Welcome to Your Fitness Hub</h1>
                <p class="text-lg text-gray-800 text-center mt-2">Track your schedules, book classes, and achieve your fitness goals with ease!</p>
            </div>
        </div>

        <!-- Membership Status -->
        <?php include 'membership.php'; ?>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-400 rounded-xl">
                            <i class="fas fa-dumbbell text-gray-900 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-yellow-400 text-sm">Total Workouts</p>
                            <p class="text-2xl font-bold text-white">24</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-400 rounded-xl">
                            <i class="fas fa-calendar-check text-gray-900 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-yellow-400 text-sm">Active Days</p>
                            <p class="text-2xl font-bold text-white">15</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-400 rounded-xl">
                            <i class="fas fa-trophy text-gray-900 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-yellow-400 text-sm">Achievements</p>
                            <p class="text-2xl font-bold text-white">7</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Classes -->
        <div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden mb-8">
            <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900">Upcoming Classes</h2>
                    <a href="book_class.php" class="text-gray-900 hover:text-gray-800 transition-colors duration-300">
                        <i class="fas fa-plus-circle mr-2"></i>Book New Class
                    </a>
                </div>
            </div>

            <div class="p-6">
                <?php if ($upcoming_classes): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($upcoming_classes as $class): ?>
                            <div class="bg-gray-700 rounded-xl p-6 hover:shadow-lg transition-all duration-300">
                                <h3 class="font-semibold text-xl text-white mb-2">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </h3>
                                <p class="text-white ">
                                    <i class="fas fa-dumbbell text-yellow-400 mr-2"></i>
                                    <?php echo htmlspecialchars($class['gym_name']); ?>
                                </p>
                                <p class="text-white ">
                                    <i class="fas fa-clock text-yellow-400 mr-2"></i>
                                    <?php echo date('M j, g:i A', strtotime($class['schedule'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-white text-center py-4">No upcoming classes scheduled.</p>
                <?php endif; ?>
            </div>
        </div>
<!-- Upcoming Schedules -->
<div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden mb-8">
    <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
        <h2 class="text-2xl font-bold text-gray-900">Upcoming Schedules</h2>
    </div>

    <div class="p-6">
        <?php if ($upcoming_schedules): ?>
            <div class="space-y-4">
                <?php foreach ($upcoming_schedules as $schedule): ?>
                    <div class="bg-gray-700 rounded-xl p-6 hover:shadow-lg transition-all duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg text-white">
                                    <?php echo htmlspecialchars($schedule['activity_type']); ?>
                                </h3>
                                <p class="text-white ">
                                    <i class="fas fa-map-marker-alt text-yellow-400 mr-2"></i>
                                    <?php echo htmlspecialchars($schedule['gym_name']); ?>
                                </p>
                                <p class="text-white ">
                                    <i class="fas fa-calendar text-yellow-400 mr-2"></i>
                                    <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?> at
                                    <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                                </p>
                            </div>
                            <?php if (!empty($schedule['notes'])): ?>
                                <span class="text-sm text-gray-400 italic">
                                    <i class="fas fa-sticky-note text-yellow-400 mr-1"></i>
                                    <?php echo htmlspecialchars($schedule['notes']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-white text-center py-4">No upcoming schedules.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Schedules -->
<div class="bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-3xl overflow-hidden">
    <div class="p-6 bg-gradient-to-r from-yellow-400 to-yellow-500">
        <h2 class="text-2xl font-bold text-gray-900">Recent Schedules</h2>
    </div>

    <div class="p-6">
        <?php if ($recent_schedules): ?>
            <div class="space-y-4">
                <?php foreach ($recent_schedules as $schedule): ?>
                    <div class="bg-gray-700 rounded-xl p-6 hover:shadow-lg transition-all duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg text-white">
                                    <?php echo htmlspecialchars($schedule['activity_type']); ?>
                                </h3>
                                <p class="text-white ">
                                    <i class="fas fa-dumbbell text-yellow-400 mr-2"></i>
                                    <?php echo htmlspecialchars($schedule['gym_name']); ?>
                                </p>
                                <p class="text-white ">
                                    <i class="fas fa-clock text-yellow-400 mr-2"></i>
                                    <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?> at
                                    <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                                </p>
                            </div>
                            <?php if ($schedule['cancellation_reason'] && $schedule['status'] === 'missed'): ?>
                                <span class="text-sm text-red-400 italic">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <?php echo htmlspecialchars($schedule['cancellation_reason']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-white text-center py-4">No recent schedules.</p>
        <?php endif; ?>
    </div>
</div>

    </div>
</div>

