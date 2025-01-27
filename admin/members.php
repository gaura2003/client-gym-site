<?php
session_start();
require_once '../config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Handle filters
$gym_id = $_GET['gym_id'] ?? 'all';
$membership = $_GET['membership'] ?? 'all';
$search = $_GET['search'] ?? '';
$plan_id = $_GET['plan_id'] ?? '';

// Fetch available gyms for the filter dropdown
$gymStmt = $conn->query("SELECT gym_id, name FROM gyms");
$gyms = $gymStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available membership plans for the filter dropdown
$planStmt = $conn->query("SELECT id, name FROM membership_plans WHERE status = 'active'");
$membershipPlans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

// Build query with filters
$query = "
SELECT 
    u.id, u.username, u.email, u.status as user_status,
    um.status as membership_status, um.start_date, um.end_date,
    gmp.plan_name, gmp.duration, gmp.price,
    g.name as gym_name,
    (SELECT COUNT(*) FROM schedules s2 
     WHERE s2.user_id = u.id 
     AND s2.start_date BETWEEN um.start_date AND um.end_date) as used_visits,
    CASE 
        WHEN gmp.duration = 1 THEN 30
        WHEN gmp.duration = 3 THEN 90
        WHEN gmp.duration = 6 THEN 180
        WHEN gmp.duration = 12 THEN 365
        ELSE gmp.duration * 30
    END as total_visits
FROM users u
LEFT JOIN user_memberships um ON u.id = um.user_id
LEFT JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
LEFT JOIN gyms g ON um.gym_id = g.gym_id
WHERE u.role = 'member'
";

if ($gym_id !== 'all') {
    $query .= " AND g.gym_id = :gym_id";
}
if ($membership !== 'all') {
    $query .= " AND um.status = :membership";
}
if ($search) {
    $query .= " AND (u.username LIKE :search OR u.email LIKE :search)";
}
if ($plan_id) {
    $query .= " AND gmp.plan_id = :plan_id";
}

$query .= " GROUP BY u.id, um.id";
$totalQuery = $query; // Query for counting total records

$query .= " LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);

// Bind parameters
if ($gym_id !== 'all')
    $stmt->bindValue(':gym_id', $gym_id);
if ($membership !== 'all')
    $stmt->bindValue(':membership', $membership);
if ($search)
    $stmt->bindValue(':search', "%$search%");
if ($plan_id)
    $stmt->bindValue(':plan_id', $plan_id);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total records for pagination
$totalStmt = $conn->prepare($totalQuery);
if ($gym_id !== 'all')
    $totalStmt->bindValue(':gym_id', $gym_id);
if ($membership !== 'all')
    $totalStmt->bindValue(':membership', $membership);
if ($search)
    $totalStmt->bindValue(':search', "%$search%");
if ($plan_id)
    $totalStmt->bindValue(':plan_id', $plan_id);
$totalStmt->execute();
$totalRecords = $totalStmt->rowCount();

$totalPages = ceil($totalRecords / $limit);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Gym</label>
                <select name="gym_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All Gyms</option>
                    <?php foreach ($gyms as $gym): ?>
                        <option value="<?php echo $gym['gym_id']; ?>" <?php echo ($gym_id == $gym['gym_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($gym['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Membership</label>
                <select name="membership" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="all">All</option>
                    <option value="active" <?php echo $membership === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $membership === 'expired' ? 'selected' : ''; ?>>Expired</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Membership Plan</label>
                <select name="plan_id" class="mt-1 block w-full rounded-md border-gray-300">
                    <option value="">All Plans</option>
                    <?php foreach ($membershipPlans as $plan): ?>
                        <option value="<?php echo $plan['id']; ?>" <?php echo ($plan_id == $plan['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plan['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                    class="mt-1 block w-full rounded-md border-gray-300">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Members Table -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gym</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Membership</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedules</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($member['username']); ?>
                            </div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?></div>
                        </td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($member['gym_name']); ?></td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($member['plan_name']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                Duration: <?php echo $member['duration']; ?>
                                <br>
                                Price: ₹<?php echo number_format($member['price'], 2); ?>
                            </div>
                        </td>

                        <td class="px-6 py-4">₹<?php echo number_format($member['price'], 2); ?></td>

                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">
                                <span class="font-medium">Used:</span> <?php echo $member['used_visits']; ?>
                            </div>
                           
                            <div class="text-sm text-gray-500">
                                <span class="font-medium">Total Limit:</span> <?php echo $member['total_visits']; ?>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $member['membership_status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo ucfirst($member['membership_status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm flex flex-col">
                            <a href="member_details.php?id=<?php echo $member['id']; ?>"
                                class="text-blue-600 hover:text-blue-900">View Details</a>
                            <a href="member_schedule.php?id=<?php echo $member['id']; ?>"
                                class="ml-3 text-green-600 hover:text-green-900">Schedule</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center mt-4">
        <p class="text-sm text-gray-500">Showing <?php echo $offset + 1; ?> to
            <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo $totalRecords; ?> results
        </p>
        <div class="space-x-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&gym_id=<?php echo $gym_id; ?>&membership=<?php echo $membership; ?>&plan_id=<?php echo $plan_id; ?>&search=<?php echo $search; ?>"
                    class="px-3 py-1 rounded-md <?php echo $page == $i ? 'bg-blue-500 text-white' : 'bg-gray-200'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>