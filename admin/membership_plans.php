<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/views/auth/login.php');
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();


// Fetch all membership plans
$stmt = $conn->prepare("SELECT * FROM membership_plans ORDER BY price ASC");
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);


include '../includes/navbar.php';

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Membership Plans Management</h2>
        <button onclick="openAddModal()" class="bg-blue-500 text-white px-4 py-2 rounded">
            Add New Plan
        </button>
    </div>

    <!-- Plans List -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($plans as $plan): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start">
                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($plan['name']); ?></h3>
                    <div class="flex space-x-2">
                        <button onclick="editPlan(<?php echo $plan['id']; ?>)" 
                                class="text-blue-500 hover:text-blue-700">
                            Edit
                        </button>
                        <button onclick="deletePlan(<?php echo $plan['id']; ?>)" 
                                class="text-red-500 hover:text-red-700">
                            Delete
                        </button>
                    </div>
                </div>
                <p class="text-2xl font-bold mt-2">₹<?php echo number_format($plan['price'], 2); ?></p>
                <p class="text-gray-600"><?php echo $plan['duration_days']; ?> days</p>
                <p class="mt-2"><?php echo htmlspecialchars($plan['description']); ?></p>
                <div class="mt-4">
                    <h4 class="font-semibold">Features:</h4>
                <p class="mt-2"><?php echo htmlspecialchars($plan['features']); ?></p>
                    
                </div>
                <div class="mt-4">
                    <span class="px-2 py-1 rounded <?php echo $plan['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($plan['status']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="planModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden scroll">
        <div class="bg-white rounded-lg mx-auto p-6 max-w-2xl">
            <h3 class="text-xl font-bold mb-4" id="modalTitle">Add New Plan</h3>
            <form id="planForm" action="membership_plans.php" method="POST">
                <input type="hidden" name="plan_id" id="planId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="planName" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" name="price" id="planPrice" step="0.01" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duration (days)</label>
                        <input type="number" name="duration_days" id="planDuration" required 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="planDescription" rows="3" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Features (one per line)</label>
                        <textarea name="features" id="planFeatures" rows="3" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="planStatus" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 border rounded-md text-gray-600">Cancel</button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-500 text-white rounded-md">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Plan';
    document.getElementById('planForm').reset();
    document.getElementById('planId').value = '';
    document.getElementById('planModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('planModal').classList.add('hidden');
}

function editPlan(planId) {
    // Fetch plan details and populate form
    fetch(`get_plan.php?id=${planId}`)
        .then(response => response.json())
        .then(plan => {
            document.getElementById('modalTitle').textContent = 'Edit Plan';
            document.getElementById('planId').value = plan.id;
            document.getElementById('planName').value = plan.name;
            document.getElementById('planPrice').value = plan.price;
            document.getElementById('planDuration').value = plan.duration_days;
            document.getElementById('planDescription').value = plan.description;
            document.getElementById('planFeatures').value = JSON.parse(plan.features).join('\n');
            document.getElementById('planStatus').value = plan.status;
            document.getElementById('planModal').classList.remove('hidden');
        });
}

function deletePlan(planId) {
    if (confirm('Are you sure you want to delete this plan?')) {
        fetch('process_membership.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&plan_id=${planId}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Error deleting plan');
            }
        });
    }
}
</script>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $plan_id = $_POST['plan_id'];
        try {
            $stmt = $conn->prepare("DELETE FROM membership_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }

    $planData = [
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'duration_days' => $_POST['duration_days'],
        'features' => json_encode(array_filter(explode("\n", $_POST['features']))),
        'status' => $_POST['status']
    ];

    if (!empty($_POST['plan_id'])) {
        // Update existing plan
        $planData['id'] = $_POST['plan_id'];
        $sql = "UPDATE membership_plans SET 
                name = :name, 
                description = :description,
                price = :price,
                duration_days = :duration_days,
                features = :features,
                status = :status
                WHERE id = :id";
    } else {
        // Create new plan
        $sql = "INSERT INTO membership_plans (name, description, price, duration_days, features, status) 
                VALUES (:name, :description, :price, :duration_days, :features, :status)";
    }
}
?>