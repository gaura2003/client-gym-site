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
<!-- Hero Section with Parallax Effect -->
<div class="relative h-screen overflow-hidden">
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent z-10"></div>

    <!-- Background Image -->
    <div class="absolute inset-0 bg-[url('assets/images/hero-bg.jpg')] bg-cover bg-center animate-scale"></div>

    <!-- Content Container -->
    <div class="relative z-20 h-full flex items-center">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto lg:mx-0">
                <!-- Heading -->
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold text-white mb-4 sm:mb-6 lg:mb-8 leading-tight animate-fade-in">
                    Elevate Your <span class="text-yellow-400">Fitness</span> Journey
                </h1>

                <!-- Subheading -->
                <p class="text-lg sm:text-xl md:text-2xl text-gray-200 mb-6 sm:mb-8 lg:mb-12 leading-relaxed animate-fade-in-delay max-w-2xl">
                    Join elite fitness centers. Access premium equipment. Transform your life.
                </p>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-6 animate-fade-in-delay-2">
                    <!-- Discover Gyms Button -->
                    <a href="gyms.php" 
                       class="group relative px-6 sm:px-8 py-3 sm:py-4 bg-yellow-400 rounded-full overflow-hidden text-center">
                        <div class="absolute inset-0 bg-yellow-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></div>
                        <span class="relative text-black font-bold text-base sm:text-lg">Discover Gyms</span>
                    </a>

                    <!-- Join Now Button -->
                    <a href="register.php" 
                       class="group relative px-6 sm:px-8 py-3 sm:py-4 border-2 border-white rounded-full overflow-hidden text-center">
                        <div class="absolute inset-0 bg-white transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></div>
                        <span class="relative text-white group-hover:text-black font-bold text-base sm:text-lg">Join Now</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 640px) {
    .animate-scale {
        animation: none;
    }
}

@media (min-width: 768px) {
    .container {
        max-width: 768px;
    }
}

@media (min-width: 1024px) {
    .container {
        max-width: 1024px;
    }
}

@media (min-width: 1280px) {
    .container {
        max-width: 1280px;
    }
}

.animate-fade-in {
    animation: fadeIn 1s ease-out;
}

.animate-fade-in-delay {
    animation: fadeIn 1s ease-out 0.3s both;
}

.animate-fade-in-delay-2 {
    animation: fadeIn 1s ease-out 0.6s both;
}

@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(20px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}
</style>

