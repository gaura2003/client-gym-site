<?php
// Database connection
$host = 'localhost';
$dbname = 'gymdb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to send notification to gym owner
function sendNotification($gymId, $userId, $pdo) {
    // Fetch gym owner email or contact information
    $query = "SELECT owners.email, users.name AS user_name, gyms.name AS gym_name FROM gyms 
              JOIN owners ON gyms.owner_id = owners.id 
              JOIN users ON users.id = :user_id 
              WHERE gyms.id = :gym_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['gym_id' => $gymId, 'user_id' => $userId]);

    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($details) {
        $to = $details['email'];
        $subject = "New Gym Booking";
        $message = "Dear Gym Owner,\n\nUser " . $details['user_name'] . " has booked a visit to your gym (" . $details['gym_name'] . ").\n\nThank you.";
        $headers = "From: no-reply@gymwebsite.com";

        // Send email notification
        mail($to, $subject, $message, $headers);
    }
}

// Function to book a gym visit
function bookGymVisit($userId, $gymId, $bookingDate, $pdo) {
    // Check if the booking date is in the past
    $currentDate = date('Y-m-d');
    if ($bookingDate < $currentDate) {
        return ["success" => false, "message" => "Cannot book past dates."];
    }

    // Check if the user already has a booking for the same date
    $query = "SELECT * FROM class_bookings WHERE user_id = :user_id AND booking_date = :booking_date";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $userId, 'booking_date' => $bookingDate]);

    if ($stmt->rowCount() > 0) {
        return ["success" => false, "message" => "You can only book one gym per day."];
    }

    // Deduct membership balance logic (if applicable)
    // Assume membership balance is stored in a 'users' table with a 'membership_balance' column
    $balanceQuery = "SELECT membership_balance FROM users WHERE id = :user_id";
    $balanceStmt = $pdo->prepare($balanceQuery);
    $balanceStmt->execute(['user_id' => $userId]);

    $user = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['membership_balance'] <= 0) {
        return ["success" => false, "message" => "Insufficient membership balance."];
    }

    // Deduct one visit from the balance
    $newBalance = $user['membership_balance'] - 1;
    $updateBalanceQuery = "UPDATE users SET membership_balance = :new_balance WHERE id = :user_id";
    $updateBalanceStmt = $pdo->prepare($updateBalanceQuery);
    $updateBalanceStmt->execute(['new_balance' => $newBalance, 'user_id' => $userId]);

    // Insert the booking record
    $insertQuery = "INSERT INTO class_bookings (class_id, user_id, booking_date, status) VALUES (:class_id, :user_id, :booking_date, 'booked')";
    $insertStmt = $pdo->prepare($insertQuery);

    $insertStmt->execute(['class_id' => $gymId, 'user_id' => $userId, 'booking_date' => $bookingDate]);

    // Send notification to gym owner
    sendNotification($gymId, $userId, $pdo);

    return ["success" => true, "message" => "Gym visit booked successfully."];
}

// Example usage
$userId = 1; // Replace with the logged-in user's ID
$gymId = 3; // Gym ID the user wants to visit
$bookingDate = '2025-01-18'; // Booking date

$response = bookGymVisit($userId, $gymId, $bookingDate, $pdo);
echo json_encode($response);

?>
