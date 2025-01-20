<?php

require_once 'includes/auth.php';
require_once 'config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();

// Get user profile
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->execute([$username, $email,  $_SESSION['user_id']]);
    $success = "Profile updated successfully!";
}
include 'includes/navbar.php';
?>

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">My Profile</h1>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-lg shadow">
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-2">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                           class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                           class="w-full p-2 border rounded">
                </div>
                <div>
                    <label class="block mb-2">Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['username']); ?>" 
                           class="w-full p-2 border rounded">
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

