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
</script>
<form action="edit_gym_details.php" method="POST" class="mx-auto bg-white shadow-lg rounded-lg p-8 space-y-6 max-w-4xl">

    <!-- Gym Name -->
    <div>
        <label for="gym_name" class="block text-sm font-medium text-gray-700">Gym Name</label>
        <input type="text" name="gym_name" value="<?php echo htmlspecialchars($gym['name']); ?>" required
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Address -->
    <div>
        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($gym['address']); ?>" required
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- City and State -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
            <input type="text" name="city" value="<?php echo htmlspecialchars($gym['city']); ?>" required
                class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="state" class="block text-sm font-medium text-gray-700">State</label>
            <input type="text" name="state" value="<?php echo htmlspecialchars($gym['state']); ?>" required
                class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <!-- Zip Code and Phone -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="zip_code" class="block text-sm font-medium text-gray-700">Zip Code</label>
            <input type="text" name="zip_code" value="<?php echo htmlspecialchars($gym['zip_code']); ?>" required
                class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($gym['contact_phone']); ?>" required
                class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <!-- Email -->
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($gym['contact_email']); ?>" required
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Capacity -->
    <div>
        <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity</label>
        <input type="number" name="capacity" value="<?php echo htmlspecialchars($gym['max_capacity']); ?>" required
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Description -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" rows="4"
            class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($gym['description']); ?></textarea>
    </div>
    <div>
    <label for="cover_photo" class="block text-sm font-medium text-gray-700">Cover Photo</label>
    <div class="mt-2 flex items-center space-x-4">
        <?php if ($gym['cover_photo']): ?>
            <img src="../gym/uploads/gym_images/<?php echo htmlspecialchars($gym['cover_photo']); ?>" alt="Gym Cover" class="w-32 h-32 object-cover rounded-lg">
        <?php endif; ?>
        <input type="file" name="cover_photo" accept="image/*" class="w-full p-3 border border-gray-300 rounded-md">
    </div>
