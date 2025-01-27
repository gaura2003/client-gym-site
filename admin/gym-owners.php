<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch all gym owners with their gyms
$stmt = $conn->prepare("
    SELECT 
        go.*, g.balance,
        COUNT(g.gym_id) as total_gyms,
        GROUP_CONCAT(g.name) as gym_names
    FROM gym_owners go
    LEFT JOIN gyms g ON go.id = g.owner_id
    GROUP BY go.id
    ORDER BY go.created_at DESC
");
$stmt->execute();
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Gym Owners</h1>
        <a href="add-owner.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Add New Owner
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gyms</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($owners as $owner): ?>
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <?php if ($owner['profile_picture']): ?>
                                <img class="h-10 w-10 rounded-full" 
                                     src="../gym/<?php echo htmlspecialchars($owner['profile_picture']); ?>" 
                                     alt="Profile">
                            <?php endif; ?>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($owner['name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Joined: <?php echo date('M d, Y', strtotime($owner['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($owner['email']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($owner['phone']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">
                            <?php echo htmlspecialchars($owner['city'] . ', ' . $owner['state']); ?>
                        </div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($owner['country']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $owner['is_approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo $owner['is_approved'] ? 'Approved' : 'Pending'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900"><?php echo $owner['total_gyms']; ?> gyms</div>
                        <?php if ($owner['gym_names']): ?>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($owner['gym_names']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold  text-yellow-800">
                            <?php echo $owner['balance'] ; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="view-owners.php?id=<?php echo $owner['id']; ?>" 
                           class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        <?php if (!$owner['is_approved']): ?>
                            <a href="approve-owner.php?id=<?php echo $owner['id']; ?>" 
                               class="text-green-600 hover:text-green-900 mr-3">Approve</a>
                        <?php endif; ?>
                        <a href="edit-owner.php?id=<?php echo $owner['id']; ?>" 
                           class="text-indigo-600 hover:text-indigo-900">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>