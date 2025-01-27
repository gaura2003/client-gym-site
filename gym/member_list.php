<?php
session_start();
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

if (!isset($_SESSION['owner_id'])) {
    header('Location: ./login.html');
    exit;
}

$owner_id = $_SESSION['owner_id'];

// Get gym details
$gymsStmt = $conn->prepare("SELECT gym_id, name FROM gyms WHERE owner_id = :owner_id");
$gymsStmt->bindValue(':owner_id', $owner_id);
$gymsStmt->execute();
$gyms = $gymsStmt->fetchAll(PDO::FETCH_ASSOC);

$gym_id = isset($gyms[0]['gym_id']) ? $gyms[0]['gym_id'] : 'all'; 

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filters
$membership = $_GET['membership'] ?? 'all';
$search = $_GET['search'] ?? '';
$plan_id = $_GET['plan_id'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'username';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Get membership plans
$planStmt = $conn->prepare("SELECT plan_id, plan_name FROM gym_membership_plans WHERE gym_id = :gym_id");
$planStmt->bindValue(':gym_id', $gym_id);
$planStmt->execute();
$membershipPlans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

// Build base query
$baseQuery = "
    SELECT 
        u.id, u.username, u.email, u.phone,
        um.status as membership_status, 
        um.start_date, 
        um.end_date,
        gmp.plan_name, 
        gmp.duration, 
        gmp.price,
        g.name as gym_name,
        COUNT(DISTINCT s.id) as visit_count,
        COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.id END) as completed_workouts
    FROM users u
    LEFT JOIN user_memberships um ON u.id = um.user_id
    LEFT JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    LEFT JOIN gyms g ON um.gym_id = g.gym_id
    LEFT JOIN schedules s ON u.id = s.user_id 
    WHERE u.role = 'member'
";


// Add filter conditions
$conditions = [];
$params = [];

if ($gym_id !== 'all') {
    $conditions[] = "g.gym_id = :gym_id";
    $params[':gym_id'] = $gym_id;
}

if ($membership !== 'all') {
    $conditions[] = "um.status = :membership";
    $params[':membership'] = $membership;
}

if ($search) {
    $conditions[] = "(u.username LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($plan_id) {
    $conditions[] = "gmp.plan_id = :plan_id";
    $params[':plan_id'] = $plan_id;
}

if (!empty($conditions)) {
    $baseQuery .= " AND " . implode(" AND ", $conditions);
}

$baseQuery .= " GROUP BY u.id";

// Add sorting and pagination
$query = $baseQuery . " ORDER BY $sort_by $sort_order LIMIT $offset, $limit";

// Execute main query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$countQuery = "SELECT COUNT(DISTINCT u.id) FROM users u 
               LEFT JOIN user_memberships um ON u.id = um.user_id
               LEFT JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
               LEFT JOIN gyms g ON um.gym_id = g.gym_id
               WHERE u.role = 'member'";

if (!empty($conditions)) {
    $countQuery .= " AND " . implode(" AND ", $conditions);
}

$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(DISTINCT u.id) as total_members,
        SUM(CASE WHEN um.status = 'active' THEN 1 ELSE 0 END) as active_members,
        COUNT(DISTINCT CASE WHEN DATE(s.start_date) = CURRENT_DATE THEN s.id END) as today_visits,
        COUNT(DISTINCT gmp.plan_id) as total_plans
    FROM users u
    LEFT JOIN user_memberships um ON u.id = um.user_id
    LEFT JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    LEFT JOIN schedules s ON u.id = s.user_id
    WHERE u.role = 'member' AND um.gym_id = :gym_id
";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bindValue(':gym_id', $gym_id);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Total Members</p>
                    <h3 class="text-2xl font-bold"><?php echo count($members); ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-500">
                    <i class="fas fa-user-check text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Active Members</p>
                    <h3 class="text-2xl font-bold"><?php echo array_reduce($members, function($carry, $member) {
                        return $carry + ($member['membership_status'] === 'active' ? 1 : 0);
                    }, 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                    <i class="fas fa-calendar-check text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Today's Visits</p>
                    <h3 class="text-2xl font-bold"><?php echo array_reduce($members, function($carry, $member) {
                        return $carry + ($member['visit_count'] > 0 ? 1 : 0);
                    }, 0); ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                    <i class="fas fa-dumbbell text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500">Active Plans</p>
                    <h3 class="text-2xl font-bold"><?php echo count(array_unique(array_column($members, 'plan_name'))); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Membership Status</label>
                <select name="membership" class="w-full rounded-lg border-gray-300">
                    <option value="all">All Status</option>
                    <option value="active" <?php echo $membership === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $membership === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="pending" <?php echo $membership === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Type</label>
                <select name="plan_id" class="w-full rounded-lg border-gray-300">
                    <option value="">All Plans</option>
                    <?php foreach ($membershipPlans as $plan): ?>
                        <option value="<?php echo $plan['plan_id']; ?>" <?php echo ($plan_id == $plan['plan_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plan['plan_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                <select name="sort_by" class="w-full rounded-lg border-gray-300">
                    <option value="username" <?php echo $sort_by === 'username' ? 'selected' : ''; ?>>Name</option>
                    <option value="visit_count" <?php echo $sort_by === 'visit_count' ? 'selected' : ''; ?>>Visit Count</option>
                    <option value="start_date" <?php echo $sort_by === 'start_date' ? 'selected' : ''; ?>>Join Date</option>
                    <option value="end_date" <?php echo $sort_by === 'end_date' ? 'selected' : ''; ?>>Expiry Date</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>" 
                       class="w-full rounded-lg border-gray-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full rounded-lg border-gray-300" 
                       placeholder="Name, Email, Phone...">
            </div>

            <div class="flex items-end">
                <button type="submit" 
                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg transition-colors duration-200">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($members as $member): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user text-gray-500"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($member['username']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($member['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($member['plan_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Expires: <?php echo date('M d, Y', strtotime($member['end_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-yellow-500 rounded-full" 
                                             style="width: <?php echo min(($member['completed_workouts'] / 30) * 100, 100); ?>%">
                                        </div>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-600">
                                        <?php echo $member['completed_workouts']; ?>/30
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $member['membership_status'] === 'active' 
                                        ? 'bg-green-100 text-green-800' 
                                        : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($member['membership_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium space-x-2">
                                <a href="member_details.php?id=<?php echo $member['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="member_schedule.php?id=<?php echo $member['id']; ?>" 
                                   class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-calendar"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
