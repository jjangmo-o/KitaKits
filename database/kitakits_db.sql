-- phpMyAdmin SQL Dump
-- Updated schema for KitaKits cataract mission platform
-- Compatible with MariaDB 10.4+ / MySQL 8-style InnoDB features used by phpMyAdmin exports
--
-- New coverage:
-- 1. Authentication + role-based access through `users.role`
-- 2. Patient profile management through `patients`
-- 3. Bookings connected to patients and missions
-- 4. Booking lifecycle: booked, confirmed, rejected, cancelled, completed, no_show
-- 5. Pre-screening medical intake and coordinator review
-- 6. Analytics-ready mission and booking views
-- 7. Mission search/filter/sort fields and indexes
-- 8. Printable confirmation slip data through booking reference + slip view
-- 9. Admin-managed info/content pages

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
CREATE DATABASE IF NOT EXISTS `kitakits_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `kitakits_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS `v_patient_booking_slips`;
DROP VIEW IF EXISTS `v_admin_booking_directory`;
DROP VIEW IF EXISTS `v_admin_mission_analytics`;

DROP TABLE IF EXISTS `content_pages`;
DROP TABLE IF EXISTS `medical_intake_forms`;
DROP TABLE IF EXISTS `booking_status_history`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `booking_reference_counters`;
DROP TABLE IF EXISTS `missions`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Authentication / Role-based access
-- --------------------------------------------------------

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('admin','patient') NOT NULL DEFAULT 'patient',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_contact_role` (`contact_number`, `role`),
  KEY `idx_users_role_active` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Patient profile management / patient directory
-- --------------------------------------------------------

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(60) NOT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `sex` enum('Male','Female','Other','Prefer not to say') DEFAULT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `full_address` varchar(255) DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `uq_patients_user` (`user_id`),
  KEY `idx_patients_name` (`last_name`, `first_name`),
  KEY `idx_patients_location` (`city`, `barangay`),
  CONSTRAINT `fk_patients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Missions with search/filter/sort fields
-- --------------------------------------------------------

