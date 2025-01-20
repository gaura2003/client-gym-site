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

    // Validate new gym exists
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

    // Fetch the original gym IDs from the schedules table for the given date range
    $gymStmt = $conn->prepare("
        SELECT gym_id, COUNT(*) as occurrence 
        FROM schedules 
        WHERE user_id = ? AND start_date BETWEEN ? AND ?
        GROUP BY gym_id
    ");
    $gymStmt->execute([$_SESSION['user_id'], $_POST['start_date'], $_POST['end_date']]);
    $gymOccurrences = $gymStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($gymOccurrences)) {
        throw new Exception('No gyms found for the specified dates.');
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

    $totalNewDays = 0; // Counter for new days
    
    $updateVisitStmt = $conn->prepare("
        UPDATE visit 
        SET gym_id = ?, check_in_time = ?
        WHERE user_id = ? AND DATE(check_in_time) = ?
    ");
    $createVisitStmt = $conn->prepare("
        INSERT INTO visit (user_id, gym_id, check_in_time, status)
        VALUES (?, ?, ?, 'active')
    ");


    $current = clone $start;
    while ($current <= $end) {
        $visit_date = $current->format('Y-m-d');
        $check_in_time = $visit_date . ' ' . $_POST['start_time'];

         // Check if a visit exists for this date
        $checkVisitStmt = $conn->prepare("
            SELECT COUNT(*) FROM visit WHERE user_id = ? AND DATE(check_in_time) = ?
        ");
        $checkVisitStmt->execute([$_SESSION['user_id'], $visit_date]);
        $visitExists = $checkVisitStmt->fetchColumn();

        if ($visitExists) {
            // Update existing visit
            $updateVisitStmt->execute([
                $_POST['new_gym_id'],
                $check_in_time,
                $_SESSION['user_id'],
                $visit_date,
            ]);
        } else {
            // Create new visit
            $createVisitStmt->execute([
                $_SESSION['user_id'],
                $_POST['new_gym_id'],
                $check_in_time,
            ]);
        }
        $current->modify('+1 day');
    }
    $visitRevenueStmt = $conn->prepare("
        INSERT INTO gym_visit_revenue 
        (visit_id, original_gym_id, visited_gym_id, daily_rate, visit_date) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $duplicateCheckStmt = $conn->prepare("
        SELECT COUNT(*) FROM visit 
        WHERE user_id = ? AND gym_id = ? AND DATE(check_in_time) = ?
    ");

    $current = clone $start;
    while ($current <= $end) {
        $visit_date = $current->format('Y-m-d');
        $check_in_time = $visit_date . ' ' . $_POST['start_time'];

        foreach ($gymOccurrences as $gymOccurrence) {
            $gym_id = $gymOccurrence['gym_id'];
            $occurrenceCount = $gymOccurrence['occurrence'];

            // Check for duplicate visit
            $duplicateCheckStmt->execute([
                $_SESSION['user_id'],
                $gym_id,
                $visit_date
            ]);
            $duplicateCount = $duplicateCheckStmt->fetchColumn();

            if ($duplicateCount == 0) {
                // Count this day as a new day for payment calculation
                $totalNewDays++;

                // Insert visit record
                $createVisitStmt->execute([
                    $_SESSION['user_id'],
                    $gym_id,
                    $check_in_time
                ]);

                $visit_id = $conn->lastInsertId();

                // Insert visit revenue record
                $visitRevenueStmt->execute([
                    $visit_id,
                    $membership['original_gym_id'],
                    $gym_id,
                    $dailyRate,
                    $visit_date
                ]);
            }
        }

        $current->modify('+1 day');
    }

    if ($totalNewDays > 0) {
        // Calculate the total deduction amount for all gyms based on occurrences
        $totalAmount = 0;
        foreach ($gymOccurrences as $gymOccurrence) {
            $occurrenceCount = $gymOccurrence['occurrence'];
            $gym_id = $gymOccurrence['gym_id'];

            // Calculate the gym's total amount based on its occurrence
            $totalAmountForGym = $dailyRate * $occurrenceCount;
            $totalAmount += $totalAmountForGym;

            // Record deduction from the gym
            $deductRevenueStmt = $conn->prepare("
                INSERT INTO gym_revenue 
                (gym_id, date, amount, source_type, notes) 
                VALUES (?, CURRENT_DATE, ?, 'transfer_deduction', ?)
            ");
            $deductRevenueStmt->execute([
                $gym_id,
                -$totalAmountForGym,
                "Revenue transfer for member visits to gym #{$_POST['new_gym_id']}"
            ]);
        }

        // Credit the total amount to the new gym
        $addRevenueStmt = $conn->prepare("
            INSERT INTO gym_revenue 
            (gym_id, date, amount, source_type, notes) 
            VALUES (?, CURRENT_DATE, ?, 'visit_revenue', ?)
        ");
        $addRevenueStmt->execute([
            $_POST['new_gym_id'],
            $totalAmount,
            "Revenue from visiting member #{$_SESSION['user_id']}"
        ]);
    }

    // Update schedules with new gym
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

    // Commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule updated and revenue distributed successfully.']);
    header('Location: user_schedule.php');
} catch (Exception $e) {
    // Rollback the transaction in case of error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