<!-- Featured Gyms Section -->
<section class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-b from-gray-900 to-black">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-8 sm:mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4">
                Elite Fitness Centers
            </h2>
            <div class="w-16 sm:w-20 lg:w-24 h-1 bg-yellow-400 mx-auto"></div>
        </div>
       
        <!-- Gyms Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <?php foreach ($featured_gyms as $gym): ?>
                <div class="group bg-gray-800 rounded-xl sm:rounded-2xl overflow-hidden transform hover:scale-105 transition-all duration-300">
                    <!-- Image Container -->
                    <div class="relative h-48 sm:h-60 lg:h-72">
                        <img src="./gym/uploads/gym_images/<?= htmlspecialchars($gym['cover_photo']) ?>"
                             alt="<?= htmlspecialchars($gym['name']) ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        
                        <!-- Overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-40 group-hover:bg-opacity-20 transition-all duration-300"></div>
                        
                        <!-- Featured Badge -->
                        <div class="absolute top-3 right-3 sm:top-4 sm:right-4 bg-yellow-400 text-black px-3 py-1 sm:px-4 sm:py-2 rounded-full text-sm sm:text-base font-bold">
                            Featured
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 sm:p-6 lg:p-8">
                        <h3 class="text-xl sm:text-2xl font-bold text-white mb-2 sm:mb-3">
                            <?= htmlspecialchars($gym['name']) ?>
                        </h3>
                        
                        <p class="text-sm sm:text-base text-white mb-3 sm:mb-4">
                            <?= htmlspecialchars($gym['city']) ?>, <?= htmlspecialchars($gym['state']) ?>
                        </p>

                        <!-- Rating -->
                        <div class="flex items-center mb-4 sm:mb-6">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 lg:h-6 lg:w-6 
                                    <?= $i <= round($gym['avg_rating']) ? 'text-yellow-400' : 'text-gray-600' ?>"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                            <span class="ml-2 text-sm sm:text-base text-white">
                                <?= $gym['review_count'] ?> reviews
                            </span>
                        </div>

                        <!-- CTA Button -->
                        <a href="gym_details.php?gym_id=<?= $gym['gym_id'] ?>"
                           class="block w-full bg-yellow-400 hover:bg-yellow-500 text-black text-center py-3 sm:py-4 rounded-lg sm:rounded-xl text-sm sm:text-base font-bold transition-colors duration-300">
                            Explore Now
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- Membership Benefits -->
<section class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-b from-black to-gray-900 relative overflow-hidden">
    <div class="absolute inset-0 bg-pattern opacity-10"></div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-8 sm:mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-100 mb-2 sm:mb-4">
                Premium <span class="text-yellow-400">Benefits</span>
            </h2>
            <p class="text-base sm:text-lg lg:text-xl text-white max-w-xl sm:max-w-2xl mx-auto px-4">
                Experience exclusive advantages designed for your fitness success
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
            <!-- Personal Training -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 hover:bg-yellow-400 transition-all duration-500">
                <div class="h-12 w-12 sm:h-14 sm:w-14 lg:h-16 lg:w-16 bg-yellow-400 group-hover:bg-black rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6">
                    <i class="fas fa-user-friends text-lg sm:text-xl lg:text-2xl text-black group-hover:text-yellow-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-white group-hover:text-black mb-2 sm:mb-3 lg:mb-4">Personal Training</h3>
                <p class="text-sm sm:text-base text-white group-hover:text-black">One-on-one sessions with certified trainers for personalized guidance</p>
            </div>

            <!-- Advanced Equipment -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 hover:bg-yellow-400 transition-all duration-500">
                <div class="h-12 w-12 sm:h-14 sm:w-14 lg:h-16 lg:w-16 bg-yellow-400 group-hover:bg-black rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6">
                    <i class="fas fa-dumbbell text-lg sm:text-xl lg:text-2xl text-black group-hover:text-yellow-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-white group-hover:text-black mb-2 sm:mb-3 lg:mb-4">Premium Equipment</h3>
                <p class="text-sm sm:text-base text-white group-hover:text-black">Access to state-of-the-art fitness equipment and facilities</p>
            </div>

            <!-- Nutrition Planning -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 hover:bg-yellow-400 transition-all duration-500">
                <div class="h-12 w-12 sm:h-14 sm:w-14 lg:h-16 lg:w-16 bg-yellow-400 group-hover:bg-black rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6">
                    <i class="fas fa-apple-alt text-lg sm:text-xl lg:text-2xl text-black group-hover:text-yellow-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-white group-hover:text-black mb-2 sm:mb-3 lg:mb-4">Nutrition Planning</h3>
                <p class="text-sm sm:text-base text-white group-hover:text-black">Customized diet plans and nutritional guidance for optimal results</p>
            </div>

            <!-- Fitness Classes -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 hover:bg-yellow-400 transition-all duration-500">
                <div class="h-12 w-12 sm:h-14 sm:w-14 lg:h-16 lg:w-16 bg-yellow-400 group-hover:bg-black rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6">
                    <i class="fas fa-users text-lg sm:text-xl lg:text-2xl text-black group-hover:text-yellow-400"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-white group-hover:text-black mb-2 sm:mb-3 lg:mb-4">Group Classes</h3>
                <p class="text-sm sm:text-base text-white group-hover:text-black">Join energetic group sessions led by expert instructors</p>
            </div>
        </div>
    </div>
</section>

<section class="relative py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-b from-gray-900 to-black overflow-hidden">
    <div class="absolute inset-0 bg-pattern opacity-10"></div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Section Header -->
        <div class="text-center mb-8 sm:mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-3 sm:mb-4">
                Why Choose <span class="text-yellow-400">FitConnect</span>?
            </h2>
            <div class="w-16 sm:w-20 lg:w-24 h-1 bg-yellow-400 mx-auto"></div>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8 lg:gap-12">
            <!-- Wide Network -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 transform hover:-translate-y-2 transition-all duration-300">
                <div class="w-14 h-14 sm:w-16 sm:h-16 lg:w-20 lg:h-20 bg-yellow-400 rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6 transform -rotate-6 group-hover:rotate-0 transition-transform duration-300">
                    <svg class="h-7 w-7 sm:h-8 sm:w-8 lg:h-10 lg:w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-white mb-3 sm:mb-4">Wide Network</h3>
                <p class="text-sm sm:text-base text-white  leading-relaxed">Access premium fitness centers across India. Connect with the largest network of certified gyms and trainers.</p>
            </div>

            <!-- Flexible Plans -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 transform hover:-translate-y-2 transition-all duration-300">
                <div class="w-14 h-14 sm:w-16 sm:h-16 lg:w-20 lg:h-20 bg-yellow-400 rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6 transform -rotate-6 group-hover:rotate-0 transition-transform duration-300">
                    <svg class="h-7 w-7 sm:h-8 sm:w-8 lg:h-10 lg:w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-white mb-3 sm:mb-4">Flexible Plans</h3>
                <p class="text-sm sm:text-base text-white  leading-relaxed">Customize your fitness journey with flexible membership options. Choose plans that fit your schedule and goals.</p>
            </div>

            <!-- Quality Assured -->
            <div class="group bg-gray-800 bg-opacity-50 backdrop-blur-lg rounded-xl sm:rounded-2xl lg:rounded-3xl p-6 sm:p-7 lg:p-8 transform hover:-translate-y-2 transition-all duration-300">
                <div class="w-14 h-14 sm:w-16 sm:h-16 lg:w-20 lg:h-20 bg-yellow-400 rounded-xl sm:rounded-2xl flex items-center justify-center mb-4 sm:mb-5 lg:mb-6 transform -rotate-6 group-hover:rotate-0 transition-transform duration-300">
                    <svg class="h-7 w-7 sm:h-8 sm:w-8 lg:h-10 lg:w-10 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-white mb-3 sm:mb-4">Quality Assured</h3>
                <p class="text-sm sm:text-base text-white  leading-relaxed">Experience fitness in verified and certified facilities. Every gym meets our strict quality and safety standards.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action with Dynamic Background -->
