<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    $user_id = $_SESSION['user_id'];
    $gym_id = $_POST['gym_id'];
    $original_gym_id = $_POST['gym_id'];
    $start_date = new DateTime($_POST['start_date']);
    $end_date = new DateTime($_POST['end_date']);
    $activity_type = $_POST['activity_type'];
    $start_time = $_POST['start_time'] ?? '00:00:00';
    $notes = $_POST['notes'] ?? null;

    // Calculate daily rate
    $membershipStmt = $conn->prepare("
        SELECT gmp.price, gmp.duration
        FROM user_memberships um
        JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
        WHERE um.user_id = ? AND um.status = 'active'
    ");
    $membershipStmt->execute([$user_id]);
    $membership = $membershipStmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        throw new Exception('No active membership found.');
    }

    $dailyRate = match ($membership['duration']) {
        'Daily' => floor($membership['price']),
        'Weekly' => floor($membership['price'] / 7),
        'Monthly' => floor($membership['price'] / 30),
        'Yearly' => floor($membership['price'] / 365),
    };

    $total_days = $end_date->diff($start_date)->days + 1;
    $total_amount = $dailyRate * $total_days;

    // Insert gym revenue
    $revenueStmt = $conn->prepare("
        INSERT INTO gym_revenue (gym_id, date, amount, source_type)
        VALUES (?, CURRENT_DATE, ?, 'visit')
    ");
    $revenueStmt->execute([$gym_id, $total_amount]);

    // Deduct amount from original gym
    $deductStmt = $conn->prepare("
        INSERT INTO gym_revenue (gym_id, date, amount, source_type)
        VALUES (?, CURRENT_DATE, ?, 'transfer_deduction')
    ");
    $deductStmt->execute([$original_gym_id, -$total_amount]);

    // Insert schedules
    while ($start_date <= $end_date) {
        $scheduleStmt = $conn->prepare("
            INSERT INTO schedules (
                user_id, gym_id, activity_type, start_date, end_date,
                start_time, status, notes, recurring
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 'scheduled', ?, 'none'
            )
        ");
        $scheduleStmt->execute([
            $user_id,
            $gym_id,
            $activity_type,
            $start_date->format('Y-m-d'),
            $start_date->format('Y-m-d'),
            $start_time,
            $notes
        ]);
        $start_date->modify('+1 day');
    }

    // Add notifications
    $notificationStmt = $conn->prepare("
        INSERT INTO notifications (user_id, message, created_at, status)
        VALUES (?, ?, NOW(), 'unread')
    ");
    $notificationStmt->execute([
        $user_id,
        "Your schedule has been successfully updated for gym ID: $gym_id."
    ]);

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
