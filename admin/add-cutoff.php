<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /gym/login.php');
    exit();
}

$db = new GymDatabase();
$conn = $db->getConnection();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Cut-Off Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Add Cut-Off Settings</h2>
        
        <!-- Tier Based Cut-Off Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h3 class="text-xl font-semibold mb-4">Tier Based Cut-Off</h3>
            <form method="POST" action="process_cutoff.php" class="space-y-4">
                <input type="hidden" name="cut_type" value="tier_based">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2">Tier</label>
                        <select name="tier" required class="w-full border rounded px-3 py-2">
                            <option value="">Select Tier</option>
                            <option value="Tier 1">Tier 1</option>
                            <option value="Tier 2">Tier 2</option>
                            <option value="Tier 3">Tier 3</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block mb-2">Duration</label>
                        <select name="duration" required class="w-full border rounded px-3 py-2">
                            <option value="">Select Duration</option>
                            <option value="1 Month">1 Month</option>
                            <option value="3 Months">3 Months</option>
                            <option value="6 Months">6 Months</option>
                            <option value="12 Months">12 Months</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block mb-2">Admin Cut (%)</label>
                        <input type="number" name="admin_cut" required class="w-full border rounded px-3 py-2" min="0" max="100">
                    </div>
                    
                    <div>
                        <label class="block mb-2">Gym Cut (%)</label>
                        <input type="number" name="gym_cut" required class="w-full border rounded px-3 py-2" min="0" max="100">
                    </div>
                </div>
                
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Add Tier Based Cut-Off
                </button>
            </form>
        </div>

        <!-- Fee Based Cut-Off Form -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold mb-4">Fee Based Cut-Off</h3>
            <form method="POST" action="process_cutoff.php" class="space-y-4">
                <input type="hidden" name="cut_type" value="fee_based">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2">Price Range Start (₹)</label>
                        <input type="number" name="price_start" required class="w-full border rounded px-3 py-2" min="0">
                    </div>
                    
                    <div>
                        <label class="block mb-2">Price Range End (₹)</label>
                        <input type="number" name="price_end" required class="w-full border rounded px-3 py-2" min="0">
                    </div>
                    
                    <div>
                        <label class="block mb-2">Admin Cut (%)</label>
                        <input type="number" name="admin_cut" required class="w-full border rounded px-3 py-2" min="0" max="100">
                    </div>
                    
                    <div>
                        <label class="block mb-2">Gym Cut (%)</label>
                        <input type="number" name="gym_cut" required class="w-full border rounded px-3 py-2" min="0" max="100">
                    </div>
                </div>
                
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Add Fee Based Cut-Off
                </button>
            </form>
        </div>
    </div>

    <script>
        // Validate total percentage equals 100
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const adminCut = parseInt(form.querySelector('[name="admin_cut"]').value);
                const gymCut = parseInt(form.querySelector('[name="gym_cut"]').value);
                
                if (adminCut + gymCut !== 100) {
                    e.preventDefault();
                    alert('Total percentage must equal 100%');
                }
            });
        });
    </script>
</body>
</html>
