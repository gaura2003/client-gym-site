<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$keyId = 'rzp_test_E5BNM56ZxxZAwk';
$order_id = $_GET['order_id'];
$membership_id = $_GET['membership_id'];
$amount = $_GET['amount'];
$plan_name = $_GET['plan_name'];

include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4">Complete Payment</h1>
        
        <div class="mb-6">
            <p class="text-gray-600">Plan: <?php echo htmlspecialchars($plan_name); ?></p>
            <p class="text-gray-600">Amount: ₹<?php echo number_format($amount/100, 2); ?></p>
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
    rzp1.on('payment.failed', function (response){
        alert("Payment Failed: " + response.error.description);
    });
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
            membership_id: <?php echo $membership_id; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.href = 'payment_success.php?status=success';
        } else {
            alert("Something went wrong with the payment verification. Please try again.");
            window.location.href = 'payment_failed.php';
        }
    })
    .catch(error => {
        alert("Error processing payment: " + error.message);
    });
}
</script>
