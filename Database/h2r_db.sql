-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2026 at 08:39 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `h2r_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `id` int(11) NOT NULL,
  `case_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_ngo_id` int(11) DEFAULT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','closed') NOT NULL DEFAULT 'pending',
  `problem_category` text DEFAULT NULL,
  `severity` varchar(50) DEFAULT NULL,
  `risk_level` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cases`
--

INSERT INTO `cases` (`id`, `case_title`, `description`, `user_id`, `assigned_ngo_id`, `assigned_staff_id`, `status`, `problem_category`, `severity`, `risk_level`, `created_at`, `closed_at`) VALUES
(1, 'New dfres', 'fdses', 1, 1, 1, 'closed', 'fds', 'Medium', 'Low', '2025-12-09 11:34:04', '2026-01-30 11:23:05'),
(2, 'Case 3', 'fsa', 1, 1, 1, 'closed', 'fds', 'Medium', 'Medium', '2026-01-30 11:16:49', '2026-03-24 11:33:18'),
(3, 'fdsafds', 'gdsf', 1, 1, 1, 'pending', 'fdsa', 'Medium', 'Low', '2026-01-30 11:36:19', NULL),
(4, 'Smoking', 'SmokingSmoking', 2, 12, 2, 'approved', 'Smoking', 'Medium', 'High', '2026-02-19 21:23:35', NULL),
(5, 'CaseXYZdd', 'fdsa', 1, 1, 1, 'approved', 'Anxiety', 'High', 'Low', '2026-03-24 10:41:00', NULL),
(6, 'New Case After Prototype', 'New Case After Prototype', 3, 1, 1, 'approved', 'Depression', 'Medium', 'Medium', '2026-03-24 12:20:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `case_counselor`
--

CREATE TABLE `case_counselor` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `recommended_by` int(11) NOT NULL,
  `recommended_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_counselor`
--

INSERT INTO `case_counselor` (`id`, `case_id`, `counselor_id`, `recommended_by`, `recommended_at`) VALUES
(1, 5, 2, 1, '2026-03-24 11:29:51'),
(2, 6, 2, 1, '2026-03-24 12:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `closure_requests`
--

CREATE TABLE `closure_requests` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `closure_requests`
--

INSERT INTO `closure_requests` (`id`, `case_id`, `staff_id`, `ngo_id`, `request_date`, `status`, `notes`, `responded_at`) VALUES
(2, 2, 1, 1, '2026-01-30 11:18:06', 'approved', 'XYZ', '2026-01-30 11:20:24'),
(3, 1, 1, 1, '2026-01-30 11:21:44', 'approved', '', '2026-01-30 11:23:05'),
(4, 2, 1, 1, '2026-03-24 11:31:44', 'approved', 'dsfd', '2026-03-24 11:33:18');

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--

CREATE TABLE `counseling_sessions` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `session_type` varchar(50) NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `next_session` datetime DEFAULT NULL,
  `meeting_link` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counseling_sessions`
--

INSERT INTO `counseling_sessions` (`id`, `case_id`, `counselor_id`, `session_date`, `session_type`, `duration_minutes`, `notes`, `next_session`, `meeting_link`, `created_at`) VALUES
(1, 5, 2, '2026-03-24 11:55:00', 'Follow-up', 44, 'fdsa', '2026-03-24 11:02:00', '', '2026-03-24 11:56:10'),
(2, 6, 2, '2026-03-24 12:28:00', 'Initial Consultation', 33, 'fdas', NULL, '', '2026-03-24 12:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `counselors`
--

CREATE TABLE `counselors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `qualification` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `specialization` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counselors`
--

INSERT INTO `counselors` (`id`, `full_name`, `email`, `phone`, `password_hash`, `qualification`, `experience_years`, `specialization`, `status`, `created_at`) VALUES
(2, 'Counsellor Ali', 'Counsellor@gmail.com', '3176526827', '$2y$10$z7EQlTQsPxsuDd7HLmeZ2OHtsyQnZ6eUQYuD0sOZEtXIYfMACZk7.', 'MBBS', 2, 'MMM', 'active', '2026-03-24 11:18:41');

-- --------------------------------------------------------

--
-- Table structure for table `ngos`
--

CREATE TABLE `ngos` (
  `id` int(11) NOT NULL,
  `ngo_name` varchar(200) NOT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `service_areas` text DEFAULT NULL,
  `capacity` int(11) DEFAULT 50,
  `status` enum('verified','pending','blocked') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ngos`
--

INSERT INTO `ngos` (`id`, `ngo_name`, `registration_number`, `address`, `contact_person`, `phone`, `email`, `password`, `service_areas`, `capacity`, `status`, `created_at`) VALUES
(1, 'Eedhi', 'ds4gfd', 'rec12', 'NASIR ABBAS', '03176526827', 'nasiryt.827@gmail.com', '$2y$10$sJuV4ulWTkBhWGqQxojns.Kb.0fV.5uKDJR5Ozbt0k1l4MnV7HveS', 'lahore , Multan', 50, 'verified', '2025-12-09 11:04:02'),
(2, 'HopeCare Foundation', 'REG-001', 'Karachi, Pakistan', 'Ayesha Khan', '0301-1111111', 'hopecare@example.com', '$2y$10$examplehash1', 'Mental Health, Counseling', 60, 'verified', '2026-01-30 11:15:49'),
(3, 'MindWell Society', 'REG-002', 'Lahore, Pakistan', 'Ali Raza', '0302-2222222', 'mindwell@example.com', '$2y$10$examplehash2', 'Depression, Anxiety Support', 50, 'verified', '2026-01-30 11:15:49'),
(4, 'LifeSupport Trust', 'REG-003', 'Islamabad, Pakistan', 'Sara Ahmed', '0303-3333333', 'lifesupport@example.com', '$2y$10$examplehash3', 'Trauma Recovery, Therapy', 70, 'verified', '2026-01-30 11:15:49'),
(5, 'BrightFuture NGO', 'REG-004', 'Rawalpindi, Pakistan', 'Usman Iqbal', '0304-4444444', 'brightfuture@example.com', '$2y$10$examplehash4', 'Youth Counseling, Education', 55, 'verified', '2026-01-30 11:15:49'),
(6, 'PeaceMind Organization', 'REG-005', 'Faisalabad, Pakistan', 'Hina Malik', '0305-5555555', 'peacemind@example.com', '$2y$10$examplehash5', 'Stress Management, Counseling', 65, 'verified', '2026-01-30 11:15:49'),
(7, 'WellBeing Network', 'REG-006', 'Multan, Pakistan', 'Bilal Hussain', '0306-6666666', 'wellbeing@example.com', '$2y$10$examplehash6', 'Mental Wellness, Therapy', 80, 'verified', '2026-01-30 11:15:49'),
(8, 'CareBridge NGO', 'REG-007', 'Peshawar, Pakistan', 'Nadia Shah', '0307-7777777', 'carebridge@example.com', '$2y$10$examplehash7', 'Crisis Support, Counseling', 45, 'verified', '2026-01-30 11:15:49'),
(9, 'Healing Hands', 'REG-008', 'Quetta, Pakistan', 'Kamran Ali', '0308-8888888', 'healinghands@example.com', '$2y$10$examplehash8', 'Psychological Support', 40, 'verified', '2026-01-30 11:15:49'),
(10, 'MindCare Pakistan', 'REG-009', 'Sialkot, Pakistan', 'Rabia Noor', '0309-9999999', 'mindcare@example.com', '$2y$10$examplehash9', 'Mental Health Awareness', 75, 'verified', '2026-01-30 11:15:49'),
(11, 'SafeSpace Initiative', 'REG-010', 'Gujranwala, Pakistan', 'Farhan Latif', '0310-1010101', 'safespace@example.com', '$2y$10$examplehash10', 'Counseling, Rehab Support', 90, 'verified', '2026-01-30 11:15:49'),
(12, 'Cheepa', 'ds4gf33', 'p/o box basti cheena tehsil jampur district rajanpur\r\nAp04', 'Haider', '03176526833', 'cheepa@gmail.com', '$2y$10$.5/MxkF4Np3eM1L8CSs5s.aXUIP.cTxLKhXUdR8.jrprVg72e5Uue', 'Multan', 100, 'verified', '2026-02-19 21:19:13');

-- --------------------------------------------------------

--
-- Table structure for table `skill_programs`
--

CREATE TABLE `skill_programs` (
  `id` int(11) NOT NULL,
  `counselor_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `program_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_weeks` int(11) DEFAULT NULL,
  `mode` varchar(50) DEFAULT 'Online',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_programs`
--

INSERT INTO `skill_programs` (`id`, `counselor_id`, `case_id`, `program_name`, `description`, `duration_weeks`, `mode`, `created_at`) VALUES
(3, 2, 6, 'Just for Prototype Case', 'dfsa', 2, 'Online', '2026-03-24 12:33:33');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `designation` varchar(50) NOT NULL,
  `qualification` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `full_name`, `email`, `phone`, `password_hash`, `designation`, `qualification`, `experience_years`, `status`, `created_at`) VALUES
(1, 'Haider Ali', 'haider@gmail.com', '03266029051', '$2y$10$LJKq3GyQFy0gw6vS8.wndOIUlheuAvUWLxZkq4VRl3mmg458LxPxW', 'Clerk', 'Bsit', 3, 'active', '2025-12-09 11:44:19'),
(2, 'Staff One', 'Staff@gmail.com', '03176526822', '$2y$10$sPZMCCqzqznYzpnyQV6SZ.BAJDkCwbD5FVoJuo8xADt3DsqUvLs4q', 'Physi', 'MBBS, FCPS', 20, 'active', '2026-02-19 21:21:07'),
(3, 'XYZ', 'xyz@gmail.com', '03266029059', '$2y$10$w52chX.LxIN9mDrBjbCRougHqsAVRd7QDg8kQhHxqtr2W.s38LtDy', 'ds', 'Bsit', 2, 'active', '2026-03-24 11:06:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `role` enum('user','counselor','psychiatrist') NOT NULL DEFAULT 'user',
  `gender` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `status` enum('active','pending','blocked') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `emergency_contact`, `role`, `gender`, `age`, `status`, `created_at`) VALUES
(1, 'NASIR ABBAS', 'nasiryt.827@gmail.com', '$2y$10$5BEcp/RZ4cCzsRihfkQmW./cAhunWz8zdsk8o7bhJw64.7OQ2/ehy', '03176526827', '03176526827', 'user', 'Male', 22, 'active', '2025-12-09 11:01:07'),
(2, 'Example User', 'example@gmail.com', '$2y$10$qvkqqfOhNVjiJCTQckolTO2qIiEu98fLpGPDkJuza8UU3qIVQPuUu', '03266029044', 'Haider', 'user', 'male', 23, 'active', '2026-02-19 21:22:11'),
(3, 'New User After Prototype', 'newuser@gmail.com', '$2y$10$K88F0HuGnQReUI28Ojnrhup1ietm7svTQ6Q9135GFXVQ4/NwFFvhO', '+92 3120558090', '+92 3120558090', 'user', 'male', 23, 'active', '2026-03-24 12:17:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_ngo_id` (`assigned_ngo_id`);

--
-- Indexes for table `case_counselor`
--
ALTER TABLE `case_counselor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `counselor_id` (`counselor_id`),
  ADD KEY `recommended_by` (`recommended_by`);

--
-- Indexes for table `closure_requests`
--
ALTER TABLE `closure_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `ngo_id` (`ngo_id`);

--
-- Indexes for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_session_case` (`case_id`),
  ADD KEY `fk_session_counselor` (`counselor_id`);

--
-- Indexes for table `counselors`
--
ALTER TABLE `counselors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Indexes for table `ngos`
--
ALTER TABLE `ngos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `skill_programs`
--
ALTER TABLE `skill_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_program_counselor` (`counselor_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `case_counselor`
--
ALTER TABLE `case_counselor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `closure_requests`
--
ALTER TABLE `closure_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `counselors`
--
ALTER TABLE `counselors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ngos`
--
ALTER TABLE `ngos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `skill_programs`
--
ALTER TABLE `skill_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_2` FOREIGN KEY (`assigned_ngo_id`) REFERENCES `ngos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `case_counselor`
--
ALTER TABLE `case_counselor`
  ADD CONSTRAINT `case_counselor_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_counselor_ibfk_2` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_counselor_ibfk_3` FOREIGN KEY (`recommended_by`) REFERENCES `team_members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `closure_requests`
--
ALTER TABLE `closure_requests`
  ADD CONSTRAINT `closure_requests_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`),
  ADD CONSTRAINT `closure_requests_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `team_members` (`id`),
  ADD CONSTRAINT `closure_requests_ibfk_3` FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`id`);

--
-- Constraints for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD CONSTRAINT `fk_session_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_session_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skill_programs`
--
ALTER TABLE `skill_programs`
  ADD CONSTRAINT `fk_program_counselor` FOREIGN KEY (`counselor_id`) REFERENCES `counselors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
