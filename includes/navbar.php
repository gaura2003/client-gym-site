<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['owner_id']);
$user_id = $_SESSION['user_id'] ?? ($_SESSION['owner_id'] ?? null);
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

class NavbarDatabase
{
    private $host = "localhost";
    private $db_name = "gym-db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }
}

$db = new NavbarDatabase();
$conn = $db->getConnection();

// Get unread notifications count based on role
$unreadNotificationsCount = 0;
if ($isLoggedIn) {
    if ($role === 'admin') {
        // Admin sees all system notifications
        $query = "SELECT COUNT(*) FROM notifications 
                 WHERE user_id IS NULL 
                 AND status = 'unread'";
        $stmt = $conn->prepare($query);
        $stmt->execute();

    } elseif ($role === 'member') {
        // Users see their personal notifications
        $query = "SELECT COUNT(*) FROM notifications 
                 WHERE user_id = ? 
                 AND status = 'unread'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id]);

    } elseif (isset($_SESSION['owner_id'])) {
        // Gym owners see notifications for their gym
        $query = "SELECT COUNT(*) FROM notifications 
                 WHERE gym_id = (SELECT gym_id FROM gyms WHERE owner_id = ?) 
                 AND status = 'unread'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['owner_id']]);
    }

    $unreadNotificationsCount = $stmt->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .loader-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .gym-loader {
            position: relative;
            width: 200px;
            height: 200px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fbbf24;
        }

        .weightlifter i {
            font-size: 60px;
            animation: lift 1.5s infinite;
            display: none;
        }

        .dumbbell i {
            font-size: 40px;
            margin-left: 20px;
            animation: rotate 2s infinite linear;
        }

        @keyframes lift {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }

            100% {
                transform: translateY(0);
            }
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        :root {
    --bg-primary: #111827;
    --bg-secondary: #1F2937;
    --text-primary: #FFFFFF;
    --text-secondary: #E5E7EB;
    --bg-tertiary:#374151;
    --accent: #FBBF24;
}

:root.light-mode {
    --bg-primary: #F3F4F6;
    --bg-secondary: #FFFFFF;
    --text-primary: #111827;
    --bg-tertiary: #F9FAFB;
    --text-secondary: #374151;
    --accent: #D97706;
}

.bg-gray-700 {
    background-color: var(--bg-tertiary);
}
/* Core theme classes */
.from-gray-900 {
    --tw-gradient-from: var(--bg-primary);
}

.to-black {
    --tw-gradient-to: var(--bg-secondary);
}

.bg-gray-800 {
    background-color: var(--bg-secondary);
}

.text-white {
    color: var(--text-primary);
}

.text-gray-500 {
    color: var(--text-secondary);
}

.text-yellow-400 {
    color: var(--accent);
}

/* Additional theme utilities */
.bg-opacity-50 {
    --tw-bg-opacity: 0.5;
}

.backdrop-blur-lg {
    --tw-backdrop-blur: blur(16px);
}

.transition-colors {
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}
.hover\:bg-gray-700:hover {
    --tw-bg-opacity: 0.7;
}

    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loader = document.querySelector('.loader-container');

            window.addEventListener('load', () => {
                setTimeout(() => {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 100);
                }, 100);
            });
        });

        // toggle theme
        function initTheme() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.toggle('light-mode', theme === 'light');
            return theme;
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.classList.contains('light-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', currentTheme);
            document.documentElement.classList.toggle('light-mode');
        }
        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        });
    </script>
    

</head>

