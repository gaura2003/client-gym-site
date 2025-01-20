<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = intval($_POST['schedule_id']);
    $cancellation_reason = trim($_POST['cancellation_reason']);
    $user_id = $_SESSION['user_id'];

    $db = new GymDatabase();
    $conn = $db->getConnection();

    // Fetch schedule and validate
    $stmt = $conn->prepare("
        SELECT s.*, m.end_date 
        FROM schedules s 
        JOIN user_memberships m ON s.user_id = m.user_id 
        WHERE s.id = :schedule_id AND s.user_id = :user_id AND s.status = 'scheduled'
    ");
    $stmt->bindParam(':schedule_id', $schedule_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        die("Invalid schedule or already canceled.");
    }

    // Calculate new membership end date
    $new_end_date = date('Y-m-d', strtotime($schedule['end_date'] . ' +1 day'));

    // Begin transaction for cancellation
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("
            UPDATE schedules 
            SET status = 'cancelled', cancellation_reason = :reason 
            WHERE id = :schedule_id
        ");
        $stmt->bindParam(':reason', $cancellation_reason);
        $stmt->bindParam(':schedule_id', $schedule_id);
        $stmt->execute();

        $stmt = $conn->prepare("
            UPDATE user_memberships 
            SET end_date = :new_end_date 
            WHERE user_id = :user_id
        ");
        $stmt->bindParam(':new_end_date', $new_end_date);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "Schedule canceled and membership extended!";
        header('Location: schedule_details.php?schedule_id=' . $schedule_id);
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        die("An error occurred: " . $e->getMessage());
    }
}
?>