CREATE TABLE `missions` (
  `mission_id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_name` varchar(150) NOT NULL,
  `organizer_name` varchar(100) NOT NULL,
  `mission_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue_name` varchar(150) DEFAULT NULL,
  `location` varchar(255) NOT NULL COMMENT 'Backward-compatible area/location field used by the original site',
  `city_area` varchar(100) NOT NULL,
  `full_address` varchar(255) NOT NULL,
  `total_slots` int(5) NOT NULL DEFAULT 0,
  `available_slots` int(5) NOT NULL DEFAULT 0,
  `mission_status` enum('draft','open','closed','completed','cancelled') NOT NULL DEFAULT 'open',
  `guidelines` text DEFAULT NULL,
  `day_of_instructions` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`mission_id`),
  KEY `idx_missions_date` (`mission_date`),
  KEY `idx_missions_filters` (`mission_status`, `city_area`, `mission_date`, `available_slots`),
  KEY `idx_missions_created_by` (`created_by`),
  FULLTEXT KEY `ft_missions_search` (`mission_name`, `organizer_name`, `location`, `city_area`, `full_address`),
  CONSTRAINT `fk_missions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_missions_slots` CHECK (`total_slots` >= 0 AND `available_slots` >= 0 AND `available_slots` <= `total_slots`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Booking lifecycle with approval
-- `booked` = request submitted / not yet secured
-- `confirmed` = approved and secured slot
-- --------------------------------------------------------

CREATE TABLE `booking_reference_counters` (
  `ref_year` int(4) NOT NULL,
  `last_number` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ref_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(20) DEFAULT NULL COMMENT 'Auto-generated as KK-YYYY-XXXXX when left blank.',
  `mission_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_name` varchar(120) NOT NULL COMMENT 'Snapshot for compatibility with the original site and printable slip',
  `contact_number` varchar(20) NOT NULL COMMENT 'Snapshot for compatibility with the original site and printable slip',
  `booking_status` enum('booked','confirmed','rejected','cancelled','completed','no_show') NOT NULL DEFAULT 'booked',
  `companion_count` int(3) NOT NULL DEFAULT 0,
  `total_headcount` int(3) NOT NULL DEFAULT 1 COMMENT 'Patient + companions; useful for mission-day supplies planning',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `patient_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `uq_bookings_reference` (`booking_reference`),
  UNIQUE KEY `uq_bookings_patient_mission` (`patient_id`, `mission_id`),
  KEY `idx_bookings_mission` (`mission_id`),
  KEY `idx_bookings_status_date` (`booking_status`, `requested_at`),
  KEY `idx_bookings_admin_filters` (`mission_id`, `booking_status`, `requested_at`),
  KEY `idx_bookings_approved_by` (`approved_by`),
  CONSTRAINT `fk_bookings_mission` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`mission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_bookings_headcount` CHECK (`companion_count` >= 0 AND `total_headcount` = (`companion_count` + 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `booking_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `old_status` enum('booked','confirmed','rejected','cancelled','completed','no_show') DEFAULT NULL,
  `new_status` enum('booked','confirmed','rejected','cancelled','completed','no_show') NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `idx_booking_status_history_booking` (`booking_id`),
  KEY `idx_booking_status_history_changed_by` (`changed_by`),
  CONSTRAINT `fk_booking_status_history_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_status_history_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Pre-screening medical intake form
-- --------------------------------------------------------

CREATE TABLE `medical_intake_forms` (
  `intake_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `has_diabetes` tinyint(1) NOT NULL DEFAULT 0,
  `has_hypertension` tinyint(1) NOT NULL DEFAULT 0,
  `has_heart_disease` tinyint(1) NOT NULL DEFAULT 0,
  `has_asthma` tinyint(1) NOT NULL DEFAULT 0,
  `has_bleeding_disorder` tinyint(1) NOT NULL DEFAULT 0,
  `has_fever_or_infection` tinyint(1) NOT NULL DEFAULT 0,
  `is_pregnant` tinyint(1) NOT NULL DEFAULT 0,
  `previous_eye_surgery` tinyint(1) NOT NULL DEFAULT 0,
  `allergies` text DEFAULT NULL,
  `current_medications` text DEFAULT NULL,
  `other_conditions` text DEFAULT NULL,
  `contraindication_flags` text DEFAULT NULL,
  `consent_to_share` tinyint(1) NOT NULL DEFAULT 0,
  `review_status` enum('pending','cleared','flagged','not_cleared') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `coordinator_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`intake_id`),
  UNIQUE KEY `uq_intake_booking` (`booking_id`),
  KEY `idx_intake_patient` (`patient_id`),
  KEY `idx_intake_mission_review` (`mission_id`, `review_status`),
  KEY `idx_intake_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_intake_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_intake_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_intake_mission` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`mission_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_intake_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Admin content management for info pages
-- --------------------------------------------------------

CREATE TABLE `content_pages` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `page_key` varchar(80) NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` longtext NOT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `updated_by` int(11) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `uq_content_pages_key` (`page_key`),
  KEY `idx_content_pages_status` (`status`),
  KEY `idx_content_pages_updated_by` (`updated_by`),
  CONSTRAINT `fk_content_pages_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Seed data migrated from the original dump
-- The demo app accepts admin@kitakits.local / admin123 while this placeholder hash is present.
-- Demo patient accounts use patient123.
-- Replace demo password hashes before production use.
-- --------------------------------------------------------

INSERT INTO `users` (`user_id`, `full_name`, `email`, `contact_number`, `password_hash`, `role`, `is_active`) VALUES
(1, 'KitaKits Admin', 'admin@kitakits.local', NULL, '$2y$10$replace_with_real_admin_password_hash', 'admin', 1),
(2, 'John Does', NULL, '09672313755', '$2y$10$4jhpEwIBhjm806E3KVcWgeLwLM1BZLLdYIOJ8kDuc8v.p22IguwEy', 'patient', 1),
(3, 'Freddie Mercury', NULL, '123456789', '$2y$10$4jhpEwIBhjm806E3KVcWgeLwLM1BZLLdYIOJ8kDuc8v.p22IguwEy', 'patient', 1),
(4, 'Richard F.', NULL, '09123456789', '$2y$10$4jhpEwIBhjm806E3KVcWgeLwLM1BZLLdYIOJ8kDuc8v.p22IguwEy', 'patient', 1),
(5, 'Demo Patient', NULL, '09111111111', '$2y$10$4jhpEwIBhjm806E3KVcWgeLwLM1BZLLdYIOJ8kDuc8v.p22IguwEy', 'patient', 1);

INSERT INTO `patients` (`patient_id`, `user_id`, `first_name`, `last_name`, `contact_number`) VALUES
(1, 2, 'John', 'Does', '09672313755'),
(2, 3, 'Freddie', 'Mercury', '123456789'),
(3, 4, 'Richard', 'F.', '09123456789'),
(4, 5, 'Demo', 'Patient', '09111111111');

INSERT INTO `missions` (`mission_id`, `mission_name`, `organizer_name`, `mission_date`, `location`, `city_area`, `full_address`, `total_slots`, `available_slots`, `mission_status`, `guidelines`, `day_of_instructions`, `created_by`) VALUES
(3, 'Marikina City Health Office Mission', 'Marikina City Health Office', '2026-05-29', 'Brgy. Tañong', 'Marikina', 'Brgy. Tañong, Marikina City', 50, 49, 'open', 'Bring a valid ID and disclose existing medical conditions during pre-screening.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(4, 'Quezon City Health Office Mission', 'Quezon City Health Office', '2026-06-18', 'Brgy. Payatas', 'Quezon City', 'Brgy. Payatas, Quezon City', 250, 250, 'open', 'Bring a valid ID and complete the medical intake form before mission day.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(5, 'Lingayen Medical Center Mission', 'Lingayen Medical Center', '2026-06-07', 'Lingayen, Pangasinan', 'Lingayen', 'Lingayen, Pangasinan', 6, 6, 'open', 'Bring a valid ID and disclose existing medical conditions during pre-screening.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(6, 'Caloocan City Health Office Mission', 'Caloocan City Health Office', '2026-05-26', 'Deparo, Caloocan', 'Caloocan', 'Deparo, Caloocan City', 68, 68, 'open', 'Bring a valid ID and disclose existing medical conditions during pre-screening.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(7, 'Marikina City Health Office Mission', 'Marikina City Health Office', '2026-05-31', 'Brgy. Concepcion Uno', 'Marikina', 'Brgy. Concepcion Uno, Marikina City', 1, 0, 'closed', 'This mission has no remaining slots.', 'Bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(8, 'Sumeru Bimarstan Mission', 'Sumeru Bimarstan', '2026-09-28', 'Sumeru, Bimarstan', 'Sumeru', 'Sumeru, Bimarstan', 116, 116, 'open', 'Bring a valid ID and complete the medical intake form before mission day.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(9, 'Fort Bonifacio Office Mission', 'Fort Bonifacio Office', '2027-01-16', 'Sitio III, Fort Bonifacio', 'Fort Bonifacio', 'Sitio III, Fort Bonifacio', 2000, 2000, 'open', 'Bring a valid ID and complete the medical intake form before mission day.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1),
(10, 'Maxicare Primary Care Clinic Mission', 'Maxicare Primary Care Clinic', '2027-05-13', 'Maxicare Primary Care Clinic - Bridgetowne', 'Bridgetowne', 'Maxicare Primary Care Clinic - Bridgetowne', 3200, 3200, 'open', 'Bring a valid ID and complete the medical intake form before mission day.', 'Arrive early, bring your confirmation slip, valid ID, water, and any maintenance medication.', 1);

INSERT INTO `bookings` (`booking_id`, `booking_reference`, `mission_id`, `patient_id`, `patient_name`, `contact_number`, `booking_status`, `companion_count`, `total_headcount`, `confirmed_at`, `approved_by`) VALUES
(2, 'KK-2026-00002', 3, 1, 'John Does', '09672313755', 'confirmed', 0, 1, current_timestamp(), 1),
(3, 'KK-2026-00003', 5, 2, 'Freddie Mercury', '123456789', 'booked', 0, 1, NULL, NULL),
(4, 'KK-2026-00004', 7, 3, 'Richard F.', '09123456789', 'confirmed', 0, 1, current_timestamp(), 1),
(5, 'KK-2026-00005', 6, 4, 'Demo Patient', '09111111111', 'booked', 0, 1, NULL, NULL);

INSERT INTO `booking_reference_counters` (`ref_year`, `last_number`) VALUES
(2026, 5);

INSERT INTO `booking_status_history` (`booking_id`, `old_status`, `new_status`, `changed_by`, `notes`) VALUES
(2, NULL, 'confirmed', 1, 'Migrated from original bookings table.'),
(3, NULL, 'booked', 1, 'Migrated from original bookings table.'),
(4, NULL, 'confirmed', 1, 'Migrated from original bookings table.'),
(5, NULL, 'booked', 1, 'Migrated from original bookings table.');

INSERT INTO `medical_intake_forms` (`booking_id`, `patient_id`, `mission_id`, `consent_to_share`, `review_status`) VALUES
(2, 1, 3, 0, 'pending'),
(3, 2, 5, 0, 'pending'),
(4, 3, 7, 0, 'pending'),
(5, 4, 6, 0, 'pending');

INSERT INTO `content_pages` (`page_key`, `title`, `body`, `status`, `updated_by`, `published_at`) VALUES
('mission_guidelines', 'Mission Guidelines', 'Patients should complete their pre-screening intake form, bring a valid ID, and arrive at the venue on time.', 'published', 1, current_timestamp()),
('health_advisory', 'Health Advisory', 'Please disclose fever, infection, allergies, current medication, diabetes, hypertension, heart disease, bleeding disorders, pregnancy, and previous eye surgery during pre-screening.', 'published', 1, current_timestamp()),
('day_of_instructions', 'Day-of Instructions', 'Bring your confirmation slip, valid ID, water, maintenance medicine, and one companion only if needed.', 'published', 1, current_timestamp()),
('faq_booking_policy', 'Booking Policy', 'Booking requests start as booked. A slot is secured only after an admin confirms the booking, then the printable slip becomes available.', 'published', 1, current_timestamp()),
('faq_health_privacy', 'Patient Data Reminder', 'Use the demo with sample data only. A real deployment should add formal consent, privacy notices, audit logs, and compliance review before collecting live patient information.', 'published', 1, current_timestamp()),
('patient_guide_preparation', 'Preparation Reminder', 'Before mission day, check your Patient Portal, complete pre-screening, arrange transportation, and follow any organizer instructions about food, medicines, or arrival time.', 'published', 1, current_timestamp());

ALTER TABLE `users` AUTO_INCREMENT = 6;
ALTER TABLE `patients` AUTO_INCREMENT = 5;
ALTER TABLE `missions` AUTO_INCREMENT = 11;
ALTER TABLE `bookings` AUTO_INCREMENT = 6;
ALTER TABLE `booking_status_history` AUTO_INCREMENT = 5;
ALTER TABLE `medical_intake_forms` AUTO_INCREMENT = 5;
ALTER TABLE `content_pages` AUTO_INCREMENT = 7;

-- --------------------------------------------------------
-- Automation triggers for future app actions
-- --------------------------------------------------------

DELIMITER //
CREATE TRIGGER `trg_bookings_before_insert`
BEFORE INSERT ON `bookings`
FOR EACH ROW
BEGIN
  SET NEW.`total_headcount` = NEW.`companion_count` + 1;

  IF NEW.`booking_reference` IS NULL OR NEW.`booking_reference` = '' THEN
    INSERT INTO `booking_reference_counters` (`ref_year`, `last_number`)
    VALUES (YEAR(CURDATE()), LAST_INSERT_ID(1))
    ON DUPLICATE KEY UPDATE `last_number` = LAST_INSERT_ID(`last_number` + 1);

    SET NEW.`booking_reference` = CONCAT('KK-', YEAR(CURDATE()), '-', LPAD(LAST_INSERT_ID(), 5, '0'));
  END IF;

  IF NEW.`booking_status` = 'confirmed' AND NEW.`confirmed_at` IS NULL THEN
    SET NEW.`confirmed_at` = current_timestamp();
  END IF;
END//

CREATE TRIGGER `trg_bookings_before_update`
BEFORE UPDATE ON `bookings`
FOR EACH ROW
BEGIN
  SET NEW.`total_headcount` = NEW.`companion_count` + 1;

  IF OLD.`booking_status` <> NEW.`booking_status` THEN
    IF NEW.`booking_status` = 'confirmed' AND NEW.`confirmed_at` IS NULL THEN
      SET NEW.`confirmed_at` = current_timestamp();
    END IF;

    IF NEW.`booking_status` = 'cancelled' AND NEW.`cancelled_at` IS NULL THEN
      SET NEW.`cancelled_at` = current_timestamp();
    END IF;

    IF NEW.`booking_status` = 'completed' AND NEW.`completed_at` IS NULL THEN
      SET NEW.`completed_at` = current_timestamp();
    END IF;
  END IF;
END//

CREATE TRIGGER `trg_bookings_after_insert`
AFTER INSERT ON `bookings`
FOR EACH ROW
BEGIN
  INSERT INTO `booking_status_history` (`booking_id`, `old_status`, `new_status`, `changed_by`, `notes`)
  VALUES (NEW.`booking_id`, NULL, NEW.`booking_status`, NEW.`approved_by`, 'Initial booking status.');

  IF NEW.`booking_status` = 'confirmed' THEN
    UPDATE `missions`
    SET `available_slots` = GREATEST(`available_slots` - 1, 0)
    WHERE `mission_id` = NEW.`mission_id`;
  END IF;
END//

CREATE TRIGGER `trg_bookings_after_update`
AFTER UPDATE ON `bookings`
FOR EACH ROW
BEGIN
  IF OLD.`booking_status` <> NEW.`booking_status` THEN
    INSERT INTO `booking_status_history` (`booking_id`, `old_status`, `new_status`, `changed_by`, `notes`)
    VALUES (NEW.`booking_id`, OLD.`booking_status`, NEW.`booking_status`, NEW.`approved_by`, 'Booking status changed.');

    IF OLD.`booking_status` <> 'confirmed' AND NEW.`booking_status` = 'confirmed' THEN
      UPDATE `missions`
      SET `available_slots` = GREATEST(`available_slots` - 1, 0)
      WHERE `mission_id` = NEW.`mission_id`;
    ELSEIF OLD.`booking_status` = 'confirmed' AND NEW.`booking_status` <> 'confirmed' THEN
      UPDATE `missions`
      SET `available_slots` = LEAST(`available_slots` + 1, `total_slots`)
      WHERE `mission_id` = NEW.`mission_id`;
    END IF;
  END IF;
END//
DELIMITER ;

-- --------------------------------------------------------
-- Admin analytics dashboard view
-- --------------------------------------------------------

CREATE VIEW `v_admin_mission_analytics` AS
SELECT
  m.`mission_id`,
  m.`mission_name`,
  m.`organizer_name`,
  m.`mission_date`,
  m.`city_area`,
  m.`full_address`,
  m.`mission_status`,
  m.`total_slots`,
  m.`available_slots`,
  COUNT(b.`booking_id`) AS `total_bookings`,
  SUM(CASE WHEN b.`booking_status` = 'booked' THEN 1 ELSE 0 END) AS `booked_count`,
  SUM(CASE WHEN b.`booking_status` = 'confirmed' THEN 1 ELSE 0 END) AS `confirmed_count`,
  SUM(CASE WHEN b.`booking_status` = 'completed' THEN 1 ELSE 0 END) AS `completed_count`,
  SUM(CASE WHEN b.`booking_status` = 'cancelled' THEN 1 ELSE 0 END) AS `cancelled_count`,
  SUM(CASE WHEN b.`booking_status` = 'rejected' THEN 1 ELSE 0 END) AS `rejected_count`,
  SUM(CASE WHEN b.`booking_status` = 'no_show' THEN 1 ELSE 0 END) AS `no_show_count`,
  SUM(CASE WHEN b.`booking_status` = 'confirmed' THEN b.`total_headcount` ELSE 0 END) AS `confirmed_headcount`,
  SUM(CASE WHEN b.`booking_status` = 'completed' THEN 1 ELSE 0 END) / NULLIF(COUNT(b.`booking_id`), 0) AS `completion_rate`
FROM `missions` m
LEFT JOIN `bookings` b ON b.`mission_id` = m.`mission_id`
GROUP BY
  m.`mission_id`,
  m.`mission_name`,
  m.`organizer_name`,
  m.`mission_date`,
  m.`city_area`,
  m.`full_address`,
  m.`mission_status`,
  m.`total_slots`,
  m.`available_slots`;

-- --------------------------------------------------------
-- Admin patient/booking directory view
-- --------------------------------------------------------

CREATE VIEW `v_admin_booking_directory` AS
SELECT
  b.`booking_id`,
  b.`booking_reference`,
  b.`booking_status`,
  b.`requested_at`,
  b.`confirmed_at`,
  b.`companion_count`,
  b.`total_headcount`,
  p.`patient_id`,
  CONCAT_WS(' ', p.`first_name`, p.`middle_name`, p.`last_name`, p.`suffix`) AS `patient_full_name`,
  p.`contact_number`,
  p.`email`,
  p.`city`,
  p.`barangay`,
  m.`mission_id`,
  m.`mission_name`,
  m.`mission_date`,
  m.`city_area`,
  m.`full_address`,
  i.`review_status` AS `intake_review_status`,
  i.`contraindication_flags`,
  i.`coordinator_notes`
FROM `bookings` b
INNER JOIN `patients` p ON p.`patient_id` = b.`patient_id`
INNER JOIN `missions` m ON m.`mission_id` = b.`mission_id`
LEFT JOIN `medical_intake_forms` i ON i.`booking_id` = b.`booking_id`;

-- --------------------------------------------------------
-- Printable booking confirmation slip view
-- Show/print only confirmed bookings in the app, but the view keeps all statuses
-- so patients can see whether the booking is secured.
-- --------------------------------------------------------

CREATE VIEW `v_patient_booking_slips` AS
SELECT
  b.`booking_id`,
  b.`booking_reference`,
  b.`booking_status`,
  b.`confirmed_at`,
  m.`mission_name`,
  m.`organizer_name`,
  m.`mission_date`,
  m.`start_time`,
  m.`end_time`,
  m.`venue_name`,
  m.`full_address`,
  COALESCE(NULLIF(b.`patient_name`, ''), CONCAT_WS(' ', p.`first_name`, p.`middle_name`, p.`last_name`, p.`suffix`)) AS `patient_name`,
  b.`contact_number`,
  p.`email`,
  b.`companion_count`,
  b.`total_headcount`,
  m.`day_of_instructions`
FROM `bookings` b
INNER JOIN `patients` p ON p.`patient_id` = b.`patient_id`
INNER JOIN `missions` m ON m.`mission_id` = b.`mission_id`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
