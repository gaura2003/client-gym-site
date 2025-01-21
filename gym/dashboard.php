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

// Get gym ID first
$stmt = $conn->prepare("SELECT gym_id, name FROM gyms WHERE owner_id = :owner_id");
$stmt->bindParam(':owner_id', $owner_id);
$stmt->execute();
$gym = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/navbar.php';

if (!$gym) {
    ?>
    <div class="min-h-screen bg-gray-100 py-12">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <h1 class="text-2xl font-bold mb-4">Welcome to Gym Management System</h1>
                <p class="text-gray-600 mb-6">Let's get started by setting up your gym profile.</p>
                
                <div class="space-y-4">
                    <svg class="w-64 h-64 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <a href="add gym.php" 
                       class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
                        Create Your Gym Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$gym_id = $gym['gym_id'];

// Initialize stats array
$stats = [
    'active_memberships' => 0,
    'monthly_revenue' => 0,
    'total_classes' => 0,
    'total_equipment' => 0,
    'total_reviews' => 0,
    'average_rating' => 0,
    'total_bookings' => 0
];


$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM user_memberships um 
    JOIN membership_plans mp ON um.plan_id = mp.id 
    WHERE mp.id = :gym_id 
    AND um.status = 'active' 
    AND CURRENT_DATE BETWEEN um.start_date AND um.end_date
");
$stmt->bindParam(':gym_id', $gym_id);
$stmt->execute();
$stats['active_memberships'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$monthly_query = "
SELECT COALESCE(SUM(p.amount), 0) as monthly
FROM payments p
JOIN gyms g ON p.gym_id = g.gym_id
WHERE g.owner_id = ?
AND p.status = 'completed'
AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())
";
$monthly_stmt = $conn->prepare($monthly_query);
$monthly_stmt->execute([$owner_id]);
$monthly_earnings = $monthly_stmt->fetch(PDO::FETCH_ASSOC)['monthly'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM gym_classes WHERE gym_id = :gym_id AND status = 'active'");
$stmt->bindParam(':gym_id', $gym_id);
$stmt->execute();
$stats['total_classes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM gym_equipment WHERE gym_id = :gym_id");
$stmt->bindParam(':gym_id', $gym_id);
$stmt->execute();
$stats['total_equipment'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as count, COALESCE(AVG(rating), 0) as avg_rating 
    FROM reviews 
    WHERE gym_id = :gym_id AND status = 'approved'
");
$stmt->bindParam(':gym_id', $gym_id);
$stmt->execute();
$reviews = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_reviews'] = $reviews['count'];
$stats['average_rating'] = number_format($reviews['avg_rating'], 1);

$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM class_bookings cb
    JOIN gym_classes gc ON cb.class_id = gc.id
    WHERE gc.gym_id = :gym_id 
    AND cb.status = 'booked'
");
$stmt->bindParam(':gym_id', $gym_id);
$stmt->execute();
$stats['total_bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Add this revenue calculation code after getting $gym_id
$revenueQuery = "
    SELECT 
        COALESCE(SUM(CASE 
            WHEN source_type = 'visit' THEN amount 
            ELSE 0 
        END), 0) as visit_revenue,
        COALESCE(SUM(amount), 0) as total_revenue,
        COUNT(DISTINCT CASE WHEN source_type = 'visit' THEN date END) as visit_days
    FROM gym_revenue 
    WHERE gym_id = ? 
    AND MONTH(date) = MONTH(CURRENT_DATE())
    AND YEAR(date) = YEAR(CURRENT_DATE())
";

$revenueStmt = $conn->prepare($revenueQuery);
$revenueStmt->execute([$gym_id]);
$revenue = $revenueStmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Dashboard - <?php echo htmlspecialchars($gym['name']); ?></h1>
        <span class="text-gray-600">Welcome back!</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Members Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Total Members</h3>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Members</span>
            </div>
        
            <p class="text-sm text-gray-600 mt-1">Active: <?php echo number_format($stats['active_memberships']); ?></p>
        </div>
        <!-- Revenue Stats Card -->
<div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
    <div class="flex items-center justify-between">
        <h3 class="text-gray-500 text-sm font-medium">Revenue Statistics</h3>
        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Monthly</span>
    </div>
    <div class="mt-2">
        <p class="text-3xl font-bold text-gray-900">₹<?php echo number_format($revenue['visit_revenue'], 2); ?></p>
        <p class="text-sm text-gray-600">Visit Revenue</p>
    </div>
    <div class="mt-2">
        <p class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($revenue['total_revenue'], 2); ?></p>
        <p class="text-sm text-gray-600">Total Revenue</p>
    </div>
    <p class="text-sm text-gray-500 mt-2">Visits this month: <?php echo $revenue['visit_days']; ?> days</p>
</div>

        <!-- Revenue Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Monthly Revenue</h3>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Income</span>
            </div>
            <p class="text-3xl font-bold <?php echo ($monthly_earnings + $revenue['total_revenue'] <= 0) ? 'text-red-600' : 'text-green-600'; ?> mt-2">
    ₹<?php echo number_format(abs($monthly_earnings + $revenue['total_revenue']), 2); ?>
</p>

        </div>
        
        <!-- Classes Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Total Classes</h3>
                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded">Classes</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_classes']); ?></p>
            <p class="text-sm text-gray-600 mt-1">Bookings: <?php echo number_format($stats['total_bookings']); ?></p>
        </div>
        
        <!-- Equipment Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Equipment</h3>
                <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded">Assets</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_equipment']); ?></p>
        </div>
        
        <!-- Reviews Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <h3 class="text-gray-500 text-sm font-medium">Reviews</h3>
                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Feedback</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_reviews']); ?></p>
            <div class="flex items-center mt-1">
                <span class="text-sm text-gray-600">Rating:</span>
                <span class="text-sm font-medium text-yellow-500 ml-1"><?php echo $stats['average_rating']; ?>/5</span>
            </div>
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
