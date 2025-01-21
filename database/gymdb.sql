-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2025 at 04:49 PM
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
-- Database: `gymdb`
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

--
-- Dumping data for table `class_bookings`
--

INSERT INTO `class_bookings` (`id`, `class_id`, `user_id`, `booking_date`, `status`, `created_at`) VALUES
(1, 1, 1, '2025-01-15', 'booked', '2025-01-15 18:29:27'),
(2, 2, 1, '2025-01-15', 'booked', '2025-01-15 18:29:33');

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
  `status` enum('active','inactive','pending') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gyms`
--

INSERT INTO `gyms` (`gym_id`, `owner_id`, `name`, `description`, `address`, `city`, `state`, `country`, `zip_code`, `cover_photo`, `latitude`, `longitude`, `contact_phone`, `contact_email`, `amenities`, `max_capacity`, `current_occupancy`, `rating`, `status`) VALUES
(1, 1, 'Body Garage', 'get acces', 'Maharana Pratap colony , maksi Road ,Ujjain', 'ujjain', 'Madhya Pradesh', 'india', '456001', '', NULL, NULL, '08788938434', 'bodygarage@gmail.com', '[\"Locker Rooms\",\"Showers\",\"Parking\",\"Personal Training\"]', 200, 1, NULL, 'active'),
(2, 2, 'fitness first', 'get premium machines ', 'maksi road , saint paul school', 'ujjain', 'Madhya Pradesh', 'india', '450994', '', NULL, NULL, '09878887784', 'fitnessfirst@gmail.com', '[\"Locker Rooms\",\"Parking\",\"Personal Training\"]', 200, 0, NULL, 'active');

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

--
-- Dumping data for table `gym_classes`
--

INSERT INTO `gym_classes` (`id`, `gym_id`, `name`, `description`, `instructor`, `capacity`, `current_bookings`, `schedule`, `duration_minutes`, `difficulty_level`, `status`) VALUES
(1, 1, 'Yoga Session', 'feel the heal of yoga', 'jay prashad', 100, 0, '{\"monday\":{\"enabled\":\"on\",\"start_time\":\"05:00\",\"end_time\":\"06:00\"},\"tuesday\":{\"start_time\":\"\",\"end_time\":\"\"},\"wednesday\":{\"start_time\":\"\",\"end_time\":\"\"},\"thursday\":{\"start_time\":\"\",\"end_time\":\"\"},\"friday\":{\"start_time\":\"\",\"end_time\":\"\"},\"saturday\":{\"start_time\":\"\",\"end_time\":\"\"},\"sunday\":{\"start_time\":\"\",\"end_time\":\"\"}}', 60, 'beginner', 'active'),
(2, 1, 'Yoga Session', 'feel the heal of yoga', 'jay prashad', 100, 0, '{\"monday\":{\"enabled\":\"on\",\"start_time\":\"05:00\",\"end_time\":\"06:00\"},\"tuesday\":{\"start_time\":\"\",\"end_time\":\"\"},\"wednesday\":{\"start_time\":\"\",\"end_time\":\"\"},\"thursday\":{\"start_time\":\"\",\"end_time\":\"\"},\"friday\":{\"start_time\":\"\",\"end_time\":\"\"},\"saturday\":{\"start_time\":\"\",\"end_time\":\"\"},\"sunday\":{\"start_time\":\"\",\"end_time\":\"\"}}', 60, 'beginner', 'active');

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
(1, 1, 'cross cable', 2, 'equipment_6787f846b2106.jpg'),
(2, 1, 'Treadmill', 5, 'equipment_6787f87637466.webp'),
(3, 1, 'Willie Spinbike', 5, 'equipment_6787f8957c6da.webp');

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
(1, 1, 'uploads/your fitness center.jpg', 1),
(2, 2, 'uploads/Screenshot 2024-11-13 002057.png', 1);

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
(10, 2, 'Tier 1', 'Yearly', 9000.00, 'access all machine + treadmill');

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
(2, 2, 'Daily', '05:00:00', '10:00:00', '16:00:00', '22:00:00');

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



INSERT INTO `gym_owners` (`id`, `name`, `email`, `phone`, `password_hash`, `is_verified`, `is_approved`, `created_at`, `address`, `city`, `state`, `country`, `zip_code`, `profile_picture`) VALUES
(1, 'Raghav Rai', 'raghavrai@gmail.com', '08788938434', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-15 16:14:38', 'nya poora', 'ujjain', 'Madhya Pradesh', 'India', '456001', 'uploads/Screenshot 2024-12-20 151330.png'),
(2, 'rahul kumawat', 'rahulkumawat1@gmail.com', '09878887784', '$2y$10$NwTeB7d/XboafHlAnWbSeeNysccaBfAcDG3Q9eB2kx97Q6J9ZA..y', 1, 1, '2025-01-17 08:20:05', 'maksi road , saint paul school', 'ujjain', 'Madhya Pradesh', 'India', '450994', 'uploads/Screenshot 2024-12-19 235302.png');



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



CREATE TABLE `gym_revenue_distribution` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `distribution_date` date NOT NULL,
  `payment_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `gym_visit_revenue` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `original_gym_id` int(11) NOT NULL,
  `visited_gym_id` int(11) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `visit_date` date NOT NULL,
  `distribution_status` enum('pending','processed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `login_attempts` (`id`, `email`, `attempt_time`) VALUES
(1, 'raghavrai@gmail.com', '2025-01-15 22:04:35'),
(2, 'raghavrai@gmail.com', '2025-01-15 22:05:00'),
(3, 'raghavrai@gmail.com', '2025-01-15 22:05:03'),
(4, 'raghavrai@gmail.com', '2025-01-15 22:07:17');



CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `membership_type` varchar(50) DEFAULT NULL,
  `joining_date` date NOT NULL,
  `subscription_plan` varchar(100) DEFAULT NULL,
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `visit_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visit_history`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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


INSERT INTO `membership_plans` (`id`, `name`, `description`, `price`, `duration_days`, `visit_limit`, `features`, `status`, `created_at`) VALUES
(1, 'basic plan', 'Access All 3 Tier GYM', 599.00, 30, NULL, '[\"Access All GYM \\r\",\"Access any City GYM\"]', 'active', '2025-01-15 15:43:29'),
(2, 'basic plan', 'Access All 2 Tier GYM', 999.00, 30, NULL, '[\"Access All GYM \\r\",\"Access any City GYM\"]', 'active', '2025-01-15 15:44:00');


CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `notification_type` enum('Email','SMS','Message') NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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



INSERT INTO `payments` (`id`, `gym_id`, `user_id`, `membership_id`, `amount`, `payment_method`, `transaction_id`, `status`, `payment_date`) VALUES
(1, 1, 1, 1, 10000.00, 'razorpay', 'pay_PkPkj0JbnnQnWV', 'completed', '2025-01-17 06:38:09');


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



INSERT INTO `reviews` (`id`, `user_id`, `gym_id`, `rating`, `comment`, `visit_date`, `status`, `created_at`) VALUES
(1, 1, 1, 4, 'fggfgfh', NULL, 'approved', '2025-01-15 19:41:59');



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



INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `phone`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'user', 'user@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$VjI1YlZ4aXhIRjRCS1NPTg$TUXAiUir7MFzoUH7f17rrQvogFFIU215pVzFfbu2DDM', 'member', '8799877978', NULL, 'active', '2025-01-15 14:25:19', '2025-01-15 14:25:19'),
(2, 'admin', 'admin@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$WmI5L1FNbnY0OFZ4L3BPaQ$WhMo3Xe51DmSOt07J8lUSDs73ZhkSdjoVvTouCgM1rk', 'admin', '7097923443', NULL, 'active', '2025-01-15 14:33:08', '2025-01-15 14:35:22');


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



