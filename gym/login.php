<?php
session_start();
require '../config/database.php'; // Include the GymDatabase connection file
$db = new GymDatabase();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Retrieve and validate inputs
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            throw new Exception("Both email and password are required.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if the gym owner exists in the GymDatabase
        $stmt = $conn->prepare("SELECT id, name, email, password_hash, is_verified, is_approved FROM gym_owners WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($owner) {
            echo "Stored hash: " . $owner['password_hash'];
            echo "<br>";
            echo "Entered password: " . $password;
        }
        if (!$owner) {
            throw new Exception("No account found with that email.");
        }

        // Verify password
        if (!password_verify($password, $owner['password_hash'])) {
            throw new Exception("Incorrect password.");
        }

        // Check if the account is verified and approved
        if ($owner['is_verified'] == 0) {
            throw new Exception("Your account is not verified yet.");
        }

        if ($owner['is_approved'] == 0) {
            throw new Exception("Your account is not approved yet.");
        }

        // Store the gym owner's details in the session
        $_SESSION['owner_id'] = $owner['id'];
        $_SESSION['role'] = $owner['role'];
        $_SESSION['username'] = $owner['name'];
        $_SESSION['owner_email'] = $owner['email'];

        // Redirect to the gym owner's dashboard or home page
        header("Location: dashboard.php");
        exit;

    } catch (Exception $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Invalid request method.";
}
?>
