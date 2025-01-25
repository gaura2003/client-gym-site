<?php 
session_start();
require_once 'config/database.php';

$GymDatabase = new GymDatabase();
$db = $GymDatabase->getConnection();

// Get the search query from the request
$search = $_GET['search'] ?? '';

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

// Search filter
if ($search) {
    $sql .= " AND (g.name LIKE ? OR g.description LIKE ?)";
    $params = ["%$search%", "%$search%"];
} else {
    $params = [];
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$gyms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output the gyms in HTML format
foreach ($gyms as $gym) {
    echo '<div class="bg-white rounded-lg shadow-md p-6">';
    echo '<h3 class="font-semibold text-lg text-gray-900">' . htmlspecialchars($gym['name']) . '</h3>';
    echo '<p class="text-gray-600 text-sm">' . htmlspecialchars($gym['description']) . '</p>';
    echo '<div class="mt-4">';
    echo '<span class="text-gray-800">Price: ₹' . htmlspecialchars($gym['monthly_price']) . '/Month</span>';
    echo '</div>';
    echo '<div class="mt-2">';
    echo '<span class="text-gray-500">Rating: ' . round($gym['avg_rating'], 1) . '/5 (' . $gym['review_count'] . ' reviews)</span>';
    echo '</div>';
    echo '</div>';
}
?>
