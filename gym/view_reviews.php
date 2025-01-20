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

// Fetch reviews with user details
$stmt = $conn->prepare("
    SELECT 
        r.*,
        u.username,
        u.email,
        u.profile_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.gym_id = :gym_id
    ORDER BY r.created_at DESC
");
$stmt->execute([':gym_id' => $gym_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Gym Reviews</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($reviews as $review): ?>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center mb-4">
                <?php if ($review['profile_image']): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($review['profile_image']); ?>" 
                         alt="Profile" 
                         class="w-10 h-10 rounded-full mr-4">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-gray-200 mr-4 flex items-center justify-center">
                        <span class="text-gray-500 text-lg"><?php echo strtoupper(substr($review['username'], 0, 1)); ?></span>
                    </div>
                <?php endif; ?>
                
                <div>
                    <h3 class="font-semibold"><?php echo htmlspecialchars($review['username']); ?></h3>
                    <p class="text-sm text-gray-500">
                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                    </p>
                </div>
            </div>

            <div class="mb-4">
                <div class="flex items-center mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" 
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    <?php endfor; ?>
                    <span class="ml-2 text-gray-600"><?php echo $review['rating']; ?>/5</span>
                </div>
                
                <?php if ($review['visit_date']): ?>
                    <p class="text-sm text-gray-500">
                        Visited on: <?php echo date('M d, Y', strtotime($review['visit_date'])); ?>
                    </p>
                <?php endif; ?>
            </div>

            <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>

            <?php if ($review['status'] === 'pending'): ?>
                <div class="mt-4 flex justify-end space-x-2">
                    <a href="approve_review.php?id=<?php echo $review['id']; ?>" 
                       class="text-green-600 hover:text-green-800">Approve</a>
                    <a href="reject_review.php?id=<?php echo $review['id']; ?>" 
                       class="text-red-600 hover:text-red-800"
                       onclick="return confirm('Are you sure you want to reject this review?')">Reject</a>
                </div>
            <?php endif; ?>

            <div class="mt-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    <?php echo getStatusClass($review['status']); ?>">
                    <?php echo ucfirst($review['status']); ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
function getStatusClass($status) {
    switch ($status) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>
