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

if ($gym) {
    $gym_id = $gym['gym_id'];

    // Fetch related data
    $query_images = "SELECT * FROM gym_images WHERE gym_id = :gym_id";
    $stmt_images = $conn->prepare($query_images);
    $stmt_images->execute([':gym_id' => $gym_id]);
    $gym_images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

    $query_equipment = "SELECT * FROM gym_equipment WHERE gym_id = :gym_id";
    $stmt_equipment = $conn->prepare($query_equipment);
    $stmt_equipment->execute([':gym_id' => $gym_id]);
    $gym_equipment = $stmt_equipment->fetchAll(PDO::FETCH_ASSOC);

    $query_plans = "SELECT * FROM gym_membership_plans WHERE gym_id = :gym_id";
    $stmt_plans = $conn->prepare($query_plans);
    $stmt_plans->execute([':gym_id' => $gym_id]);
    $gym_plans = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle cover photo update
    if(isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/gym_covers/';
        $cover_photo_name = uniqid() . '_' . basename($_FILES['cover_photo']['name']);
        $cover_photo_path = $upload_dir . $cover_photo_name;
        
        if(move_uploaded_file($_FILES['cover_photo']['tmp_name'], $cover_photo_path)) {
            // Delete old cover photo if exists
            if($gym['cover_photo'] && file_exists('../gym/' . $gym['cover_photo'])) {
                unlink('../gym/' . $gym['cover_photo']);
            }
            
            // Update cover_photo_url in database
            $update_cover = "UPDATE gyms SET cover_photo = :cover_photo_url WHERE gym_id = :gym_id";
            $stmt_cover = $conn->prepare($update_cover);
            $stmt_cover->execute([
                ':cover_photo_url' => $cover_photo_path,
                ':gym_id' => $gym_id
            ]);
        }
    }
}
// Handle form submission to update GymDatabase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gym_name = $_POST['gym_name'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip_code = $_POST['zip_code'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $capacity = $_POST['capacity'];
    $description = $_POST['description'];
    $amenities = json_encode($_POST['amenities']);

    $update_query = "UPDATE gyms SET 
        name = :name, address = :address, city = :city, state = :state, 
        zip_code = :zip_code, contact_phone = :phone, contact_email = :email, 
        max_capacity = :capacity, description = :description, amenities = :amenities 
        WHERE gym_id = :gym_id";

    $stmt_update = $conn->prepare($update_query);
    $stmt_update->execute([
        ':name' => $gym_name,
        ':address' => $address,
        ':city' => $city,
        ':state' => $state,
        ':zip_code' => $zip_code,
        ':phone' => $phone,
        ':email' => $email,
        ':capacity' => $capacity,
        ':description' => $description,
        ':amenities' => $amenities,
        ':gym_id' => $gym_id
    ]);

    // Redirect to the same page to reflect updated changes
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// Update Membership Plans
if (isset($_POST['gym_membership_plans'])) {
    foreach ($_POST['gym_membership_plans'] as $plan_id => $plan) {
        $stmt = $conn->prepare("
            UPDATE gym_membership_plans 
            SET plan_name = :plan_name,
                tier = :tier,
                duration = :duration,
                plan_type = :plan_type,
                price = :price,
                inclusions = :inclusions,
                best_for = :best_for
            WHERE plan_id = :plan_id AND gym_id = :gym_id
        ");

        $stmt->execute([
            ':plan_name' => $plan['plan_name'],
            ':tier' => $plan['tier'],
            ':duration' => $plan['duration'],
            ':plan_type' => $plan['plan_type'],
            ':price' => $plan['price'],
            ':inclusions' => $plan['inclusions'],
            ':best_for' => $plan['best_for'],
            ':plan_id' => $plan_id,
            ':gym_id' => $gym_id
        ]);
    }
}


// Update Equipment
if (isset($_POST['gym_equipment'])) {
    foreach ($_POST['gym_equipment'] as $equipment_id => $equipment) {
        $stmt = $conn->prepare("
            UPDATE gym_equipment 
            SET equipment_name = :name,
                quantity = :quantity
            WHERE equipment_id = :equipment_id AND gym_id = :gym_id
        ");

        $stmt->execute([
            ':name' => $equipment['name'],
            ':quantity' => $equipment['quantity'],
            ':equipment_id' => $equipment_id,
            ':gym_id' => $gym_id
        ]);
    }
}
include '../includes/navbar.php';

?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea, select');
        const editButton = document.createElement('button');
        editButton.textContent = 'Edit';
        editButton.type = 'button';
        editButton.classList.add('bg-blue-500', 'text-white', 'p-2', 'rounded');

        form.prepend(editButton);

        inputs.forEach(input => input.disabled = true);

        editButton.addEventListener('click', () => {
            inputs.forEach(input => input.disabled = false);
            editButton.remove();
        });

        // Show all amenities when editing
        const additionalAmenities = document.querySelectorAll('.additional-amenity');
        additionalAmenities.forEach(amenity => amenity.style.display = 'none');

        editButton.addEventListener('click', () => {
            additionalAmenities.forEach(amenity => amenity.style.display = 'block');
        });
    });
