<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Validate inputs
$membership_id = filter_input(INPUT_POST, 'membership_id', FILTER_VALIDATE_INT);
$gym_id = filter_input(INPUT_POST, 'gym_id', FILTER_VALIDATE_INT);
$start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
$end_date = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
$start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
$activity_type = filter_input(INPUT_POST, 'activity_type', FILTER_SANITIZE_STRING);
$notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

if (!$membership_id || !$gym_id || !$start_date || !$end_date || !$start_time) {
    $_SESSION['error'] = "All fields are required";
    header('Location: schedule.php');
    exit();
}

function getExactDaysBetween($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1;
}

try {
    $conn->beginTransaction();

   // Add this check before inserting schedule
$occupancyCheck = $conn->prepare("
SELECT COUNT(*) as current_occupancy 
FROM schedules 
WHERE gym_id = ? 
AND start_date = ? 
AND start_time = ?
");
$occupancyCheck->execute([$gym_id, $start_date, $start_time]);
$currentOccupancy = $occupancyCheck->fetch(PDO::FETCH_ASSOC)['current_occupancy'];

if ($currentOccupancy >= 50) {
throw new Exception("Selected time slot is full. Maximum capacity (50) reached.");
}

// Update gym occupancy tracking in the existing code
$stmt = $conn->prepare("
UPDATE gyms 
SET current_occupancy = (
    SELECT COUNT(*) 
    FROM schedules 
    WHERE gym_id = ? 
    AND start_date = CURRENT_DATE 
    AND start_time = ?
)
WHERE gym_id = ?
");
$stmt->execute([$gym_id, $start_time, $gym_id]);

    $stmt = $conn->prepare("
        SELECT 
            um.*,
            gmp.tier,
            gmp.price as plan_price,
            gmp.duration,
            g.name as gym_name,
            CASE 
                WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end THEN 'fee_based'
                ELSE 'tier_based'
            END as cut_type,
            CASE 
                WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end THEN fbc.admin_cut_percentage
                ELSE coc.admin_cut_percentage
            END as admin_cut_percentage,
            CASE 
                WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end THEN fbc.gym_cut_percentage
                ELSE coc.gym_owner_cut_percentage
            END as gym_owner_cut_percentage,
            u.balance as user_balance
        FROM user_memberships um
        JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
        JOIN gyms g ON um.gym_id = g.gym_id
        LEFT JOIN cut_off_chart coc ON gmp.tier = coc.tier AND gmp.duration = coc.duration
        LEFT JOIN fee_based_cuts fbc ON gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end
        JOIN users u ON um.user_id = u.id
        WHERE um.id = ? AND um.user_id = ?
        AND um.status = 'active'
        AND um.payment_status = 'paid'
    ");
    
    $stmt->execute([$membership_id, $user_id]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        throw new Exception("Invalid membership selected");
    }

    // Calculate revenue distribution
    $total_plan_price = $membership['plan_price'];
    $gym_cut_total = floor(($total_plan_price * $membership['gym_owner_cut_percentage']) / 100);
    $admin_cut_total = $total_plan_price - $gym_cut_total;

    // Calculate daily rates
    $total_days = getExactDaysBetween($start_date, $end_date);
    $daily_gym_rate = floor($gym_cut_total / $total_days);
    $daily_admin_rate = floor($admin_cut_total / $total_days);

    // Convert string dates to DateTime objects
    $start_date_obj = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);

    // Insert schedules
    while($start_date_obj <= $end_date_obj) {
        $stmt = $conn->prepare("
            INSERT INTO schedules (
                user_id, gym_id, membership_id,
                activity_type, start_date, end_date,
                start_time, status, notes,
                daily_rate, cut_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, $gym_id, $membership_id,
            $activity_type, 
            $start_date_obj->format('Y-m-d'),
            $start_date_obj->format('Y-m-d'),
            $start_time, $notes, $daily_gym_rate,
            $membership['cut_type']
        ]);
        $start_date_obj->modify('+1 day');
    }
    $schedule_id = $conn->lastInsertId();

    // Record gym revenue
    $stmt = $conn->prepare("
        INSERT INTO gym_revenue (
            gym_id, date, amount, admin_cut,
            source_type, schedule_id, notes,
            daily_rate, cut_type
        ) VALUES (?, ?, ?, ?, 'visit', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $gym_id,
        $start_date,
        $gym_cut_total,
        $admin_cut_total,
        $schedule_id,
        "Revenue for $total_days days schedule",
        $daily_gym_rate,
        $membership['cut_type']
    ]);

    // Update gym balance
    $stmt = $conn->prepare("
        UPDATE gyms 
        SET balance = balance + ?,
            current_occupancy = current_occupancy + 1
        WHERE gym_id = ?
    ");
    $stmt->execute([$gym_cut_total, $gym_id]);

    // Update user balance
    $stmt = $conn->prepare("
        UPDATE users 
        SET balance = balance - ? 
        WHERE id = ?
    ");
    $stmt->execute([$total_plan_price, $user_id]);

    // Create notifications
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            user_id, gym_id, message, title, status
        ) VALUES 
        (?, NULL, ?, 'Schedule Created', 'unread'),
        (NULL, ?, ?, 'New Schedule', 'unread')
    ");
    $stmt->execute([
        $user_id,
        "Schedule created from " . date('d M Y', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date)),
        $gym_id,
        "New schedule for " . $total_days . " days starting " . date('d M Y', strtotime($start_date))
    ]);

    $conn->commit();
    $_SESSION['success'] = "Schedule created successfully for $total_days days";
    header('Location: user_schedule.php');
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Schedule creation failed: " . $e->getMessage());
    $_SESSION['error'] = "Failed to create schedule: " . $e->getMessage();
    header('Location: schedule.php');
    exit();
}
?>
