<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();
$owner_id = $_SESSION['owner_id'];

// Get Gym Details
$stmt = $conn->prepare("SELECT * FROM gyms WHERE owner_id = ?");
$stmt->execute([$owner_id]);
$gym = $stmt->fetch(PDO::FETCH_ASSOC);

$gym_id = ($gym && isset($gym['gym_id'])) ? $gym['gym_id'] : null;


// Analytics Data
$analytics = [
    'daily_visits' => 0,
    'active_members' => 0,
    'monthly_revenue' => 0,
    'total_revenue' => 0
];

// Get Daily Visits
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM schedules 
    WHERE gym_id = ? 
    AND DATE(start_date) = CURRENT_DATE
");
$stmt->execute([$gym_id]);
$analytics['daily_visits'] = $stmt->fetchColumn();

// Get Active Members
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT user_id) as count 
    FROM user_memberships 
    WHERE gym_id = ? 
    AND status = 'active'
");
$stmt->execute([$gym_id]);
$analytics['active_members'] = $stmt->fetchColumn();

// Get Monthly Revenue
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM gym_revenue 
    WHERE gym_id = ? 
    AND MONTH(date) = MONTH(CURRENT_DATE)
    AND YEAR(date) = YEAR(CURRENT_DATE)
");
$stmt->execute([$gym_id]);
$analytics['monthly_revenue'] = $stmt->fetchColumn();

// Get Total Revenue
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM gym_revenue 
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$analytics['total_revenue'] = $stmt->fetchColumn();

// Current Time Slot Occupancy
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM schedules 
    WHERE gym_id = ? 
    AND DATE(start_date) = CURRENT_DATE 
    AND HOUR(start_time) = HOUR(CURRENT_TIME)
");
$stmt->execute([$gym_id]);
$currentSlotOccupancy = $stmt->fetchColumn();

