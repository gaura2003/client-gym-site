<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}

$gymOwnerId = $_SESSION['owner_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $memberId = $_GET['id'];

    // Fetch member details
    $stmt = $conn->prepare("SELECT * FROM members WHERE owner_id = :owner_id AND member_id = :member_id");
    $stmt->bindParam(':owner_id', $gymOwnerId);
    $stmt->bindParam(':member_id', $memberId);
    $stmt->execute();
    $member = $stmt->fetch();
} else {
    echo "<p class='bg-red-500 text-white p-4'>Member ID is missing.</p>";
}
include './includes/navbar.php';

?>

    <div class="container mx-auto p-6">
        <div class="bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-xl font-bold mb-4">Member Details</h1>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($member['name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
            <p><strong>Joined Date:</strong> <?php echo htmlspecialchars($member['joined_date']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($member['status']); ?></p>
        </div>
    </div>
</body>
</html>
