<?php
session_start();
require_once 'config/database.php';
require_once 'vendor/autoload.php'; // Load Razorpay SDK

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Razorpay\Api\Api;

$apiKey = $_ENV['RAZORPAY_KEY_ID'];
$apiSecret = $_ENV['RAZORPAY_KEY_SECRET'];


$api = new Api($apiKey, $apiSecret);

$membershipPlanId = $_POST['plan_id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$membershipPlanId || !$userId) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Fetch membership plan details
$db = new GymDatabase();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM gym_membership_plans WHERE id = ?");
$stmt->execute([$membershipPlanId]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    echo json_encode(['error' => 'Invalid plan']);
    exit();
}

$amount = $plan['price'] * 100; // Razorpay expects amount in paise
$currency = "INR";

// Create Razorpay Order
$order = $api->order->create([
    'amount' => $amount,
    'currency' => $currency,
    'receipt' => 'order_rcptid_' . time(),
]);

// Save order details in the database
$stmt = $conn->prepare("
    INSERT INTO payments (user_id, gym_id, plan_id, order_id, amount, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $userId,
    $plan['gym_id'],
    $membershipPlanId,
    $order['id'],
    $amount / 100,
    'created',
]);

echo json_encode([
    'order_id' => $order['id'],
    'amount' => $amount,
    'currency' => $currency,
    'key' => $apiKey,
    'plan_name' => $plan['tier'],
]);
?>