<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    $cut_type = filter_input(INPUT_POST, 'cut_type', FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("
        UPDATE gym_membership_plans 
        SET cut_type = ?, 
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $result = $stmt->execute([$cut_type]);

    if (!$result) {
        throw new Exception("Failed to update cut types");
    }

    $conn->commit();
    $_SESSION['success'] = 'All plans updated to ' . $cut_type . ' successfully';
    header('Location: cut-of-chart.php');
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Failed to update cut types: ' . $e->getMessage();
    header('Location: cut-off-chart.php');
    exit();
}
