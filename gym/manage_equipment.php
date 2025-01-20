<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$owner_id = $_SESSION['owner_id'];

// Get gym ID
$stmt = $conn->prepare("SELECT gym_id FROM gyms WHERE owner_id = :owner_id");
$stmt->bindParam(':owner_id', $owner_id);
$stmt->execute();
$gym = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gym) {
    header('Location: create_gym.php');
    exit;
}

$gym_id = $gym['gym_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_name = trim($_POST['equipment_name']);
    $quantity = (int)$_POST['quantity'];
    $image = null;
    
    if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] == 0) {
        $target_dir = "../uploads/equipments/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["equipment_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid('equipment_') . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        $valid_types = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_extension, $valid_types) && $_FILES["equipment_image"]["size"] <= 5000000) {
            if (move_uploaded_file($_FILES["equipment_image"]["tmp_name"], $target_file)) {
                $image = $new_filename;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO gym_equipment (gym_id, equipment_name, quantity, image) VALUES (:gym_id, :equipment_name, :quantity, :image)");
    $result = $stmt->execute([
        ':gym_id' => $gym_id,
        ':equipment_name' => $equipment_name,
        ':quantity' => $quantity,
        ':image' => $image
    ]);

    if ($result) {
        header("Location: manage_equipment.php?success=1");
        exit;
    }
}

// Fetch equipment
$stmt = $conn->prepare("SELECT * FROM gym_equipment WHERE gym_id = :gym_id ORDER BY equipment_name");
$stmt->execute([':gym_id' => $gym_id]);
$equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto p-6">
    <?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        Equipment added successfully!
    </div>
    <?php endif; ?>

    <div class="bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-6">Manage Equipment</h1>
        
        <form method="POST" action="" enctype="multipart/form-data" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Equipment Name</label>
                    <input type="text" name="equipment_name" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input type="number" name="quantity" required min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Equipment Image</label>
                    <input type="file" name="equipment_image" accept=".jpg,.jpeg,.png,.webp" 
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Max file size: 5MB. Accepted formats: JPG, JPEG, PNG, WEBP</p>
                </div>
            </div>
            
            <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Add Equipment
            </button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($equipments as $equipment): ?>
            <div class="border rounded-lg p-4">
                <?php if ($equipment['image']): ?>
                <img src="../gym/uploads/equipments/<?php echo htmlspecialchars($equipment['image']); ?>" 
                     alt="<?php echo htmlspecialchars($equipment['equipment_name']); ?>"
                     class="w-full h-48 object-cover rounded-lg mb-4">
                <?php endif; ?>
                
                <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($equipment['equipment_name']); ?></h3>
                <p class="text-gray-600">Quantity: <?php echo htmlspecialchars($equipment['quantity']); ?></p>
                
                <div class="mt-4 flex space-x-2">
                    <a href="edit_equipment.php?id=<?php echo $equipment['equipment_id']; ?>" 
                       class="text-blue-600 hover:text-blue-800">Edit</a>
                    <a href="delete_equipment.php?id=<?php echo $equipment['equipment_id']; ?>" 
                       class="text-red-600 hover:text-red-800"
                       onclick="return confirm('Are you sure you want to delete this equipment?')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    if (this.files[0].size > 5000000) {
        alert('File size must be less than 5MB');
        this.value = '';
    }
});
</script>
