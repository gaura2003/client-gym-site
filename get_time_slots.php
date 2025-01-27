<?php
require_once 'config/database.php';

$membership_id = $_GET['membership_id'];

$db = new GymDatabase();
$conn = $db->getConnection();

// Get gym operating hours
$stmt = $conn->prepare("
    SELECT 
        goh.morning_open_time,
        goh.morning_close_time,
        goh.evening_open_time,
        goh.evening_close_time
    FROM gym_operating_hours goh
    JOIN user_memberships um ON um.gym_id = goh.gym_id
    WHERE um.id = ? AND goh.day = 'Daily'
");
$stmt->execute([$membership_id]);
$hours = $stmt->fetch(PDO::FETCH_ASSOC);

$slots = [];
if ($hours) {
    // Generate morning slots
    $morning_start = strtotime($hours['morning_open_time']);
    $morning_end = strtotime($hours['morning_close_time']);
    for ($time = $morning_start; $time <= $morning_end; $time += 3600) {
        $slots[] = [
            'time' => date('H:i:s', $time),
            'formatted_time' => date('g:i A', $time),
            'current_occupancy' => 0
        ];
    }

    // Generate evening slots
    $evening_start = strtotime($hours['evening_open_time']);
    $evening_end = strtotime($hours['evening_close_time']);
    for ($time = $evening_start; $time <= $evening_end; $time += 3600) {
        $slots[] = [
            'time' => date('H:i:s', $time),
            'formatted_time' => date('g:i A', $time),
            'current_occupancy' => 0
        ];
    }
}

header('Content-Type: application/json');
echo json_encode(['slots' => $slots]);
?>