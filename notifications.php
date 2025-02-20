<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

try {
    // Get gym ID and name
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<div class='text-red-500 text-center mt-8'>No user find with this id .</div>";
        exit;
    }


    // Fetch notifications for the gym
    $notificationStmt = $conn->prepare(
        "SELECT n.id, n.title, n.message, n.created_at 
         FROM notifications n 
         WHERE n.user_id = :user_id 
         ORDER BY n.created_at DESC"
    );
    $notificationStmt->bindParam(':user_id', $user_id);
    $notificationStmt->execute();
    $notifications = $notificationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='text-red-500 text-center mt-8'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
include 'includes/navbar.php';

?>
    <div class="container mx-auto mt-8">

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
