<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['owner_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: withdraw.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    $amount = $_POST['amount'];
    $bank_account = $_POST['bank_account'];
    $owner_id = $_SESSION['owner_id'];

    // Get gym ID
    $gymStmt = $conn->prepare("SELECT gym_id FROM gyms WHERE owner_id = ?");
    $gymStmt->execute([$owner_id]);
    $gym_id = $gymStmt->fetchColumn();

    // Create withdrawal record
    $withdrawalStmt = $conn->prepare("
        INSERT INTO withdrawals (
            gym_id,
            amount,
            bank_account,
            status,
            created_at
        ) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)
    ");
    
    $withdrawalStmt->execute([
        $gym_id,
        $amount,
        $bank_account
    ]);

    // Update gym revenue
    $revenueStmt = $conn->prepare("
        INSERT INTO gym_revenue (
            gym_id,
            date,
            amount,
            source_type
        ) VALUES (?, CURRENT_DATE, ?, 'withdrawal')
    ");
    
    $revenueStmt->execute([
        $gym_id,
        -$amount
    ]);

    $conn->commit();
    $_SESSION['success'] = 'Withdrawal request submitted successfully';
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Failed to process withdrawal request';
}

header('Location: withdraw.php');
exit;
