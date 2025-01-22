<?php 
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Initialize GymDatabase connection
$GymDatabase = new GymDatabase();
$db = $GymDatabase->getConnection();
$auth = new Auth($db);

// Search and filter parameters
$search = $_GET['search'] ?? '';
$city = $_GET['city'] ?? '';
$amenities = $_GET['amenities'] ?? [];
$min_price = $_GET['min_price'] ?? $_POST['min_price'] ?? '';  // Added min price filter
$max_price = $_GET['max_price'] ?? '';  // Added max price filter

// Base query for gyms with membership price
$sql = "
    SELECT g.*, 
           (SELECT AVG(rating) FROM reviews r WHERE r.gym_id = g.gym_id) as avg_rating,
           (SELECT COUNT(*) FROM reviews r WHERE r.gym_id = g.gym_id) as review_count,
           gmp.price as monthly_price
    FROM gyms g 
    JOIN gym_membership_plans gmp ON g.gym_id = gmp.gym_id
    WHERE g.status = 'active'
    AND gmp.duration = 'Monthly'";

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

// Execute the query
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

    <!-- Gyms Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($gyms as $gym): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($gym['name']); ?></h3>
                    
                    <!-- Rating Display -->
                    <div class="flex items-center mb-3">
                        <?php
                        $rating = round($gym['avg_rating'] ?? 0);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <svg class="h-5 w-5 <?php echo $i <= $rating ? 'text-yellow-400' : 'text-gray-300'; ?> fill-current" 
                                 viewBox="0 0 20 20">
                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                            </svg>
                        <?php endfor; ?>
                        <span class="ml-2 text-sm text-gray-600">
                            (<?php echo $gym['review_count'] ?? 0; ?> reviews)
                        </span>
                    </div>

                    <!-- Location and Contact -->
                    <p class="text-gray-600 mb-2">
                        <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <?php echo htmlspecialchars($gym['city']); ?>
                    </p>

                    <!-- Amenities -->
                    <div class="flex flex-wrap gap-2 my-3">
                        <?php foreach (json_decode($gym['amenities'], true) as $amenity): ?>
                            <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-sm">
                                <?php echo ucfirst($amenity); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Monthly Price -->
                    <div class="mb-4">
                        <p class="text-lg font-semibold text-gray-800">
                            ₹<?php echo number_format($gym['monthly_price'], 2); ?> / Month
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between items-center mt-4">
                        <a href="../gym/gym_details.php?gym_id=<?php echo $gym['gym_id']; ?>" 
                           class="text-blue-600 hover:text-blue-800">View Details</a>
                        <a href="../gym/schedule.php?gym_id=<?php echo $gym['gym_id']; ?>" 
                           class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Schedule Visit
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
