<?php
session_start();
require_once 'config/database.php';
require_once 'vendor/autoload.php';

$db = new GymDatabase();
$conn = $db->getConnection();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$plan_id = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
$gym_id = filter_input(INPUT_POST, 'gym_id', FILTER_VALIDATE_INT);

if (!$plan_id || !$gym_id) {
    header('Location: error.php?message=invalid_input');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM gym_membership_plans WHERE plan_id = ? AND gym_id = ?");
$stmt->execute([$plan_id, $gym_id]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    header('Location: error.php?message=plan_not_found');
    exit();
}

$duration_mapping = [
    'Daily' => 1,
    'Weekly' => 7,
    'Bi-Weekly' => 14,
    'Semi-Monthly' => 15,
    'Monthly' => 30,
    'Quarterly' => 90,
    'Half Yearly' => 180,
    'Yearly' => 365
];

$duration_days = $duration_mapping[$plan['duration']];

// Calculate dates using DateTime for accuracy
$start_date_obj = new DateTime();
$start_date_obj->setTime(0, 0, 0); // Set time to midnight
$end_date_obj = clone $start_date_obj;
$end_date_obj->modify("+$duration_days days");

// Format dates for database
$start_date = $start_date_obj->format('Y-m-d');
$end_date = $end_date_obj->format('Y-m-d');

// Create membership record
$stmt = $conn->prepare("
    INSERT INTO user_memberships (
        amount, gym_id, user_id, plan_id, 
        start_date, end_date, status, payment_status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 'active', 'pending'
    )
");
$stmt->execute([$plan['price'], $gym_id, $user_id, $plan_id, $start_date, $end_date]);


$membership_id = $conn->lastInsertId();

// Razorpay Configuration and remaining code...
$keyId = $_ENV['RAZORPAY_KEY_ID'];
$keySecret = $_ENV['RAZORPAY_KEY_SECRET'];

if (!$keyId || !$keySecret) {
    header('Location: error.php?message=razorpay_config_missing');
    exit();
}

$data = [
    'receipt' => 'membership_' . $membership_id,
    'amount' => $plan['price'] * 100,
    'currency' => 'INR',
    'notes' => [
        'membership_id' => $membership_id,
        'plan_id' => $plan_id,
        'user_id' => $user_id
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

if (!isset($razorpayOrder['id'])) {
    error_log('Razorpay order creation failed: ' . json_encode($razorpayOrder));
    header('Location: error.php?message=payment_init_failed');
    exit();
}

// Insert payment record
$stmt = $conn->prepare("
    INSERT INTO payments (
        gym_id, user_id, membership_id, amount, 
        payment_method, transaction_id, status
    ) VALUES (
        ?, ?, ?, ?, 'razorpay', ?, 'pending'
    )
");
$stmt->execute([$gym_id, $user_id, $membership_id, $plan['price'], $razorpayOrder['id']]);

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


$redirectUrl = "verify_payment.php?" . http_build_query([
    'order_id' => $razorpayOrder['id'],
    'membership_id' => $membership_id,
    'amount' => $data['amount'],
    'plan_name' => $plan['tier'],
    'gym_id' => $gym_id,
    'plan_id' => $plan_id,
    'user_id' => $user_id,
    'plan_price' => $plan['price'],
    'plan_duration' => $plan['duration'],
   'start_date' => $start_date,
   'end_date' => $end_date,
]);

header("Location: " . $redirectUrl);
exit();
?>