<section class="py-16 sm:py-20 md:py-24 lg:py-32 bg-gradient-to-r from-yellow-400 via-yellow-500 to-yellow-600 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('assets/images/pattern-light.svg')] opacity-10"></div>
    
    <!-- Content Container -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <!-- Heading -->
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-black mb-4 sm:mb-6 lg:mb-8">
            Start Your Journey Today
        </h2>
        
        <!-- Description -->
        <p class="text-lg sm:text-xl md:text-2xl text-gray-800 mb-8 sm:mb-10 lg:mb-12 max-w-xl sm:max-w-2xl lg:max-w-3xl mx-auto">
            Join the community of fitness enthusiasts and transform your life with professional guidance.
        </p>
        
        <!-- CTA Button -->
        <a href="register.php" 
           class="inline-block bg-black text-yellow-400 
                  px-8 sm:px-10 lg:px-12 
                  py-4 sm:py-5 lg:py-6 
                  rounded-full 
                  text-base sm:text-lg lg:text-xl 
                  font-bold 
                  hover:bg-gray-900 
                  transform hover:scale-105
                  transition-all duration-300
                  shadow-lg hover:shadow-xl">
            Begin Your Transformation
        </a>
    </div>
</section>

<!-- Contact Form Section -->
<section class="py-12 sm:py-16 md:py-20 lg:py-24 bg-gradient-to-b from-gray-900 to-black relative overflow-hidden">
    <div class="absolute inset-0 bg-pattern opacity-10"></div>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Section Header -->
        <div class="text-center mb-8 sm:mb-12 lg:mb-16">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-2 sm:mb-4">
                Get In <span class="text-yellow-400">Touch</span>
            </h2>
            <div class="w-16 sm:w-20 lg:w-24 h-1 bg-yellow-400 mx-auto"></div>
            <p class="mt-4 text-base sm:text-lg text-white max-w-xl mx-auto">
                Have questions? We're here to help and answer any question you might have.
            </p>
        </div>

        <!-- Form Container -->
        <div class="max-w-xl sm:max-w-2xl lg:max-w-3xl mx-auto">
            <form action="process_contact.php" method="POST" class="space-y-4 sm:space-y-6 lg:space-y-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
                    <!-- Name Field -->
                    <div class="group">
                        <label class="block text-yellow-400 text-sm sm:text-base mb-1.5 sm:mb-2">Name</label>
                        <input type="text" name="name" required
                               class="w-full bg-gray-800 border-2 border-gray-700 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base text-white
                                      focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-50
                                      transition-all duration-300">
                    </div>

                    <!-- Email Field -->
                    <div class="group">
                        <label class="block text-yellow-400 text-sm sm:text-base mb-1.5 sm:mb-2">Email</label>
                        <input type="email" name="email" required
                               class="w-full bg-gray-800 border-2 border-gray-700 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base text-white
                                      focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-50
                                      transition-all duration-300">
                    </div>
                </div>

                <!-- Phone Field -->
                <div class="group">
                    <label class="block text-yellow-400 text-sm sm:text-base mb-1.5 sm:mb-2">Phone Number</label>
                    <input type="tel" name="phone" pattern="[0-9]{10}"
                           class="w-full bg-gray-800 border-2 border-gray-700 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base text-white
                                  focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-50
                                  transition-all duration-300">
                </div>

                <!-- Subject Field -->
                <div class="group">
                    <label class="block text-yellow-400 text-sm sm:text-base mb-1.5 sm:mb-2">Subject</label>
                    <select name="subject" required
                            class="w-full bg-gray-800 border-2 border-gray-700 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base text-white
                                   focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-50
                                   transition-all duration-300">
                        <option value="">Select a subject</option>
                        <option value="membership">Membership Inquiry</option>
                        <option value="training">Personal Training</option>
                        <option value="facilities">Facility Information</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Message Field -->
                <div class="group">
                    <label class="block text-yellow-400 text-sm sm:text-base mb-1.5 sm:mb-2">Message</label>
                    <textarea name="message" rows="5" required
                              class="w-full bg-gray-800 border-2 border-gray-700 rounded-lg px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base text-white
                                     focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:ring-opacity-50
                                     transition-all duration-300 resize-none"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-center pt-4">
                    <button type="submit"
                            class="bg-yellow-400 text-black px-8 sm:px-10 lg:px-12 py-3 sm:py-4 rounded-full 
                                   text-sm sm:text-base lg:text-lg font-bold
                                   hover:bg-yellow-500 transform hover:scale-105 
                                   transition-all duration-300 shadow-lg">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>


<!-- Footer with Social Icons -->
 <?php include 'includes/footer.php'; ?>