<body class="bg-gray-100 transition-colors duration-200">
    <!-- Loader -->
    <div class="loader-container">
        <div class="gym-loader">
            <div class="weightlifter">
                <i class="fas fa-running"></i>
            </div>
            <div class="dumbbell">
                <i class="fas fa-dumbbell"></i>
            </div>
        </div>
    </div>
    <!-- Navbar -->
    <nav class="bg-gray-800 bg-opacity-50 backdrop-blur-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/gym" class="text-white font-bold text-2xl">
                        <span class="text-yellow-400">GYM</span> PRO
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <?php if ($role === 'admin'): ?>
                            <a href="/gym/admin/dashboard.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Dashboard</a>
                            <a href="/gym/admin/members.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Members</a>
                            <a href="/gym/admin/users.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Users</a>
                            <a href="/gym/admin/gym-owners.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Owners</a>
                            <a href="/gym/admin/manage_gym.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Gyms</a>
                            <a href="/gym/admin/reviews.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Reviews</a>
                            <a href="/gym/admin/membership_plans.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Plans</a>
                            <a href="/gym/admin/see-gym-earn.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Gym Earnings</a>
                            <a href="/gym/admin/cut-off-chart.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Cut Offs</a>
                        <?php elseif ($role === 'member' && isset($_SESSION['user_id'])): ?>
                            <a href="/gym/dashboard.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Dashboard</a>
                            <a href="/gym/schedule-history.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Schedule History</a>
                            <a href="/gym/user_schedule.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">My Schedules</a>
                            <a href="/gym/view_membership.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Membership</a>
                            <a href="/gym/payment_history.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Payment History</a>
                            <a href="/gym/profile.php" class="text-white  hover:text-yellow-400 px-1 py-2 rounded-md text-lg font-medium">Profile</a>
                        <?php elseif (isset($_SESSION['owner_id'])): ?>
                            <a href="../gym/dashboard.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Dashboard</a>
                            <a href="../gym/edit_gym_details.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">My Gym</a>
                            <a href="../gym/manage_membership_plans.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Plans</a>
                            <a href="../gym/manage_equipment.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Equipment</a>
                            <a href="../gym/booking.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Schedules</a>
                            <a href="../gym/member_list.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Members</a>
                            <a href="../gym/earning-history.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Earnings</a>
                            <a href="../gym/visit_attendance.php" class="text-white  hover:text-yellow-400 py-2 rounded-md text-lg font-medium">Visits</a>
                        <?php else: ?>
                            <a href="/gym/" class="text-white  hover:text-yellow-400 px-3 py-2 rounded-md text-lg font-medium">Home</a>
                            <a href="/gym/view_membership.php" class="text-white  hover:text-yellow-400 px-3 py-2 rounded-md text-lg font-medium">Membership Plans</a>
                            <a href="/gym/contact.php" class="text-white  hover:text-yellow-400 px-3 py-2 rounded-md text-lg font-medium">Contact</a>
                            <a href="/gym/about-us.php" class="text-white  hover:text-yellow-400 px-3 py-2 rounded-md text-lg font-medium">About Us</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Section -->
                <div class="hidden md:flex items-center space-x-6">
                    <?php if ($isLoggedIn): ?>
                        <div class="relative">
                            <a href="<?php echo isset($_SESSION['owner_id']) ? '/gym/gym/notifications.php' : '/gym/notifications.php'; ?>" class="text-white  hover:text-yellow-500 transition-all duration-200">
                                <i class="fas fa-bell text-xl"></i>
                                <?php if ($unreadNotificationsCount > 0): ?>
                                    <span class="absolute top-0 right-0 bg-red-500 text-white text-xs px-1 rounded-full">
                                        <?php echo $unreadNotificationsCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <div class="flex items-center space-x-3">
                            <span class="text-white  font-medium">
                                <i class="fas fa-user-circle text-yellow-500 mr-2"></i>
                                <?php echo $username; ?>
                            </span>
                            <a href="/gym/includes/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors duration-200">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>

                        <button id="themeToggle" class="p-2 rounded-full bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                            <svg class="hidden dark:block w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg class="block dark:hidden w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                    <?php else: ?>
                        <a href="/gym/register.php" class="bg-yellow-500 hover:bg-yellow-600 text-black px-6 py-2 rounded-lg font-medium transition-colors duration-200">Sign Up</a>
                        <a href="/gym/login.php" class="border-2 border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black px-6 py-2 rounded-lg font-medium transition-all duration-200">Login</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="-mr-2 flex md:hidden">
                    <button id="mobileMenuButton" class="text-white hover:text-white hover:bg-gray-700 p-2 rounded-md">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="md:hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-95 backdrop-blur-lg hidden">
            <div class="pt-16 pb-6 px-4 space-y-6">
                <?php if ($role === 'admin'): ?>
                    <div class="space-y-4">
                        <a href="/gym/admin/dashboard.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Dashboard</a>
                        <a href="/gym/admin/members.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Members</a>
                        <a href="/gym/admin/users.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Users</a>
                        <a href="/gym/admin/gym-owners.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Owners</a>
                        <a href="/gym/admin/manage_gym.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Gyms</a>
                        <a href="/gym/admin/reviews.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Reviews</a>
                        <a href="/gym/admin/membership_plans.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Plans</a>
                        <a href="/gym/admin/see-gym-earn.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Gym Earnings</a>
                        <a href="/gym/admin/cut-off-chart.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Cut Offs</a>
                    </div>
                <?php elseif ($role === 'member' && isset($_SESSION['user_id'])): ?>
                    <div class="space-y-4">
                        <a href="/gym/dashboard.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Dashboard</a>
                        <a href="/gym/schedule-history.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Schedule History</a>
                        <a href="/gym/user_schedule.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">My Schedules</a>
                        <a href="/gym/view_membership.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Membership</a>
                        <a href="/gym/payment_history.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Payment History</a>
                        <a href="/gym/profile.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Profile</a>
                    </div>
                <?php elseif (isset($_SESSION['owner_id'])): ?>
                    <div class="space-y-4">
                        <a href="../gym/dashboard.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Dashboard</a>
                        <a href="../gym/edit_gym_details.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">My Gym</a>
                        <a href="../gym/manage_membership_plans.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Plans</a>
                        <a href="../gym/manage_equipment.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Equipment</a>
                        <a href="../gym/booking.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Schedules</a>
                        <a href="../gym/member_list.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Members</a>
                        <a href="../gym/earning-history.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Earn History</a>
                        <a href="../gym/visit_attendance.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Visit History</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <a href="/gym/" class="block text-white  hover:text-yellow-400 text-lg font-medium">Home</a>
                        <a href="/gym/view_membership.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Membership Plans</a>
                        <a href="/gym/contact.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">Contact</a>
                        <a href="/gym/about-us.php" class="block text-white  hover:text-yellow-400 text-lg font-medium">About Us</a>
                    </div>
                <?php endif; ?>

                <!-- Mobile User Section -->
                <?php if ($isLoggedIn): ?>
                    <div class="pt-4 border-t border-gray-700">
                        <a href="<?php echo isset($_SESSION['owner_id']) ? '/gym/gym/notifications.php' : '/gym/notifications.php'; ?>" class="flex items-center text-white  hover:text-yellow-400 mb-4">
                            <i class="fas fa-bell text-xl mr-2"></i> Notifications
                            <?php if ($unreadNotificationsCount > 0): ?>
                                <span class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                    <?php echo $unreadNotificationsCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <div class="flex items-center mb-4">
                            <i class="fas fa-user-circle text-yellow-500 text-2xl mr-2"></i>
                            <span class="text-white  font-medium"><?php echo $username; ?></span>
                        </div>

                        <button id="themeToggle" class="p-2 rounded-full bg-gray-800 hover:bg-gray-700 transition-colors duration-200">
                            <svg class="hidden dark:block w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg class="block dark:hidden w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <a href="/gym/includes/logout.php" class="flex items-center text-red-400 hover:text-red-300">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="pt-4 border-t border-gray-700 space-y-4">
                        <a href="/gym/register.php" class="block w-full text-center bg-yellow-500 hover:bg-yellow-600 text-black px-6 py-2 rounded-lg font-medium">Sign Up</a>
                        <a href="/gym/login.php" class="block w-full text-center border-2 border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black px-6 py-2 rounded-lg font-medium">Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        // JavaScript for toggling mobile menu
        document.getElementById('mobileMenuButton').addEventListener('click', function () {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('hidden'); // Toggle visibility
        });
    </script>
