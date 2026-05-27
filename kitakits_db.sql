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
  `contact_number` varchar(20) NOT NULL
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
