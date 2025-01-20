<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}

$gymOwnerId = $_SESSION['owner_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

if (isset($_GET['id'])) {
    $equipmentId = $_GET['id'];
    
    // Get current equipment details
    $stmt = $conn->prepare("SELECT * FROM gym_equipment WHERE equipment_id = :equipment_id");
    $stmt->bindParam(':equipment_id', $equipmentId);
    $stmt->execute();
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $equipment_name = $_POST['equipment_name'];
        $quantity = $_POST['quantity'];
        
        // Handle image upload
        if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['size'] > 0) {
            $target_dir = "uploads/equipments/";
            $file_extension = strtolower(pathinfo($_FILES["equipment_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Check if image file is valid
            $valid_types = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_extension, $valid_types) && move_uploaded_file($_FILES["equipment_image"]["tmp_name"], $target_file)) {
                // Delete old image if exists
                if ($equipment['image']) {
                    $old_file = $target_dir . $equipment['image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $image = $new_filename;
            }
        } else {
            $image = $equipment['image'];
        }

        // Update equipment details
        $stmt = $conn->prepare("
            UPDATE gym_equipment 
            SET equipment_name = :equipment_name, 
                quantity = :quantity, 
                image = :image 
            WHERE equipment_id = :equipment_id
        ");

        $result = $stmt->execute([
            ':equipment_name' => $equipment_name,
            ':quantity' => $quantity,
            ':image' => $image,
            ':equipment_id' => $equipmentId
        ]);

        if ($result) {
            header("Location: manage_equipment.php");
            exit;
        }
    }
}

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">Edit Equipment</h1>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Equipment Name</label>
                <input type="text" 
                       name="equipment_name" 
                       value="<?php echo htmlspecialchars($equipment['equipment_name']); ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" 
                       name="quantity" 
                       value="<?php echo htmlspecialchars($equipment['quantity']); ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Current Image</label>
                <?php if ($equipment['image']): ?>
                    <img src="uploads/equipments/<?php echo htmlspecialchars($equipment['image']); ?>" 
                         alt="Equipment" 
                         class="mt-2 h-32 w-32 object-cover rounded-lg">
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Update Image</label>
                <input type="file" 
                       name="equipment_image" 
                       accept=".jpg,.jpeg,.png,.webp"
                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-sm text-gray-500">Accepted formats: JPG, JPEG, PNG, WEBP</p>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="manage_equipment.php" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update Equipment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        let maxSize = 5 * 1024 * 1024; // 5MB
        if (this.files[0].size > maxSize) {
            alert('File size must be less than 5MB');
            this.value = '';
        }
    }
});
</script>
