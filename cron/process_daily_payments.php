<?php
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Get today's scheduled visits
    $stmt = $conn->prepare("
        SELECT s.id, s.gym_id, s.daily_rate, gr.id as revenue_id
        FROM schedules s
        JOIN gym_revenue gr ON JSON_CONTAINS(gr.schedule_ids, CAST(s.id AS JSON))
        WHERE DATE(s.start_date) = CURRENT_DATE
        AND s.status = 'scheduled'
        AND gr.payment_status = 'pending'
    ");
    
    $stmt->execute();
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($visits as $visit) {
        // Update gym balance
        $updateGymStmt = $conn->prepare("
            UPDATE gyms 
            SET balance = balance + ?
            WHERE gym_id = ?
        ");
        $updateGymStmt->execute([$visit['daily_rate'], $visit['gym_id']]);

        // Mark schedule as paid
        $updateScheduleStmt = $conn->prepare("
            UPDATE schedules 
            SET payment_status = 'paid'
            WHERE id = ?
        ");
        $updateScheduleStmt->execute([$visit['id']]);
    }
    $conn->commit();
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Daily payment processing failed: " . $e->getMessage());
}
echo 'done';
echo $visits;

?>
