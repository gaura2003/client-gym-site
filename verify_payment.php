<?php
session_start();
require_once 'config/database.php';
require_once 'vendor/autoload.php';

$db = new GymDatabase();
$conn = $db->getConnection();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get all parameters from URL
$order_id = $_GET['order_id'];
$membership_id = $_GET['membership_id'];
$amount = $_GET['amount'];
$plan_name = $_GET['plan_name'];
$gym_id = $_GET['gym_id'];
$plan_id = $_GET['plan_id'];
$user_id = $_GET['user_id'];
$plan_price = $_GET['plan_price'];
$plan_duration = $_GET['plan_duration'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$keyId = $_ENV['RAZORPAY_KEY_ID'];

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4">Complete Payment</h1>
        
        <div class="mb-6">
            <p class="text-gray-600 mb-2">Plan: <?php echo htmlspecialchars($plan_name); ?></p>
            <p class="text-gray-600 mb-2">Duration: <?php echo htmlspecialchars($plan_duration); ?></p>
            <p class="text-gray-600 mb-2">Start Date: <?php echo date('d M Y', strtotime($start_date)); ?></p>
            <p class="text-gray-600 mb-2">End Date: <?php echo date('d M Y', strtotime($end_date)); ?></p>
            <p class="text-gray-600 mb-4">Amount: ₹<?php echo number_format($amount/100, 2); ?></p>
        </div>

        <button id="payButton" 
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            Pay Now
        </button>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('payButton').onclick = function() {
    var options = {
        "key": "<?php echo $keyId; ?>",
        "amount": "<?php echo $amount; ?>",
        "currency": "INR",
        "name": "Gym Membership",
        "description": "<?php echo $plan_name; ?> Membership",
        "order_id": "<?php echo $order_id; ?>",
        "handler": function (response) {
            verifyPayment(response);
        },
        "modal": {
            "ondismiss": function() {
                alert("Payment cancelled. Please try again.");
            }
        },
        "theme": {
            "color": "#3399cc"
        }
    };
    
    var rzp1 = new Razorpay(options);
    rzp1.open();
};

function verifyPayment(response) {
    fetch('payment_success.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_order_id: response.razorpay_order_id,
            razorpay_signature: response.razorpay_signature,
            membership_id: <?php echo $membership_id; ?>,
            start_date: '<?php echo $start_date; ?>',
            end_date: '<?php echo $end_date; ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.href = 'payment_success.php?membership_id=' + <?php echo $membership_id; ?>;
        } else {
            alert(data.message || "Payment verification failed");
            window.location.href = 'payment_success.php?membership_id=' + <?php echo $membership_id; ?>;
        }
    })
    .catch(error => {
        console.error("Error:", error);
        window.location.href = 'payment_success.php?membership_id=' + <?php echo $membership_id; ?>;
    });
}
</script>
