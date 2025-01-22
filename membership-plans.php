<?php
require_once 'config/database.php';
require_once 'vendor/autoload.php';
$db = new GymDatabase();
$conn = $db->getConnection();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Fetch available membership plans
$stmt = $conn->prepare("
    SELECT * FROM membership_plans 
    WHERE status = 'active' 
    ORDER BY price ASC
");
$stmt->execute();
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<div class="max-w-full" id="membership">
    <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
            Choose Your Membership Plan
        </h2>
    </div>

    <div class="mt-12 grid gap-8 grid-cols-1 md:grid-cols-3 mx-10">
    <?php foreach ($plans as $plan): ?>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-8">
            <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($plan['name']); ?></h3>
            <p class="mt-4 text-gray-500"><?php echo htmlspecialchars($plan['description']); ?></p>
            <p class="mt-8">
                <span class="text-4xl font-bold text-gray-900">₹<?php echo number_format($plan['price'], 2); ?></span>
                <span class="text-gray-500">/<?php echo $plan['duration_days']; ?> days</span>
            </p>

            <div>
                    <p class="font-semibold">Features:</p>
                    <?php 
                    $features = json_decode($plan['features'], true);
                    foreach ($features as $feature): ?>
                        <p class="text-gray-600">• <?php echo htmlspecialchars($feature); ?></p>
                    <?php endforeach; ?>
                </div>

            <div class="mt-8">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button 
                        class="purchaseMembershipBtn w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center block"
                        data-user-id="<?php echo $_SESSION['user_id']; ?>"
                        data-plan-id="<?php echo $plan['id']; ?>"
                        data-gym-id="1">
                        Purchase Membership
                    </button>
                <?php else: ?>
                    <a href="./register.php?redirect=membership&plan_id=<?php echo $plan['id']; ?>"
                        class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center block">
                        Register to Subscribe
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.querySelectorAll('.purchaseMembershipBtn').forEach(button => {
    button.addEventListener('click', function() {
        const planId = this.dataset.planId;
        const gymId = this.dataset.gymId;
        const userId = this.dataset.userId;
        
        fetch('create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                plan_id: planId,
                gym_id: gymId,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            const options = {
                $apiKey = $_ENV['RAZORPAY_KEY_ID'];
                key: <?php echo $apiKey ?>, 
                amount: data.amount,
                currency: 'INR',
                name: '<?php echo htmlspecialchars($gym['name']); ?>',
                description: 'Gym Membership Purchase',
                order_id: data.order_id,
                handler: function(response) {
                    verifyPayment(response, data.order_id, planId, gymId);
                }
            };
            const rzp = new Razorpay(options);
            rzp.open();
        });
    });
});

function verifyPayment(payment, orderId, planId, gymId) {
    fetch('verify_payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            razorpay_payment_id: payment.razorpay_payment_id,
            razorpay_order_id: payment.razorpay_order_id,
            razorpay_signature: payment.razorpay_signature,
            plan_id: planId,
            gym_id: gymId
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.href = 'membership_success.php';
        } else {
            alert('Payment verification failed');
        }
    });
}
</script>
