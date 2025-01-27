<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/views/auth/login.php');
    exit();
}

require_once '../config/database.php';
$db = new GymDatabase();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Users</h1>
        <a href="add.php" class="bg-blue-500 text-white px-4 py-2 rounded">Add New User</a>
    </div>
    <div class="bg-white shadow-md rounded">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="px-6 py-3">Username</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Phone</th>
                    <th class="px-6 py-3">City</th>
                    <th class="px-6 py-3">Balance</th>
                    <th class="px-6 py-3">Role</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr class="border-b ">
                    <td class="px-6 py-4"><?php echo $user['username']; ?></td>
                    <td class="px-6 py-4"><?php echo $user['email']; ?></td>
                    <td class="px-6 py-4"><?php echo $user['phone']; ?></td>
                    <td class="px-6 py-4"><?php echo $user['city']; ?></td>
                    <td class="px-6 py-4"><?php echo $user['balance']; ?></td>
                    <td class="px-6 py-4"><?php echo $user['role']; ?></td>
                    <td class="px-6 py-4"><?php echo $user['status']; ?></td>
                    <td class="px-6 py-4">
                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="text-blue-500">Edit</a>
                        <a href="delete.php?id=<?php echo $user['id']; ?>" class="text-red-500 ml-3">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
