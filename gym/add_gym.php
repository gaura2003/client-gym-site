<?php
session_start();
require '../config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();

// Check if the gym owner is logged in
if (!isset($_SESSION['owner_id'])) {
    header("Location: login.html");
    exit;
}

$gymOwnerId = $_SESSION['owner_id']; // Store the gym owner's ID from session

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Basic Information
        $gym_name = $_POST['gym_name'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $zip_code = $_POST['zip_code'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $capacity = $_POST['capacity'];
        $description = $_POST['description'];
        $country = 'india'; // Default country
        $amenities = $_POST['amenities'];  // Amenity list as an array
        $status = 'active'; // Default status

        // Convert amenities array to JSON string
        $amenities_json = !empty($amenities) ? json_encode($amenities) : null;

        // Insert Gym Details
        $query = "INSERT INTO gyms 
            (owner_id, name, address, city, state, zip_code, contact_phone, contact_email, max_capacity, description, country, amenities, status)  
            VALUES (:owner_id, :name, :address, :city, :state, :zip_code, :contact_phone, :contact_email, :capacity, :description, :country, :amenities, :status)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':owner_id' => $gymOwnerId,
            ':name' => $gym_name,
            ':address' => $address,
            ':city' => $city,
            ':state' => $state,
            ':zip_code' => $zip_code,
            ':contact_phone' => $phone,
            ':contact_email' => $email,
            ':capacity' => $capacity,
            ':description' => $description,
            ':country' => $country,
            ':amenities' => $amenities_json,
            ':status' => $status
        ]);

        // Get the last inserted gym ID
        $gym_id = $conn->lastInsertId();

        // Store gym details in session after adding the gym
        $_SESSION['gym_id'] = $gym_id; // Save gym ID in session

        // Insert Operating Hours (if provided)
        if (isset($_POST['operating_hours'])) {
            foreach ($_POST['operating_hours'] as $hours) {
                $day = $hours['day'];
                $morning_open = $hours['morning_open_time'];
                $morning_close = $hours['morning_close_time'];
                $evening_open = $hours['evening_open_time'];
                $evening_close = $hours['evening_close_time'];

                $query = "INSERT INTO gym_operating_hours 
                    (gym_id, day, morning_open_time, morning_close_time, evening_open_time, evening_close_time) 
                    VALUES (:gym_id, :day, :morning_open, :morning_close, :evening_open, :evening_close)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':gym_id' => $gym_id,
                    ':day' => $day,
                    ':morning_open' => $morning_open,
                    ':morning_close' => $morning_close,
                    ':evening_open' => $evening_open,
                    ':evening_close' => $evening_close
                ]);
            }
        }

        // Insert Equipment Details (if provided)
        if (isset($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equipment) {
                $name = $equipment['name'];
                $quantity = $equipment['quantity'];

                // Handle file upload for equipment images
                if (isset($_FILES['equipment']['name']['image'])) {
                    $imageTmp = $_FILES['equipment']['tmp_name']['image'];
                    $imageName = $_FILES['equipment']['name']['image'];
                    $imageSize = $_FILES['equipment']['size']['image'];
                    $imageError = $_FILES['equipment']['error']['image'];

                    // Validate file upload
                    if ($imageError === UPLOAD_ERR_OK) {
                        $imageExtension = pathinfo($imageName, PATHINFO_EXTENSION);
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                        // Check if the file type is allowed
                        if (in_array(strtolower($imageExtension), $allowedExtensions)) {
                            $imagePath = 'uploads/equipments/' . uniqid() . '.' . $imageExtension;

                            // Move the uploaded file to the desired directory
                            if (move_uploaded_file($imageTmp, $imagePath)) {
                                // Insert equipment details into the GymDatabase, including the image path
                                $query = "INSERT INTO gym_equipment (gym_id, name, quantity, image) 
                                          VALUES (:gym_id, :name, :quantity, :image)";
                                $stmt = $conn->prepare($query);
                                $stmt->execute([
                                    ':gym_id' => $gym_id,
                                    ':name' => $name,
                                    ':quantity' => $quantity,
                                    ':image' => $imagePath
                                ]);
                            } else {
                                echo "Error moving uploaded file.";
                            }
                        } else {
                            echo "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
                        }
                    } else {
                        echo "Error uploading file.";
                    }
                }
            }
        }

        // Insert Gym Images
        if (isset($_FILES['gym_images'])) {
            $gym_images = $_FILES['gym_images'];
            foreach ($gym_images['tmp_name'] as $key => $image) {
                // Handle file upload
                $image_path = 'uploads/gym_images' . basename($gym_images['name'][$key]);
                move_uploaded_file($image, $image_path);

                // Insert image into the gym images table
                $query = "INSERT INTO gym_images (gym_id, image_path, is_cover) 
                    VALUES (:gym_id, :image_path, :is_cover)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':gym_id' => $gym_id,
                    ':image_path' => $image_path,
                    ':is_cover' => isset($_POST['is_cover']) ? 1 : 0
                ]);
            }
        }

        // Insert Membership Plans
        if (isset($_POST['membership_plans'])) {
            foreach ($_POST['membership_plans'] as $plan) {
                $tier = $plan['tier'];
                $duration = $plan['duration'];
                $price = $plan['price'];
                $inclusions = $plan['inclusions'];

                $query = "INSERT INTO gym_membership_plans 
                    (gym_id, tier, duration, price, inclusions) 
                    VALUES (:gym_id, :tier, :duration, :price, :inclusions)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    ':gym_id' => $gym_id,
                    ':tier' => $tier,
                    ':duration' => $duration,
                    ':price' => $price,
                    ':inclusions' => $inclusions
                ]);
            }
        }

        // Redirect to gym details page after success
        echo "Gym details added successfully!";
        header("Location: dashboard.php");
        exit;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
