-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2025 at 11:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rtims5`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `password`, `created_at`) VALUES
(1, 'System Administrator', 'Admin', '$2y$10$nwilktU607EyxOrmxMtFQOA1karpM6nEv4AT5/qnw6I41ZlA5liky', '2025-07-07 08:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `offence_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL,
  `location` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `control_number` varchar(50) NOT NULL,
  `status` enum('Pending','Paid','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `user_id`, `offence_id`, `officer_id`, `location`, `image_path`, `control_number`, `status`, `created_at`) VALUES
(3, 1, 1, 5, 'Mbezi', '686b900a657ec.jpg', 'GOV-TZ-RTIMS-2025-00003', 'Paid', '2025-07-07 09:14:50'),
(4, 2, 2, 5, 'Kariakoo', '686b902f78070.jpg', 'GOV-TZ-RTIMS-2025-00004', 'Paid', '2025-07-07 09:15:27'),
(5, 1, 5, 5, 'Ununio', 'evidence_2025-07-07_11-32-16_686b9420b5e76.jpg', 'GOV-TZ-RTIMS-2025-00005', 'Pending', '2025-07-07 09:32:16'),
(6, 1, 6, 5, 'Sinza', 'evidence_2025-07-07_11-41-19_686b963f783f8.jpg', 'GOV-TZ-RTIMS-2025-00006', 'Pending', '2025-07-07 09:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `offences`
--

CREATE TABLE `offences` (
  `id` int(11) NOT NULL,
  `keyword` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount_tzs` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offences`
--

INSERT INTO `offences` (`id`, `keyword`, `description`, `amount_tzs`, `created_at`) VALUES
(1, 'overspeeding', 'Driving above the speed limit', 50000.00, '2025-07-07 08:10:21'),
(2, 'wrong lane', 'Driving on the wrong lane', 30000.00, '2025-07-07 08:10:21'),
(3, 'wrong turn', 'Making an illegal turn', 25000.00, '2025-07-07 08:10:21'),
(4, 'parking', 'Illegal parking', 15000.00, '2025-07-07 08:10:21'),
(5, 'seatbelt', 'Not wearing seatbelt', 20000.00, '2025-07-07 08:10:21'),
(6, 'phone', 'Using phone while driving', 40000.00, '2025-07-07 08:10:21'),
(7, 'drunk driving', 'Driving under influence of alcohol', 100000.00, '2025-07-07 08:10:21'),
(8, 'no license', 'Driving without valid license', 80000.00, '2025-07-07 08:10:21'),
(9, 'red light', 'Running red light', 45000.00, '2025-07-07 08:10:21'),
(10, 'no insurance', 'Driving without insurance', 60000.00, '2025-07-07 08:10:21');

-- --------------------------------------------------------

--
-- Table structure for table `officers`
--

CREATE TABLE `officers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `badge_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `officers`
--

INSERT INTO `officers` (`id`, `name`, `username`, `password`, `badge_number`, `created_at`) VALUES
(1, 'Officer John', 'officer1', '$2y$10$nwilktU607EyxOrmxMtFQOA1karpM6nEv4AT5/qnw6I41ZlA5liky', 'TPF001', '2025-07-07 08:10:21'),
(4, 'Officer Abdul', 'Abdul', '$2y$10$nwilktU607EyxOrmxMtFQOA1karpM6nEv4AT5/qnw6I41ZlA5liky', 'TPF003', '2025-07-07 08:13:04'),
(5, 'Officer Salum', 'Salum', '$2y$10$nwilktU607EyxOrmxMtFQOA1karpM6nEv4AT5/qnw6I41ZlA5liky', 'TPF002', '2025-07-07 08:13:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `licence_no` varchar(50) NOT NULL,
  `plate_no` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `licence_no`, `plate_no`, `password`, `role`, `created_at`) VALUES
(1, 'Mohammed', '0001', 'T123ABC', '$2y$10$nwilktU607EyxOrmxMtFQOA1karpM6nEv4AT5/qnw6I41ZlA5liky', 'user', '2025-07-07 08:13:04'),
(2, 'Abdul', '0002', 'T123ABD', '$2y$10$nwilktU607EyxOrmxMtFQOA1karpM6nEv4AT5/qnw6I41ZlA5liky', 'user', '2025-07-07 08:13:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `offence_id` (`offence_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `offences`
--
ALTER TABLE `offences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `officers`
--
ALTER TABLE `officers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `badge_number` (`badge_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `licence_no` (`licence_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `offences`
--
ALTER TABLE `offences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `officers`
--
ALTER TABLE `officers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`offence_id`) REFERENCES `offences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidents_ibfk_3` FOREIGN KEY (`officer_id`) REFERENCES `officers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
