<?php 
session_start();
require '../config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

// Handle Gym Deletion
if (isset($_GET['delete_gym_id'])) {
    $gym_id_to_delete = $_GET['delete_gym_id'];

    // Delete related gym data (images, operating hours, equipment, membership plans)
    $tables = ['gym_images', 'gym_operating_hours', 'gym_equipment', 'gym_membership_plans'];
    foreach ($tables as $table) {
        $query = "DELETE FROM $table WHERE gym_id = :gym_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':gym_id' => $gym_id_to_delete]);
    }

    // Delete the gym
    $query = "DELETE FROM gyms WHERE gym_id = :gym_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':gym_id' => $gym_id_to_delete]);

    echo "Gym deleted successfully!";
    header("Location: manage_gyms.php");
    exit;
}

// Fetch all gyms with their respective owner names
$query = "
    SELECT g.*, go.name AS owner_name
    FROM gyms g
    LEFT JOIN gym_owners go ON g.owner_id = go.id
";
$stmt = $conn->prepare($query);
$stmt->execute();
$gyms = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="gyms-list p-6 bg-white rounded-lg shadow-lg">
    <h1 class="text-3xl font-semibold text-gray-800 mb-4">Manage Gyms</h1>
    <a href="add_gym.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 mb-4">Add Gym</a>
    
    <table class="min-w-full table-auto">
        <thead>
            <tr class="bg-gray-100 text-gray-600">
                <th class="px-4 py-2 text-left">Gym Name</th>
                <th class="px-6 py-3 text-left">Owner</th>
                <th class="px-4 py-2 text-left">Location</th>
                <th class="px-4 py-2 text-left">Capacity</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gyms as $gym): ?>
                <tr class="border-t">
                    <td class="px-4 py-2"><?php echo htmlspecialchars($gym['name']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($gym['owner_name'] ?? 'Unknown'); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($gym['city']); ?>, <?php echo htmlspecialchars($gym['state']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($gym['max_capacity']); ?></td>
                    <td class="px-4 py-2"><?php echo htmlspecialchars($gym['status']); ?></td>
                    <td class="px-4 py-2">
                        <a href="edit_gym.php?gym_id=<?php echo $gym['gym_id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                        <span class="mx-2">|</span>
                        <a href="?delete_gym_id=<?php echo $gym['gym_id']; ?>" onclick="return confirm('Are you sure you want to delete this gym?');" class="text-red-600 hover:text-red-800">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
