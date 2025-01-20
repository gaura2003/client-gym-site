<?php
session_start();
require '../config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();

// Check if owner is logged in
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.html");
    exit;
}

$owner_id = $_SESSION['owner_id'];

// Fetch gym details for this owner
$query = "SELECT * FROM gyms WHERE owner_id = :owner_id";
$stmt = $conn->prepare($query);
$stmt->execute([':owner_id' => $owner_id]);
$gym = $stmt->fetch(PDO::FETCH_ASSOC);

$gym_id = $gym['gym_id']; //Get gym_id from URL or session

// Query to fetch payment history
$query = "SELECT * FROM gym_revenue WHERE gym_id = :gym_id ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':gym_id', $gym_id, PDO::PARAM_INT);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Close the connection
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-semibold mb-4">Payment History</h1>

        <!-- Payment History Table -->
        <table class="min-w-full table-auto bg-white rounded-lg shadow-md">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Amount</th>
                    <th class="px-4 py-2 text-left">Source Type</th>
                    <th class="px-4 py-2 text-left">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($payment['date']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($payment['amount']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($payment['source_type']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($payment['notes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-center">No payments found for this gym.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
