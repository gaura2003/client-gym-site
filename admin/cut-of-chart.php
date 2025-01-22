<?php
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

try {
    // Fetch plan data
    $planStmt = $conn->prepare("
        SELECT 
            gmp.plan_id, 
            gmp.gym_id, 
            gmp.tier, 
            gmp.duration, 
            gmp.price, 
            gmp.inclusions, 
            coc.admin_cut_percentage, 
            coc.gym_owner_cut_percentage
        FROM gym_membership_plans gmp
        JOIN cut_off_chart coc ON gmp.tier = coc.tier AND gmp.duration = coc.duration
    ");
    $planStmt->execute();
    $plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($plans)) {
        throw new Exception('No plans or cut-off data found.');
    }
include '../includes/navbar.php';
    // Display plans as a chart/table
    echo "<table border='1' class='table-auto container mx-auto px-4 py-8'>";
    echo "<tr>
            <th>Plan ID</th>
            <th>Gym ID</th>
            <th>Tier</th>
            <th>Duration</th>
            <th>Price</th>
            <th>Admin Cut (%)</th>
            <th>Gym Owner Cut (%)</th>
            <th>Admin Revenue</th>
            <th>Gym Revenue</th>
            <th>Inclusions</th>
          </tr>";
    foreach ($plans as $plan) {
        $adminRevenue = ($plan['price'] * $plan['admin_cut_percentage']) / 100;
        $gymRevenue = ($plan['price'] * $plan['gym_owner_cut_percentage']) / 100;

        echo "<tr>
                <td>{$plan['plan_id']}</td>
                <td>{$plan['gym_id']}</td>
                <td>{$plan['tier']}</td>
                <td>{$plan['duration']}</td>
                <td>{$plan['price']}</td>
                <td>{$plan['admin_cut_percentage']}</td>
                <td>{$plan['gym_owner_cut_percentage']}</td>
                <td>{$adminRevenue}</td>
                <td>{$gymRevenue}</td>
                <td>{$plan['inclusions']}</td>
                <td><a href='update-cut-off.php?plan_id={$plan['plan_id']}'>edit</a></td>
              </tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
