<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$plan_id = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
$gym_id = filter_input(INPUT_POST, 'gym_id', FILTER_VALIDATE_INT);

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch plan details
$stmt = $conn->prepare("SELECT * FROM gym_membership_plans WHERE plan_id = ? AND gym_id = ?");
$stmt->execute([$plan_id, $gym_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate membership duration
$duration_mapping = [
    'Daily' => 1,
    'Weekly' => 7,
    'Monthly' => 30,
    'Yearly' => 365
];

$duration_days = $duration_mapping[$plan['duration']];
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime("+$duration_days days"));

// Create membership record
$stmt = $conn->prepare("INSERT INTO user_memberships (user_id, plan_id, gym_id, start_date, end_date, status, payment_status) VALUES (?, ?, ?, ?,?, 'active', 'pending')");
$stmt->execute([$_SESSION['user_id'], $plan_id,$gym_id, $start_date, $end_date]);
$membership_id = $conn->lastInsertId();

// Razorpay API Configuration
$keyId = "rzp_test_E5BNM56ZxxZAwk";
$keySecret = "uXo5UAsgnT7zglLrmsH749Je";

// Create Razorpay Order using cURL
$data = [
    'receipt' => 'membership_' . $membership_id,
    'amount' => $plan['price'] * 100,
    'currency' => 'INR',
    'notes' => [
        'membership_id' => $membership_id,
        'plan_id' => $plan_id,
        'user_id' => $_SESSION['user_id']
    ]
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$result = curl_exec($ch);
curl_close($ch);

$razorpayOrder = json_decode($result, true);

// Insert payment record
$stmt = $conn->prepare("INSERT INTO payments (user_id, gym_id, membership_id, amount, payment_method, transaction_id, status) VALUES (?,?, ?, ?, 'razorpay', ?, 'pending')");
$stmt->execute([$_SESSION['user_id'],$gym_id, $membership_id, $plan['price'], $razorpayOrder['id']]);

$response = [
    'key' => $keyId,
    'amount' => $data['amount'],
    'currency' => $data['currency'],
    'order_id' => $razorpayOrder['id'],
    'plan_name' => $plan['tier'],
    'membership_id' => $membership_id
];

header('Content-Type: application/json');
echo json_encode($response);

// After getting Razorpay order response, redirect to payment verification page
$redirectUrl = "verify_payment.php?" . http_build_query([
    'order_id' => $razorpayOrder['id'],
    'membership_id' => $membership_id,
    'amount' => $data['amount'],
    'plan_name' => $plan['tier']
]);

header("Location: " . $redirectUrl);
exit();

?>