// Daily Activity (Hour-wise visits)
$stmt = $conn->prepare("
    SELECT HOUR(start_time) as hour, COUNT(*) as visit_count 
    FROM schedules 
    WHERE gym_id = ? 
    AND DATE(start_date) = CURRENT_DATE 
    GROUP BY HOUR(start_time)
");
$stmt->execute([$gym_id]);
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Today's Class Bookings
$stmt = $conn->prepare("
    SELECT s.*, u.username 
    FROM schedules s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.gym_id = ? 
    AND DATE(s.start_date) = CURRENT_DATE 
    ORDER BY s.start_time ASC
");
$stmt->execute([$gym_id]);
$todayBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Equipment Status
$stmt = $conn->prepare("
    SELECT 
        equipment_name as name,
        quantity as total
    FROM gym_equipment
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$equipmentStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Reviews
$stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.gym_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$gym_id]);
$recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Membership Distribution
$stmt = $conn->prepare("
    SELECT 
        gmp.plan_name,
        COUNT(*) as member_count,
        (COUNT(*) * 100.0 / (
            SELECT COUNT(*) 
            FROM user_memberships 
            WHERE gym_id = ? 
            AND status = 'active'
        )) as percentage
    FROM user_memberships um
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    WHERE um.gym_id = ? 
    AND um.status = 'active'
    GROUP BY gmp.plan_id, gmp.plan_name
");
$stmt->execute([$gym_id, $gym_id]);
$membershipDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daily Revenue Breakdown
$stmt = $conn->prepare("
    SELECT 
        source_type as activity_type,
        SUM(amount) as daily_revenue
    FROM gym_revenue
    WHERE gym_id = ?
    AND DATE(date) = CURRENT_DATE
    GROUP BY source_type
");
$stmt->execute([$gym_id]);
$dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Member Growth Trend
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_members
    FROM user_memberships
    WHERE gym_id = ?
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
$stmt->execute([$gym_id]);
$memberGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Peak Days Analysis
$stmt = $conn->prepare("
    SELECT 
        DAYNAME(start_date) as day,
        COUNT(*) as visit_count
    FROM schedules
    WHERE gym_id = ?
    AND start_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    GROUP BY day
    ORDER BY visit_count DESC
");
$stmt->execute([$gym_id]);
$peakDays = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Revenue by Plan Type
$stmt = $conn->prepare("
    SELECT 
        gmp.plan_name,
        SUM(gr.amount) as revenue,
        COUNT(um.user_id) as subscribers
    FROM gym_revenue gr
    JOIN user_memberships um ON gr.id = um.id
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    WHERE gr.gym_id = ?
    AND MONTH(gr.date) = MONTH(CURRENT_DATE)
    GROUP BY gmp.plan_id
");
$stmt->execute([$gym_id]);
$planRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Member Retention Rate
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'active' AND DATEDIFF(end_date, start_date) > 180 THEN 1 END) * 100.0 / COUNT(*) as retention_rate
    FROM user_memberships
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$retentionRate = $stmt->fetchColumn();

// Age Demographics
$stmt = $conn->prepare("
    SELECT 
        CASE 
            WHEN age < 25 THEN 'Under 25'
            WHEN age BETWEEN 25 AND 34 THEN '25-34'
            WHEN age BETWEEN 35 AND 44 THEN '35-44'
            ELSE '45+'
        END as age_group,
        COUNT(*) as member_count
    FROM users u
    JOIN user_memberships um ON u.id = um.user_id
    WHERE um.gym_id = ? AND um.status = 'active'
    GROUP BY age_group
");
$stmt->execute([$gym_id]);
$ageDemo = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$gym): ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.2/dist/cdn.min.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <div class="min-h-screen bg-gray-100 py-12">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <h1 class="text-2xl font-bold mb-4">Welcome to Gym Management System</h1>
                <p class="text-gray-600 mb-6">Let\'s get started by setting up your gym profile.</p>

                <div class="space-y-4">
                    <svg class="w-64 h-64 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <a href="add gym.php"
                        class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
                        Create Your Gym Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else:
include '../includes/navbar.php';
?>
<div class="container mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                        <i class="fas fa-dumbbell text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($gym['name']); ?></h1>
                        <p class="text-white ">Dashboard Overview</p>
                    </div>
                </div>
                <div class="text-white text-right">
                    <p class="text-sm">Today's Date</p>
                    <p class="text-xl font-bold"><?php echo date('d M Y'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Daily Visits -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500 text-sm font-medium">Daily Visits</h3>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Today</span>
            </div>
            <div class="flex items-center">
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($analytics['daily_visits']); ?></p>
            </div>
        </div>

        <!-- Active Members -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500 text-sm font-medium">Active Members</h3>
                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Current</span>
            </div>
            <div class="flex items-center">
                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($analytics['active_members']); ?></p>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500 text-sm font-medium">Monthly Revenue</h3>
                <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded">This Month</span>
            </div>
            <div class="flex items-center">
                <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                    <i class="fas fa-rupee-sign text-purple-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900">₹<?php echo number_format($analytics['monthly_revenue']); ?></p>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-lg p-6 transform hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-gray-500 text-sm font-medium">Total Revenue</h3>
                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">All Time</span>
            </div>
            <div class="flex items-center">
                <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                    <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900">₹<?php echo number_format($analytics['total_revenue']); ?></p>
            </div>
        </div>
    </div>

    <!-- Current Occupancy Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Current Time Slot -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-clock text-yellow-500 mr-2"></i>
                Current Time Slot Occupancy
            </h3>
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="h-4 bg-gray-200 rounded-full">
                        <div class="h-4 bg-yellow-500 rounded-full" style="width: <?php echo ($currentSlotOccupancy/50)*100; ?>%"></div>
                    </div>
                </div>
                <span class="ml-4 text-2xl font-bold"><?php echo $currentSlotOccupancy; ?>/50</span>
            </div>
            <p class="text-sm text-gray-500 mt-2">Current hour capacity utilization</p>
        </div>

        <!-- Peak Hours -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-4 flex items-center">
                <i class="fas fa-chart-bar text-yellow-500 mr-2"></i>
                Peak Hours Today
            </h3>
            <div class="grid grid-cols-12 gap-2 h-32">
                <?php foreach ($dailyActivity as $activity): 
                    $height = ($activity['visit_count'] / 50) * 100;
                ?>
                    <div class="flex flex-col items-center">
                        <div class="flex-1 w-full bg-gray-200 rounded-t relative">
                            <div class="absolute bottom-0 w-full bg-yellow-500 rounded-t transition-all duration-300" 
                                 style="height: <?php echo $height; ?>%"></div>
                        </div>
                        <span class="text-xs mt-1"><?php echo date('ga', strtotime($activity['hour'].':00')); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Revenue & Equipment Section -->
     <!-- Bookings & Equipment Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Recent Bookings -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-calendar-alt text-yellow-500 mr-2"></i>
            Today's Class Bookings
        </h3>
        <div class="space-y-4">
            <?php foreach ($todayBookings as $booking): ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($booking['username']) ?></p>
                            <p class="text-sm text-gray-500"><?= date('h:i A', strtotime($booking['start_time'])) ?></p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm <?= $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= ucfirst($booking['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Reviews & Membership Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Recent Reviews -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-star text-yellow-500 mr-2"></i>
            Latest Reviews
        </h3>
        <div class="space-y-4">
            <?php foreach ($recentReviews as $review): ?>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-medium"><?= htmlspecialchars($review['username']) ?></p>
                        <div class="flex items-center">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star <?= $i < $review['rating'] ? 'text-yellow-500' : 'text-white ' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="text-gray-600"><?= htmlspecialchars($review['comment']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Membership Distribution -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <i class="fas fa-chart-pie text-yellow-500 mr-2"></i>
            Membership Distribution
        </h3>
        <div class="space-y-4">
            <?php foreach ($membershipDistribution as $type): ?>
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($type['plan_name']) ?></p>
                        <p class="text-sm text-gray-500"><?= $type['member_count'] ?> members</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2.5">
                            <div class="bg-yellow-500 h-2.5 rounded-full" style="width: <?= $type['percentage'] ?>%"></div>
                        </div>
                        <span class="text-sm font-medium"><?= number_format($type['percentage']) ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Daily Revenue -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold mb-6 flex items-center">
                <i class="fas fa-money-bill-wave text-yellow-500 mr-2"></i>
                Today's Revenue Breakdown
            </h3>
            <div class="space-y-4">
                <?php foreach ($dailyRevenue as $revenue): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium"><?php echo ucfirst($revenue['activity_type']); ?></span>
                        <span class="text-green-600 font-bold">₹<?php echo number_format($revenue['daily_revenue'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
<!-- Member Growth Chart -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold mb-4">
        <i class="fas fa-chart-line text-yellow-500 mr-2"></i>
        Member Growth Trend
    </h3>
    <div class="h-64">
        <canvas id="memberGrowthChart"></canvas>
    </div>
</div>

<!-- Peak Days Analysis -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold mb-4">
        <i class="fas fa-calendar-week text-yellow-500 mr-2"></i>
        Peak Days
    </h3>
    <div class="grid grid-cols-7 gap-2">
        <?php foreach ($peakDays as $day): ?>
            <div class="text-center">
                <div class="h-24 bg-gray-100 rounded-lg relative">
                    <div class="absolute bottom-0 w-full bg-yellow-500 rounded-b-lg transition-all"
                         style="height: <?= ($day['visit_count'] / max(array_column($peakDays, 'visit_count'))) * 100 ?>%">
                    </div>
                </div>
                <p class="mt-2 text-sm"><?= substr($day['day'], 0, 3) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Revenue by Plan -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold mb-4">
        <i class="fas fa-money-bill-wave text-yellow-500 mr-2"></i>
        Revenue by Plan
    </h3>
    <div class="space-y-4">
        <?php foreach ($planRevenue as $plan): ?>
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($plan['plan_name']) ?></p>
                        <p class="text-sm text-gray-500"><?= $plan['subscribers'] ?> subscribers</p>
                    </div>
                    <p class="text-green-600 font-bold">₹<?= number_format($plan['revenue'], 2) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Member Demographics -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold mb-4">
        <i class="fas fa-users text-yellow-500 mr-2"></i>
        Member Demographics
    </h3>
    <div class="grid grid-cols-2 gap-4">
        <!-- Retention Rate -->
        <div class="p-4 bg-gray-50 rounded-lg text-center">
            <p class="text-sm text-gray-500">Retention Rate</p>
            <p class="text-3xl font-bold text-green-600"><?= number_format($retentionRate, 1) ?>%</p>
        </div>
        <!-- Age Distribution -->
        <div class="p-4 bg-gray-50 rounded-lg">
            <?php foreach ($ageDemo as $age): ?>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm"><?= $age['age_group'] ?></span>
                    <span class="text-sm font-medium"><?= $age['member_count'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold mb-6 flex items-center">
            <i class="fas fa-bolt text-yellow-500 mr-2"></i>
            Quick Actions
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="member_list.php" class="p-4 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-center transition-colors duration-200">
                <i class="fas fa-users mb-2 text-2xl"></i>
                <p>Manage Members</p>
            </a>
            <a href="manage_equipment.php" class="p-4 bg-green-500 hover:bg-green-600 text-white rounded-xl text-center transition-colors duration-200">
                <i class="fas fa-dumbbell mb-2 text-2xl"></i>
                <p>Equipment</p>
            </a>
            <a href="booking.php" class="p-4 bg-purple-500 hover:bg-purple-600 text-white rounded-xl text-center transition-colors duration-200">
                <i class="fas fa-calendar-check mb-2 text-2xl"></i>
                <p>Schedules</p>
            </a>
            <a href="earning-history.php" class="p-4 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl text-center transition-colors duration-200">
                <i class="fas fa-chart-line mb-2 text-2xl"></i>
                <p>Earnings</p>
            </a>
        </div>
    </div>
</div>

  
<?php endif; ?>