<?php
session_start();
require_once 'config/database.php';

$db = new GymDatabase();
$conn = $db->getConnection();

// Fetch featured gyms
$stmt = $conn->prepare("
    SELECT g.*, COUNT(r.id) as review_count, AVG(r.rating) as avg_rating
    FROM gyms g
    LEFT JOIN reviews r ON g.gym_id = r.gym_id
    WHERE g.status = 'active'
    GROUP BY g.gym_id
    ORDER BY avg_rating DESC
    LIMIT 6
");
$stmt->execute();
$featured_gyms = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitConnect - Find Your Perfect Gym</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <!-- Hero Section -->
    <div class="relative bg-gray-900 h-[600px]">
        <div class="absolute inset-0">
            <img src="../../gym/assets/image/gymbg.jpg" alt="Gym Background" class="w-full h-full object-cover opacity-50">
        </div>
        <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">Find Your Perfect Gym</h1>
            <p class="mt-6 text-xl text-gray-300 max-w-3xl">Transform your fitness journey with access to the best gyms in your area. Join FitConnect today and discover a healthier you.</p>
            <div class="mt-10">
                <a href="gyms.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Explore Gyms
                </a>
            </div>
        </div>
    </div>

    <!-- Featured Gyms Section -->
    <div class="bg-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Featured Gyms</h2>
                <p class="mt-4 text-lg text-gray-500">Discover top-rated fitness facilities in your area</p>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($featured_gyms as $gym): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <img src="./gym/uploads/gym_images/<?= htmlspecialchars($gym['cover_photo']) ?>" 
                             alt="<?= htmlspecialchars($gym['name']) ?>" 
                             class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900">
                                <?= htmlspecialchars($gym['name']) ?>
                            </h3>
                            <p class="mt-2 text-gray-600">
                                <?= htmlspecialchars($gym['city']) ?>, <?= htmlspecialchars($gym['state']) ?>
                            </p>
                            <div class="mt-4 flex items-center">
                                <div class="flex items-center">
                                    <?php
                                    $rating = round($gym['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++):
                                    ?>
                                        <svg class="h-5 w-5 <?= $i <= $rating ? 'text-yellow-400' : 'text-gray-300' ?>" 
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <span class="ml-2 text-sm text-gray-600">
                                    (<?= $gym['review_count'] ?> reviews)
                                </span>
                            </div>
                            <a href="../gym/gym_details.php?gym_id=<?php echo $gym['gym_id']; ?>" 
                           class="mt-4 block w-full bg-blue-600 text-white text-center py-2 rounded-md hover:bg-blue-700">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-12 text-center">
                <a href="gyms.php" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    View All Gyms
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="bg-gray-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">Why Choose FitConnect?</h2>
            </div>
            <div class="mt-12 grid gap-8 md:grid-cols-3">
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white mx-auto">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Wide Network</h3>
                    <p class="mt-2 text-gray-500">Access to hundreds of gyms across the country</p>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white mx-auto">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Flexible Plans</h3>
                    <p class="mt-2 text-gray-500">Choose from various membership options</p>
                </div>
                <div class="text-center">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white mx-auto">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-xl font-medium text-gray-900">Quality Assured</h3>
                    <p class="mt-2 text-gray-500">All gyms vetted for quality and safety</p>
                </div>
            </div>
        </div>
    </div>

  <!-- Contact Form Section -->
  <section class="max-w-6xl mx-auto p-8">
    <div class="bg-white shadow-md rounded-lg p-6">
      <h2 class="text-2xl font-semibold text-center mb-6">Get in Touch</h2>
      <form action="contact-form.php" method="POST">
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label for="name" class="block text-gray-700">Full Name</label>
            <input type="text" id="name" name="name" required class="w-full p-3 border border-gray-300 rounded-lg mt-2">
          </div>
          <div>
            <label for="email" class="block text-gray-700">Email Address</label>
            <input type="email" id="email" name="email" required class="w-full p-3 border border-gray-300 rounded-lg mt-2">
          </div>
        </div>4321`

        <div class="mt-4">
          <label for="message" class="block text-gray-700">Your Message</label>
          <textarea id="message" name="message" rows="4" required class="w-full p-3 border border-gray-300 rounded-lg mt-2"></textarea>
        </div>

        <div class="mt-6 text-center">
          <button type="submit" class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
            Send Message
          </button>
        </div>
      </form>
    </div>
  </section>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
