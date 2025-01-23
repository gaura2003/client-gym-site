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
    // Start a transaction
    $conn->beginTransaction();

    // Ensure new gym exists
    $newGymCheckStmt = $conn->prepare("SELECT COUNT(*) FROM gyms WHERE gym_id = ?");
    $newGymCheckStmt->execute([$_POST['new_gym_id']]);
    if ($newGymCheckStmt->fetchColumn() == 0) {
        throw new Exception('New gym not found.');
    }

    // Ensure the start and end dates are valid
    try {
        $start = new DateTime($_POST['start_date']);
        $end = new DateTime($_POST['end_date']);
        if ($start > $end) {
            throw new Exception('Start date cannot be later than end date.');
        }
    } catch (Exception $e) {
        throw new Exception('Invalid date format.');
    }

    // Fetch the membership details, including the original gym ID
    $membershipStmt = $conn->prepare("
        SELECT um.*, gmp.price, gmp.duration, gmp.gym_id as original_gym_id
        FROM user_memberships um
        JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
        WHERE um.id = ? AND um.user_id = ? AND um.status = 'active'
    ");
    $membershipStmt->execute([$_POST['membership_id'], $_SESSION['user_id']]);
    $membership = $membershipStmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        throw new Exception('Membership not found or inactive.');
    }

    // Calculate daily rate based on membership duration
    $dailyRate = match ($membership['duration']) {
        'Daily' => floor($membership['price']),
        'Weekly' => floor($membership['price'] / 7),
        'Monthly' => floor($membership['price'] / 30),
        'Yearly' => floor($membership['price'] / 365),
        default => throw new Exception('Invalid membership duration.'),
    };

    // Calculate the total number of days between start and end dates
    $interval = $start->diff($end);
    $totalDays = $interval->days + 1; // Including the end date

    // Total amount for the entire period
    $totalAmount = $dailyRate * $totalDays;

    // Revenue Distribution
    $revenueDistributionStmt = $conn->prepare("
        INSERT INTO gym_revenue 
        (gym_id, date, amount, source_type, notes) 
        VALUES (?, CURRENT_DATE, ?, 'visit_revenue', ?)
    ");
    $deductRevenueStmt = $conn->prepare("
        INSERT INTO gym_revenue 
        (gym_id, date, amount, source_type, notes) 
        VALUES (?, CURRENT_DATE, ?, 'transfer_deduction', ?)
    ");
     // Send notification
     $userStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
     $userStmt->execute([$_SESSION['user_id']]);
     $user = $userStmt->fetch(PDO::FETCH_ASSOC);
 
     $gymStmt = $conn->prepare("SELECT name FROM gyms WHERE gym_id = ?");
     $gymStmt->execute([$_POST['new_gym_id']]);
     $gym = $gymStmt->fetch(PDO::FETCH_ASSOC);

    $newGymIds = is_array($_POST['new_gym_id']) ? $_POST['new_gym_id'] : [$_POST['new_gym_id']];

    foreach ($newGymIds as $new_gym_id) {
        $deductRevenueStmt->execute([
            $membership['original_gym_id'],
            -$totalAmount,
            "Revenue transfer for member visits to gym {$gym['name']}"
        ]);

        $revenueDistributionStmt->execute([
            $new_gym_id,
            $totalAmount,
            "Revenue from visiting member #{$user['username']}"
        ]);
    }

    // Update schedules with the new gym
    $updateScheduleStmt = $conn->prepare("
        UPDATE schedules 
        SET gym_id = ?, start_time = ?, notes = ? 
        WHERE user_id = ? AND start_date BETWEEN ? AND ?
    ");
    $updateScheduleStmt->execute([
        $_POST['new_gym_id'],
        $_POST['start_time'],
        $_POST['notes'],
        $_SESSION['user_id'],
        $_POST['start_date'],
        $_POST['end_date']
    ]);

    $notificationTitle = "Schedule Update";
    $notificationMessage = "{$user['username']} has updated their schedule to visit {$gym['name']} from {$_POST['start_date']} to {$_POST['end_date']}.";

    // Insert notification into the database
    $notificationStmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, gym_id) 
        VALUES (?, ?, ?, ?)
    ");
    $notificationStmt->execute([
        $_SESSION['user_id'],
        $notificationTitle,
        $notificationMessage,
        $_POST['new_gym_id']
    ]);

    // Commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule updated, revenue distributed, and notification sent successfully.']);
    header('Location: user_schedule.php');
} catch (Exception $e) {
    // Rollback the transaction in case of error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
