<?php
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Update schedules where start time has passed
    $stmt = $conn->prepare("
        UPDATE schedules 
        SET status = 'completed',
            completed_at = NOW()
        WHERE status = 'scheduled'
        AND DATE(start_date) = CURDATE()
        AND CONCAT(start_date, ' ', start_time) < NOW()
    ");
    
    $stmt->execute();

    // Record attendance for completed sessions
    $stmt = $conn->prepare("
        INSERT INTO attendance (
            schedule_id, user_id, gym_id,
            check_in_time, status
        )
        SELECT 
            id, user_id, gym_id,
            CONCAT(start_date, ' ', start_time),
            'auto_completed'
        FROM schedules
        WHERE status = 'completed'
        AND completed_at = NOW()
    ");
    
    $stmt->execute();

    $conn->commit();
    
    echo "Schedule statuses updated successfully";

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Schedule status update failed: " . $e->getMessage());
    echo "Failed to update schedule statuses";
}
?>
