<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}

$owner_id = $_SESSION['owner_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

try {
    // Get gym ID and name
    $stmt = $conn->prepare("SELECT gym_id, name FROM gyms WHERE owner_id = :owner_id");
    $stmt->bindParam(':owner_id', $owner_id);
    $stmt->execute();
    $gym = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gym) {
        echo "<div class='text-red-500 text-center mt-8'>No gym found for the logged-in owner.</div>";
        exit;
    }

    $gym_id = $gym['gym_id'];

    // Fetch notifications for the gym
    $notificationStmt = $conn->prepare(
        "SELECT n.id, n.title, n.message, n.created_at 
         FROM notifications n 
         WHERE n.gym_id = :gym_id 
         ORDER BY n.created_at DESC"
    );
    $notificationStmt->bindParam(':gym_id', $gym_id);
    $notificationStmt->execute();
    $notifications = $notificationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='text-red-500 text-center mt-8'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
include '../includes/navbar.php';

?>
    <div class="container mx-auto mt-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            Notifications for Gym: <span class="text-blue-600"><?= htmlspecialchars($gym['name']) ?></span>
        </h2>

        <?php if (count($notifications) > 0): ?>
            <ul class="space-y-4">
                <?php foreach ($notifications as $notification): ?>
                    <li class="bg-white p-4 shadow rounded">
                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($notification['title']) ?></h3>
                        <p class="text-gray-600"><?= htmlspecialchars($notification['message']) ?></p>
                        <p class="text-sm text-gray-500">Received on: <?= htmlspecialchars($notification['created_at']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-500">No notifications available for your gym.</p>
        <?php endif; ?>
    </div>
</body>
</html>
