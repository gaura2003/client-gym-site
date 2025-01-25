<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

if (!isset($_POST['membership_id'], $_POST['start_date'], $_POST['end_date'], $_POST['new_gym_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Missing required parameters.']));
}

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();
    // Calculate total days and amount to cut
    $start = new DateTime($_POST['start_date']);
    $end = new DateTime($_POST['end_date']);
    $interval = $start->diff($end);
    $total_days = $interval->days + 1;

    // Get original gym's schedule details and total amount to cut
    $scheduleStmt = $conn->prepare("
    SELECT daily_rate, gym_id as original_gym_id
    FROM schedules 
    WHERE user_id = ? 
    AND start_date BETWEEN ? AND ?
    AND status = 'scheduled'
    LIMIT 1
");

    $scheduleStmt->execute([
        $_SESSION['user_id'],
        $_POST['start_date'],
        $_POST['end_date']
    ]);

    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    $total_amount_to_cut = $schedule['daily_rate'] * $total_days;

    // Deduct total amount from original gym
    $deductStmt = $conn->prepare("
    UPDATE gyms 
    SET balance = balance - ?
    WHERE gym_id = ?
");
    $deductStmt->execute([$total_amount_to_cut, $schedule['original_gym_id']]);

    // Add total amount to new gym
    $addStmt = $conn->prepare("
    UPDATE gyms 
    SET balance = balance + ?
    WHERE gym_id = ?
");
    $addStmt->execute([$total_amount_to_cut, $_POST['new_gym_id']]);

    // Update schedule with new gym
    $updateScheduleStmt = $conn->prepare("
        UPDATE schedules 
        SET gym_id = ?,
            start_time = ?,
            notes = ?
        WHERE user_id = ? 
        AND start_date BETWEEN ? AND ?
        AND status = 'scheduled'
    ");

    $updateScheduleStmt->execute([
        $_POST['new_gym_id'],
        $_POST['start_time'],
        $_POST['notes'],
        $_SESSION['user_id'],
        $_POST['start_date'],
        $_POST['end_date']
    ]);

    // Record transfer in gym_revenue
    $transferStmt = $conn->prepare("
        INSERT INTO gym_revenue (
            gym_id, date, amount, source_type, notes
        ) VALUES 
        (?, CURRENT_DATE, ?, 'transfer_out', ?),
        (?, CURRENT_DATE, ?, 'transfer_in', ?)
    ");

    $transferStmt->execute([
        $schedule['original_gym_id'],
        -$total_amount_to_cut,
        "Transfer out - Schedule update",
        $_POST['new_gym_id'],
        $total_amount_to_cut,
        "Transfer in - Schedule update"
    ]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Schedule and payments transferred successfully']);
    header('Location: user_schedule.php');
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    header('Location: user_schedule.php');
    exit();

}
?>