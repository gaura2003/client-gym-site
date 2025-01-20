<?php
require './config/GymDatabase.php';

$gym_id = $_SESSION['gym_id']; // Assume gym_id is stored in session after login

// Fetch members for the gym
$sql = "SELECT * FROM members WHERE gym_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $gym_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

echo json_encode($members);
?>
