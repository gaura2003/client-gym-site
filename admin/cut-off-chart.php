<?php
require_once '../config/database.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

class GymMembershipPlans {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function fetchPlans($search = '', $tierFilter = '', $durationFilter = '', $cutTypeFilter = '', $sortColumn = 'plan_id', $sortOrder = 'ASC') {
        $query = "
           SELECT 
            gmp.plan_id, 
            gmp.gym_id, 
            gmp.tier, 
            gmp.duration, 
            gmp.price, 
            gmp.inclusions,
            CASE 
                WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end THEN 'fee_based'
                ELSE 'tier_based'
            END as cut_type,
            CASE 
                WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end THEN fbc.admin_cut_percentage
                ELSE coc.admin_cut_percentage
            END as admin_cut_percentage,
            CASE 
                WHEN gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end THEN fbc.gym_cut_percentage
                ELSE coc.gym_owner_cut_percentage
            END as gym_owner_cut_percentage
        FROM gym_membership_plans gmp
        LEFT JOIN cut_off_chart coc ON gmp.tier = coc.tier AND gmp.duration = coc.duration
        LEFT JOIN fee_based_cuts fbc ON gmp.price BETWEEN fbc.price_range_start AND fbc.price_range_end";


        if (!empty($search)) {
            $query .= " AND (gmp.plan_id LIKE :search OR gmp.gym_id LIKE :search OR gmp.tier LIKE :search)";
        }
        if (!empty($tierFilter)) {
            $query .= " AND gmp.tier = :tierFilter";
        }
        if (!empty($durationFilter)) {
            $query .= " AND gmp.duration = :durationFilter";
        }
        if (!empty($cutTypeFilter)) {
            $query .= " AND COALESCE(coc.cut_type, fbc.cut_type) = :cutTypeFilter";
        }

        $query .= " ORDER BY $sortColumn $sortOrder";

