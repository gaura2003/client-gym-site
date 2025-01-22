<?php
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tier = $_POST['tier'];
        $duration = $_POST['duration'];
        $admin_cut = $_POST['admin_cut'];
        $gym_owner_cut = $_POST['gym_owner_cut'];

        // Validate percentages
        if ($admin_cut + $gym_owner_cut !== 100) {
            throw new Exception('Admin Cut and Gym Owner Cut must add up to 100%.');
        }

        // Update the cut-off chart
        $updateStmt = $conn->prepare("
            UPDATE cut_off_chart
            SET admin_cut_percentage = ?, gym_owner_cut_percentage = ?
            WHERE tier = ? AND duration = ?
        ");
        $updateStmt->execute([$admin_cut, $gym_owner_cut, $tier, $duration]);

        echo "Cut-off percentages updated successfully!";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Fetch available tiers and durations
$tierStmt = $conn->prepare("SELECT DISTINCT tier FROM cut_off_chart");
$tierStmt->execute();
$tiers = $tierStmt->fetchAll(PDO::FETCH_COLUMN);

$durationStmt = $conn->prepare("SELECT DISTINCT duration FROM cut_off_chart");
$durationStmt->execute();
$durations = $durationStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Cut-Off Percentages</title>
</head>
<body>
    <h1>Update Cut-Off Percentages</h1>
    <form method="POST">
        <label for="tier">Tier:</label>
        <select name="tier" id="tier" required>
            <?php foreach ($tiers as $tier): ?>
                <option value="<?= $tier ?>"><?= $tier ?></option>
            <?php endforeach; ?>
        </select>

        <label for="duration">Duration:</label>
        <select name="duration" id="duration" required>
            <?php foreach ($durations as $duration): ?>
                <option value="<?= $duration ?>"><?= $duration ?></option>
            <?php endforeach; ?>
        </select>

        <label for="admin_cut">Admin Cut Percentage:</label>
        <input type="number" name="admin_cut" id="admin_cut" step="0.01" min="0" max="100" required>

        <label for="gym_owner_cut">Gym Owner Cut Percentage:</label>
        <input type="number" name="gym_owner_cut" id="gym_owner_cut" step="0.01" min="0" max="100" required>

        <button type="submit">Update Percentages</button>
    </form>
</body>
</html>
