<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

if (!isset($_POST['membership_id'], $_POST['start_date'], $_POST['end_date'], $_POST['new_gym_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Missing required parameters']));
}

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    // Time validation checks
    $currentDateTime = new DateTime('now');
    $selectedDate = new DateTime($_POST['start_date']);
    $selectedTime = new DateTime($_POST['start_time']);
    $selectedDateTime = new DateTime($_POST['start_date'] . ' ' . $_POST['start_time']);

    if ($selectedDate < $currentDateTime) {
        exit(json_encode(['success' => false, 'error' => 'Cannot update past schedules']));
        header('Location: schedule-workout.php');
    }

    if ($selectedDate->format('Y-m-d') === $currentDateTime->format('Y-m-d')) {
        $timeBuffer = clone $currentDateTime;
        $timeBuffer->add(new DateInterval('PT15M'));
        
        if ($selectedDateTime <= $currentDateTime) {
            exit(json_encode(['success' => false, 'error' => 'Cannot update schedule for past time slots']));
        }
        
        if ($selectedDateTime <= $timeBuffer) {
            exit(json_encode(['success' => false, 'error' => 'Schedule can only be updated at least 15 minutes before the slot time']));
        }
    }

    // Calculate total days
    $start = new DateTime($_POST['start_date']);
    $end = new DateTime($_POST['end_date']);
    $interval = $start->diff($end);
    $total_days = $interval->days + 1;

    // Start transaction
    $conn->beginTransaction();

    // Get schedule details
    $scheduleStmt = $conn->prepare("
        SELECT daily_rate, gym_id as original_gym_id
        FROM schedules 
        WHERE user_id = ? 
        AND start_date BETWEEN ? AND ?
        AND status = 'scheduled'
        LIMIT 1
    ");

    $scheduleStmt->execute([$_SESSION['user_id'], $_POST['start_date'], $_POST['end_date']]);
    $schedule = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        $conn->rollBack();
        exit(json_encode(['success' => false, 'error' => 'No valid schedule found for the selected date range']));
    }

    // Verify gyms exist
    $checkGymStmt = $conn->prepare("SELECT gym_id FROM gyms WHERE gym_id IN (?, ?)");
    $checkGymStmt->execute([$schedule['original_gym_id'], $_POST['new_gym_id']]);
    $validGyms = $checkGymStmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($validGyms) !== 2) {
        $conn->rollBack();
        exit(json_encode(['success' => false, 'error' => 'Invalid gym selection']));
    }

    $total_amount_to_cut = $schedule['daily_rate'] * $total_days;

    // Update gyms balance
    $deductStmt = $conn->prepare("UPDATE gyms SET balance = balance - ? WHERE gym_id = ?");
    $deductStmt->execute([$total_amount_to_cut, $schedule['original_gym_id']]);

    $addStmt = $conn->prepare("UPDATE gyms SET balance = balance + ? WHERE gym_id = ?");
    $addStmt->execute([$total_amount_to_cut, $_POST['new_gym_id']]);

    // Update schedule
    $updateScheduleStmt = $conn->prepare("
        UPDATE schedules 
        SET gym_id = ?, start_time = ?, notes = ?
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

    // Record revenue transfer
    $transferStmt = $conn->prepare("
        INSERT INTO gym_revenue (gym_id, date, amount, source_type, notes)
        VALUES (?, CURRENT_DATE, ?, 'transfer_out', ?),
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
    echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>