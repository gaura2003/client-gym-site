<?php
require '../config/database.php'; // Include the GymDatabase connection file
$db = new GymDatabase();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Retrieve and validate inputs
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $country = trim($_POST['country']);
        $zipCode = trim($_POST['zip_code']);
        $profilePicture = $_FILES['profile_picture'];

        if (empty($name) || empty($email) || empty($password) || empty($address) || empty($city) || empty($state) || empty($country) || empty($zipCode)) {
            throw new Exception("All fields are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        if (!preg_match(
            "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/",
            $password
        )) {
            throw new Exception("Password must be at least 8 characters long and include an uppercase letter, a lowercase letter, a number, and a special character.");
        }

        // Hash the password securely
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Handle identity proof upload
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if ($identityProof['size'] > 2 * 1024 * 1024) { // 2MB limit
            throw new Exception("File size must not exceed 2MB.");
        }


        // Handle profile picture upload
        $profilePicturePath = null;
        if ($profilePicture['name']) {
            $profilePicturePath = $uploadDir . basename($profilePicture['name']);
            if (!move_uploaded_file($profilePicture['tmp_name'], $profilePicturePath)) {
                throw new Exception("Failed to upload profile picture.");
            }
        }

        // Insert data into the GymDatabase
        $stmt = $conn->prepare("INSERT INTO gym_owners (name, email, phone, password_hash, address, city, state, country, zip_code, profile_picture) 
        VALUES (:name, :email, :phone, :password_hash,  :address, :city, :state, :country, :zip_code, :profile_picture)");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password_hash', $passwordHash);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':state', $state);
        $stmt->bindParam(':country', $country);
        $stmt->bindParam(':zip_code', $zipCode);
        $stmt->bindParam(':profile_picture', $profilePicturePath);

        if ($stmt->execute()) {
            // Get the owner_id of the newly inserted gym owner
            $gymOwnerId = $conn->lastInsertId();

            // Store the owner_id in the session
            $_SESSION['owner_id'] = $gymOwnerId;

            // Optionally store more data in the session as needed
            $_SESSION['gym_owner'] = [
                'id' => $gymOwnerId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ];
            echo "Registration successful. Please wait for admin approval.";
            header("Location: add gym.php");
            exit;
        } else {
            throw new Exception("Failed to register. Please try again.");
        }
    } catch (Exception $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Invalid request method.";
}
?>
