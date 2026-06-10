-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 05:18 PM
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
-- Database: `kitakits_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `patient_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `booking_ref` varchar(20) DEFAULT NULL,
  `status` enum('booked','confirmed','cancelled') NOT NULL DEFAULT 'booked',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `mission_id`, `patient_name`, `contact_number`) VALUES
(2, 3, 'John Does', '09672313755'),
(3, 5, 'Freddie Mercury', '123456789'),
(4, 7, 'Richard F.', '09123456789'),
(5, 6, 'Demo Patient', '09111111111');

-- --------------------------------------------------------

--
-- Table structure for table `missions`
--

CREATE TABLE `missions` (
  `mission_id` int(11) NOT NULL,
  `organizer_name` varchar(100) NOT NULL,
  `mission_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `available_slots` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `missions`
--

INSERT INTO `missions` (`mission_id`, `organizer_name`, `mission_date`, `location`, `available_slots`) VALUES
(3, 'Marikina City Health Office', '2026-05-29', 'Brgy. Tañong', 49),
(4, 'Quezon City Health Office', '2026-06-18', 'Brgy. Payatas', 250),
(5, 'Lingayen Medical Center', '2026-06-07', 'Lingayen, Pangasinan', 5),
(6, 'Caloocan City Health Office', '2026-05-26', 'Deparo, Caloocan', 67),
(7, 'Marikina City Health Office', '2026-05-31', 'Brgy. Concepcion Uno', 0),
(8, 'Sumeru Bimarstan', '2026-09-28', 'Sumeru, Bimarstan', 116),
(9, 'Fort Bonifacio Office', '2027-01-16', 'Sitio III, Fort Bonifacio', 2000),
(10, 'Maxicare Primary Care Clinic', '2027-05-13', 'Maxicare Primary Care Clinic - Bridgetowne', 3200);

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `pre_screenings`
--

CREATE TABLE `pre_screenings` (
  `pre_screening_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `responses` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `booking_id` (`booking_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `pre_screenings_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `pre_screenings_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`mission_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `pre_screenings`
--
ALTER TABLE `pre_screenings`
  ADD PRIMARY KEY (`pre_screening_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `mission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `pre_screenings`
--
ALTER TABLE `pre_screenings`
  MODIFY `pre_screening_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`mission_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