</script><div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                        <i class="fas fa-dumbbell text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($gym['name']); ?></h1>
                        <p class="text-gray-300"><?php echo htmlspecialchars($gym['address']); ?></p>
                    </div>
                </div>
                <span class="px-4 py-2 rounded-full bg-green-500 text-white font-semibold">
                    Active
                </span>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form action="edit_gym_details.php" method="POST" enctype="multipart/form-data" class="space-y-8">
        <!-- Basic Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                Basic Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gym Name</label>
                    <input type="text" name="gym_name" value="<?php echo htmlspecialchars($gym['name']); ?>" 
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($gym['contact_email']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($gym['contact_phone']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacity</label>
                    <input type="number" name="capacity" value="<?php echo htmlspecialchars($gym['max_capacity']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
            </div>
        </div>

        <!-- Location Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-map-marker-alt text-yellow-500 mr-2"></i>
                Location Details
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($gym['address']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($gym['city']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($gym['state']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                    <input type="text" name="zip_code" value="<?php echo htmlspecialchars($gym['zip_code']); ?>"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
            </div>
        </div>

        <!-- Amenities Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-list-ul text-yellow-500 mr-2"></i>
                Amenities
            </h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php
                $amenities = json_decode($gym['amenities'], true);
                $amenity_list = [
                    'wifi' => 'Wi-Fi',
                    'parking' => 'Parking',
                    'locker_rooms' => 'Locker Rooms',
                    'showers' => 'Showers',
                    'sauna' => 'Sauna',
                    'pool' => 'Pool',
                    'cardio_equipment' => 'Cardio Equipment',
                    'strength_equipment' => 'Strength Equipment',
                    'personal_training' => 'Personal Training',
                    'group_classes' => 'Group Classes',
                    'nutrition_counseling' => 'Nutrition Counseling',
                    'childcare' => 'Childcare'
                ];
                
                foreach ($amenity_list as $key => $label):
                ?>
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" name="amenities[]" value="<?php echo $key; ?>"
                               <?php echo in_array($key, $amenities) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-200">
                        <label class="text-sm text-gray-700"><?php echo $label; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Images Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-images text-yellow-500 mr-2"></i>
                Gym Images
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cover Photo</label>
                    <input type="file" name="cover_photo" accept="image/*"
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Images</label>
                    <input type="file" name="gym_images[]" accept="image/*" multiple
                           class="w-full rounded-lg border-gray-300 focus:border-yellow-500 focus:ring focus:ring-yellow-200">
                </div>
            </div>

            <!-- Current Images Preview -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($gym_images as $image): ?>
                    <div class="relative group">
                        <img src="../gym/uploads/gym_images/<?php echo htmlspecialchars($image['image_path']); ?>" 
                             alt="Gym Image" class="rounded-lg w-full h-40 object-cover">
                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-lg flex items-center justify-center">
                            <button type="button" class="text-white hover:text-red-500">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-8 py-3 rounded-lg transition-colors duration-200">
                <i class="fas fa-save mr-2"></i>
                Save Changes
            </button>
        </div>
    </form>
</div>
