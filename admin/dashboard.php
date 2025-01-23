<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

require_once '../config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch total counts for all tables
$counts = [];

// Users and Members
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$counts['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Gyms and Related Tables
$stmt = $conn->query("SELECT COUNT(*) as total FROM gyms");
$counts['gyms'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM gym_classes");
$counts['classes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM gym_equipment");
$counts['equipment'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM gym_images");
$counts['gym_images'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM gym_membership_plans");
$counts['gym_plans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM gym_operating_hours");
$counts['operating_hours'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM gym_owners");
$counts['gym_owners'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Bookings and Schedules
$stmt = $conn->query("SELECT COUNT(*) as total FROM class_bookings");
$counts['bookings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM schedules");
$counts['schedules'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Memberships and Plans
$stmt = $conn->query("SELECT COUNT(*) as total FROM membership_plans");
$counts['membership_plans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM user_memberships");
$counts['user_memberships'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Other Tables
$stmt = $conn->query("SELECT COUNT(*) as total FROM reviews");
$counts['reviews'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM payments");
$counts['payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM notifications");
$counts['notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Fetch admin revenue statistics
$stmt = $conn->query("
    SELECT 
        SUM(admin_cut) as total_admin_revenue,
        COUNT(*) as total_transactions,
        SUM(CASE WHEN DATE(date) = CURRENT_DATE THEN admin_cut ELSE 0 END) as today_revenue,
        SUM(CASE WHEN MONTH(date) = MONTH(CURRENT_DATE) THEN admin_cut ELSE 0 END) as monthly_revenue
    FROM gym_revenue
");
$revenue_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch revenue by tier
$stmt = $conn->query("
    SELECT 
        gmp.tier,
        COUNT(*) as visit_count,
        SUM(gr.admin_cut) as tier_revenue
    FROM gym_revenue gr
    JOIN schedules s ON gr.schedule_id = s.id
    JOIN user_memberships um ON s.membership_id = um.id
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    GROUP BY gmp.tier
");
$tier_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent transactions
$stmt = $conn->query("
    SELECT 
        gr.date,
        gr.admin_cut,
        g.name as gym_name,
        gmp.tier
    FROM gym_revenue gr
    JOIN gyms g ON gr.gym_id = g.gym_id
    JOIN schedules s ON gr.schedule_id = s.id
    JOIN user_memberships um ON s.membership_id = um.id
    JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    ORDER BY gr.date DESC
    LIMIT 10
");
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/navbar.php';
?><?php
// Add this to your existing database queries:
$stmt = $conn->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE status = 'completed' 
    AND MONTH(payment_date) = MONTH(CURRENT_DATE)
");
$counts['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center">
    <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
    <button id="distribute-revenue" onclick="distributeRevenue()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Distribute Revenues</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Users & Members Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-2">Users & Owners</h3>
            <p class="text-3xl font-bold text-blue-600">Users: <?php echo number_format($counts['users']); ?></p>
            <p class="text-3xl font-bold text-purple-600">Gym Owners: <?php echo number_format($counts['gym_owners']); ?></p>
        </div>

        <!-- Gyms Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-2">Gyms & Classes</h3>
            <p class="text-3xl font-bold text-green-600">Gyms: <?php echo number_format($counts['gyms']); ?></p>
            <p class="text-sm text-gray-600">Classes: <?php echo number_format($counts['classes']); ?></p>
            <p class="text-sm text-gray-600">Equipment: <?php echo number_format($counts['equipment']); ?></p>
        </div>

        <!-- Memberships Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-2">Memberships</h3>
            <p class="text-3xl font-bold text-purple-600">Active: <?php echo number_format($counts['user_memberships']); ?></p>
            <p class="text-sm text-gray-600">Plans: <?php echo number_format($counts['membership_plans']); ?></p>
            <p class="text-sm text-gray-600">Gym Plans: <?php echo number_format($counts['gym_plans']); ?></p>
        </div>

        <!-- Activity Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-2">Activity</h3>
            <p class="text-sm text-gray-600">Bookings: <?php echo number_format($counts['bookings']); ?></p>
            <p class="text-sm text-gray-600">Schedules: <?php echo number_format($counts['schedules']); ?></p>
        </div>

        <!-- Reviews Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-2">Reviews & Feedback</h3>
            <p class="text-3xl font-bold text-yellow-600">Reviews: <?php echo number_format($counts['reviews']); ?></p>
            <p class="text-sm text-gray-600">Notifications: <?php echo number_format($counts['notifications']); ?></p>
            <p class="text-sm text-gray-600">Operating Hours: <?php echo number_format($counts['operating_hours']); ?></p>
        </div>

        <!-- Payments Card -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-2">Payments</h3>
            <p class="text-3xl font-bold text-indigo-600">Total: <?php echo number_format($counts['payments']); ?></p>
            <p class="text-sm text-gray-600">Images: <?php echo number_format($counts['gym_images']); ?></p>
            <p class="text-sm text-gray-600">Monthly Revenue: ₹<?php echo number_format($counts['monthly_revenue'], 2); ?></p>
        </div>

    
<div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold text-blue-600">
                ₹<?= number_format($revenue_stats['total_admin_revenue'], 2) ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Today's Revenue</h3>
            <p class="text-3xl font-bold text-green-600">
                ₹<?= number_format($revenue_stats['today_revenue'], 2) ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Monthly Revenue</h3>
            <p class="text-3xl font-bold text-purple-600">
                ₹<?= number_format($revenue_stats['monthly_revenue'], 2) ?>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Transactions</h3>
            <p class="text-3xl font-bold text-indigo-600">
                <?= number_format($revenue_stats['total_transactions']) ?>
            </p>
        </div>
    </div>

    <!-- Revenue Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
        <!-- Revenue by Tier -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Revenue by Tier</h3>
            <div class="space-y-4">
                <?php foreach ($tier_revenue as $tier): ?>
                    <div class="flex justify-between items-center">
                        <span class="font-medium"><?= $tier['tier'] ?></span>
                        <span class="text-gray-600">
                            ₹<?= number_format($tier['tier_revenue'], 2) ?>
                            <span class="text-sm text-gray-500">
                                (<?= $tier['visit_count'] ?> visits)
                            </span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Recent Transactions</h3>
            <div class="space-y-4">
                <?php foreach ($recent_transactions as $transaction): ?>
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($transaction['gym_name']) ?></p>
                            <p class="text-sm text-gray-500">
                                <?= date('d M Y', strtotime($transaction['date'])) ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">₹<?= number_format($transaction['admin_cut'], 2) ?></p>
                            <p class="text-sm text-gray-500"><?= $transaction['tier'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>


    </div>
        
<script>
    function distributeRevenue() {
        fetch('distribute_revenue.php', {
            method: 'POST',
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
    
</div>
