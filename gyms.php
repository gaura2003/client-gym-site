<?php 
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize GymDatabase connection
$GymDatabase = new GymDatabase();
$db = $GymDatabase->getConnection();
$auth = new Auth($db);

$userCity = '';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT city FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
    $userCity = $stmt->fetchColumn() ?: '';
}

// Search and filter parameters
$search = $_GET['search'] ?? '';
$city = $_GET['city'] ?? '';
$amenities = $_GET['amenities'] ?? [];
$min_price = $_GET['min_price'] ?? $_POST['min_price'] ?? '';  // Added min price filter
$max_price = $_GET['max_price'] ?? '';  // Added max price filter
if (!$city && $userCity) {
    $city = $userCity;
}

// Base query for gyms with membership price
$sql = "
    SELECT g.*, 
           (SELECT AVG(rating) FROM reviews r WHERE r.gym_id = g.gym_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews r WHERE r.gym_id = g.gym_id) as review_count,
           gmp.price as monthly_price
    FROM gyms g 
    JOIN gym_membership_plans gmp ON g.gym_id = gmp.gym_id
    WHERE g.status = 'active'
    AND gmp.duration = 'Monthly' AND gmp.plan_type ='Premium'";

$params = [];

if ($search) {
    $sql .= " AND (g.name LIKE ? OR g.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($city) {
    $sql .= " AND g.city = ?";
    $params[] = $city;
}

if (!empty($amenities)) {
    foreach ($amenities as $amenity) {
        $sql .= " AND JSON_CONTAINS(g.amenities, ?)";
        $params[] = json_encode($amenity);
    }
}

// Add price filter if set
if ($min_price !== '') {
    $sql .= " AND gmp.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== '') {
    $sql .= " AND gmp.price <= ?";
    $params[] = $max_price;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$gyms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct cities for filter
$cityStmt = $db->query("SELECT DISTINCT city FROM gyms WHERE status = 'active'");
$cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(amenities, CONCAT('$[', n, ']'))) AS amenity
          FROM gyms
          CROSS JOIN (SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) n
          WHERE status = 'active' AND amenities IS NOT NULL";

$stmt = $db->query($query);
$amenities = $stmt->fetchAll(PDO::FETCH_COLUMN);

include 'includes/navbar.php';
?>
<div class="container mx-auto px-4 py-8">
    <!-- Search and Filters -->
    <div class="mb-8 bg-white p-6 rounded-lg shadow">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Search Gyms</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           class="block w-full rounded-md border border-gray-300 p-3" placeholder="Search by name or description">
                </div>
                
                <!-- City Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">City</label>
                    <select name="city" class="p-3 border border-gray-300 rounded-md w-full">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $cityOption): ?>
                            <option value="<?php echo $cityOption; ?>" 
                                    <?php echo $city === $cityOption ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cityOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Price Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Price</label>
                    <div class="flex space-x-4">
                        <div>
                            <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>"
                                   class="block w-full rounded-md border border-gray-300 p-3" placeholder="Min Price">
                        </div>
                        <div>
                            <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>"
                                   class="block w-full rounded-md border border-gray-300 p-3" placeholder="Max Price">
                        </div>
                    </div>
                </div>
                 <!-- Amenities Filter -->
                 <div>
                    <label class="block text-sm font-medium text-gray-700">Amenities</label>
                    <div class="mt-2 space-y-2">
                        <?php
                        $amenityOptions = ['parking', 'shower', 'locker', 'wifi', 'cafe', 'spa'];
                        foreach ($amenities as $amenity):
                            if (!empty($amenity)) {
                        ?>
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox"  name="amenities[]" value="<?php echo $amenity; ?>"
                                       <?php echo in_array($amenity, $amenities) ? '' : 'checked'; ?>
                                       class="rounded border-gray-300 text-blue-600">
                                <span class="ml-2"><?php echo ucfirst($amenity); ?></span>
                            </label>
                        <?php } endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
    <!-- Gyms Grid Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($gyms as $gym): ?>
            <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition duration-300">
                <img 
                    src="./gym/uploads/gym_images/<?php echo htmlspecialchars($gym['cover_photo'] ?? 'default_gym.jpg'); ?>" 
                    alt="Gym Image" 
                    class="w-full h-48 object-cover rounded-t-lg">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($gym['name']); ?></h3>
                    <div class="flex items-center space-x-2 mt-2">
                        <?php
                        $rating = round($gym['avg_rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <svg 
                                class="h-5 w-5 <?php echo $i <= $rating ? 'text-yellow-400' : 'text-white '; ?>" 
                                fill="currentColor" 
                                viewBox="0 0 20 20">
                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                            </svg>
                        <?php endfor; ?>
                        <span class="text-sm text-gray-600">(<?php echo $gym['review_count'] ?? 0; ?> reviews)</span>
                    </div>
                    <p class="text-gray-600 mt-3 text-sm"><?php echo htmlspecialchars($gym['city']); ?></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach (json_decode($gym['amenities'], true) as $amenity): ?>
                            <span class="px-2 py-1 text-sm bg-blue-50 text-blue-700 rounded-full">
                                <?php echo ucfirst($amenity); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-lg font-semibold text-gray-800 mt-4">
                        ₹<?php echo number_format($gym['monthly_price'], 2); ?> / Month
                    </p>
                    <div class="flex justify-between items-center mt-6">
                        <a 
                            href="../gym/gym_details.php?gym_id=<?php echo $gym['gym_id']; ?>" 
                            class="text-blue-600 hover:text-blue-800">
                            View Details
                        </a>
                        <a 
                            href="../gym/schedule.php?gym_id=<?php echo $gym['gym_id']; ?>" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Schedule Visit
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
// Live search function triggered on keyup in the search bar
function liveSearch(query) {
    if (query.length > 2) { // Trigger search if input length is greater than 2 characters
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "search_gym.php?search=" + encodeURIComponent(query), true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Update the gym listings with the response
                document.getElementById('gym-listings').innerHTML = xhr.responseText;
            }
        };

        xhr.send();
    }
}
</script>
