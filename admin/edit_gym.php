<?php
session_start();
require '../config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();

// Check if the admin is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['gym_id'])) {
    $gym_id = $_GET['gym_id'];

    // Fetch the gym details
    $query = "SELECT * FROM gyms WHERE gym_id = :gym_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':gym_id' => $gym_id]);
    $gym = $stmt->fetch();

    if (!$gym) {
        echo "Gym not found!";
        exit;
    }

    // Fetch related data (images, operating hours, equipment, membership plans)
    $query_images = "SELECT * FROM gym_images WHERE gym_id = :gym_id";
    $stmt_images = $conn->prepare($query_images);
    $stmt_images->execute([':gym_id' => $gym_id]);
    $gym_images = $stmt_images->fetchAll();

    $query_hours = "SELECT * FROM gym_operating_hours WHERE gym_id = :gym_id";
    $stmt_hours = $conn->prepare($query_hours);
    $stmt_hours->execute([':gym_id' => $gym_id]);
    $gym_hours = $stmt_hours->fetchAll();

    $query_equipment = "SELECT * FROM gym_equipment WHERE gym_id = :gym_id";
    $stmt_equipment = $conn->prepare($query_equipment);
    $stmt_equipment->execute([':gym_id' => $gym_id]);
    $gym_equipment = $stmt_equipment->fetchAll();

    $query_plans = "SELECT * FROM gym_membership_plans WHERE gym_id = :gym_id";
    $stmt_plans = $conn->prepare($query_plans);
    $stmt_plans->execute([':gym_id' => $gym_id]);
    $gym_plans = $stmt_plans->fetchAll();
}

// Handle Gym Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $gym_name = $_POST['gym_name'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $zip_code = $_POST['zip_code'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $capacity = $_POST['capacity'];
        $description = $_POST['description'];
        $amenities = $_POST['amenities'];
        $amenities_json = !empty($amenities) ? json_encode($amenities) : null;

        // Update Gym Details
        $query = "UPDATE gyms SET 
            name = :name, address = :address, city = :city, state = :state, zip_code = :zip_code, 
            contact_phone = :contact_phone, contact_email = :contact_email, max_capacity = :capacity, 
            description = :description, amenities = :amenities 
            WHERE gym_id = :gym_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':gym_id' => $gym_id,
            ':name' => $gym_name,
            ':address' => $address,
            ':city' => $city,
            ':state' => $state,
            ':zip_code' => $zip_code,
            ':contact_phone' => $phone,
            ':contact_email' => $email,
            ':capacity' => $capacity,
            ':description' => $description,
            ':amenities' => $amenities_json
        ]);


        // Handle Image Uploads
        if (isset($_FILES['gym_images'])) {
            $image_urls = [];
            foreach ($_FILES['gym_images']['tmp_name'] as $key => $tmp_name) {
                $target_dir = "uploads/gym_images/";
                $target_file = $target_dir . basename($_FILES['gym_images']['name'][$key]);
                $image_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Check if the file is an image
                if (getimagesize($tmp_name)) {
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        // Update the GymDatabase with the new image URL
                        $image_urls[] = $target_file;
                    }
                } else {
                    echo "File is not an image.";
                    exit;
                }
            }

            // Update gym images in the GymDatabase
            foreach ($image_urls as $image_url) {
                $query_image_update = "UPDATE gym_images SET image_url = :image_url WHERE gym_id = :gym_id";
                $stmt_image_update = $conn->prepare($query_image_update);
                $stmt_image_update->execute([':gym_id' => $gym_id, ':image_url' => $image_url]);
            }
        }

        // Update gym equipment
        if (isset($_POST['gym_equipment'])) {
            foreach ($_POST['gym_equipment'] as $equipment) {
                $query_equipment_update = "UPDATE gym_equipment SET equipment_name = :equipment_name WHERE gym_id = :gym_id";
                $stmt_equipment_update = $conn->prepare($query_equipment_update);
                $stmt_equipment_update->execute([':gym_id' => $gym_id, ':equipment_name' => $equipment]);
            }
        }

        // Update gym membership plans
        if (isset($_POST['gym_membership_plans'])) {
            foreach ($_POST['gym_membership_plans'] as $plan) {
                $query_plan_update = "UPDATE gym_membership_plans SET plan_name = :plan_name, price = :price, duration = :duration WHERE gym_id = :gym_id";
                $stmt_plan_update = $conn->prepare($query_plan_update);
                $stmt_plan_update->execute([
                    ':gym_id' => $gym_id,
                    ':plan_name' => $plan['plan_name'],
                    ':price' => $plan['price'],
                    ':duration' => $plan['duration']
                ]);
            }
        }

        echo "Gym details updated successfully!";
        header("Location: manage_gym.php");
        exit;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
include '../includes/navbar.php';

?>

