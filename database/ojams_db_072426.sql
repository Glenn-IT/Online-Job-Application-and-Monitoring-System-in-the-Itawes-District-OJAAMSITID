-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 03:35 PM
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
-- Database: `ojams_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `performed_by` int(10) UNSIGNED DEFAULT NULL,
  `job_id` int(10) UNSIGNED DEFAULT NULL,
  `application_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `status`, `performed_by`, `job_id`, `application_id`, `created_at`) VALUES
(18, 'Staff account approved: Juan Santelmo', 'Updated', 1, NULL, NULL, '2026-07-16 01:15:23'),
(19, 'Registered new user account: Killua Zoldyck', 'Created', 1, NULL, NULL, '2026-07-16 01:18:09'),
(20, 'User account deactivated: Killua Zoldyck', 'Updated', 1, NULL, NULL, '2026-07-16 01:18:59'),
(21, 'New job posted: \"Web Developer\" at ABC Technologies', 'Created', 1, 8, NULL, '2026-07-21 19:12:15'),
(22, 'New job posted: \"Full Stack Developer\" at Accenture', 'Created', 1, 9, NULL, '2026-07-23 00:42:20');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `elementary` varchar(200) DEFAULT NULL,
  `jhs` varchar(200) DEFAULT NULL,
  `shs` varchar(200) DEFAULT NULL,
  `college` varchar(200) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `date_applied` date NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `job_id`, `full_name`, `email`, `contact`, `address`, `birthdate`, `age`, `elementary`, `jhs`, `shs`, `college`, `skills`, `experience`, `status`, `date_applied`, `updated_at`) VALUES
(10, 11, 8, 'Glenard Pagurayan', 'glenard2308@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-06-01', '2026-07-24 21:17:28'),
(11, 12, 8, 'Glenard Pagurayan', 'glenard@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', '2026-06-05', '2026-07-24 21:17:28'),
(12, 13, 8, 'Lea Pagurayan', 'lea@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rejected', '2026-06-10', '2026-07-24 21:17:28'),
(13, 15, 9, 'Juan Pagurayan', 'juan@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-06-12', '2026-07-24 21:17:28'),
(14, 18, 9, 'Killua Zoldyck', 'killua@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-06-15', '2026-07-24 21:17:28'),
(15, 19, 9, 'Marga Begada', 'marga@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', '2026-06-20', '2026-07-24 21:17:28'),
(16, 11, 10, 'Glenard Pagurayan', 'glenard2308@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-06-02', '2026-07-24 21:21:40'),
(17, 12, 10, 'Glenard Pagurayan', 'glenard@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', '2026-06-04', '2026-07-24 21:21:40'),
(18, 13, 10, 'Lea Pagurayan', 'lea@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Approved', '2026-06-06', '2026-07-24 21:21:40'),
(19, 15, 10, 'Juan Pagurayan', 'juan@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', '2026-06-08', '2026-07-24 21:21:40'),
(20, 18, 10, 'Killua Zoldyck', 'killua@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Rejected', '2026-06-09', '2026-07-24 21:21:40'),
(21, 19, 11, 'Marga Begada', 'marga@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', '2026-06-11', '2026-07-24 21:21:40');

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

