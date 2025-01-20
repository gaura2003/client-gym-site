<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$schedule_id = intval($_GET['schedule_id']);
$user_id = $_SESSION['user_id'];

// Fetch schedule details
$db = new GymDatabase();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT s.*, g.name as gym_name, m.end_date 
    FROM schedules s 
    JOIN gyms g ON s.gym_id = g.gym_id 
    JOIN user_memberships m ON s.user_id = m.user_id 
    WHERE s.id = :schedule_id AND s.user_id = :user_id
");
$stmt->bindParam(':schedule_id', $schedule_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    die("Schedule not found or you're not authorized to view this.");
}

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Schedule Details</h2>
    <p><strong>Activity Type:</strong> <?php echo htmlspecialchars($schedule['activity_type']); ?></p>
    <p><strong>Gym Name:</strong> <?php echo htmlspecialchars($schedule['gym_name']); ?></p>
    <p><strong>Start Date:</strong> <?php echo date('M j, Y', strtotime($schedule['start_date'])); ?></p>
    <p><strong>Start Time:</strong> <?php echo date('g:i A', strtotime($schedule['start_time'])); ?></p>
    <p><strong>Status:</strong> <?php echo htmlspecialchars($schedule['status']); ?></p>
    <p><strong>Notes:</strong> <?php echo htmlspecialchars($schedule['notes']); ?></p>

    <?php if ($schedule['status'] === 'scheduled'): ?>
        <hr class="my-4">
        <form action="cancel_schedule.php" method="POST" class="space-y-4">
            <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
            <textarea name="cancellation_reason" class="w-full p-2 border rounded-lg" placeholder="Enter the reason for cancellation" required></textarea>
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">
                Cancel Schedule
            </button>
        </form>
        <p class="text-gray-500 italic mt-4">Please note that cancellation may incur a cancellation fee.</p>
        <p class="text-gray-500 italic mt-4">Membership ends on: <?php echo date('F j, Y', strtotime($schedule['end_date'])); ?></p>
        <p class="text-gray-500 italic mt-4">Please contact the gym administrator for more information.</p>

    <?php else: ?>
        <p class="text-gray-500 italic mt-4">This schedule cannot be canceled.</p>
    <?php endif; ?>
</div>
<?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>
</div>