</div>
    <!-- Amenities -->
    <div>
        <label for="amenities" class="block text-sm font-medium text-gray-700">Amenities</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="wifi" <?php if (in_array('wifi', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="wifi" class="ml-3 text-sm">Wifi</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="pool" <?php if (in_array('pool', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="pool" class="ml-3 text-sm">Pool</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="sauna" <?php if (in_array('sauna', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="sauna" class="ml-3 text-sm">Sauna</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="steam_room" <?php if (in_array('steam_room', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="steam_room" class="ml-3 text-sm">Steam Room</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="free_weights" <?php if (in_array('free_weights', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="free_weights" class="ml-3 text-sm">Free Weights</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="cardio" <?php if (in_array('cardio', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="cardio" class="ml-3 text-sm">Cardio Equipment</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="personal_training" <?php if (in_array('personal_training', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="personal_training" class="ml-3 text-sm">Personal Training</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="group_classes" <?php if (in_array('group_classes', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="group_classes" class="ml-3 text-sm">Group Classes</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="locker_rooms" <?php if (in_array('locker_rooms', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="locker_rooms" class="ml-3 text-sm">Locker Rooms</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="nutrition_counseling" <?php if (in_array('nutrition_counseling', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="nutrition_counseling" class="ml-3 text-sm">Nutrition Counseling</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" name="amenities[]" value="childcare" <?php if (in_array('childcare', json_decode($gym['amenities']))) echo 'checked'; ?> class="h-5 w-5 text-blue-500 focus:ring-0">
                <label for="childcare" class="ml-3 text-sm">Childcare</label>
            </div>
        </div>
    </div>
   
    <!-- Gym Images -->
    <div>
        <label for="gym_images" class="block text-sm font-medium text-gray-700">Gym Images</label>
        <div class="space-y-4">
            <?php foreach ($gym_images as $image): ?>
                <div class="flex items-center space-x-4">
                    <img src="../gym/uploads/gym_images/<?php echo $image['image_path']; ?>" alt="Gym Image" class="w-24 h-24 object-cover rounded-md">
                    <input type="file" name="gym_images[]" class="w-full p-3 border border-gray-300 rounded-md">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

   <!-- Membership Plans -->
<div class="mt-6">
    <h3 class="text-lg font-bold mb-4">Membership Plans</h3>
    <?php foreach ($gym_plans as $plan): ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg">
            <div>
                <label class="block text-sm font-medium text-gray-700">Plan Name</label>
                <input type="text" name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][plan_name]" 
                    value="<?php echo htmlspecialchars($plan['plan_name']); ?>"
                    class="w-full p-2 border rounded-md">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Tier</label>
                <select name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][tier]" 
                    class="w-full p-2 border rounded-md">
                    <option value="Tier 1" <?php echo ($plan['tier'] == 'Tier 1') ? 'selected' : ''; ?>>Tier 1</option>
                    <option value="Tier 2" <?php echo ($plan['tier'] == 'Tier 2') ? 'selected' : ''; ?>>Tier 2</option>
                    <option value="Tier 3" <?php echo ($plan['tier'] == 'Tier 3') ? 'selected' : ''; ?>>Tier 3</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Duration</label>
                <select name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][duration]" 
                    class="w-full p-2 border rounded-md">
                    <option value="Daily" <?php echo ($plan['duration'] == 'Daily') ? 'selected' : ''; ?>>Daily</option>
                    <option value="Weekly" <?php echo ($plan['duration'] == 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                    <option value="Monthly" <?php echo ($plan['duration'] == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                    <option value="Quartrly" <?php echo ($plan['duration'] == 'Quartrly') ? 'selected' : ''; ?>>Quarterly</option>
                    <option value="Half Yearly" <?php echo ($plan['duration'] == 'Half Yearly') ? 'selected' : ''; ?>>Half Yearly</option>
                    <option value="Yearly" <?php echo ($plan['duration'] == 'Yearly') ? 'selected' : ''; ?>>Yearly</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Plan Type</label>
                <input type="text" name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][plan_type]" 
                    value="<?php echo htmlspecialchars($plan['plan_type']); ?>"
                    class="w-full p-2 border rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Price</label>
                <input type="number" step="0.01" name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][price]" 
                    value="<?php echo htmlspecialchars($plan['price']); ?>"
                    class="w-full p-2 border rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Best For</label>
                <input type="text" name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][best_for]" 
                    value="<?php echo htmlspecialchars($plan['best_for']); ?>"
                    class="w-full p-2 border rounded-md">
            </div>

            <div class="col-span-3">
                <label class="block text-sm font-medium text-gray-700">Inclusions</label>
                <textarea name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][inclusions]" 
                    class="w-full p-2 border rounded-md"><?php echo htmlspecialchars($plan['inclusions']); ?></textarea>
            </div>
        </div>
    <?php endforeach; ?>
</div>

    <!-- Gallery -->
    <h3 class="text-lg font-bold mb-4">Gallery</h3>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
        <?php foreach ($gym_images as $image): ?>
            <img src="../gym/<?php echo htmlspecialchars($image['image_path']); ?>" alt="Gym Image" class="rounded-md">
        <?php endforeach; ?>
    </div>

    <!-- Equipment -->
    <div class="mt-6">
        <h3 class="text-lg font-bold mb-4">Equipment</h3>
        <?php foreach ($gym_equipment as $equipment): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                <input type="text" name="gym_equipment[<?php echo $equipment['equipment_id']; ?>][name]" value="<?php echo htmlspecialchars($equipment['equipment_name']); ?>"
                    class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="number" name="gym_equipment[<?php echo $equipment['equipment_id']; ?>][quantity]" value="<?php echo htmlspecialchars($equipment['quantity']); ?>"
                    class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-center">
        <button type="submit"
            class="px-8 py-3 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Save Changes
        </button>
    </div>
</form>
