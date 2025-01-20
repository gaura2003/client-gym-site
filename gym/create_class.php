<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['owner_id'])) {
    header('Location: login.php');
    exit;
}

$db = new GymDatabase();
$conn = $db->getConnection();

// Get gym ID
$stmt = $conn->prepare("SELECT gym_id FROM gyms WHERE owner_id = :owner_id");
$stmt->bindParam(':owner_id', $_SESSION['owner_id']);
$stmt->execute();
$gym = $stmt->fetch(PDO::FETCH_ASSOC);
$gym_id = $gym['gym_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $instructor = $_POST['instructor'];
    $capacity = (int)$_POST['capacity'];
    $duration = (int)$_POST['duration_minutes'];
    $difficulty = $_POST['difficulty_level'];
    $schedule = json_encode($_POST['schedule']);
    
    $stmt = $conn->prepare("
        INSERT INTO gym_classes (
            gym_id, name, description, instructor, 
            capacity, duration_minutes, difficulty_level, 
            schedule, status
        ) VALUES (
            :gym_id, :name, :description, :instructor,
            :capacity, :duration, :difficulty,
            :schedule, 'active'
        )
    ");

    $result = $stmt->execute([
        ':gym_id' => $gym_id,
        ':name' => $name,
        ':description' => $description,
        ':instructor' => $instructor,
        ':capacity' => $capacity,
        ':duration' => $duration,
        ':difficulty' => $difficulty,
        ':schedule' => $schedule
    ]);

    if ($result) {
        header('Location: manage_classes.php?success=1');
        exit;
    }
}

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Create New Class</h1>

        <form method="POST" class="bg-white rounded-lg shadow-lg p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Class Name</label>
                    <input type="text" name="name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Instructor</label>
                    <input type="text" name="instructor" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Capacity</label>
                        <input type="number" name="capacity" required min="1"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" required min="15" step="15"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Difficulty Level</label>
                    <select name="difficulty_level" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Schedule</label>
                    <div class="space-y-2">
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day):
                        ?>
                        <div class="flex items-center space-x-4">
                            <input type="checkbox" name="schedule[<?php echo strtolower($day); ?>][enabled]" 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="w-24"><?php echo $day; ?></span>
                            <input type="time" name="schedule[<?php echo strtolower($day); ?>][start_time]"
                                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span>to</span>
                            <input type="time" name="schedule[<?php echo strtolower($day); ?>][end_time]"
                                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" 
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Create Class
                </button>
            </div>
        </form>
    </div>
</div>
