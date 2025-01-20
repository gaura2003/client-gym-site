<?php
session_start();
require 'config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

$gymId = $_GET['gym_id'];
$startDate = $_GET['start_date'];

$stmt = $conn->prepare("
    SELECT end_date 
    FROM schedules 
    WHERE user_id = ? AND gym_id = ? 
    AND end_date >= ?
    ORDER BY end_date DESC 
    LIMIT 1
");

$stmt->execute([$_SESSION['user_id'], $gymId, $startDate]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'exists' => $result ? true : false,
    'end_date' => $result ? $result['end_date'] : null
]);
