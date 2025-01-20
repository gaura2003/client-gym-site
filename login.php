<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();
$auth = new Auth($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if ($auth->login($email, $password)) {
            $_SESSION['success'] = "Login successful!";
            header("Location: dashboard.php"); // or any other page after login
            exit();
        } else {
            throw new Exception("Invalid email or password.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
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
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Left Side - Image and Motivation -->
        <div class="hidden lg:flex lg:w-1/2 bg-cover bg-center" 
             style="background-image: url('assets/image/gymbg.jpg')">
            <div class="w-full flex items-center justify-center bg-black bg-opacity-50">
                <div class="px-12 text-white">
                    <h1 class="text-5xl font-bold mb-8">Transform Your Life</h1>
                    <p class="text-xl mb-8">Join GymPro and start your fitness journey today.</p>
                    <ul class="space-y-4">
                        <li class="flex items-center"><i class="fas fa-check-circle mr-2"></i> Access to premium equipment</li>
                        <li class="flex items-center"><i class="fas fa-check-circle mr-2"></i> Expert trainers</li>
                        <li class="flex items-center"><i class="fas fa-check-circle mr-2"></i> Flexible membership plans</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-2xl">
                <div>
                    <h2 class="text-3xl font-bold text-center text-gray-900">Login to Your Account</h2>
                    <p class="mt-2 text-center text-gray-600">Welcome back, member</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6" action="login.php" method="POST" id="loginForm">
                    <div class="rounded-md shadow-sm space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" required
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="your.email@example.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" required
                                   class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                                   placeholder="Your password">
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-sign-in-alt"></i>
                            </span>
                            Login
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                            Register here
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.querySelector('input[name="email"]').value;
        const password = document.querySelector('input[name="password"]').value;

        if (!email || !password) {
            e.preventDefault();
            alert('Please fill out both fields');
            return;
        }
    });
    </script>
</body>
</html>
