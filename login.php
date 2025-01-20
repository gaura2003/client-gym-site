<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();
$auth = new Auth($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($email, $password)) {
        $redirectPath = match($_SESSION['role']) {
            'admin' => '/gym/admin/dashboard.php',
            'gym_partner' => '/gym/gym/index.php',
            'member' => '/gym/dashboard.php',
            default => '/gym/index.php',
        };
        header("Location: " . $redirectPath);
        exit();
    } else {
        $error = "Invalid credentials or account locked.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold text-center">Login</h2>
        <?php if (isset($error)): ?>
            <div class="text-red-500 text-center mt-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST" class="mt-6">
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Email</label>
                <input type="email" name="email" id="email" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" name="password" id="password" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Login</button>
            <div class="mt-4 text-center">
                <a href="register.php" class="text-blue-500">Don't have an account? Register</a>
                <div>or</div>
                <a href="./gym/register.html" class="text-blue-500">Register Your GYM</a>
            </div>
        </form>
    </div>
</body>
</html>
