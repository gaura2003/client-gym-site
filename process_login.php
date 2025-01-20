<?php
session_start();
require_once '../../config/GymDatabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new GymDatabase();
    $conn = $db->getConnection();

    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Get user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set user sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // If user is gym partner, fetch and set gym sessions
            if ($user['role'] === 'gym_partner') {
                $stmt = $conn->prepare("SELECT * FROM gyms WHERE owner_id = ?");
                $stmt->execute([$user['id']]);
                $gym = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($gym) {
                    $_SESSION['gym_id'] = $gym['id'];
                    $_SESSION['gym_name'] = $gym['name'];
                    $_SESSION['gym_email'] = $gym['contact_email'];
                    $_SESSION['gym_phone'] = $gym['contact_phone'];
                    $_SESSION['gym_address'] = $gym['address'];
                    $_SESSION['gym_city'] = $gym['city'];
                    $_SESSION['gym_state'] = $gym['state'];
                    $_SESSION['gym_country'] = $gym['country'];
                    $_SESSION['gym_postal_code'] = $gym['postal_code'];
                    $_SESSION['gym_max_capacity'] = $gym['max_capacity'];
                    $_SESSION['gym_current_occupancy'] = $gym['current_occupancy'];
                    $_SESSION['gym_amenities'] = $gym['amenities'];
                    $_SESSION['gym_status'] = $gym['status'];
                    $_SESSION['gym_qr_secret'] = $gym['qr_secret'];
                }
            }

            $redirectPath = match($_SESSION['role']) {
                'admin' => '/gym/views/admin/dashboard.php',
                'gym_partner' => '/gym/gym/index.php',
                'member' => '/gym/dashboard.php',
                default => '/gym/home.php',
            };

            header("Location: $redirectPath");
            exit();
        } else {
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: /gym/views/auth/login.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Login failed. Please try again.';
        header('Location: /gym/views/auth/login.php');
        exit();
    }
}
