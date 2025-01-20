<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<div class="bg-cover bg-center h-screen relative" style="background-image: url('../../gym/assets/image/gymbg.jpg');">
  <!-- Overlay -->
  <div class="absolute inset-0 bg-black bg-opacity-50"></div>

  <!-- Content -->
  <div class="container mx-auto px-6 lg:px-20 relative z-10 flex items-center h-full">
    <div class="text-white max-w-2xl">
      <h1 class="text-4xl sm:text-6xl font-bold mb-6">
        Achieve Your <span class="text-blue-500">Fitness Goals</span>
      </h1>
      <p class="text-lg sm:text-xl mb-8">
        Unlock your potential with world-class gym equipment, expert trainers, and a supportive fitness community.
      </p>
      <div class="flex gap-4">
        <a href="#membership" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300">
          Join Now
        </a>
        <a href="#about" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300">
          Learn More
        </a>
      </div>
    </div>
  </div>
</div>
