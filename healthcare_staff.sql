-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2025 at 06:40 AM
-- Server version: 10.1.9-MariaDB
-- PHP Version: 7.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `healthcare_staff`
--

-- --------------------------------------------------------

--
-- Table structure for table `actions_logs`
--

CREATE TABLE `actions_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','OTHER') NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `record_name` varchar(191) DEFAULT NULL,
  `affected_fields` varchar(255) DEFAULT NULL,
  `details` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `room_assignments`
--

CREATE TABLE `room_assignments` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `staff_om`
--

CREATE TABLE `staff_om` (
  `om_id` int(11) NOT NULL,
  `staff_name` varchar(191) NOT NULL,
  `shift` enum('Morning','Evening') NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `staff_sn`
--

CREATE TABLE `staff_sn` (
  `sn_id` int(11) NOT NULL,
  `staff_name` varchar(191) NOT NULL,
  `shift` enum('Morning','Evening') NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ward_assignments`
--

CREATE TABLE `ward_assignments` (
  `ward_id` int(11) NOT NULL,
  `ward_name` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actions_logs`
--
ALTER TABLE `actions_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actions_logs_user_id` (`user_id`),
  ADD KEY `idx_actions_logs_action_type` (`action_type`),
  ADD KEY `idx_actions_logs_table_name` (`table_name`),
  ADD KEY `idx_actions_logs_record_name` (`record_name`),
  ADD KEY `idx_actions_logs_search` (`action_type`,`table_name`,`record_name`,`created_at`);

--
-- Indexes for table `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `staff_om`
--
ALTER TABLE `staff_om`
  ADD PRIMARY KEY (`om_id`),
  ADD UNIQUE KEY `staff_name` (`staff_name`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `idx_staff_om_name_date` (`staff_name`,`assignment_date`);

--
-- Indexes for table `staff_sn`
--
ALTER TABLE `staff_sn`
  ADD PRIMARY KEY (`sn_id`),
  ADD UNIQUE KEY `staff_name` (`staff_name`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `ward_id` (`ward_id`),
  ADD KEY `idx_staff_sn_name_date` (`staff_name`,`assignment_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `ward_assignments`
--
ALTER TABLE `ward_assignments`
  ADD PRIMARY KEY (`ward_id`),
  ADD UNIQUE KEY `ward_name` (`ward_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `actions_logs`
--
ALTER TABLE `actions_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
--
-- AUTO_INCREMENT for table `room_assignments`
--
ALTER TABLE `room_assignments`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `staff_om`
--
ALTER TABLE `staff_om`
  MODIFY `om_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `staff_sn`
--
ALTER TABLE `staff_sn`
  MODIFY `sn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `ward_assignments`
--
ALTER TABLE `ward_assignments`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `actions_logs`
--
ALTER TABLE `actions_logs`
  ADD CONSTRAINT `actions_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_om`
--
ALTER TABLE `staff_om`
  ADD CONSTRAINT `staff_om_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room_assignments` (`room_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_om_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `ward_assignments` (`ward_id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_sn`
--
ALTER TABLE `staff_sn`
  ADD CONSTRAINT `staff_sn_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `room_assignments` (`room_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_sn_ibfk_2` FOREIGN KEY (`ward_id`) REFERENCES `ward_assignments` (`ward_id`) ON DELETE SET NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
