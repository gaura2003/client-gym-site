<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic Information Validation
    $gym_name = trim($_POST['gym_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $capacity = (int)$_POST['capacity'];
    $description = trim($_POST['description']);

    $errors = [];

    // Validate required fields
    if (empty($gym_name)) $errors[] = "Gym name is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($zip_code)) $errors[] = "Zip code is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($email)) $errors[] = "Email is required";
    if ($capacity <= 0) $errors[] = "Valid capacity is required";

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Validate phone number format (basic)
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Invalid phone number format";
    }

    // Validate zip code format
    if (!preg_match("/^[0-9]{5}(-[0-9]{4})?$/", $zip_code)) {
        $errors[] = "Invalid zip code format";
    }

    // Operating Hours Validation
    if (isset($_POST['operating_hours'])) {
        foreach ($_POST['operating_hours'] as $hours) {
            if (empty($hours['morning_open_time']) || empty($hours['morning_close_time'])) {
                $errors[] = "Morning operating hours are required";
            }
        }
    }

    // Equipment Validation
    if (isset($_POST['equipment'])) {
        foreach ($_POST['equipment'] as $equipment) {
            if (empty($equipment['name']) || empty($equipment['quantity'])) {
                $errors[] = "Equipment name and quantity are required";
            }
            if ($equipment['quantity'] <= 0) {
                $errors[] = "Equipment quantity must be greater than 0";
            }
        }
    }

    // Membership Plans Validation
    if (isset($_POST['membership_plans'])) {
        foreach ($_POST['membership_plans'] as $plan) {
            if (empty($plan['tier']) || empty($plan['duration']) || empty($plan['price'])) {
                $errors[] = "All membership plan fields are required";
            }
            if ($plan['price'] <= 0) {
                $errors[] = "Membership price must be greater than 0";
            }
        }
    }

    // Image Validation
    if (isset($_FILES['gym_images'])) {
        foreach ($_FILES['gym_images']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                $file_type = $_FILES['gym_images']['type'][$key];
                
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Invalid image format. Only JPG, JPEG, and PNG allowed";
                }

                $file_size = $_FILES['gym_images']['size'][$key];
                if ($file_size > 5000000) { // 5MB limit
                    $errors[] = "Image size should be less than 5MB";
                }
            }
        }
    }

    if (empty($errors)) {
        $_SESSION['verified_gym_data'] = $_POST;
        header('Location: add_gym.php');
        exit;
    } else {
        $_SESSION['gym_errors'] = $errors;
        header('Location: add_gym.html');
        exit;
    }
}
?>
