<?php
session_start();
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();
$member_id = $_GET['id'];

// Fetch member's schedules
$stmt = $conn->prepare("
    SELECT 
        s.*,
        g.name as gym_name,
        g.address as gym_address
    FROM schedules s
    JOIN gyms g ON s.id = g.gym_id
    WHERE s.user_id = ?
    ORDER BY s.start_date DESC, s.start_time DESC
");
$stmt->execute([$member_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch member basic info
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/navbar.php';

?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Schedule Header -->
        <div class="p-6 bg-gray-50 border-b">
            <h1 class="text-2xl font-bold">Schedule for <?php echo htmlspecialchars($member['username']); ?></h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($member['email']); ?></p>
        </div>

        <!-- Calendar View -->
        <div class="p-6">
            <div id="calendar"></div>
        </div>

        <!-- Schedule List -->
        <div class="p-6 border-t">
            <h2 class="text-lg font-semibold mb-4">Upcoming Schedules</h2>
            <div class="space-y-4">
                <?php foreach ($schedules as $schedule): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold"><?php echo htmlspecialchars($schedule['gym_name']); ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?php echo ucfirst($schedule['activity_type']); ?>
                                </p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-sm <?php 
                                echo $schedule['status'] === 'scheduled' ? 'bg-green-100 text-green-800' : 
                                    ($schedule['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 
                                    'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($schedule['status']); ?>
                            </span>
                        </div>
                        
                        <div class="mt-2 text-sm text-gray-600">
                            <p>Date: <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?></p>
                            <p>Time: <?php echo date('g:i A', strtotime($schedule['start_time'])); ?></p>
                            <?php if ($schedule['notes']): ?>
                                <p class="mt-2 italic"><?php echo htmlspecialchars($schedule['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: <?php echo json_encode(array_map(function($schedule) {
            return [
                'title' => $schedule['gym_name'] . ' - ' . ucfirst($schedule['activity_type']),
                'start' => $schedule['start_date'] . 'T' . $schedule['start_time'],
                'className' => 'bg-blue-500 text-white'
            ];
        }, $schedules)); ?>,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        }
    });
    calendar.render();
});
</script>