        $stmt = $this->conn->prepare($query);

        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%");
        }
        if (!empty($tierFilter)) {
            $stmt->bindValue(':tierFilter', $tierFilter);
        }
        if (!empty($durationFilter)) {
            $stmt->bindValue(':durationFilter', $durationFilter);
        }
        if (!empty($cutTypeFilter)) {
            $stmt->bindValue(':cutTypeFilter', $cutTypeFilter);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$db = new GymDatabase();
$conn = $db->getConnection();
$gymPlans = new GymMembershipPlans($conn);

try {
    $search = $_POST['search'] ?? '';
    $tierFilter = $_POST['tier'] ?? '';
    $durationFilter = $_POST['duration'] ?? '';
    $cutTypeFilter = $_POST['cut_type'] ?? '';
    $sortColumn = $_GET['sort'] ?? 'price';
    $sortOrder = $_GET['order'] ?? 'ASC';

    $plans = $gymPlans->fetchPlans($search, $tierFilter, $durationFilter, $cutTypeFilter, $sortColumn, $sortOrder);

    include '../includes/navbar.php';

    // Your existing total revenue calculations
    $totalAdminRevenue = 0;
    $totalGymRevenue = 0;
    foreach ($plans as $plan) {
        $totalAdminRevenue += ($plan['price'] * $plan['admin_cut_percentage']) / 100;
        $totalGymRevenue += ($plan['price'] * $plan['gym_owner_cut_percentage']) / 100;
    }

    echo "<div class='container mx-auto px-4 py-6'>
            <form method='post' class='flex flex-wrap gap-4 mb-6'>
                <input type='text' name='search' placeholder='Search by Plan ID, Gym ID, Tier' value='$search' class='border px-4 py-2 rounded w-1/3' />
                <select name='tier' class='border px-4 py-2 rounded'>
                    <option value=''>Select Tier</option>
                    <option value='Tier 1' ".($tierFilter == 'Tier 1' ? 'selected' : '').">Tier 1</option>
                    <option value='Tier 2' ".($tierFilter == 'Tier 2' ? 'selected' : '').">Tier 2</option>
                    <option value='Tier 3' ".($tierFilter == 'Tier 3' ? 'selected' : '').">Tier 3</option>
                </select>
                <select name='duration' class='border px-4 py-2 rounded'>
                    <option value=''>Select Duration</option>
                    <option value='1 Month' ".($durationFilter == '1 Month' ? 'selected' : '').">1 Month</option>
                    <option value='3 Months' ".($durationFilter == '3 Months' ? 'selected' : '').">3 Months</option>
                    <option value='6 Months' ".($durationFilter == '6 Months' ? 'selected' : '').">6 Months</option>
                </select>
                <select name='cut_type' class='border px-4 py-2 rounded'>
                    <option value=''>Select Cut Type</option>
                    <option value='tier_based' ".($cutTypeFilter == 'tier_based' ? 'selected' : '').">Tier Based</option>
                    <option value='fee_based' ".($cutTypeFilter == 'fee_based' ? 'selected' : '').">Fee Based</option>
                </select>
                <button type='submit' class='bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600'>Search</button>
                <a href='add-cutoff.php' class='bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600'>Add Cut-off</a>
            </form>
             <div class='flex justify-between items-center mb-6'>
                <div>
                    <strong>Total Admin Revenue:</strong> ₹".number_format($totalAdminRevenue, 2)."<br>
                    <strong>Total Gym Revenue:</strong> ₹".number_format($totalGymRevenue, 2)."
                </div>
                <form method='post' action='download-plans.php' class='flex items-center gap-4'>
                    <select name='file_type' class='border px-4 py-2 rounded'>
                        <option value='csv'>CSV</option>
                        <option value='excel'>Excel</option>
                    </select>
                    <button type='submit' class='bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600'>Download</button>
                </form>
            </div>
            ";
            // Add this button at the top of your table, after the search filters
echo "<div class='flex justify-between items-center mb-4'>
        <form method='POST' action='update_all_cutoffs.php' class='flex gap-2'>
            <select name='cut_type' class='border rounded px-3 py-2'>
                <option value='tier_based'>Tier Based</option>
                <option value='fee_based'>Fee Based</option>
            </select>
            <button type='submit' class='bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600'>
                Update All Plans
            </button>
        </form>
    </div>";


    // Add the new Cut Type column to your table headers
    echo "<table class='table-auto w-full border-collapse border border-gray-300'>
    <thead class='bg-gray-100'>
        <tr>
            <th class='border border-gray-300 px-4 py-2'>
                <a href='?sort=plan_id&order=".($sortColumn == 'plan_id' && $sortOrder == 'ASC' ? 'DESC' : 'ASC')."' class='text-gray-700 hover:text-black'>
                    Plan ID ".($sortColumn == 'plan_id' ? ($sortOrder == 'ASC' ? '↑' : '↓') : '')."
                </a>
            </th>
            <th class='border border-gray-300 px-4 py-2'>
                <a href='?sort=gym_id&order=".($sortColumn == 'gym_id' && $sortOrder == 'ASC' ? 'DESC' : 'ASC')."' class='text-gray-700 hover:text-black'>
                    Gym ID ".($sortColumn == 'gym_id' ? ($sortOrder == 'ASC' ? '↑' : '↓') : '')."
                </a>
            </th>
            <th class='border border-gray-300 px-4 py-2'>
                <a href='?sort=tier&order=".($sortColumn == 'tier' && $sortOrder == 'ASC' ? 'DESC' : 'ASC')."' class='text-gray-700 hover:text-black'>
                    Tier ".($sortColumn == 'tier' ? ($sortOrder == 'ASC' ? '↑' : '↓') : '')."
                </a>
            </th>
            <th class='border border-gray-300 px-4 py-2'>Duration</th>
            <th class='border border-gray-300 px-4 py-2'>
                <a href='?sort=price&order=".($sortColumn == 'price' && $sortOrder == 'ASC' ? 'DESC' : 'ASC')."' class='text-gray-700 hover:text-black'>
                    Price ".($sortColumn == 'price' ? ($sortOrder == 'ASC' ? '↑' : '↓') : '')."
                </a>
            </th>
            <th class='border border-gray-300 px-4 py-2'>Admin Cut (%)</th>
            <th class='border border-gray-300 px-4 py-2'>Gym Cut (%)</th>
            <th class='border border-gray-300 px-4 py-2'>Admin Revenue</th>
            <th class='border border-gray-300 px-4 py-2'>Gym Revenue</th>
            <th class='border border-gray-300 px-4 py-2'>Inclusions</th>
            <th class='border border-gray-300 px-4 py-2'>
                <a href='?sort=cut_type&order=".($sortColumn == 'cut_type' && $sortOrder == 'ASC' ? 'DESC' : 'ASC')."' class='text-gray-700 hover:text-black'>
                    Cut Type ".($sortColumn == 'cut_type' ? ($sortOrder == 'ASC' ? '↑' : '↓') : '')."
                </a>
            </th>
            <th class='border border-gray-300 px-4 py-2'>Actions</th>

        </tr>
    </thead>
    <tbody>";

foreach ($plans as $plan) {
    $adminRevenue = ($plan['price'] * $plan['admin_cut_percentage']) / 100;
    $gymRevenue = ($plan['price'] * $plan['gym_owner_cut_percentage']) / 100;

    echo "<tr>
            <td class='border border-gray-300 px-4 py-2'>{$plan['plan_id']}</td>
            <td class='border border-gray-300 px-4 py-2'>{$plan['gym_id']}</td>
            <td class='border border-gray-300 px-4 py-2'>{$plan['tier']}</td>
            <td class='border border-gray-300 px-4 py-2'>{$plan['duration']}</td>
            <td class='border border-gray-300 px-4 py-2'>₹{$plan['price']}</td>
            <td class='border border-gray-300 px-4 py-2'>{$plan['admin_cut_percentage']}%</td>
            <td class='border border-gray-300 px-4 py-2'>{$plan['gym_owner_cut_percentage']}%</td>
            <td class='border border-gray-300 px-4 py-2'>₹".number_format($adminRevenue, 2)."</td>
            <td class='border border-gray-300 px-4 py-2'>₹".number_format($gymRevenue, 2)."</td>
            <td class='border border-gray-300 px-4 py-2'>{$plan['inclusions']}</td>
            <td class='border border-gray-300 px-4 py-2'>".ucfirst(str_replace('_', ' ', $plan['cut_type']))."</td>
            <td class='border border-gray-300 px-4 py-2'>
                <a href='update-cut-off.php?plan_id={$plan['plan_id']}' class='text-blue-500 hover:underline'>Edit</a>
            </td>
<td class='border border-gray-300 px-4 py-2'>
    <form method='POST' action='update_cut_type.php' class='flex gap-2'>
        <input type='hidden' name='plan_id' value='{$plan['plan_id']}'>
        <select name='cut_type' class='border rounded px-2 py-1'>
            <option value='tier_based' ".($plan['cut_type'] == 'tier_based' ? 'selected' : '').">Tier Based</option>
            <option value='fee_based' ".($plan['cut_type'] == 'fee_based' ? 'selected' : '').">Fee Based</option>
        </select>
        <button type='submit' class='bg-blue-500 text-white px-3 py-1 rounded'>Update</button>
    </form>
</td>
          </tr>";
}

echo "</tbody></table>";


} catch (Exception $e) {
    echo "<div class='container mx-auto px-4 py-8 text-red-500'>Error: " . $e->getMessage() . "</div>";
}
?>
