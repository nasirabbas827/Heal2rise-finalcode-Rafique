-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 09, 2025 at 09:11 AM
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
  `assigned_ngo_id` int(11) NOT NULL,
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
(1, 'New dfres', 'fdses', 1, 1, 1, 'closed', 'fds', 'Medium', 'Low', '2025-12-09 11:34:04', NULL);

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
(1, 1, 1, 1, '2025-12-09 12:09:43', '', 'Please Close this Case', '2025-12-09 12:27:26');

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--

CREATE TABLE `counseling_sessions` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
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

INSERT INTO `counseling_sessions` (`id`, `case_id`, `staff_id`, `session_date`, `session_type`, `duration_minutes`, `notes`, `next_session`, `meeting_link`, `created_at`) VALUES
(1, 1, 1, '2025-12-09 12:01:00', 'fdsafd', 222, 'fds', '2025-12-11 12:02:00', 'http://localhost/fall25/heal2rise/', '2025-12-09 12:02:11'),
(2, 1, 1, '2025-12-10 12:37:00', 'fdsafd', 222, '', '2025-12-11 12:37:00', 'http://localhost/fall25/heal2rise/', '2025-12-09 12:37:23');

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
(1, 'Eedhi', 'ds4gfd', 'rec12', 'NASIR ABBAS', '03176526827', 'nasiryt.827@gmail.com', '$2y$10$sJuV4ulWTkBhWGqQxojns.Kb.0fV.5uKDJR5Ozbt0k1l4MnV7HveS', 'lahore', 50, 'verified', '2025-12-09 11:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `ngo_staff`
--

CREATE TABLE `ngo_staff` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
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
-- Dumping data for table `ngo_staff`
--

INSERT INTO `ngo_staff` (`id`, `ngo_id`, `full_name`, `email`, `phone`, `password_hash`, `designation`, `qualification`, `experience_years`, `status`, `created_at`) VALUES
(1, 1, 'Haider Ali', 'haider@gmail.com', '03266029051', '$2y$10$Zaifz3OgDc7InrSImvT67utokfLltqEwmh488SHoLjStIyFOkuwBS', 'Clerk', 'Bsit', 2, 'active', '2025-12-09 11:44:19');

-- --------------------------------------------------------

--
-- Table structure for table `progress_notes`
--

CREATE TABLE `progress_notes` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL DEFAULT 0,
  `note_date` date NOT NULL,
  `description` text NOT NULL,
  `overall_progress` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `progress_notes`
--

INSERT INTO `progress_notes` (`id`, `case_id`, `staff_id`, `note_date`, `description`, `overall_progress`, `created_at`) VALUES
(1, 1, 0, '2025-12-09', 'Zyfdsfds', 'dfe', '2025-12-09 12:19:41');

-- --------------------------------------------------------

--
-- Table structure for table `skill_programs`
--

CREATE TABLE `skill_programs` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `program_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_weeks` int(11) DEFAULT NULL,
  `mode` varchar(50) DEFAULT 'Online',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_programs`
--

INSERT INTO `skill_programs` (`id`, `ngo_id`, `program_name`, `description`, `duration_weeks`, `mode`, `created_at`) VALUES
(1, 1, 'dfsa', 'fdaea', 2, 'Online', '2025-12-09 12:06:48');

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
(1, 'NASIR ABBAS', 'nasiryt.827@gmail.com', '$2y$10$5BEcp/RZ4cCzsRihfkQmW./cAhunWz8zdsk8o7bhJw64.7OQ2/ehy', '03176526827', '03176526827', 'user', 'male', 22, 'active', '2025-12-09 11:01:07');

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
  ADD KEY `case_id` (`case_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `ngos`
--
ALTER TABLE `ngos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ngo_staff`
--
ALTER TABLE `ngo_staff`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `progress_notes`
--
ALTER TABLE `progress_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`);

--
-- Indexes for table `skill_programs`
--
ALTER TABLE `skill_programs`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `closure_requests`
--
ALTER TABLE `closure_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ngos`
--
ALTER TABLE `ngos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ngo_staff`
--
ALTER TABLE `ngo_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `progress_notes`
--
ALTER TABLE `progress_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skill_programs`
--
ALTER TABLE `skill_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Constraints for table `closure_requests`
--
ALTER TABLE `closure_requests`
  ADD CONSTRAINT `closure_requests_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`),
  ADD CONSTRAINT `closure_requests_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `ngo_staff` (`id`),
  ADD CONSTRAINT `closure_requests_ibfk_3` FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`id`);

--
-- Constraints for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD CONSTRAINT `counseling_sessions_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `counseling_sessions_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `ngo_staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `progress_notes`
--
ALTER TABLE `progress_notes`
  ADD CONSTRAINT `progress_notes_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
