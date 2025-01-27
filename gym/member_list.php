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
$gymsStmt = $conn->prepare("SELECT gym_id, name FROM gyms WHERE owner_id = :owner_id");
$gymsStmt->bindValue(':owner_id', $owner_id);
$gymsStmt->execute();
$gyms = $gymsStmt->fetchAll(PDO::FETCH_ASSOC);

$gym_id = isset($gyms[0]['gym_id']) ? $gyms[0]['gym_id'] : 'all'; 
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Enhanced filters
$membership = $_GET['membership'] ?? 'all';
$search = $_GET['search'] ?? '';
$plan_id = $_GET['plan_id'] ?? '';
$trainer_id = $_GET['id'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'username';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Fetch trainers for filter
$trainersStmt = $conn->prepare("SELECT id, name FROM trainers WHERE gym_id = :gym_id");
$trainersStmt->bindValue(':gym_id', $gym_id);
$trainersStmt->execute();
$trainers = $trainersStmt->fetchAll(PDO::FETCH_ASSOC);

// Enhanced query with trainer information
$query = "
    SELECT 
        u.id, u.username, u.email, u.status as user_status,
        um.status as membership_status, um.start_date, um.end_date,
        gmp.plan_name, gmp.duration, gmp.price,
        g.name as gym_name,
        t.name as trainer_name,
        (SELECT COUNT(*) FROM schedules s2 
         WHERE s2.user_id = u.id 
         AND s2.start_date BETWEEN um.start_date AND um.end_date) as visit_count,
        (SELECT COUNT(*) FROM schedules s3 
         WHERE s3.user_id = u.id 
         AND s3.status = 'completed') as completed_workouts
    FROM users u
    LEFT JOIN user_memberships um ON u.id = um.user_id
    LEFT JOIN gym_membership_plans gmp ON um.plan_id = gmp.plan_id
    LEFT JOIN gyms g ON um.gym_id = g.gym_id
    LEFT JOIN trainers t ON um.id = t.id
    WHERE u.role = 'member'
";

// Add filter conditions
if ($gym_id !== 'all') $query .= " AND g.gym_id = :gym_id";
if ($membership !== 'all') $query .= " AND um.status = :membership";
if ($search) $query .= " AND (u.username LIKE :search OR u.email LIKE :search)";
if ($trainer_id) $query .= " AND t.id = :id";

$query .= " ORDER BY $sort_by $sort_order LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);

// Bind parameters
if ($gym_id !== 'all') $stmt->bindValue(':gym_id', $gym_id);
if ($membership !== 'all') $stmt->bindValue(':membership', $membership);
if ($search) $stmt->bindValue(':search', "%$search%");
if ($trainer_id) $stmt->bindValue(':id', $trainer_id);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Dashboard Stats -->
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
        <!-- Add more stat cards here -->
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Membership Status</label>
                <select name="membership" class="w-full rounded-lg border-gray-300">
                    <option value="all">All Status</option>
                    <option value="active" <?php echo $membership === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $membership === 'expired' ? 'selected' : ''; ?>>Expired</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Trainer</label>
                <select name="trainer_id" class="w-full rounded-lg border-gray-300">
                    <option value="">All Trainers</option>
                    <?php foreach ($trainers as $trainer): ?>
                        <option value="<?php echo $trainer['id']; ?>" 
                                <?php echo ($trainer_id == $trainer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($trainer['name']); ?>
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
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full rounded-lg border-gray-300" placeholder="Search members...">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg transition-colors duration-200">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trainer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($members as $member): ?>
                        <tr class="hover:bg-gray-50">
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
                                    <?php echo htmlspecialchars($member['trainer_name'] ?? 'Not Assigned'); ?>
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
                                <div class="text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <span class="mr-2"><?php echo $member['completed_workouts']; ?> workouts</span>
                                        <div class="relative w-24 h-2 bg-gray-200 rounded">
                                            <div class="absolute top-0 left-0 h-2 bg-yellow-500 rounded" 
                                                 style="width: <?php echo min(($member['completed_workouts'] / 30) * 100, 100); ?>%">
                                            </div>
                                        </div>
                                    </div>
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
