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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Handle profile image upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $profile_image = $targetPath;
        }
    }

    try {
        $stmt = $conn->prepare("
            UPDATE users 
            SET username = ?, 
                email = ?, 
                phone = ?,
                " . ($profile_image ? "profile_image = ?," : "") . "
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");

        $params = [$username, $email, $phone];
        if ($profile_image) {
            $params[] = $profile_image;
        }
        $params[] = $user_id;

        $stmt->execute($params);
        
        header('Location: profile.php?success=1');
        exit;
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-6">Edit Profile</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="profile_image">
                        Profile Image
                    </label>
                    <input type="file" 
                           name="profile_image" 
                           accept="image/*"
                           class="w-full px-3 py-2 border rounded">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input type="text" 
                           name="username" 
                           value="<?= htmlspecialchars($user['username']) ?>"
                           class="w-full px-3 py-2 border rounded">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input type="email" 
                           name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>"
                           class="w-full px-3 py-2 border rounded">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                        Phone
                    </label>
                    <input type="tel" 
                           name="phone" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           class="w-full px-3 py-2 border rounded">
                </div>

                <div class="flex justify-between">
                    <a href="profile.php" 
                       class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
