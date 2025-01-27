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

<?php
// Keep existing PHP code at the top
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-12">
    <div class="max-w-xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Payment Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                <h1 class="text-2xl font-bold text-white text-center">Complete Your Payment</h1>
            </div>

            <!-- Payment Details -->
            <div class="p-8">
                <!-- Plan Summary Card -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Plan</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($plan_name); ?></span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Duration</span>
                        <span class="font-semibold"><?php echo htmlspecialchars($plan_duration); ?></span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Start Date</span>
                        <span class="font-semibold"><?php echo date('d M Y', strtotime($start_date)); ?></span>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">End Date</span>
                        <span class="font-semibold"><?php echo date('d M Y', strtotime($end_date)); ?></span>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t">
                        <span class="text-lg font-semibold">Total Amount</span>
                        <span class="text-2xl font-bold text-blue-600">₹<?php echo number_format($amount/100, 2); ?></span>
                    </div>
                </div>

                <!-- Secure Payment Notice -->
                <div class="flex items-center justify-center mb-6 text-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="text-sm">Secure Payment Powered by Razorpay</span>
                </div>

                <!-- Payment Button -->
                <button id="payButton" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 px-8 rounded-xl font-semibold text-lg hover:from-blue-700 hover:to-indigo-700 transform transition-all duration-200 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pay Now
                </button>

                <!-- Payment Methods -->
                <div class="mt-6 flex justify-center space-x-4">
                    <img src="assets/visa.svg" alt="Visa" class="h-8">
                    <img src="assets/mastercard.svg" alt="Mastercard" class="h-8">
                    <img src="assets/upi.svg" alt="UPI" class="h-8">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Keep existing JavaScript code -->

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
