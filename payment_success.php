<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db = new GymDatabase();
    $conn = $db->getConnection();
    
    // Update payment status
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', transaction_id = ? WHERE membership_id = ?");
    $stmt->execute([$data['razorpay_payment_id'], $data['membership_id']]);
    
    // Update membership status
    $stmt = $conn->prepare("UPDATE user_memberships SET payment_status = 'paid' WHERE id = ?");
    $stmt->execute([$data['membership_id']]);
    
    echo json_encode(['success' => true]);
    exit();
}

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 text-center">
        <div class="text-green-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4">Payment Successful!</h1>
        <p class="text-gray-600 mb-6">Your membership has been activated successfully.</p>
        <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            Go to Dashboard
        </a>
    </div>
</div>
