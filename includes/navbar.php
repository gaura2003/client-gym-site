<?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $role         = $_SESSION['role'] ?? '';
    $isLoggedIn   = isset($_SESSION['user_id']) || isset($_SESSION['owner_id']);
    $user_id      = $_SESSION['user_id'] ?? ($_SESSION['owner_id'] ?? null);

    class NavbarDatabase
    {
        private $host     = "localhost";
        private $db_name  = "gym-db";
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

    $db   = new NavbarDatabase();
    $conn = $db->getConnection();

    // Get unread notifications count
    $unreadNotificationsCount = 0;
    if ($isLoggedIn) {
        $query = "SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR gym_id = ?) AND status = 'unread'";
        $stmt  = $conn->prepare($query);
        $stmt->execute([$user_id, $user_id]);
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
</head>
<body class="bg-gray-100">
    <nav x-data="{ open: false }" class="bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/gym" class="text-white font-bold text-xl">GYM PRO</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                    <?php if ($role === 'admin'): ?>
                                <a href="/gym/admin/dashboard.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="/gym/admin/members.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Members</a>
                                <a href="/gym/admin/users.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Users</a>
                                <a href="/gym/admin/gym-owners.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gym Owners</a>
                                <a href="/gym/admin/manage_gym.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gyms</a>
                                <a href="/gym/admin/reviews.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Reviews</a>
                                <a href="/gym/admin/membership_plans.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Plans</a>
                                <a href="/gym/admin/see-gym-earn.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gym Earnings</a>
                            <?php elseif ($role === 'member' && isset($_SESSION['user_id'])): ?>
                                <a href="/gym/dashboard.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="/gym/schedule-history.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Schedule History</a>
                                <a href="/gym/user_schedule.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">My Schedules</a>
                                <a href="/gym/view_membership.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Membership</a>
                                <a href="/gym/payment_history.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Payment History</a>
                                <a href="/gym/profile.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                            <?php elseif (isset($_SESSION['owner_id'])): ?>
                                <a href="../gym/dashboard.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="../gym/edit_gym_details.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">My Gyms</a>
                                <a href="../gym/manage_equipment.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Equipment</a>
                                <a href="../gym/bookings.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Schedules</a>
                                <a href="../gym/member_list.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Members</a>
                                <a href="../gym/earning-history.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Earn History</a>
                            <?php else : ?>
                                <a href="/gym/" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Home</a>
                                <a href="/gym/view_membership.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Membership Plans</a>
                                <a href="/gym/contact.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Contact</a>
                                <a href="/gym/about-us.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">About Us</a>

                            <?php endif; ?>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center">
                        <?php if ($isLoggedIn): ?>
                            <span class="text-white mr-4"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <a href="/gym/includes/logout.php" class="text-red-500 hover:text-red-400 px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                            <a href="/gym/notifications.php" class="relative text-white hover:text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6v4a2 2 0 01-1 1.732V14h14v-.268A2 2 0 0116 12V8a6 6 0 00-6-6zM4 14v1a2 2 0 002 2h8a2 2 0 002-2v-1H4z" />
                                </svg>
                                <?php if ($unreadNotificationsCount > 0): ?>
                                    <span class="absolute top-0 right-0 bg-red-500 text-white text-xs px-1 rounded-full">
                                        <?php echo $unreadNotificationsCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>

                            <a href="/gym/register.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Sign Up</a>
                            <a href="/gym/login.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <button @click="open = !open" class="text-gray-400 hover:text-white hover:bg-gray-700 p-2 rounded-md">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="open" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 flex flex-col">
                <?php if ($role === 'admin'): ?>
                    <a href="/gym/admin/dashboard.php" class="block text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                    <a href="/gym/admin/members.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Members</a>
                    <a href="/gym/admin/users.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Users</a>
                    <a href="/gym/admin/gym-owners.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gym Owners</a>
                    <a href="/gym/admin/manage_gym.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gyms</a>
                    <a href="/gym/admin/reviews.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Reviews</a>
                    <a href="/gym/admin/membership_plans.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Plans</a>
                    <a href="/gym/admin/see-gym-earn.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Gym Earnings</a>
                <?php elseif ($role === 'member'): ?>
                    <a href="/gym/dashboard.php" class="block text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                    <a href="/gym/schedule-history.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Schedule History</a>
                    <a href="/gym/user_schedule.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">My Schedules</a>
                    <a href="/gym/view_membership.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Membership</a>
                    <a href="/gym/payment_history.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Payment History</a>
                    <a href="/gym/profile.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                <?php elseif ($role === 'owner'): ?>
                    <a href="/gym/dashboard.php" class="block text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                    <a href="../gym/edit_gym_details.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">My Gyms</a>
                   <a href="../gym/manage_equipment.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Equipment</a>
                   <a href="../gym/bookings.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Schedules</a>
                   <a href="../gym/member_list.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Members</a>
                   <a href="../gym/earning-history.php" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Earn History</a>
                <?php endif; ?>
<?php if ($isLoggedIn): ?>
                    <a href="/gym/includes/logout.php" class="block text-red-500 hover:bg-gray-700 px-3 py-2 rounded-md text-base font-medium">Logout</a>
                <?php else: ?>
                    <a href="/gym/login.php" class="block text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-base font-medium">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</body>
</html>
