<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}
include 'includes/navbar.php';
?>
    <div class="container mx-auto px-4 py-8">
        <!-- Profile Card -->
        <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="bg-gray-800 text-white text-center p-6">
                <div class="w-24 h-24 mx-auto rounded-full overflow-hidden border-4 border-gray-300">
                    <img src="<?= htmlspecialchars($user['profile_image'] ?? 'default-profile.png') ?>" alt="Profile Image" class="object-cover w-full h-full">
                </div>
                <h2 class="mt-4 text-2xl font-semibold"><?= htmlspecialchars($user['username']) ?></h2>
                <p class="text-gray-300"><?= htmlspecialchars($user['role']) ?></p>
            </div>
            <div class="p-6">
                <!-- User Details -->
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">Personal Details</h3>
                    <div class="mt-2">
                        <p class="text-gray-600"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p class="text-gray-600"><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></p>
                        <p class="text-gray-600"><strong>Status:</strong> <span class="capitalize"><?= htmlspecialchars($user['status']) ?></span></p>
                    </div>
                </div>
                <!-- Account Details -->
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-700">Account Details</h3>
                    <div class="mt-2">
                        <p class="text-gray-600"><strong>Balance:</strong> ₹<?= number_format($user['balance'], 2) ?></p>
                        <p class="text-gray-600"><strong>Joined:</strong> <?= date('d M Y', strtotime($user['created_at'])) ?></p>
                        <p class="text-gray-600"><strong>Last Updated:</strong> <?= date('d M Y H:i:s', strtotime($user['updated_at'])) ?></p>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="flex justify-end gap-4 mt-6">
                    <a href="edit_profile.php" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Edit Profile</a>
                    <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Logout</a>
                </div>
            </div>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>