<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    if ($_POST['cut_type'] === 'tier_based') {
        // Validate inputs
        $tier = filter_input(INPUT_POST, 'tier', FILTER_SANITIZE_STRING);
        $duration = filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_STRING);
        $adminCut = filter_input(INPUT_POST, 'admin_cut', FILTER_VALIDATE_FLOAT);
        $gymCut = filter_input(INPUT_POST, 'gym_cut', FILTER_VALIDATE_FLOAT);

        // Insert tier-based cut-off
        $stmt = $conn->prepare("
            INSERT INTO cut_off_chart 
            (tier, duration, admin_cut_percentage, gym_owner_cut_percentage, cut_type) 
            VALUES (?, ?, ?, ?, 'tier_based')
        ");
        $stmt->execute([$tier, $duration, $adminCut, $gymCut]);

    } else {
        // Validate inputs
        $priceStart = filter_input(INPUT_POST, 'price_start', FILTER_VALIDATE_FLOAT);
        $priceEnd = filter_input(INPUT_POST, 'price_end', FILTER_VALIDATE_FLOAT);
        $adminCut = filter_input(INPUT_POST, 'admin_cut', FILTER_VALIDATE_FLOAT);
        $gymCut = filter_input(INPUT_POST, 'gym_cut', FILTER_VALIDATE_FLOAT);

        // Insert fee-based cut-off
        $stmt = $conn->prepare("
            INSERT INTO fee_based_cuts 
            (price_range_start, price_range_end, admin_cut_percentage, gym_cut_percentage, cut_type) 
            VALUES (?, ?, ?, ?, 'fee_based')
        ");
        $stmt->execute([$priceStart, $priceEnd, $adminCut, $gymCut]);
    }

    $conn->commit();
    $_SESSION['success'] = 'Cut-off settings added successfully';
    header('Location: cut-off-chart.php');
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = 'Failed to add cut-off settings: ' . $e->getMessage();
    header('Location: add-cutoff.php');
    exit();
}
?>
