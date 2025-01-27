<?php
session_start();

require_once 'config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

$gym_id = filter_input(INPUT_GET, 'gym_id', FILTER_VALIDATE_INT);
if (!$gym_id) {
    header('Location: gyms.php');
    exit();
}

// Fetch gym details with reviews
$stmt = $conn->prepare("
    SELECT g.*, 
           (SELECT AVG(rating) FROM reviews r WHERE r.gym_id = g.gym_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews r WHERE r.gym_id = g.gym_id) as review_count
    FROM gyms g 
    WHERE g.gym_id = ?
");
$stmt->execute([$gym_id]);
$gym = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent reviews
$stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.gym_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$stmt->execute([$gym_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch operating hours
$stmt = $conn->prepare("
    SELECT * 
    FROM gym_operating_hours 
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$operating_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch equipment details
$stmt = $conn->prepare("
    SELECT * 
    FROM gym_equipment 
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch gym images
$stmt = $conn->prepare("
    SELECT * 
    FROM gym_images 
    WHERE gym_id = ?
");
$stmt->execute([$gym_id]);
$gym_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch membership plans for this gym
// Add this query after fetching gym details
$planStmt = $conn->prepare("
    SELECT DISTINCT
        gmp.*,
        coc.admin_cut_percentage,
        coc.gym_owner_cut_percentage
    FROM gym_membership_plans gmp
    LEFT JOIN cut_off_chart coc ON gmp.tier = coc.tier 
    AND gmp.duration = coc.duration
    WHERE gmp.gym_id = ?
    ORDER BY gmp.tier, 
    CASE gmp.duration
        WHEN 'Daily' THEN 1
        WHEN 'Weekly' THEN 2
        WHEN 'Monthly' THEN 3
        WHEN 'Quartrly' THEN 4
        WHEN 'Half Yearly' THEN 5
        WHEN 'Yearly' THEN 6
    END
");
$planStmt->execute([$gym_id]);
$plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);


if (!isset($_SESSION['user_id']) && isset($_GET['gym_id'])) {  // Check for gym_id in GET parameter
    $_SESSION['return_to'] = $_SERVER['REQUEST_URI']; // Store the current URL in the session
}
include 'includes/navbar.php';
?>
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($gym['name']); ?></h1>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($gym['address']); ?></p>
                </div>
                <a href="schedule.php?gym_id=<?php echo $gym['gym_id']; ?>"
                    class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600">
                    Schedule Visit
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                <div>
                    <h2 class="text-xl font-bold mb-4">About</h2>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($gym['description'])); ?></p>

                    <h3 class="text-lg font-bold mt-6 mb-3">Amenities</h3>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $amenities = json_decode($gym['amenities'], true);
                        if ($amenities):
                            foreach ($amenities as $amenity): ?>
                                <span class="px-3 py-1 bg-gray-100 rounded-full text-sm">
                                    <?php echo ucfirst($amenity); ?>
                                </span>
                        <?php endforeach;
                        endif; ?>

                    </div>

                    <h3 class="text-lg font-bold mt-6 mb-3">Operating Hours</h3>
                    <div class="space-y-2">
                        <?php
                        // Filter the array to include only 'Daily' operating hours
                        $daily_hours = array_filter($operating_hours, function ($hour) {
                            return $hour['day'] === 'Daily';
                        });

                        foreach ($daily_hours as $hour): ?>
                            <div>
                                <strong><?php echo htmlspecialchars($hour['day']); ?>:</strong>
                                <?php
                                // Format the morning open and close times
                                $morning_open_time = date("h:i a", strtotime($hour['morning_open_time']));
                                $morning_close_time = date("h:i a", strtotime($hour['morning_close_time']));

                                // Format the evening open and close times
                                $evening_open_time = date("h:i a", strtotime($hour['evening_open_time']));
                                $evening_close_time = date("h:i a", strtotime($hour['evening_close_time']));
                                ?>
                                <div><b>Morning :</b></div>
                                <?php echo htmlspecialchars($morning_open_time . ' - ' . $morning_close_time); ?>,
                                <div><b>Evening :</b></div>
                                <?php echo htmlspecialchars($evening_open_time . ' - ' . $evening_close_time); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>


                </div>

                <div>
                    <h2 class="text-xl font-bold mb-4">Reviews</h2>
                    <div class="mb-6">
                        <div class="flex items-center mb-2">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="h-5 w-5 <?php echo $i <= $gym['avg_rating'] ? 'fill-current' : 'fill-gray-300'; ?>"
                                        viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="ml-2"><?php echo number_format($gym['avg_rating'], 1); ?> out of 5</span>
                        </div>
                        <p class="text-sm text-gray-600"><?php echo $gym['review_count']; ?> reviews</p>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="border-b pb-4">
                                <div class="flex items-center mb-2">
                                    <span class="font-medium"><?php echo htmlspecialchars($review['username']); ?></span>
                                    <span class="mx-2">•</span>
                                    <span class="text-sm text-gray-600">
                                        <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="flex text-yellow-400 mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="h-4 w-4 <?php echo $i <= $review['rating'] ? 'fill-current' : 'fill-gray-300'; ?>"
                                            viewBox="0 0 20 20">
                                            <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z" />
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <h3 class="text-lg font-bold mt-6 mb-3">Gallery</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($gym_images as $image): ?>
            <?php if (file_exists("gym/" . $image['image_path'])): ?>
                <img src="gym/<?php echo htmlspecialchars($image['image_path']); ?>" alt="Gym Image" class="rounded-md">
            <?php endif; ?>
        <?php endforeach; ?>

    </div>
    <h3 class="text-lg font-bold mt-6 mb-3">Equipment</h3>
    <div class="space-y-2">
        <?php foreach ($equipment as $item): ?>
            <div class="flex items-center gap-4">
                <img src="./gym/uploads/equipments/<?php echo htmlspecialchars($item['image']); ?>" alt="Equipment" class="h-16 w-16 rounded-md">
                <div>
                    <strong><?php echo htmlspecialchars($item['equipment_name']); ?></strong>
                    <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-8">
    <h3 class="text-2xl font-bold mb-6">Membership Plans</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($plans as $plan): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="text-xl font-bold mb-2 text-blue-600">
                    <?php echo htmlspecialchars($plan['tier']); ?>
                </div>
                <div class="text-3xl font-bold mb-4">
                    ₹<?php echo number_format($plan['price'], 2); ?>
                    <span class="text-sm text-gray-600 font-normal">
                        /<?php echo strtolower($plan['duration']); ?>
                    </span>
                </div>
                <div class="text-gray-600 mb-4">
                    <?php echo htmlspecialchars($plan['inclusions']); ?>
                </div>
                <div class="text-sm text-gray-500 mb-4">
                    <p>Best For: <?php echo htmlspecialchars($plan['best_for']); ?></p>
                </div>
                <ul class="text-sm text-gray-600 mb-6 space-y-2">
                    <?php 
                    $inclusions = explode(',', $plan['inclusions']);
                    foreach ($inclusions as $inclusion): ?>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <?php echo htmlspecialchars(trim($inclusion)); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="buy_membership.php?plan_id=<?php echo $plan['plan_id']; ?>&gym_id=<?php echo $gym_id; ?>"
                        class="block w-full bg-blue-600 text-white text-center px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Select Plan
                    </a>
                <?php else: ?>
                    <a href="login.php" 
                        class="block w-full bg-blue-600 text-white text-center px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Login to Subscribe
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>


    <div class="mt-8">
        <h3 class="text-2xl font-bold mb-6">Write a Review</h3>
        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" action="submit_review.php" class="bg-white rounded-lg shadow-lg p-6">
                <input type="hidden" name="gym_id" value="<?php echo $gym_id; ?>">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rating</label>
                        <div class="mt-1 flex items-center space-x-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" required
                                    class="hidden peer" id="star<?php echo $i; ?>">
                                <label for="star<?php echo $i; ?>"
                                    class="cursor-pointer text-gray-300 peer-checked:text-yellow-400">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Your Review</label>
                        <textarea name="comment" rows="4" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            placeholder="Share your experience..."></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Submit Review
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="bg-gray-50 rounded-lg p-6 text-center">
                <p class="text-gray-600">Please <a href="login.php" class="text-blue-600 hover:underline">login</a> to write a review.</p>
            </div>
        <?php endif; ?>
    </div>
</div>