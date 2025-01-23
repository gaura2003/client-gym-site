<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.html');
    exit;
}

$owner_id = $_SESSION['owner_id'];
$db = new GymDatabase();
$conn = $db->getConnection();

// Get gym details
$stmt = $conn->prepare("SELECT gym_id, name FROM gyms WHERE owner_id = :owner_id");
$stmt->bindParam(':owner_id', $owner_id);
$stmt->execute();
$gym = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gym) {

    echo '
    <div class="min-h-screen bg-gray-100 py-12">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <h1 class="text-2xl font-bold mb-4">Welcome to Gym Management System</h1>
                <p class="text-gray-600 mb-6">Let\'s get started by setting up your gym profile.</p>
                
                <div class="space-y-4">
                    <svg class="w-64 h-64 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <a href="add_gym.php" 
                       class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
                        Create Your Gym Profile
                    </a>
                </div>
            </div>
        </div>
    </div>';

}

$gym_id = $gym['gym_id'];

// Fetch Analytics Data
$analytics = [
    'daily_visits' => 0,
    'active_members' => 0,
    'monthly_revenue' => 0,
    'total_revenue' => 0,
    'total_classes' => 0,
    'equipment_count' => 0,
    'review_count' => 0,
    'avg_rating' => 0,
    'class_bookings' => 0,
    'membership_distribution' => [],
    'revenue_by_plan' => []
];

// Daily Visits
$stmt = $conn->prepare("
    SELECT COUNT(*) as visits 
    FROM schedules 
    WHERE gym_id = ? 
    AND DATE(start_date) = CURRENT_DATE
");
$stmt->execute([$gym_id]);
$analytics['daily_visits'] = $stmt->fetch(PDO::FETCH_ASSOC)['visits'];

// Active Members
$stmt = $conn->prepare("
    SELECT COUNT(*) as members 
    FROM user_memberships 
    WHERE gym_id = ? 
    AND status = 'active' 
    AND payment_status = 'paid'
    AND CURRENT_DATE BETWEEN start_date AND end_date
");
$stmt->execute([$gym_id]);
$analytics['active_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['members'];

// Monthly Revenue
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as revenue 
    FROM payments 
    WHERE gym_id = ? 
    AND status = 'completed'
    AND MONTH(payment_date) = MONTH(CURRENT_DATE)
    AND YEAR(payment_date) = YEAR(CURRENT_DATE)
");
$stmt->execute([$gym_id]);
$analytics['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

// Total Revenue
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE gym_id = ? 
    AND status = 'completed'
");
$stmt->execute([$gym_id]);
$analytics['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Equipment Count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM gym_equipment 
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$analytics['equipment_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Reviews Stats
$stmt = $conn->prepare("
    SELECT COUNT(*) as count, COALESCE(AVG(rating), 0) as avg_rating 
    FROM reviews 
    WHERE gym_id = ? 
    AND status = 'approved'
");
$stmt->execute([$gym_id]);
$reviews = $stmt->fetch(PDO::FETCH_ASSOC);
$analytics['review_count'] = $reviews['count'];
$analytics['avg_rating'] = number_format($reviews['avg_rating'], 1);

// Class Bookings
$stmt = $conn->prepare("
    SELECT COUNT(*) as bookings 
    FROM class_bookings cb 
    JOIN gym_classes gc ON cb.class_id = gc.id 
    WHERE gc.gym_id = ? 
    AND cb.status = 'booked'
");
$stmt->execute([$gym_id]);
$analytics['class_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['bookings'];

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Dashboard - <?php echo htmlspecialchars($gym['name']); ?></h1>
        <span class="text-gray-600">Welcome back!</span>
    </div>

    <!-- Analytics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Daily Visits Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Daily Visits</h3>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Today</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($analytics['daily_visits']); ?></p>
        </div>

        <!-- Active Members Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Active Members</h3>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Current</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($analytics['active_members']); ?></p>
        </div>

        <!-- Monthly Revenue Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Monthly Revenue</h3>
                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded">This Month</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2">₹<?php echo number_format($analytics['monthly_revenue'], 2); ?></p>
        </div>

        <!-- Total Revenue Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">All Time</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2">₹<?php echo number_format($analytics['total_revenue'], 2); ?></p>
        </div>

        <!-- Equipment Count Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Equipment</h3>
                <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Total</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($analytics['equipment_count']); ?></p>
        </div>

        <!-- Reviews Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Reviews</h3>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded">Rating</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $analytics['avg_rating']; ?>/5</p>
            <p class="text-sm text-gray-600 mt-1">Total Reviews: <?php echo number_format($analytics['review_count']); ?></p>
        </div>

        <!-- Class Bookings Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Class Bookings</h3>
                <span class="bg-pink-100 text-pink-800 text-xs font-medium px-2.5 py-0.5 rounded">Active</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($analytics['class_bookings']); ?></p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="member_list.php" class="bg-blue-500 text-white rounded-lg p-4 text-center hover:bg-blue-600 transition duration-200">
                Manage Members
            </a>
            <a href="manage_classes.php" class="bg-purple-500 text-white rounded-lg p-4 text-center hover:bg-purple-600 transition duration-200">
                Manage Classes
            </a>
            <a href="manage_equipment.php" class="bg-red-500 text-white rounded-lg p-4 text-center hover:bg-red-600 transition duration-200">
                Manage Equipment
            </a>
            <a href="view_reviews.php" class="bg-yellow-500 text-white rounded-lg p-4 text-center hover:bg-yellow-600 transition duration-200">
                View Reviews
            </a>
        </div>
    </div>
</div>
