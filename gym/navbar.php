<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <header class="bg-blue-600 text-white p-4">
        <h1 class="text-2xl font-bold">Welcome to Your Gym Management Panel</h1>
        <nav>
            <ul class="flex space-x-4 mt-4">
                <li><a href="dashboard.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded">Dashboard</a></li>
                <li><a href="edit_gym_details.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded">Edit Gym Details</a></li>
                <li><a href="manage_equipment.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded">Manage Equipment</a></li>
                <li><a href="manage_members.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded">Manage Members</a></li>
                <li><a href="manage_bookings.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded">Manage Bookings</a></li>
                <li><a href="logout.php" class="text-white hover:bg-blue-500 px-4 py-2 rounded">Logout</a></li>
            </ul>
        </nav>
    </header>