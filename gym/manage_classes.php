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

// Fetch all classes
$stmt = $conn->prepare("
    SELECT 
        gc.*,
        COUNT(cb.id) as total_bookings
    FROM gym_classes gc
    LEFT JOIN class_bookings cb ON gc.id = cb.class_id
    WHERE gc.gym_id = :gym_id
    GROUP BY gc.id
    ORDER BY gc.name
");
$stmt->execute([':gym_id' => $gym_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            Class created successfully!
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Classes</h1>
        <a href="create_class.php"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create New Class
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($classes as $class): ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($class['name']); ?></h3>
                        <p class="text-gray-600">Instructor: <?php echo htmlspecialchars($class['instructor']); ?></p>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                    <?php echo getStatusClass($class['status']); ?>">
                        <?php echo ucfirst($class['status']); ?>
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <p class="text-sm text-gray-600">
                        Capacity: <?php echo $class['capacity']; ?>
                        (<?php echo $class['current_bookings']; ?> booked)
                    </p>
                    <p class="text-sm text-gray-600">
                        Duration: <?php echo $class['duration_minutes']; ?> minutes
                    </p>
                    <p class="text-sm text-gray-600">
                        Level: <?php echo ucfirst($class['difficulty_level']); ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        Total Bookings: <?php echo $class['total_bookings']; ?>
                    </p>
                </div>

                <div class="border-t pt-4">
                    <h4 class="font-medium mb-2">Schedule</h4>
                    <?php
                    $schedule = json_decode($class['schedule'], true);
                    if ($schedule && is_array($schedule)):
                    ?>
                        <div class="text-sm space-y-1">
                            <?php foreach ($schedule as $day => $times): ?>
                                <?php if (isset($times['enabled']) && $times['enabled']): ?>
                                    <div class="flex justify-between">
                                        <span class="font-medium"><?php echo ucfirst($day); ?></span>
                                        <span><?php echo $times['start_time']; ?> - <?php echo $times['end_time']; ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 flex justify-end space-x-2">
                    <a href="edit_class.php?id=<?php echo $class['id']; ?>"
                        class="text-blue-600 hover:text-blue-800">Edit</a>
                    <a href="view_bookings.php?class_id=<?php echo $class['id']; ?>"
                        class="text-green-600 hover:text-green-800">View Bookings</a>
                    <?php if ($class['status'] === 'active'): ?>
                        <a href="cancel_class.php?id=<?php echo $class['id']; ?>"
                            class="text-red-600 hover:text-red-800"
                            onclick="return confirm('Are you sure you want to cancel this class?')">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
function getStatusClass($status)
{
    switch ($status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'completed':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>