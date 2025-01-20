<?php
session_start();
include 'includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6 text-center">
        <div class="text-red-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4">Payment Failed</h1>
        <p class="text-gray-600 mb-6">Something went wrong with your payment. Please try again.</p>
        <a href="gym_details.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            Try Again
        </a>
    </div>
</div>