INSERT INTO `user_memberships` (`id`, `gym_id`, `user_id`, `plan_id`, `start_date`, `end_date`, `status`, `payment_status`, `auto_renewal`) VALUES
(1, 1, 1, 4, '2025-01-17', '2026-01-17', 'active', 'paid', 0);


CREATE TABLE `visit` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `check_in_time` datetime NOT NULL DEFAULT current_timestamp(),
  `check_out_time` datetime DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `gym_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bank_account` varchar(255) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `class_bookings`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `gyms`
  ADD PRIMARY KEY (`gym_id`);


ALTER TABLE `gym_classes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gym_equipment`
  ADD PRIMARY KEY (`equipment_id`);

ALTER TABLE `gym_images`
  ADD PRIMARY KEY (`image_id`);

ALTER TABLE `gym_membership_plans`
  ADD PRIMARY KEY (`plan_id`);

ALTER TABLE `gym_operating_hours`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gym_owners`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gym_revenue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gym_id` (`gym_id`),
  ADD KEY `gym_revenue_ibfk_2` (`schedule_id`);


ALTER TABLE `gym_revenue_distribution`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gym_visit_revenue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `original_gym_id` (`original_gym_id`),
  ADD KEY `visited_gym_id` (`visited_gym_id`);

ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`);

ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user_memberships`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `visit`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gym_id` (`gym_id`);

ALTER TABLE `class_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `gyms`
  MODIFY `gym_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `gym_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `gym_equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `gym_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `gym_membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

ALTER TABLE `gym_operating_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `gym_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `gym_revenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `gym_revenue_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `gym_visit_revenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `membership_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=367;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `user_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `visit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `gym_revenue`
  ADD CONSTRAINT `gym_revenue_ibfk_1` FOREIGN KEY (`gym_id`) REFERENCES `gyms` (`gym_id`),
  ADD CONSTRAINT `gym_revenue_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE;
COMMIT;

