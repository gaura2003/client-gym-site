-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 22, 2025 at 09:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym-db`
--

-- --------------------------------------------------------

--
-- Table structure for table `class_bookings`
--

CREATE TABLE `class_bookings` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `status` enum('booked','attended','cancelled','missed') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gyms`
--

CREATE TABLE `gyms` (
  `gym_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `cover_photo` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `max_capacity` int(11) NOT NULL,
  `current_occupancy` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `total_revenue` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gyms`
--

INSERT INTO `gyms` (`gym_id`, `owner_id`, `name`, `description`, `address`, `city`, `state`, `country`, `zip_code`, `cover_photo`, `latitude`, `longitude`, `contact_phone`, `contact_email`, `amenities`, `max_capacity`, `current_occupancy`, `rating`, `status`, `total_revenue`) VALUES
(1, 1, 'Body Garage', 'get acces', 'Maharana Pratap colony , maksi Road ,Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456001', 'your fitness center.jpg', NULL, NULL, '08788938434', 'bodygarage@gmail.com', '[\"steam_room\",\"free_weights\",\"personal_training\",\"locker_rooms\",\"nutrition_counseling\"]', 200, 1, NULL, 'active', 0.00),
(2, 2, 'fitness first', 'get premium machines ', 'maksi road , saint paul school', 'ujjain', 'Madhya Pradesh', 'india', '450994', 'your fitness center.jpg', NULL, NULL, '09878887784', 'fitnessfirst@gmail.com', NULL, 200, 0, NULL, 'active', -189.00),
(3, 3, 'Iron Paradise', 'High-intensity training for professionals', 'Rajendra Nagar, Indore', 'indore', 'Madhya Pradesh', 'india', '452012', 'your fitness center.jpg', NULL, NULL, '09876543210', 'ironparadise@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Parking\",\"Personal Training\"]', 150, 5, NULL, 'active', 0.00),
(4, 4, 'Flex Gym', 'Where fitness meets passion', 'Shakti Nagar, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462003', 'your fitness center.jpg', NULL, NULL, '08987654321', 'flexgym@gmail.com', '[\"Personal Training\",\"Group Classes\",\"Showers\"]', 300, 10, NULL, 'active', 0.00),
(5, 5, 'Elite Fitness', 'Premium fitness equipment and coaching', 'Vijay Nagar, Indore', 'indore', 'Madhya Pradesh', 'india', '452010', 'your fitness center.jpg', NULL, NULL, '07865432109', 'elitefitness@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Personal Training\",\"Sauna\"]', 250, 20, NULL, 'active', 27.00),
(6, 6, 'Muscle Factory', 'Strength training and conditioning', 'Maharana Pratap Nagar, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462011', 'your fitness center.jpg', NULL, NULL, '07712345678', 'musclefactory@gmail.com', '[\"Parking\",\"Personal Training\",\"Group Classes\"]', 180, 12, NULL, 'active', 0.00),
(7, 7, 'Fitness Arena', 'State-of-the-art facilities', 'South Tukoganj, Indore', 'indore', 'Madhya Pradesh', 'india', '452001', 'your fitness center.jpg', NULL, NULL, '07654321098', 'fitnessarena@gmail.com', '[\"Locker Rooms\",\"Parking\",\"Showers\"]', 220, 8, NULL, 'active', 0.00),
(8, 8, 'The Gym Palace', 'Experience fitness like royalty', 'Arera Colony, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462016', 'your fitness center.jpg', NULL, NULL, '07543210987', 'gympalace@gmail.com', '[\"Sauna\",\"Group Classes\",\"Personal Training\"]', 200, 6, NULL, 'active', 0.00),
(9, 9, 'Powerhouse Gym', 'Where power meets performance', 'Sindhi Colony, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456010', 'your fitness center.jpg', NULL, NULL, '07432109876', 'powerhousegym@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Parking\"]', 300, 15, NULL, 'active', 0.00),
(10, 10, 'FitZone', 'Your ultimate fitness zone', 'Kolar Road, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462042', 'your fitness center.jpg', NULL, NULL, '07321098765', 'fitzone@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Personal Training\"]', 250, 10, NULL, 'active', 0.00),
(11, 11, 'Pulse Fitness', 'High-energy workouts for everyone', 'MG Road, Indore', 'indore', 'Madhya Pradesh', 'india', '452007', 'your fitness center.jpg', NULL, NULL, '07210987654', 'pulsefitness@gmail.com', '[\"Parking\",\"Personal Training\",\"Group Classes\"]', 180, 7, NULL, 'active', 0.00),
(12, 12, 'Core Fitness', 'Building your core strength', 'Rohit Nagar, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462039', 'your fitness center.jpg', NULL, NULL, '07109876543', 'corefitness@gmail.com', '[\"Sauna\",\"Showers\",\"Group Classes\"]', 200, 9, NULL, 'active', 0.00),
(13, 13, 'Peak Performance Gym', 'Achieve peak performance', 'Freeganj, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456006', 'your fitness center.jpg', NULL, NULL, '07098765432', 'peakperformance@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Personal Training\"]', 150, 5, NULL, 'active', 0.00),
(14, 14, 'Dynamic Fitness', 'Dynamic workouts for dynamic people', 'Tilak Nagar, Indore', 'indore', 'Madhya Pradesh', 'india', '452018', 'your fitness center.jpg', NULL, NULL, '06987654321', 'dynamicfitness@gmail.com', '[\"Parking\",\"Personal Training\",\"Group Classes\"]', 240, 12, NULL, 'active', 0.00),
(15, 15, 'Infinity Fitness', 'Push your limits to infinity', 'TT Nagar, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462003', 'your fitness center.jpg', NULL, NULL, '06876543210', 'infinityfitness@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Sauna\"]', 300, 18, NULL, 'active', 0.00),
(16, 16, 'Vigor Gym', 'Revitalize your vigor', 'Dewas Road, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456003', 'your fitness center.jpg', NULL, NULL, '06765432109', 'vigorgym@gmail.com', '[\"Locker Rooms\",\"Parking\",\"Group Classes\"]', 220, 13, NULL, 'active', 0.00),
(17, 17, 'Prime Fitness', 'Your primary destination for fitness', 'Geeta Bhawan, Indore', 'indore', 'Madhya Pradesh', 'india', '452001', 'your fitness center.jpg', NULL, NULL, '06654321098', 'primefitness@gmail.com', '[\"Parking\",\"Personal Training\",\"Showers\"]', 280, 11, NULL, 'active', 0.00),
(18, 18, 'Momentum Gym', 'Keep the momentum going', 'New Market, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462003', 'your fitness center.jpg', NULL, NULL, '06543210987', 'momentumgym@gmail.com', '[\"Group Classes\",\"Personal Training\",\"Sauna\"]', 250, 9, NULL, 'active', 0.00),
(19, 19, 'Energy Hub', 'Unleash your energy', 'Nanakheda, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456010', 'your fitness center.jpg', NULL, NULL, '06432109876', 'energyhub@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Parking\"]', 200, 8, NULL, 'active', 162.00),
(20, 20, 'Athlete Gym', 'Train like an athlete', 'Manorama Ganj, Indore', 'indore', 'Madhya Pradesh', 'india', '452007', 'your fitness center.jpg', NULL, NULL, '06321098765', 'athletegym@gmail.com', '[\"Locker Rooms\",\"Parking\",\"Personal Training\"]', 300, 20, NULL, 'active', 0.00),
(21, 21, 'Champion Gym', 'Become a champion', 'Shivaji Nagar, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462016', 'your fitness center.jpg', NULL, NULL, '06210987654', 'championgym@gmail.com', '[\"Sauna\",\"Group Classes\",\"Showers\"]', 220, 10, NULL, 'active', 0.00),
(22, 22, 'Torque Fitness', 'Torque your fitness to the next level', 'Mahakaleshwar Road, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456001', 'your fitness center.jpg', NULL, NULL, '06109876543', 'torquefitness@gmail.com', '[\"Parking\",\"Personal Training\",\"Group Classes\"]', 230, 15, NULL, 'active', 0.00),
(23, 23, 'PowerFit', 'Empowering your fitness journey', 'AB Road, Indore', 'indore', 'Madhya Pradesh', 'india', '452001', 'your fitness center.jpg', NULL, NULL, '06098765432', 'powerfit@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Personal Training\"]', 250, 18, NULL, 'active', 0.00),
(24, 24, 'Balance Fitness', 'Achieve fitness balance', 'BHEL Township, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462022', 'your fitness center.jpg', NULL, NULL, '05987654321', 'balancefitness@gmail.com', '[\"Locker Rooms\",\"Parking\",\"Sauna\"]', 280, 14, NULL, 'active', 0.00),
(25, 25, 'Strength Hub', 'Strength training specialists', 'Rishi Nagar, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456010', 'your fitness center.jpg', NULL, NULL, '05876543210', 'strengthhub@gmail.com', '[\"Parking\",\"Personal Training\",\"Group Classes\"]', 200, 9, NULL, 'active', 0.00),
(26, 26, 'Endurance Gym', 'Build your endurance', 'Patnipura, Indore', 'indore', 'Madhya Pradesh', 'india', '452002', 'your fitness center.jpg', NULL, NULL, '05765432109', 'endurancegym@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Personal Training\"]', 300, 15, NULL, 'active', 0.00),
(27, 27, 'Zen Fitness', 'Fitness with a zen mindset', 'Shyamla Hills, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462002', 'your fitness center.jpg', NULL, NULL, '05654321098', 'zenfitness@gmail.com', '[\"Sauna\",\"Group Classes\",\"Showers\"]', 200, 8, NULL, 'active', 0.00),
(28, 28, 'Lift Gym', 'Lift your way to fitness', 'Chamunda Mata Square, Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456005', 'your fitness center.jpg', NULL, NULL, '05543210987', 'liftgym@gmail.com', '[\"Locker Rooms\",\"Parking\",\"Personal Training\"]', 250, 4, NULL, 'active', 0.00),
(29, 29, 'Shred Gym', 'Shred fat and build muscle', 'Old Palasia, Indore', 'indore', 'Madhya Pradesh', 'india', '452018', 'your fitness center.jpg', NULL, NULL, '05432109876', 'shredgym@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Group Classes\"]', 250, 14, NULL, 'active', 0.00),
(30, 30, 'Titan Fitness', 'Be strong as a titan', 'Ashoka Garden, Bhopal', 'bhopal', 'Madhya Pradesh', 'india', '462023', 'your fitness center.jpg', NULL, NULL, '05321098765', 'titanfitness@gmail.com', '[\"Parking\",\"Personal Training\",\"Showers\"]', 200, 10, NULL, 'active', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `gym_classes`
--

CREATE TABLE `gym_classes` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `current_bookings` int(11) DEFAULT 0,
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule`)),
  `duration_minutes` int(11) NOT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `status` enum('active','cancelled','completed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_equipment`
--

CREATE TABLE `gym_equipment` (
  `equipment_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `equipment_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_equipment`
--

INSERT INTO `gym_equipment` (`equipment_id`, `gym_id`, `equipment_name`, `quantity`, `image`) VALUES
(1, 1, 'cross cable', 2, 'cross cable machine.jpg'),
(2, 1, 'Treadmill', 5, 'treadmill.webp'),
(3, 1, 'Willie Spinbike', 5, 'willie spinbike.webp'),
(4, 2, 'Rowing Machine', 20, 'cross cable machine.jpg'),
(5, 2, 'Rowing Machine', 3, 'treadmill.webp'),
(6, 2, 'Rowing Machine', 4, 'willie spinbike.webp'),
(7, 3, 'Smith Machine', 2, 'cross cable machine.jpg'),
(8, 3, 'Elliptical Trainer', 3, 'treadmill.webp'),
(9, 3, 'Flat Bench', 6, 'willie spinbike.webp'),
(10, 4, 'Incline Bench', 4, 'cross cable machine.jpg'),
(11, 4, 'Pull-Up Bar', 3, 'treadmill.webp'),
(12, 4, 'Preacher Curl Bench', 2, 'willie spinbike.webp'),
(13, 5, 'Barbells', 15, 'cross cable machine.jpg'),
(14, 5, 'Pec Deck Machine', 2, 'treadmill.webp'),
(15, 5, 'Stepper', 4, 'willie spinbike.webp'),
(16, 6, 'Lat Pulldown Machine', 3, 'cross cable machine.jpg'),
(17, 6, 'Chest Press Machine', 2, 'treadmill.webp'),
(18, 6, 'Adjustable Bench', 5, 'willie spinbike.webp'),
(19, 7, 'Kettlebells', 25, 'cross cable machine.jpg'),
(20, 7, 'Resistance Bands', 30, 'treadmill.webp'),
(21, 7, 'Power Rack', 2, 'willie spinbike.webp'),
(22, 8, 'Foam Rollers', 12, 'cross cable machine.jpg'),
(23, 8, 'Leg Curl Machine', 3, 'treadmill.webp'),
(24, 8, 'Tricep Dip Machine', 2, 'willie spinbike.webp'),
(25, 9, 'Battle Ropes', 4, 'cross cable machine.jpg'),
(26, 9, 'Spin Bikes', 8, 'treadmill.webp'),
(27, 9, 'Medicine Balls', 10, 'willie spinbike.webp'),
(28, 10, 'Leg Extension Machine', 3, 'cross cable machine.jpg'),
(29, 10, 'Pull-Over Machine', 2, 'treadmill.webp'),
(30, 10, 'Calf Raise Machine', 3, 'willie spinbike.webp'),
(31, 11, 'Rowing Machine', 4, 'cross cable machine.jpg'),
(32, 11, 'Squat Rack', 2, 'treadmill.webp'),
(33, 11, 'Glute Bridge Machine', 3, 'willie spinbike.webp'),
(34, 12, 'Punching Bag', 5, 'cross cable machine.jpg'),
(35, 12, 'Cable Crossover', 3, 'treadmill.webp'),
(36, 12, 'Incline Dumbbell Press', 6, 'willie spinbike.webp'),
(37, 13, 'Ab Crunch Machine', 2, 'cross cable machine.jpg'),
(38, 13, 'Back Extension Bench', 4, 'treadmill.webp'),
(39, 13, 'Step Mill', 1, 'willie spinbike.webp'),
(40, 14, 'Chest Fly Machine', 3, 'cross cable machine.jpg'),
(41, 14, 'Seated Row Machine', 2, 'treadmill.webp'),
(42, 14, 'Weighted Vest', 10, 'willie spinbike.webp'),
(43, 15, 'Hyperextension Bench', 2, 'cross cable machine.jpg'),
(44, 15, 'Vertical Knee Raise', 3, 'treadmill.webp'),
(45, 15, 'Hack Squat Machine', 2, 'willie spinbike.webp'),
(46, 16, 'Elliptical Cross Trainer', 3, 'cross cable machine.jpg'),
(47, 16, 'Air Bike', 4, 'treadmill.webp'),
(48, 16, 'Stair Climber', 2, 'willie spinbike.webp'),
(49, 17, 'Landmine Attachment', 1, 'cross cable machine.jpg'),
(50, 17, 'Powerlifting Platform', 2, 'treadmill.webp'),
(51, 17, 'Hex Dumbbells', 20, 'willie spinbike.webp'),
(52, 18, 'Adjustable Cable Machine', 2, 'cross cable machine.jpg'),
(53, 18, 'Incline Leg Press', 3, 'treadmill.webp'),
(54, 18, 'Weighted Jump Rope', 15, 'willie spinbike.webp'),
(55, 19, 'Speed Ladder', 10, 'cross cable machine.jpg'),
(56, 19, 'Plyometric Boxes', 6, 'treadmill.webp'),
(57, 19, 'Sandbags', 12, 'willie spinbike.webp'),
(58, 20, 'Sled Push', 1, 'cross cable machine.jpg'),
(59, 20, 'Battle Rope Anchor', 4, 'treadmill.webp'),
(60, 20, 'Agility Cones', 30, 'willie spinbike.webp');

-- --------------------------------------------------------

--
-- Table structure for table `gym_images`
--

CREATE TABLE `gym_images` (
  `image_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_cover` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_images`
--

INSERT INTO `gym_images` (`image_id`, `gym_id`, `image_path`, `is_cover`) VALUES
(1, 1, 'your fitness center.jpg', 1),
(2, 2, 'Screenshot 2024-11-13 002057.png', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gym_membership_plans`
--

CREATE TABLE `gym_membership_plans` (
  `plan_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `tier` enum('Tier 1','Tier 2','Tier 3') NOT NULL,
  `duration` enum('Daily','Weekly','Monthly','Yearly') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `inclusions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_membership_plans`
--

INSERT INTO `gym_membership_plans` (`plan_id`, `gym_id`, `tier`, `duration`, `price`, `inclusions`) VALUES
(1, 1, 'Tier 1', 'Daily', 120.00, 'access to GYM only '),
(2, 1, 'Tier 1', 'Weekly', 750.00, 'access Gym + Steam Bath'),
(3, 1, 'Tier 1', 'Monthly', 2500.00, 'Access Gym + Steam Bath'),
(4, 1, 'Tier 1', 'Yearly', 10000.00, 'Access Gym + Steam Bath'),
(5, 2, 'Tier 1', 'Daily', 25.00, 'access all machine except treadmill'),
(6, 2, 'Tier 1', 'Weekly', 180.00, 'access all machine except treadmill'),
(7, 2, 'Tier 1', 'Monthly', 600.00, 'access all machine except treadmill'),
(8, 2, 'Tier 1', 'Monthly', 1000.00, 'access all machine + treadmill'),
(9, 2, 'Tier 1', 'Yearly', 7000.00, 'access all machine except treadmill'),
(10, 2, 'Tier 1', 'Yearly', 9000.00, 'access all machine + treadmill'),
(11, 3, 'Tier 1', 'Daily', 50.00, 'Access to gym floor and basic equipment'),
(12, 3, 'Tier 1', 'Weekly', 300.00, 'Access to gym floor and basic equipment'),
(13, 3, 'Tier 1', 'Monthly', 1000.00, 'Access to gym floor and personal trainer sessions'),
(14, 3, 'Tier 1', 'Yearly', 8000.00, 'Full access with personal trainer and spa'),
(15, 4, 'Tier 2', 'Daily', 75.00, 'Access to premium equipment and steam bath'),
(16, 4, 'Tier 2', 'Weekly', 400.00, 'Access to premium equipment and steam bath'),
(17, 4, 'Tier 2', 'Monthly', 1500.00, 'Full access to gym and sauna'),
(18, 4, 'Tier 2', 'Yearly', 12000.00, 'Full access with personal trainer and diet consultation'),
(19, 5, 'Tier 3', 'Daily', 30.00, 'Basic gym access only'),
(20, 5, 'Tier 3', 'Weekly', 200.00, 'Basic gym access only'),
(21, 5, 'Tier 3', 'Monthly', 700.00, 'Basic gym access only'),
(22, 5, 'Tier 3', 'Yearly', 6000.00, 'Full access with additional classes'),
(23, 6, 'Tier 1', 'Daily', 40.00, 'Basic floor access only'),
(24, 6, 'Tier 1', 'Weekly', 250.00, 'Basic floor access only'),
(25, 6, 'Tier 1', 'Monthly', 850.00, 'Access to gym and group classes'),
(26, 6, 'Tier 1', 'Yearly', 7500.00, 'Full access with group classes and steam bath'),
(27, 7, 'Tier 2', 'Daily', 60.00, 'Access to premium equipment'),
(28, 7, 'Tier 2', 'Weekly', 350.00, 'Access to premium equipment and group classes'),
(29, 7, 'Tier 2', 'Monthly', 1200.00, 'Full access to gym, group classes, and personal trainer'),
(30, 7, 'Tier 2', 'Yearly', 10000.00, 'Full access with personal trainer and VIP lounge'),
(31, 8, 'Tier 3', 'Daily', 20.00, 'Access to basic equipment only'),
(32, 8, 'Tier 3', 'Weekly', 150.00, 'Access to basic equipment and gym floor'),
(33, 8, 'Tier 3', 'Monthly', 600.00, 'Access to gym floor and steam bath'),
(34, 8, 'Tier 3', 'Yearly', 5500.00, 'Full access with diet consultation'),
(35, 9, 'Tier 1', 'Daily', 35.00, 'Access to all basic machines'),
(36, 9, 'Tier 1', 'Weekly', 220.00, 'Access to all basic machines'),
(37, 9, 'Tier 1', 'Monthly', 800.00, 'Access to gym, classes, and basic trainer'),
(38, 9, 'Tier 1', 'Yearly', 7000.00, 'Full access with gym trainer and diet plan'),
(39, 10, 'Tier 2', 'Daily', 65.00, 'Premium gym floor access'),
(40, 10, 'Tier 2', 'Weekly', 370.00, 'Access to gym and premium classes'),
(41, 10, 'Tier 2', 'Monthly', 1400.00, 'Full access to gym and premium amenities'),
(42, 10, 'Tier 2', 'Yearly', 11500.00, 'Full access with premium trainer and spa services'),
(43, 11, 'Tier 1', 'Daily', 45.00, 'Access to gym floor only'),
(44, 11, 'Tier 1', 'Weekly', 280.00, 'Access to gym floor and basic equipment'),
(45, 11, 'Tier 1', 'Monthly', 950.00, 'Gym access with personal trainer once a week'),
(46, 11, 'Tier 1', 'Yearly', 8500.00, 'Full gym access with personal trainer and steam bath'),
(47, 12, 'Tier 2', 'Daily', 70.00, 'Access to premium equipment'),
(48, 12, 'Tier 2', 'Weekly', 400.00, 'Access to gym floor and sauna'),
(49, 12, 'Tier 2', 'Monthly', 1400.00, 'Full access with premium trainer'),
(50, 12, 'Tier 2', 'Yearly', 11000.00, 'Full access, steam bath, and diet planning'),
(51, 13, 'Tier 3', 'Daily', 25.00, 'Basic gym access only'),
(52, 13, 'Tier 3', 'Weekly', 175.00, 'Basic gym access only'),
(53, 13, 'Tier 3', 'Monthly', 700.00, 'Access to gym floor and group classes'),
(54, 13, 'Tier 3', 'Yearly', 6000.00, 'Full access with additional classes and amenities'),
(55, 14, 'Tier 1', 'Daily', 50.00, 'Access to gym floor and cardio equipment'),
(56, 14, 'Tier 1', 'Weekly', 300.00, 'Access to gym floor and cardio equipment'),
(57, 14, 'Tier 1', 'Monthly', 1000.00, 'Gym floor and group classes'),
(58, 14, 'Tier 1', 'Yearly', 9000.00, 'Full access with classes and trainer'),
(59, 15, 'Tier 2', 'Daily', 75.00, 'Premium gym access with sauna'),
(60, 15, 'Tier 2', 'Weekly', 420.00, 'Access to premium equipment and sauna'),
(61, 15, 'Tier 2', 'Monthly', 1500.00, 'Full access to gym and personal trainer'),
(62, 15, 'Tier 2', 'Yearly', 12500.00, 'Full access with trainer and additional services'),
(63, 16, 'Tier 3', 'Daily', 30.00, 'Gym floor and basic equipment'),
(64, 16, 'Tier 3', 'Weekly', 200.00, 'Gym floor and group classes'),
(65, 16, 'Tier 3', 'Monthly', 750.00, 'Access to gym and group classes'),
(66, 16, 'Tier 3', 'Yearly', 6500.00, 'Full access with special amenities'),
(67, 17, 'Tier 1', 'Daily', 40.00, 'Basic gym floor access'),
(68, 17, 'Tier 1', 'Weekly', 260.00, 'Basic gym floor access'),
(69, 17, 'Tier 1', 'Monthly', 850.00, 'Gym floor with trainer and group classes'),
(70, 17, 'Tier 1', 'Yearly', 7500.00, 'Full access with trainer and diet plan'),
(71, 18, 'Tier 2', 'Daily', 65.00, 'Premium gym access'),
(72, 18, 'Tier 2', 'Weekly', 350.00, 'Premium gym floor and trainer access'),
(73, 18, 'Tier 2', 'Monthly', 1350.00, 'Full access to gym with trainer'),
(74, 18, 'Tier 2', 'Yearly', 11500.00, 'Full access and premium services'),
(75, 19, 'Tier 3', 'Daily', 20.00, 'Basic floor access only'),
(76, 19, 'Tier 3', 'Weekly', 150.00, 'Basic floor access and classes'),
(77, 19, 'Tier 3', 'Monthly', 600.00, 'Full access to gym floor and classes'),
(78, 19, 'Tier 3', 'Yearly', 5500.00, 'Full access with additional services'),
(79, 20, 'Tier 1', 'Daily', 35.00, 'Access to basic gym equipment'),
(80, 20, 'Tier 1', 'Weekly', 200.00, 'Gym floor and group classes'),
(81, 20, 'Tier 1', 'Monthly', 850.00, 'Gym floor and personal trainer once a week'),
(82, 20, 'Tier 1', 'Yearly', 7500.00, 'Full access with trainer and diet services'),
(83, 21, 'Tier 2', 'Daily', 70.00, 'Access to premium gym floor'),
(84, 21, 'Tier 2', 'Weekly', 400.00, 'Access to premium gym floor and sauna'),
(85, 21, 'Tier 2', 'Monthly', 1400.00, 'Full access to gym and premium services'),
(86, 21, 'Tier 2', 'Yearly', 12000.00, 'Full access with trainer and additional perks'),
(87, 22, 'Tier 3', 'Daily', 30.00, 'Gym floor and basic equipment'),
(88, 22, 'Tier 3', 'Weekly', 180.00, 'Gym floor and group classes'),
(89, 22, 'Tier 3', 'Monthly', 700.00, 'Gym floor and additional classes'),
(90, 22, 'Tier 3', 'Yearly', 6500.00, 'Full access with diet consultations'),
(91, 23, 'Tier 1', 'Daily', 45.00, 'Gym access only'),
(92, 23, 'Tier 1', 'Weekly', 280.00, 'Gym access and basic equipment'),
(93, 23, 'Tier 1', 'Monthly', 950.00, 'Gym and group classes'),
(94, 23, 'Tier 1', 'Yearly', 8500.00, 'Full access with trainer and classes'),
(95, 24, 'Tier 2', 'Daily', 75.00, 'Premium gym access'),
(96, 24, 'Tier 2', 'Weekly', 420.00, 'Premium gym and sauna access'),
(97, 24, 'Tier 2', 'Monthly', 1600.00, 'Full gym access with trainer'),
(98, 24, 'Tier 2', 'Yearly', 13000.00, 'Full access with trainer and extra perks'),
(99, 25, 'Tier 3', 'Daily', 25.00, 'Basic floor and equipment access'),
(100, 25, 'Tier 3', 'Weekly', 170.00, 'Gym floor access only'),
(101, 25, 'Tier 3', 'Monthly', 750.00, 'Gym floor and classes'),
(102, 25, 'Tier 3', 'Yearly', 6500.00, 'Full access with group classes and extras'),
(103, 26, 'Tier 1', 'Daily', 35.00, 'Gym floor only'),
(104, 26, 'Tier 1', 'Weekly', 250.00, 'Gym floor and cardio machines'),
(105, 26, 'Tier 1', 'Monthly', 850.00, 'Gym and basic trainer access'),
(106, 26, 'Tier 1', 'Yearly', 7500.00, 'Full access with trainer and sauna'),
(107, 27, 'Tier 2', 'Daily', 65.00, 'Premium floor access'),
(108, 27, 'Tier 2', 'Weekly', 360.00, 'Premium floor and trainer access'),
(109, 27, 'Tier 2', 'Monthly', 1400.00, 'Full access with trainer'),
(110, 27, 'Tier 2', 'Yearly', 12000.00, 'Full access with premium trainer and services'),
(111, 28, 'Tier 3', 'Daily', 20.00, 'Basic gym floor access'),
(112, 28, 'Tier 3', 'Weekly', 150.00, 'Basic gym floor and classes'),
(113, 28, 'Tier 3', 'Monthly', 600.00, 'Gym floor and group classes'),
(114, 28, 'Tier 3', 'Yearly', 5500.00, 'Full access with extra amenities');

-- --------------------------------------------------------

--
-- Table structure for table `gym_operating_hours`
--

CREATE TABLE `gym_operating_hours` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `day` enum('Daily','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `morning_open_time` time NOT NULL,
  `morning_close_time` time NOT NULL,
  `evening_open_time` time NOT NULL,
  `evening_close_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_operating_hours`
--

INSERT INTO `gym_operating_hours` (`id`, `gym_id`, `day`, `morning_open_time`, `morning_close_time`, `evening_open_time`, `evening_close_time`) VALUES
(1, 1, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:00:00'),
(2, 2, 'Daily', '05:00:00', '10:00:00', '16:00:00', '22:00:00'),
(3, 3, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:00:00'),
(4, 4, 'Daily', '05:30:00', '10:30:00', '16:30:00', '23:00:00'),
(5, 5, 'Daily', '06:00:00', '12:00:00', '15:00:00', '21:00:00'),
(6, 6, 'Daily', '06:00:00', '11:00:00', '17:00:00', '22:00:00'),
(7, 7, 'Daily', '05:30:00', '10:30:00', '16:00:00', '21:30:00'),
(8, 8, 'Daily', '06:00:00', '12:00:00', '16:30:00', '22:00:00'),
(9, 9, 'Daily', '06:00:00', '11:00:00', '15:30:00', '22:30:00'),
(10, 10, 'Daily', '05:30:00', '10:00:00', '16:00:00', '21:30:00'),
(11, 11, 'Daily', '06:00:00', '11:30:00', '16:30:00', '22:00:00'),
(12, 12, 'Daily', '06:30:00', '11:30:00', '16:00:00', '22:30:00'),
(13, 13, 'Daily', '06:00:00', '10:30:00', '15:30:00', '21:00:00'),
(14, 14, 'Daily', '05:30:00', '11:00:00', '16:00:00', '22:00:00'),
(15, 15, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:00:00'),
(16, 16, 'Daily', '06:00:00', '12:00:00', '16:30:00', '22:30:00'),
(17, 17, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:00:00'),
(18, 18, 'Daily', '06:00:00', '11:00:00', '16:30:00', '22:00:00'),
(19, 19, 'Daily', '06:00:00', '11:00:00', '16:00:00', '21:30:00'),
(20, 20, 'Daily', '06:30:00', '11:30:00', '16:30:00', '22:30:00'),
(21, 21, 'Daily', '06:00:00', '11:00:00', '16:00:00', '21:30:00'),
(22, 22, 'Daily', '06:00:00', '11:00:00', '16:30:00', '22:00:00'),
(23, 23, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:00:00'),
(24, 24, 'Daily', '05:30:00', '10:30:00', '16:00:00', '22:00:00'),
(25, 25, 'Daily', '06:00:00', '12:00:00', '16:00:00', '22:00:00'),
(26, 26, 'Daily', '06:00:00', '10:30:00', '15:30:00', '22:00:00'),
(27, 27, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:30:00'),
(28, 28, 'Daily', '06:00:00', '11:00:00', '16:00:00', '22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `gym_owners`
--

CREATE TABLE `gym_owners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `country` varchar(100) NOT NULL,
  `zip_code` varchar(20) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_owners`
--

INSERT INTO `gym_owners` (`id`, `name`, `email`, `phone`, `password_hash`, `is_verified`, `is_approved`, `created_at`, `address`, `city`, `state`, `country`, `zip_code`, `profile_picture`) VALUES
(1, 'Raghav Rai', 'raghavrai@gmail.com', '08788938434', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-15 16:14:38', 'nya poora', 'ujjain', 'Madhya Pradesh', 'India', '456001', 'uploads/Screenshot 2024-12-20 151330.png'),
(2, 'rahul kumawat', 'rahulkumawat1@gmail.com', '09878887784', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 08:20:05', 'maksi road , saint paul school', 'ujjain', 'Madhya Pradesh', 'India', '450994', 'uploads/Screenshot 2024-12-19 235302.png'),
(3, 'Amit Sharma', 'amit.sharma3@gmail.com', '09123567890', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 03:45:30', 'shankar colony', 'indore', 'Madhya Pradesh', 'India', '452001', 'uploads/profile_amit.png'),
(4, 'Pooja Mehta', 'pooja.mehta4@gmail.com', '08765432123', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 04:55:40', 'shahpura', 'bhopal', 'Madhya Pradesh', 'India', '462002', 'uploads/profile_pooja.png'),
(5, 'Vikram Patel', 'vikram.patel5@gmail.com', '09876543210', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 06:00:00', 'palasia', 'indore', 'Madhya Pradesh', 'India', '452002', 'uploads/profile_vikram.png'),
(6, 'Anjali Yadav', 'anjali.yadav6@gmail.com', '07890123456', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 06:40:15', 'gandhi nagar', 'ujjain', 'Madhya Pradesh', 'India', '456002', 'uploads/profile_anjali.png'),
(7, 'Karan Singh', 'karan.singh7@gmail.com', '08567123456', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 07:50:30', 'rajsi nagar', 'bhopal', 'Madhya Pradesh', 'India', '462003', 'uploads/profile_karan.png'),
(8, 'Suman Gupta', 'suman.gupta8@gmail.com', '07912345678', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 08:55:40', 'madhav nagar', 'indore', 'Madhya Pradesh', 'India', '452003', 'uploads/profile_suman.png'),
(9, 'Naveen Kumar', 'naveen.kumar9@gmail.com', '09087654321', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 10:00:50', 'narmada road', 'bhopal', 'Madhya Pradesh', 'India', '462004', 'uploads/profile_naveen.png'),
(10, 'Shruti Sharma', 'shruti.sharma10@gmail.com', '08787766554', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 10:35:30', 'kolar road', 'ujjain', 'Madhya Pradesh', 'India', '456003', 'uploads/profile_shruti.png'),
(11, 'Gaurav Joshi', 'gaurav.joshi11@gmail.com', '07823456789', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 11:40:00', 'vijay nagar', 'indore', 'Madhya Pradesh', 'India', '452004', 'uploads/profile_gaurav.png'),
(12, 'Ritika Sharma', 'ritika.sharma12@gmail.com', '07934567890', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 12:30:10', 'new market', 'bhopal', 'Madhya Pradesh', 'India', '462005', 'uploads/profile_ritika.png'),
(13, 'Rajeev Verma', 'rajeeve.verma13@gmail.com', '09123456789', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 13:45:30', 'moti masjid', 'ujjain', 'Madhya Pradesh', 'India', '456004', 'uploads/profile_rajeeve.png'),
(14, 'Priya Deshmukh', 'priya.deshmukh14@gmail.com', '08765432101', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 14:50:45', 'shree nagar', 'indore', 'Madhya Pradesh', 'India', '452005', 'uploads/profile_priya.png'),
(15, 'Sandeep Tiwari', 'sandeep.tiwari15@gmail.com', '07892345678', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 16:00:00', 'gandhi path', 'bhopal', 'Madhya Pradesh', 'India', '462006', 'uploads/profile_sandeep.png'),
(16, 'Isha Singh', 'isha.singh16@gmail.com', '07987654321', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 16:45:30', 'indraprasth colony', 'ujjain', 'Madhya Pradesh', 'India', '456005', 'uploads/profile_isha.png'),
(17, 'Gautam Patel', 'gautam.patel17@gmail.com', '08573456789', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 17:30:00', 'sultanabad', 'indore', 'Madhya Pradesh', 'India', '452006', 'uploads/profile_gautam.png'),
(18, 'Komal Chauhan', 'komal.chauhan18@gmail.com', '07876543210', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 02:30:00', 'janta colony', 'bhopal', 'Madhya Pradesh', 'India', '462007', 'uploads/profile_komal.png'),
(19, 'Manish Mehta', 'manish.mehta19@gmail.com', '07986543212', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 03:50:10', 'shree ganesh nagar', 'ujjain', 'Madhya Pradesh', 'India', '456006', 'uploads/profile_manish.png'),
(20, 'Nisha Rani', 'nisha.rani20@gmail.com', '09087654356', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 05:00:20', 'sadar bazaar', 'indore', 'Madhya Pradesh', 'India', '452007', 'uploads/profile_nisha.png'),
(21, 'Alok Kumar', 'alok.kumar21@gmail.com', '08561234567', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 06:15:30', 'new colony', 'bhopal', 'Madhya Pradesh', 'India', '462008', 'uploads/profile_alok.png'),
(22, 'Sunita Yadav', 'sunita.yadav22@gmail.com', '07878901234', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 06:55:40', 'rajeshwar nagar', 'ujjain', 'Madhya Pradesh', 'India', '456007', 'uploads/profile_sunita.png'),
(23, 'Vishal Rathi', 'vishal.rathi23@gmail.com', '09054367891', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 07:40:50', 'suraj nagar', 'indore', 'Madhya Pradesh', 'India', '452008', 'uploads/profile_vishal.png'),
(24, 'Tanuj Verma', 'tanuj.verma24@gmail.com', '09876543212', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 08:50:00', 'shivaji colony', 'bhopal', 'Madhya Pradesh', 'India', '462009', 'uploads/profile_tanuj.png'),
(25, 'Vandana Agarwal', 'vandana.agarwal25@gmail.com', '08765432112', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 08:50:20', 'shivpuri', 'shivpuri', 'Madhya Pradesh', 'India', '463003', '/uploads/'),
(26, 'Aryan Bhargava', 'aryan.bhargava26@gmail.com', '07881234567', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 09:40:15', 'rajendra nagar', 'ujjain', 'Madhya Pradesh', 'India', '456008', 'uploads/profile_aryan.png'),
(27, 'Neha Jaiswal', 'neha.jaiswal27@gmail.com', '07981234567', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 11:00:25', 'shivpuri colony', 'indore', 'Madhya Pradesh', 'India', '452009', 'uploads/profile_neha.png'),
(28, 'Aditya Singh', 'aditya.singh28@gmail.com', '09086754321', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 12:15:35', 'alirajpur road', 'bhopal', 'Madhya Pradesh', 'India', '462010', 'uploads/profile_aditya.png'),
(29, 'Meenal Kapoor', 'meenal.kapoor29@gmail.com', '07899012345', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 12:50:45', 'victoria town', 'ujjain', 'Madhya Pradesh', 'India', '456009', 'uploads/profile_meenal.png'),
(30, 'Rohit Malhotra', 'rohit.malhotra30@gmail.com', '08765432167', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-18 13:35:00', 'tilak nagar', 'indore', 'Madhya Pradesh', 'India', '452010', 'uploads/profile_rohit.png');

-- --------------------------------------------------------

--
-- Table structure for table `gym_revenue`
--

CREATE TABLE `gym_revenue` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gym_revenue_distribution`
--

CREATE TABLE `gym_revenue_distribution` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `distribution_date` date NOT NULL,
  `payment_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `attempt_time`) VALUES
(1, 'raghavrai@gmail.com', '2025-01-15 22:04:35'),
(2, 'raghavrai@gmail.com', '2025-01-15 22:05:00'),
(3, 'raghavrai@gmail.com', '2025-01-15 22:05:03'),
(4, 'raghavrai@gmail.com', '2025-01-15 22:07:17'),
(11, 'raghavrai@gmail.com', '2025-01-21 00:37:11'),
(13, 'raghavrai@gmail.com', '2025-01-22 11:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `visit_limit` int(11) DEFAULT NULL,
  `features` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`id`, `name`, `description`, `price`, `duration_days`, `visit_limit`, `features`, `status`, `created_at`) VALUES
(1, 'basic plan', 'Access All 3 Tier GYM', 599.00, 30, NULL, '[\"Access All GYM \\r\",\"Access any City GYM\"]', 'active', '2025-01-15 15:43:29'),
(2, 'basic plan', 'Access All 2 Tier GYM', 999.00, 30, NULL, '[\"Access All GYM \\r\",\"Access any City GYM\"]', 'active', '2025-01-15 15:44:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread',
  `gym_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `membership_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `gym_id`, `rating`, `comment`, `visit_date`, `status`, `created_at`) VALUES
(1, 1, 1, 4, 'fggfgfh', NULL, 'approved', '2025-01-15 19:41:59');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `activity_type` enum('gym_visit','class','personal_training') DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled','missed') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recurring` enum('none','daily','weekly','monthly') DEFAULT 'none',
  `recurring_until` date DEFAULT NULL,
  `days_of_week` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`days_of_week`)),
  `reminder_time` int(11) DEFAULT 30,
  `last_reminder_sent` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('member','gym_partner','admin') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `phone`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'user', 'user@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$VjI1YlZ4aXhIRjRCS1NPTg$TUXAiUir7MFzoUH7f17rrQvogFFIU215pVzFfbu2DDM', 'member', '8799877978', NULL, 'active', '2025-01-15 14:25:19', '2025-01-15 14:25:19'),
(2, 'admin', 'admin@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$WmI5L1FNbnY0OFZ4L3BPaQ$WhMo3Xe51DmSOt07J8lUSDs73ZhkSdjoVvTouCgM1rk', 'admin', '7097923443', NULL, 'active', '2025-01-15 14:33:08', '2025-01-15 14:35:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_memberships`
--

CREATE TABLE `user_memberships` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `payment_status` enum('paid','pending','failed') DEFAULT 'pending',
  `auto_renewal` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bank_account` varchar(255) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class_bookings`
--
ALTER TABLE `class_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gyms`
--
ALTER TABLE `gyms`
  ADD PRIMARY KEY (`gym_id`);

--
-- Indexes for table `gym_classes`
--
ALTER TABLE `gym_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_equipment`
--
ALTER TABLE `gym_equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `gym_images`
--
ALTER TABLE `gym_images`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes for table `gym_membership_plans`
--
ALTER TABLE `gym_membership_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `gym_operating_hours`
--
ALTER TABLE `gym_operating_hours`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_owners`
--
ALTER TABLE `gym_owners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gym_revenue`
--
ALTER TABLE `gym_revenue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gym_id` (`gym_id`),
  ADD KEY `gym_revenue_ibfk_2` (`schedule_id`);

--
-- Indexes for table `gym_revenue_distribution`
--
ALTER TABLE `gym_revenue_distribution`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_memberships`
--
ALTER TABLE `user_memberships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gym_id` (`gym_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `class_bookings`
--
ALTER TABLE `class_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gyms`
--
ALTER TABLE `gyms`
  MODIFY `gym_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `gym_classes`
--
ALTER TABLE `gym_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_equipment`
--
ALTER TABLE `gym_equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `gym_images`
--
ALTER TABLE `gym_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `gym_membership_plans`
--
ALTER TABLE `gym_membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `gym_operating_hours`
--
ALTER TABLE `gym_operating_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `gym_owners`
--
ALTER TABLE `gym_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `gym_revenue`
--
ALTER TABLE `gym_revenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gym_revenue_distribution`
--
ALTER TABLE `gym_revenue_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_memberships`
--
ALTER TABLE `user_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gym_revenue`
--
ALTER TABLE `gym_revenue`
  ADD CONSTRAINT `gym_revenue_ibfk_1` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`gym_id`),
  ADD CONSTRAINT `gym_revenue_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
