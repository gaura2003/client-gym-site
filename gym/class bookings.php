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

// Fetch all bookings with user and class details
$stmt = $conn->prepare("
    SELECT 
        cb.id as booking_id,
        cb.booking_date,
        cb.status,
        u.username,
        u.email,
        gc.name as class_name,
        gc.instructor,
        gc.schedule
    FROM class_bookings cb
    JOIN users u ON cb.user_id = u.id
    JOIN gym_classes gc ON cb.class_id = gc.id
    WHERE gc.gym_id = :gym_id
    ORDER BY cb.booking_date DESC
");
$stmt->execute([':gym_id' => $gym_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Class Bookings</h1>
        <a href="create_class.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Create New Class
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Booking ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Member
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Class
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        #<?php echo htmlspecialchars($booking['booking_id']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($booking['username']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            <?php echo htmlspecialchars($booking['email']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($booking['class_name']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            Instructor: <?php echo htmlspecialchars($booking['instructor']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo getStatusClass($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="update_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                           class="text-blue-600 hover:text-blue-900 mr-3">Update</a>
                        <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                           class="text-red-600 hover:text-red-900"
                           onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
function getStatusClass($status) {
    switch ($status) {
        case 'booked':
            return 'bg-green-100 text-green-800';
        case 'attended':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        case 'missed':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>