CREATE TABLE `application_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `from_status` enum('Pending','Approved','Rejected') DEFAULT NULL,
  `to_status` enum('Pending','Approved','Rejected') NOT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `company` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `qualification` text NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Contract','Internship','Freelance') DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `date_posted` date NOT NULL,
  `status` enum('Open','Closed') NOT NULL DEFAULT 'Open',
  `deadline` date DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `company`, `description`, `qualification`, `location`, `job_type`, `salary_range`, `date_posted`, `status`, `deadline`, `created_by`, `created_at`, `updated_at`) VALUES
(8, 'Web Developer', 'ABC Technologies', 'Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.', 'HTML, CSS, JavaScript', NULL, 'Full-time', '60000', '2026-07-21', 'Open', '2026-09-21', 1, '2026-07-21 19:12:15', '2026-07-21 19:12:15'),
(9, 'Full Stack Developer', 'Accenture', 'asdsa asd asd a asd asd', '5 years experience on JAVA', NULL, 'Contract', '59000', '2026-07-22', 'Open', '2026-10-02', 1, '2026-07-23 00:42:20', '2026-07-23 00:42:20'),
(10, 'Backend Developer', 'OJAMS Corp', 'Develop and maintain backend services.', 'Bachelor degree in CS or related field.', 'Manila', 'Full-time', '30000-40000', '2026-07-24', 'Open', NULL, 1, '2026-07-24 21:21:23', '2026-07-24 21:21:23'),
(11, 'UI/UX Designer', 'OJAMS Corp', 'Design user interfaces and experiences.', 'Portfolio required, 2+ years experience.', 'Cebu', 'Contract', '25000-35000', '2026-07-24', 'Open', NULL, 1, '2026-07-24 21:21:23', '2026-07-24 21:21:23');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip` varchar(45) NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `last_attempt_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(2, 'glenn@gmail.com', 'ad48b893259a2c89ca7a6a91e0c0fc6b3221baec0914bb10b9b7293d965a880c', '2026-07-10 21:46:44', '2026-07-10 20:46:44');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `key` varchar(120) NOT NULL,
  `hits` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `window_start` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`key`, `hits`, `window_start`) VALUES
('::1::admin', 1, '2026-07-16 01:18:59'),
('::1::jobs', 1, '2026-07-23 00:42:20'),
('::1::profile', 2, '2026-07-10 21:15:13'),
('::1::saved_jobs', 2, '2026-06-17 15:36:19');

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED NOT NULL,
  `saved_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','staff','user') NOT NULL DEFAULT 'user',
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `profile_photo` varchar(255) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer_hash` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `full_name`, `email`, `password_hash`, `contact_number`, `address`, `birthdate`, `is_active`, `is_approved`, `profile_photo`, `security_question`, `security_answer_hash`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', 'admin@ojams.com', '$2y$12$UKcF4UtAouX8ljY2zw.Q4.rCIBm0VQ6QeUZNmQPQCOIzD5CC3aRgm', '09000000000', 'OJAMS HQ', '1990-01-01', 1, 1, NULL, NULL, NULL, '2026-01-01 00:00:00', '2026-05-06 14:06:31'),
(11, 'user', 'Glenard Pagurayan', 'glenard2308@gmail.com', '$2y$12$swQvKpuRfGex/pN.PaMnT.CCnKcFLL4njAeDkIHm0cm0CSI66pnau', '09557997409', NULL, NULL, 1, 1, NULL, 'What is the name of your first pet?', '$2y$12$kk2cOqfKvMe0ePuHhDrib.LWqBEo/ScCHFXjEEp152SXI3hj0rQgi', '2026-07-07 19:21:07', '2026-07-10 21:15:47'),
(12, 'user', 'Glenard Pagurayan', 'glenard@gmail.com', '$2y$12$mb2.9NtA.4657vpF2iCB/.OkRKACPfgKInPs8HSOxLpwmt9RKhi5.', '09557977409', NULL, NULL, 1, 1, NULL, NULL, NULL, '2026-07-07 19:26:45', '2026-07-07 19:26:45'),
(13, 'user', 'Lea Pagurayan', 'lea@gmail.com', '$2y$12$kYTWKrZnolypyq0pmfFe0.fGVmFdgH73cCZ71gaj98IOhq5hy5N0O', '09557997409', NULL, NULL, 1, 1, NULL, NULL, NULL, '2026-07-07 19:30:31', '2026-07-07 19:30:31'),
(15, 'user', 'Juan Pagurayan', 'juan@gmail.com', '$2y$12$RLOHWOVLGA8zBp7xDY0ep.bgd.xx.0PP9TA9cDFgFMTO53pgyyxC2', '09557997409', NULL, NULL, 1, 1, NULL, 'What is the name of your first pet?', '$2y$12$CSsbv8VXCOMqVcZkwber4Oj4O.zOQOu21QW09MsUbXzyizz8ez8cW', '2026-07-10 21:17:07', '2026-07-10 21:17:07'),
(17, 'staff', 'Juan Santelmo', 'juansan@gmail.com', '$2y$12$.jxM6DwuPTzhskH48T/7.eItBpflE1PTgMlGfp9CyQCkM2fPg3YTS', '09557997409', NULL, NULL, 1, 1, NULL, 'What is the name of your first pet?', '$2y$12$6B7GuFJcqOZy2jaxUFFQhuvg3oNjGsH9U8isZ93SbFhXyfNx5n9pG', '2026-07-10 21:32:00', '2026-07-16 01:15:23'),
(18, 'user', 'Killua Zoldyck', 'killua@gmail.com', '$2y$12$X8shHz3PotB0Jlw4xUex8er1fiYigWbUAKpD6NdNkwudi6WYsS1eG', '09557997409', NULL, NULL, 0, 1, NULL, 'What is the name of your first pet?', '$2y$12$WxNWggi5GO6eQhg37EH66.Tlfd6R9cB8LQUKWatWicd5473.lfESG', '2026-07-16 01:18:09', '2026-07-16 01:18:59'),
(19, 'user', 'Marga Begada', 'marga@gmail.com', '$2y$12$hVQ21i4x.hg3wZI6D8ErPuu6BfcwMPlNHSnSxsLnL9/Og/UNGDv8O', '09579774897', NULL, NULL, 1, 1, NULL, 'What is the name of your first pet?', '$2y$12$Z3INw1tWmUXMRJ5L1NQAR.neUql/0bxi.8zgtSPIkUwGYoH0HQdUu', '2026-07-21 15:18:08', '2026-07-21 15:18:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_activity_user` (`performed_by`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_job` (`user_id`,`job_id`),
  ADD KEY `fk_applications_job` (`job_id`);

--
-- Indexes for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ash_app` (`application_id`),
  ADD KEY `fk_ash_user` (`changed_by`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jobs_created_by` (`created_by`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`ip`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_resumes_application` (`application_id`),
  ADD KEY `fk_resumes_user` (`user_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_saved` (`user_id`,`job_id`),
  ADD KEY `fk_sj_job` (`job_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `application_status_history`
--
ALTER TABLE `application_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_applications_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `application_status_history`
--
ALTER TABLE `application_status_history`
  ADD CONSTRAINT `fk_ash_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ash_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_jobs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `fk_resumes_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_resumes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `fk_sj_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sj_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