<form action="" method="POST" class="max-w-lg mx-auto bg-white shadow-md rounded-lg p-6 space-y-4">

    <div>
        <label for="gym_name" class="block text-sm font-medium text-gray-700">Gym Name</label>
        <input type="text" name="gym_name" value="<?php echo htmlspecialchars($gym['name']); ?>" required
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($gym['address']); ?>" required
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="city" class="block text-sm font-medium text-gray-700">City</label>
            <input type="text" name="city" value="<?php echo htmlspecialchars($gym['city']); ?>" required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="state" class="block text-sm font-medium text-gray-700">State</label>
            <input type="text" name="state" value="<?php echo htmlspecialchars($gym['state']); ?>" required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="zip_code" class="block text-sm font-medium text-gray-700">Zip Code</label>
            <input type="text" name="zip_code" value="<?php echo htmlspecialchars($gym['zip_code']); ?>" required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($gym['contact_phone']); ?>" required
                class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($gym['contact_email']); ?>" required
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity</label>
        <input type="number" name="capacity" value="<?php echo htmlspecialchars($gym['max_capacity']); ?>" required
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" rows="4"
            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($gym['description']); ?></textarea>
    </div>

    <div>
        <label for="amenities" class="block text-sm font-medium text-gray-700">Amenities</label>
        <div class="flex space-x-4">
            <div class="flex flex-wrap space-x-6">
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="wifi" <?php if (in_array('wifi', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="wifi" class="ml-2 text-sm">Wifi</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="pool" <?php if (in_array('pool', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="pool" class="ml-2 text-sm">Pool</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="sauna" <?php if (in_array('sauna', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="sauna" class="ml-2 text-sm">Sauna</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="steam_room" <?php if (in_array('steam_room', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="steam_room" class="ml-2 text-sm">Steam Room</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="free_weights" <?php if (in_array('free_weights', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="free_weights" class="ml-2 text-sm">Free Weights</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="cardio" <?php if (in_array('cardio', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="cardio" class="ml-2 text-sm">Cardio Equipment</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="personal_training" <?php if (in_array('personal_training', json_decode($gym['amenities'])))
                        echo 'checked'; ?>
                        class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="personal_training" class="ml-2 text-sm">Personal Training</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="group_classes" <?php if (in_array('group_classes', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="group_classes" class="ml-2 text-sm">Group Classes</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="locker_rooms" <?php if (in_array('locker_rooms', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="locker_rooms" class="ml-2 text-sm">Locker Rooms</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="nutrition_counseling" <?php if (in_array('nutrition_counseling', json_decode($gym['amenities'])))
                        echo 'checked'; ?>
                        class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="nutrition_counseling" class="ml-2 text-sm">Nutrition Counseling</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="amenities[]" value="childcare" <?php if (in_array('childcare', json_decode($gym['amenities'])))
                        echo 'checked'; ?> class="h-4 w-4 text-blue-500 focus:ring-0">
                    <label for="childcare" class="ml-2 text-sm">Childcare</label>
                </div>
            </div>

        </div>
    </div>

    <!-- Gym Images -->
    <div>
        <label for="gym_images" class="block text-sm font-medium text-gray-700">Gym Images</label>
        <div class="space-y-2">
            <?php foreach ($gym_images as $image): ?>
                <div class="flex items-center space-x-2">
                    <img src="../../gym/<?php echo $image['image_path']; ?>" alt="Gym Image" class="w-24 h-24 object-cover">
                    <input type="file" name="gym_images[]" class="w-full p-2 border border-gray-300 rounded-md">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Membership Plans -->
    <div>
        <label for="gym_membership_plans" class="block text-sm font-medium text-gray-700">Membership Plans</label>
        <div>
            <?php foreach ($gym_plans as $plan): ?>
                <div class="grid grid-cols-3 gap-4">
                    <input type="text" name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][duration]"
                        value="<?php echo htmlspecialchars($plan['duration']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                    <input type="text" name="gym_membership_plans[<?php echo $plan['plan_id']; ?>][price]"
                        value="<?php echo htmlspecialchars($plan['price']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <h3 class="text-lg font-bold mt-6 mb-3">Gallery</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($gym_images as $image): ?>
            <img src="../gym/uploads/gym_images/<?php echo htmlspecialchars($image['image_path']); ?>" alt="Gym Image"
                class="rounded-md">
        <?php endforeach; ?>
    </div>
    <h3 class="text-lg font-bold mt-6 mb-3">Equipment</h3>
    <div class="space-y-2">
        <?php foreach ($gym_equipment as $item): ?>
            <div class="flex items-center gap-4">
                <img src="../gym/uploads/equipments/<?php echo htmlspecialchars($item['image']); ?>" alt="Equipment"
                    class="h-16 w-16 rounded-md">
                <div>
                    <input type="text" name="gym_equipment[]"
                        value="<?php echo htmlspecialchars($item['equipment_name']); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md">
                    <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div>
        <button type="submit"
            class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:bg-blue-600">Update
            Gym</button>
          <a href="manage_gym.php"><div class="w-full bg-red-500 text-center my-3 text-white py-2 px-4 rounded-md hover:bg-red-600 focus:outline-none focus:bg-red-600">Cancel</div></a>
    </div>
</form>