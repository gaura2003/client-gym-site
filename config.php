<?php
define('RAZORPAY_KEY_ID', 'rzp_test_E5BNM56ZxxZAwk');
define('RAZORPAY_KEY_SECRET', 'uXo5UAsgnT7zglLrmsH749Je');

$pdo = new PDO("mysql:host=localhost;dbname=gym", "username", "password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
