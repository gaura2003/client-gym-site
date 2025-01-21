<?php
session_start();
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();
$member_id = $_GET['id'];

// Fetch comprehensive member details
$stmt = $conn->prepare("
    SELECT 
        u.*,
        um.status as membership_status,
        um.start_date,
        um.end_date,
        mp.name as plan_name,
        mp.price as plan_price,
        mp.visit_limit,
        g.name as gym_name,
        g.address as gym_address,
        COUNT(s.id) as total_schedules,
        (SELECT COUNT(*) FROM schedules WHERE user_id = u.id) as total_schedules,
        (SELECT SUM(amount) FROM payments WHERE user_id = u.id) as total_payments
    FROM users u
    LEFT JOIN user_memberships um ON u.id = um.user_id
    LEFT JOIN membership_plans mp ON um.plan_id = mp.id
    LEFT JOIN gyms g ON um.id = g.gym_id
    LEFT JOIN schedules s ON u.id = s.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent schedules
$stmt = $conn->prepare("
    SELECT s.*, g.name as gym_name
    FROM schedules s
    JOIN gyms g ON s.gym_id = g.gym_id
    WHERE s.user_id = ?
    ORDER BY s.start_date DESC, s.start_time DESC
    LIMIT 5
");
$stmt->execute([$member_id]);
$recent_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Member Header -->
        <div class="p-6 bg-gray-50 border-b">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($member['username']); ?></h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($member['email']); ?></p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-semibold <?php 
                    echo $member['membership_status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo ucfirst($member['membership_status']); ?>
                </span>
            </div>
        </div>

        <!-- Member Details Grid -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Membership Information -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Membership Details</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p><strong>Plan:</strong> <?php echo htmlspecialchars($member['plan_name']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($member['membership_status']); ?></p>
                    <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($member['start_date'])); ?></p>
                    <p><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($member['end_date'])); ?></p>
                    <p><strong>Visit Limit:</strong> <?php echo $member['visit_limit']; ?></p>
                    <p><strong>Total Schedules:</strong> <?php echo $member['total_schedules']; ?></p>
                </div>
            </div>

            <!-- Gym Information -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">Gym Information</h2>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p><strong>Gym:</strong> <?php echo htmlspecialchars($member['gym_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($member['gym_address']); ?></p>
                    <p><strong>Total Payments:</strong> ₹<?php echo number_format($member['total_payments'], 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Schedules -->
        <div class="p-6 border-t">
            <h2 class="text-lg font-semibold mb-4">Recent Schedules</h2>
            <div class="space-y-4">
                <?php foreach ($recent_schedules as $schedule): ?>
                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($schedule['gym_name']); ?></p>
                            <p class="text-sm text-gray-600">
                                <?php echo date('M d, Y', strtotime($schedule['start_date'])); ?> 
                                (<?php echo date('h:i A', strtotime($schedule['start_time'])); ?>)
                            </p>
                        </div>
                        <span class="text-sm text-gray-600">
                            Status: <?php echo ucfirst($schedule['status']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
