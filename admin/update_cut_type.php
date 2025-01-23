<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

try {
    $db = new GymDatabase();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    $plan_id = $_POST['plan_id'];
    $cut_type = $_POST['cut_type'];
    
    // Update the cut type for the specific plan
    $stmt = $conn->prepare("
        UPDATE gym_membership_plans 
        SET cut_type = ? 
        WHERE plan_id = ?
    ");
    
    $stmt->execute([$cut_type, $plan_id]);
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Cut type updated successfully";
    header('Location: cut-off-chart.php');
    exit();

} catch (Exception $e) {
    if ($conn) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Failed to update cut type: " . $e->getMessage();
    header('Location: cut-off-chart.php');
    exit();
}
?>
