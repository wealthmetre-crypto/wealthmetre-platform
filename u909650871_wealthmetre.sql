-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 03, 2026 at 08:49 AM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u909650871_wealthmetre`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED DEFAULT NULL,
  `partner_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `lead_id`, `partner_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, NULL, 3, 'registered', 'New partner: Saur acha', '2405:201:5c08:d17e:6016:a203:6f4b:6083', '2026-04-02 19:57:23'),
(2, NULL, 3, 'login_otp', 'OTP login', '2405:201:5c08:d17e:6016:a203:6f4b:6083', '2026-04-03 06:54:04'),
(3, 5, 3, 'lead_created', 'Lead created: LAP ₹40 Lakh', '2405:201:5c1d:d801:5d9a:6dbc:2757:ede3', '2026-04-09 20:44:39'),
(4, 5, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c1d:d801:5d9a:6dbc:2757:ede3', '2026-04-09 20:44:52'),
(5, 6, 3, 'lead_created', 'Lead created: LAP ₹35 Lakh', '2405:201:5c1d:d801:5d9a:6dbc:2757:ede3', '2026-04-09 20:55:06'),
(6, 6, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c1d:d801:5d9a:6dbc:2757:ede3', '2026-04-09 20:55:18'),
(7, NULL, 4, 'registered', 'New partner: saurabh acharya', '2405:201:5c1d:d801:de4f:de0e:8a0a:4271', '2026-04-10 07:11:23'),
(8, 7, 4, 'lead_created', 'Lead created: INSTITUTIONAL LOAN ₹12 Lakh', '2405:201:5c1d:d801:de4f:de0e:8a0a:4271', '2026-04-10 07:12:18'),
(9, NULL, 3, 'login_otp', 'OTP login', '2405:201:5c1d:d801:852d:1ac8:90ed:1211', '2026-04-11 06:21:32'),
(10, 8, 3, 'lead_created', 'Lead created: HOME LOAN ₹10 Lakh', '2405:201:5c1d:d801:852d:1ac8:90ed:1211', '2026-04-11 06:41:40'),
(11, 8, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c1d:d801:852d:1ac8:90ed:1211', '2026-04-11 06:42:12'),
(12, NULL, 4, 'login_otp', 'OTP login', '2405:201:5c1d:d801:bd17:2fc1:5654:5384', '2026-04-11 06:45:37'),
(13, 9, 4, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹40 Lakh', '2405:201:5c1d:d801:bd17:2fc1:5654:5384', '2026-04-11 06:47:08'),
(14, 9, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c1d:d801:bd17:2fc1:5654:5384', '2026-04-11 06:47:39'),
(15, 10, 3, 'lead_created', 'Lead created: HOME LOAN ₹40 Lakh', '2405:201:5c1d:d801:852d:1ac8:90ed:1211', '2026-04-11 13:09:47'),
(16, 10, 3, 'lenders_saved', '5 lenders saved', '2405:201:5c1d:d801:852d:1ac8:90ed:1211', '2026-04-11 13:10:23'),
(17, 11, 4, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹40 Lakh', '2405:201:5c1d:d801:6514:7e5a:a668:c35', '2026-04-11 13:14:34'),
(18, 12, 4, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹40 Lakh', '2405:201:5c1d:d801:6514:7e5a:a668:c35', '2026-04-11 13:15:19'),
(19, 11, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c1d:d801:6514:7e5a:a668:c35', '2026-04-11 13:15:55'),
(20, NULL, 5, 'registered', 'New partner: Prashant Mathur', '2402:e280:231a:2f8:44cb:695b:4ce8:3824', '2026-04-13 04:35:50'),
(21, 13, 5, 'lead_created', 'Lead created: HOME LOAN ₹30 Lakh', '2402:e280:231a:2f8:44cb:695b:4ce8:3824', '2026-04-13 04:40:04'),
(22, 14, 3, 'lead_created', 'Lead created: HOME LOAN ₹15 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-13 07:32:24'),
(23, 14, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-13 07:32:45'),
(24, NULL, 6, 'registered', 'New partner: Puneet Mathur', '2409:40d4:12b:5858:6495:beff:feb0:b12', '2026-04-13 07:36:38'),
(25, 15, 3, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹50 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-13 07:39:00'),
(26, 16, 6, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹50 Lakh', '2409:40d4:12b:5858:6495:beff:feb0:b12', '2026-04-13 07:39:08'),
(27, 16, 6, 'lenders_saved', '10 lenders saved', '2409:40d4:12b:5858:6495:beff:feb0:b12', '2026-04-13 07:39:37'),
(28, 15, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-13 07:39:38'),
(29, 16, 6, 'lenders_saved', '10 lenders saved', '2409:40d4:12b:5858:6495:beff:feb0:b12', '2026-04-13 07:39:47'),
(30, 17, 3, 'lead_created', 'Lead created: HOME LOAN ₹5 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:06:49'),
(31, 18, 3, 'lead_created', 'Lead created: HOME LOAN ₹5 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:08:56'),
(32, 19, 3, 'lead_created', 'Lead created: HOME LOAN ₹5 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:10:58'),
(33, 19, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:34:51'),
(34, 20, 3, 'lead_created', 'Lead created: HOME LOAN ₹5 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:35:01'),
(35, 20, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:35:15'),
(36, 21, 3, 'lead_created', 'Lead created: HOME LOAN ₹5 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:05:33'),
(37, 21, 3, 'lenders_saved', '4 lenders saved', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:05:50'),
(38, NULL, 3, 'login_otp', 'OTP login', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 10:07:02'),
(39, NULL, 3, 'login_otp', 'OTP login', '2405:201:5c08:d17e:f848:8cc:4e5b:fca4', '2026-04-14 10:11:07'),
(40, NULL, 7, 'registered', 'New partner: Chetan So', '2401:4900:c4c6:f188:7123:3ac8:e32a:8433', '2026-04-14 10:11:10'),
(41, 22, 7, 'lead_created', 'Lead created: INSTITUTIONAL LOAN ₹100 Lakh', '2401:4900:c4c6:f188:7123:3ac8:e32a:8433', '2026-04-14 10:12:57'),
(42, 23, 3, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹10 Lakh', '2405:201:5c08:d17e:f848:8cc:4e5b:fca4', '2026-04-14 10:13:03'),
(43, 23, 3, 'lenders_saved', '9 lenders saved', '2405:201:5c08:d17e:f848:8cc:4e5b:fca4', '2026-04-14 10:13:31'),
(44, 24, 3, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹10 Lakh', '2405:201:5c08:d17e:f848:8cc:4e5b:fca4', '2026-04-14 10:14:50'),
(45, 24, 3, 'lenders_saved', '9 lenders saved', '2405:201:5c08:d17e:f848:8cc:4e5b:fca4', '2026-04-14 10:15:09'),
(46, 25, 7, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹50 Lakh', '2401:4900:c4c6:f188:7123:3ac8:e32a:8433', '2026-04-14 10:16:16'),
(47, 25, 7, 'lenders_saved', '10 lenders saved', '2401:4900:c4c6:f188:7123:3ac8:e32a:8433', '2026-04-14 10:16:37'),
(48, 26, 3, 'lead_created', 'Lead created: HOME LOAN ₹100 Lakh', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 11:02:50'),
(49, 26, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 11:03:07'),
(50, 27, 3, 'lead_created', 'Lead created: HOME LOAN ₹10 Lakh', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:02:06'),
(51, 27, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:02:22'),
(52, 28, 3, 'lead_created', 'Lead created: HOME LOAN ₹50 Lakh', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:11:28'),
(53, 29, 3, 'lead_created', 'Lead created: HOME LOAN ₹50 Lakh', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:12:37'),
(54, 29, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:12:52'),
(55, 30, 3, 'lead_created', 'Lead created: HOME LOAN ₹50 Lakh', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:29:50'),
(56, NULL, 4, 'login_otp', 'OTP login', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:14:47'),
(57, 31, 4, 'lead_created', 'Lead created: HOME LOAN ₹50 Lakh', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:15:33'),
(58, 31, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:17:10'),
(59, 32, 4, 'lead_created', 'Lead created: HOME LOAN ₹10 Lakh', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 17:06:59'),
(60, 32, 4, 'lenders_saved', '5 lenders saved', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 17:07:16'),
(61, 12, 4, 'status_update', 'Status → sanctioned', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 07:57:24'),
(62, 12, 4, 'status_update', 'Status → disbursed', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 07:57:25'),
(63, 33, 4, 'lead_created', 'Lead created: HOME LOAN ₹100 Lakh', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 07:58:40'),
(64, 33, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 07:58:58'),
(65, 34, 4, 'lead_created', 'Lead created: HOME LOAN ₹20 Lakh', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:02:04'),
(66, 34, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:02:24'),
(67, 35, 4, 'lead_created', 'Lead created: HOME LOAN ₹50 Lakh', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:03:53'),
(68, 35, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:04:35'),
(69, 35, 4, 'lender_shared', 'Case shared with Hinduja Housing Finance', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:09:58'),
(70, 35, 4, 'lender_shared', 'Case shared with PNB Housing Finance (Roshni)', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:10:03'),
(71, 35, 4, 'lender_shared', 'Case shared with Tata Capital Housing Finance', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:10:08'),
(72, 35, 4, 'quote_received', 'Quote received from Tata Capital Housing Finance @ 8.25%', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:10:33'),
(73, 34, 4, 'followup_added', 'Follow-up added: call on 2026-04-18', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:16:18'),
(74, 34, 4, 'followup_added', 'Follow-up added: call on 2026-04-23', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:16:40'),
(75, 12, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 14:44:25'),
(76, NULL, 3, 'login_otp', 'OTP login', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 13:49:09'),
(77, 36, 3, 'lead_created', 'Lead created: LOAN AGAINST PROPERTY (LAP) ₹75 Lakh', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 15:34:20'),
(78, 36, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 15:35:31'),
(79, 30, 3, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 20:34:30'),
(80, 37, 4, 'lead_created', 'Lead created: HOME LOAN ₹5 Lakh', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 21:01:51'),
(81, 37, 4, 'lenders_saved', '10 lenders saved', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 21:02:12'),
(82, 37, 4, 'lender_shared', 'Case shared with Niwas Housing Finance', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 21:03:55'),
(83, 37, 4, 'lender_shared', 'Case shared with Motilal Oswal Home Finance', '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3', '2026-04-21 21:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `type` varchar(30) DEFAULT NULL,
  `action` varchar(150) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_alerts`
--

CREATE TABLE `admin_alerts` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED DEFAULT NULL,
  `partner_id` int(10) UNSIGNED DEFAULT NULL,
  `alert_type` enum('new_lead','doc_upload','bureau_done','status_change','task_created','lender_shortlisted') DEFAULT 'new_lead',
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_alerts`
--

INSERT INTO `admin_alerts` (`id`, `lead_id`, `partner_id`, `alert_type`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 3, 'new_lead', 'Partner Saur acha logged new LAP lead ₹40 Lakh — jaipur', 0, '2026-04-09 20:26:00'),
(2, 2, 3, 'new_lead', 'Partner Saur acha logged new LAP lead ₹35 Lakh — jaipur', 0, '2026-04-09 20:27:22'),
(3, 3, 3, 'new_lead', 'Partner Saur acha logged new LAP lead ₹35 Lakh — jaipur', 0, '2026-04-09 20:28:07'),
(4, 4, 3, 'new_lead', 'Partner Saur acha logged new LAP lead ₹35 Lakh — jaipur', 0, '2026-04-09 20:28:15'),
(5, 5, 3, 'new_lead', 'Partner Saur acha logged new LAP lead ₹40 Lakh — jaipur', 0, '2026-04-09 20:44:39'),
(6, 5, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #5', 0, '2026-04-09 20:44:52'),
(7, 6, 3, 'new_lead', 'Partner Saur acha logged new LAP lead ₹35 Lakh — jaipur', 0, '2026-04-09 20:55:06'),
(8, 6, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #6', 0, '2026-04-09 20:55:18'),
(9, 7, 4, 'new_lead', 'Partner saurabh acharya logged new INSTITUTIONAL LOAN lead ₹12 Lakh — Bhilwara', 0, '2026-04-10 07:12:18'),
(10, 8, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹10 Lakh — Jaipur', 0, '2026-04-11 06:41:40'),
(11, 8, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #8', 0, '2026-04-11 06:42:12'),
(12, 9, 4, 'new_lead', 'Partner saurabh acharya logged new LOAN AGAINST PROPERTY (LAP) lead ₹40 Lakh — Jaipur', 0, '2026-04-11 06:47:08'),
(13, 9, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #9', 0, '2026-04-11 06:47:39'),
(14, 10, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹40 Lakh — Kota', 0, '2026-04-11 13:09:47'),
(15, 10, 3, 'lender_shortlisted', 'Top 5 lenders shortlisted for lead #10', 0, '2026-04-11 13:10:23'),
(16, 11, 4, 'new_lead', 'Partner saurabh acharya logged new LOAN AGAINST PROPERTY (LAP) lead ₹40 Lakh — Jaipur', 0, '2026-04-11 13:14:34'),
(17, 12, 4, 'new_lead', 'Partner saurabh acharya logged new LOAN AGAINST PROPERTY (LAP) lead ₹40 Lakh — Jaipur', 0, '2026-04-11 13:15:19'),
(18, 11, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #11', 0, '2026-04-11 13:15:55'),
(19, 13, 5, 'new_lead', 'Partner Prashant Mathur logged new HOME LOAN lead ₹30 Lakh — Jaipur', 0, '2026-04-13 04:40:04'),
(20, 14, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹15 Lakh — Jaipur', 0, '2026-04-13 07:32:24'),
(21, 14, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #14', 0, '2026-04-13 07:32:45'),
(22, 15, 3, 'new_lead', 'Partner Saur acha logged new LOAN AGAINST PROPERTY (LAP) lead ₹50 Lakh — Jaipur', 0, '2026-04-13 07:39:00'),
(23, 16, 6, 'new_lead', 'Partner Puneet Mathur logged new LOAN AGAINST PROPERTY (LAP) lead ₹50 Lakh — Jaipur', 0, '2026-04-13 07:39:08'),
(24, 16, 6, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #16', 0, '2026-04-13 07:39:37'),
(25, 15, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #15', 0, '2026-04-13 07:39:38'),
(26, 16, 6, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #16', 0, '2026-04-13 07:39:47'),
(27, 17, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹5 Lakh — Jaipur', 0, '2026-04-14 06:06:49'),
(28, 18, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹5 Lakh — Jaipur', 0, '2026-04-14 06:08:56'),
(29, 19, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹5 Lakh — Jaipur', 0, '2026-04-14 06:10:58'),
(30, 19, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #19', 0, '2026-04-14 06:34:51'),
(31, 20, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹5 Lakh — Jaipur', 0, '2026-04-14 06:35:01'),
(32, 20, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #20', 0, '2026-04-14 06:35:15'),
(33, 21, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹5 Lakh — Jodhpur', 0, '2026-04-14 08:05:33'),
(34, 21, 3, 'lender_shortlisted', 'Top 4 lenders shortlisted for lead #21', 0, '2026-04-14 08:05:50'),
(35, 22, 7, 'new_lead', 'Partner Chetan So logged new INSTITUTIONAL LOAN lead ₹100 Lakh — Jaipur', 0, '2026-04-14 10:12:57'),
(36, 23, 3, 'new_lead', 'Partner Saur acha logged new LOAN AGAINST PROPERTY (LAP) lead ₹10 Lakh — Kota', 0, '2026-04-14 10:13:03'),
(37, 23, 3, 'lender_shortlisted', 'Top 9 lenders shortlisted for lead #23', 0, '2026-04-14 10:13:31'),
(38, 24, 3, 'new_lead', 'Partner Saur acha logged new LOAN AGAINST PROPERTY (LAP) lead ₹10 Lakh — Kota', 0, '2026-04-14 10:14:50'),
(39, 24, 3, 'lender_shortlisted', 'Top 9 lenders shortlisted for lead #24', 0, '2026-04-14 10:15:09'),
(40, 25, 7, 'new_lead', 'Partner Chetan So logged new LOAN AGAINST PROPERTY (LAP) lead ₹50 Lakh — Jaipur', 0, '2026-04-14 10:16:16'),
(41, 25, 7, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #25', 0, '2026-04-14 10:16:37'),
(42, 26, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹100 Lakh — Jaipur', 0, '2026-04-15 11:02:50'),
(43, 26, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #26', 0, '2026-04-15 11:03:07'),
(44, 27, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹10 Lakh — Jaipur', 0, '2026-04-16 07:02:06'),
(45, 27, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #27', 0, '2026-04-16 07:02:22'),
(46, 28, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹50 Lakh — Jaipur', 0, '2026-04-16 07:11:28'),
(47, 29, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹50 Lakh — Jaipur', 0, '2026-04-16 07:12:37'),
(48, 29, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #29', 0, '2026-04-16 07:12:52'),
(49, 30, 3, 'new_lead', 'Partner Saur acha logged new HOME LOAN lead ₹50 Lakh — Jaipur', 0, '2026-04-16 07:29:50'),
(50, 31, 4, 'new_lead', 'Partner saurabh acharya logged new HOME LOAN lead ₹50 Lakh — Jaipur', 0, '2026-04-16 13:15:33'),
(51, 31, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #31', 0, '2026-04-16 13:17:10'),
(52, 32, 4, 'new_lead', 'Partner saurabh acharya logged new HOME LOAN lead ₹10 Lakh — Jodhpur', 0, '2026-04-16 17:06:59'),
(53, 32, 4, 'lender_shortlisted', 'Top 5 lenders shortlisted for lead #32', 0, '2026-04-16 17:07:16'),
(54, 12, 4, 'status_change', 'Lead #12 status → sanctioned by saurabh acharya', 0, '2026-04-18 07:57:24'),
(55, 12, 4, 'status_change', 'Lead #12 status → disbursed by saurabh acharya', 0, '2026-04-18 07:57:25'),
(56, 33, 4, 'new_lead', 'Partner saurabh acharya logged new HOME LOAN lead ₹100 Lakh — Jaipur', 0, '2026-04-18 07:58:40'),
(57, 33, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #33', 0, '2026-04-18 07:58:58'),
(58, 34, 4, 'new_lead', 'Partner saurabh acharya logged new HOME LOAN lead ₹20 Lakh — Jaipur', 0, '2026-04-18 14:02:04'),
(59, 34, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #34', 0, '2026-04-18 14:02:24'),
(60, 35, 4, 'new_lead', 'Partner saurabh acharya logged new HOME LOAN lead ₹50 Lakh — Jaipur', 0, '2026-04-18 14:03:53'),
(61, 35, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #35', 0, '2026-04-18 14:04:35'),
(62, 12, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #12', 0, '2026-04-18 14:44:25'),
(63, 36, 3, 'new_lead', 'Partner Saur acha logged new LOAN AGAINST PROPERTY (LAP) lead ₹75 Lakh — Jaipur', 0, '2026-04-21 15:34:20'),
(64, 36, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #36', 0, '2026-04-21 15:35:31'),
(65, 30, 3, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #30', 0, '2026-04-21 20:34:30'),
(66, 37, 4, 'new_lead', 'Partner saurabh acharya logged new HOME LOAN lead ₹5 Lakh — Jaipur', 0, '2026-04-21 21:01:51'),
(67, 37, 4, 'lender_shortlisted', 'Top 10 lenders shortlisted for lead #37', 0, '2026-04-21 21:02:12');

-- --------------------------------------------------------

--
-- Stand-in structure for view `all_leads`
-- (See below for the actual view)
--
CREATE TABLE `all_leads` (
`id` decimal(10,0)
,`name` varchar(150)
,`mobile` varchar(20)
,`city` varchar(100)
,`product` varchar(50)
,`loan_amount` bigint(20)
,`property_value` bigint(20)
,`monthly_income` int(11)
,`cibil_score` int(11)
,`employment_type` varchar(100)
,`source` varchar(14)
,`lead_date` datetime /* mariadb-5.3 */
,`lead_table` varchar(14)
);

-- --------------------------------------------------------

--
-- Table structure for table `bureau_reports`
--

CREATE TABLE `bureau_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `aadhaar_last4` char(4) DEFAULT NULL,
  `cibil` smallint(6) DEFAULT NULL,
  `active_loans` tinyint(4) DEFAULT NULL,
  `total_outstanding` bigint(20) DEFAULT NULL,
  `total_emi` int(11) DEFAULT NULL,
  `credit_cards` tinyint(4) DEFAULT NULL,
  `overdue_accounts` tinyint(4) DEFAULT NULL,
  `dpd_30` tinyint(4) DEFAULT NULL,
  `dpd_60` tinyint(4) DEFAULT NULL,
  `dpd_90` tinyint(4) DEFAULT NULL,
  `enquiries_6m` tinyint(4) DEFAULT NULL,
  `bureau_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bureau_json`)),
  `fetched_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_leads`
--

CREATE TABLE `customer_leads` (
  `lead_id` int(10) UNSIGNED NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_mobile` varchar(15) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `loan_type` varchar(50) DEFAULT NULL,
  `amount` bigint(20) DEFAULT NULL,
  `property_title` varchar(100) DEFAULT NULL,
  `patta_year` smallint(6) DEFAULT NULL,
  `property_usage` varchar(100) DEFAULT NULL,
  `property_location` varchar(200) DEFAULT NULL,
  `valuation` bigint(20) DEFAULT NULL,
  `income_type` varchar(50) DEFAULT NULL,
  `monthly_income` int(11) DEFAULT NULL,
  `existing_emi` int(11) DEFAULT 0,
  `cibil` smallint(6) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `ltv_pct` decimal(5,2) DEFAULT NULL,
  `foir_pct` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary_json`)),
  `status` enum('new','processing','lender_shortlisted','docs_uploaded','bureau_done','follow_up','sanctioned','disbursed','rejected','on_hold') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `doc_completion_pct` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_leads`
--

INSERT INTO `customer_leads` (`lead_id`, `partner_id`, `customer_name`, `customer_mobile`, `customer_email`, `loan_type`, `amount`, `property_title`, `patta_year`, `property_usage`, `property_location`, `valuation`, `income_type`, `monthly_income`, `existing_emi`, `cibil`, `city`, `occupation`, `ltv_pct`, `foir_pct`, `remarks`, `summary_json`, `status`, `created_at`, `updated_at`, `doc_completion_pct`) VALUES
(1, 3, 'abcd', '9999999999', 'mogambo@yahoo.com', 'LAP', 4000000, 'JDA', NULL, '', 'mansariver', 7000000, 'Business / Self-Employed', 100000, 50000, 650, 'jaipur', 'Business Owner / Director', 57.14, 50.00, 'nope | no | no | no | no', '{\"loan_type\":\"LAP\",\"amount\":4000000,\"property_title\":\"JDA\",\"patta_year\":null,\"valuation\":7000000,\"income\":100000,\"existing_emi\":50000,\"cibil\":650,\"city\":\"jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":57.14,\"foir_pct\":50,\"property_usage\":null,\"income_type\":\"Business \\/ Self-Employed\",\"remarks\":\"nope | no | no | no | no\"}', 'new', '2026-04-09 20:26:00', '2026-04-09 20:26:00', 0),
(2, 3, 'abcd', '9999999999', 'mogambo@yahoo.com', 'LAP', 3500000, 'JDA', NULL, '', 'mansarover', 8000000, '', 100000, 30000, 700, 'jaipur', 'Business Owner / Director', 43.75, 30.00, 'jda sorp | nope', '{\"loan_type\":\"LAP\",\"amount\":3500000,\"property_title\":\"JDA\",\"patta_year\":null,\"valuation\":8000000,\"income\":100000,\"existing_emi\":30000,\"cibil\":700,\"city\":\"jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":43.75,\"foir_pct\":30,\"property_usage\":null,\"income_type\":null,\"remarks\":\"jda sorp | nope\"}', 'new', '2026-04-09 20:27:22', '2026-04-09 20:27:22', 0),
(3, 3, 'abcd', '9999999999', 'mogambo@yahoo.com', 'LAP', 3500000, 'JDA', NULL, '', 'mansarover', 8000000, '', 100000, 30000, 700, 'jaipur', 'Business Owner / Director', 43.75, 30.00, 'jda sorp | nope | nope', '{\"loan_type\":\"LAP\",\"amount\":3500000,\"property_title\":\"JDA\",\"patta_year\":null,\"valuation\":8000000,\"income\":100000,\"existing_emi\":30000,\"cibil\":700,\"city\":\"jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":43.75,\"foir_pct\":30,\"property_usage\":null,\"income_type\":null,\"remarks\":\"jda sorp | nope | nope\"}', 'new', '2026-04-09 20:28:07', '2026-04-09 20:28:07', 0),
(4, 3, 'abcd', '9999999999', 'mogambo@yahoo.com', 'LAP', 3500000, 'JDA', NULL, '', 'mansarover', 8000000, '', 100000, 30000, 700, 'jaipur', 'Business Owner / Director', 43.75, 30.00, 'jda sorp | nope | nope | nope', '{\"loan_type\":\"LAP\",\"amount\":3500000,\"property_title\":\"JDA\",\"patta_year\":null,\"valuation\":8000000,\"income\":100000,\"existing_emi\":30000,\"cibil\":700,\"city\":\"jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":43.75,\"foir_pct\":30,\"property_usage\":null,\"income_type\":null,\"remarks\":\"jda sorp | nope | nope | nope\"}', 'new', '2026-04-09 20:28:15', '2026-04-09 20:28:15', 0),
(5, 3, 'ram', '7777777777', 'ram@yahoo.com', 'LAP', 4000000, 'JDA', NULL, '', 'jaipur', 8000000, '', 200000, 0, 650, 'jaipur', '', 50.00, NULL, '', '{\"loan_type\":\"LAP\",\"amount\":4000000,\"property_title\":\"JDA\",\"patta_year\":null,\"valuation\":8000000,\"income\":200000,\"existing_emi\":0,\"cibil\":650,\"city\":\"jaipur\",\"occupation\":null,\"ltv_pct\":50,\"foir_pct\":null,\"property_usage\":null,\"income_type\":null,\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-09 20:44:39', '2026-04-09 20:44:52', 0),
(6, 3, 'shyam', '7777777777', 'shyam@gmail.com', 'LAP', 3500000, 'JDA', NULL, '', 'khn', 7000000, '', 70000, 0, 750, 'jaipur', '', 50.00, NULL, 'good', '{\"loan_type\":\"LAP\",\"amount\":3500000,\"property_title\":\"JDA\",\"patta_year\":null,\"valuation\":7000000,\"income\":70000,\"existing_emi\":0,\"cibil\":750,\"city\":\"jaipur\",\"occupation\":null,\"ltv_pct\":50,\"foir_pct\":null,\"property_usage\":null,\"income_type\":null,\"remarks\":\"good\"}', 'lender_shortlisted', '2026-04-09 20:55:06', '2026-04-09 20:55:18', 0),
(7, 4, 'saurabh acharya', '848484844884', 'mutesoundsaurabh32@gmail.com', 'Institutional Loan', 1200000, '90-B Converter', NULL, '', 'H er ak', 2000000, '', 100000, 20000, 600, 'Bhilwara', 'Self-Employed / Proprietor', 60.00, 20.00, '', '{\"loan_type\":\"Institutional Loan\",\"amount\":1200000,\"property_title\":\"90-B Converter\",\"patta_year\":null,\"valuation\":2000000,\"income\":100000,\"existing_emi\":20000,\"cibil\":600,\"city\":\"Bhilwara\",\"occupation\":\"Self-Employed \\/ Proprietor\",\"ltv_pct\":60,\"foir_pct\":20,\"property_usage\":null,\"income_type\":null,\"remarks\":\"\"}', 'new', '2026-04-10 07:12:18', '2026-04-10 07:12:18', 0),
(8, 3, 'jcj', '8888888888', '', 'Home Loan', 1000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 50000000, 'Salary Slip', 15000, 10000, 725, 'Jaipur', 'Salaried – Private', 2.00, 66.67, 'jkhf', '{\"loan_type\":\"Home Loan\",\"amount\":1000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":50000000,\"income\":15000,\"existing_emi\":10000,\"cibil\":725,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":2,\"foir_pct\":66.67,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"jkhf\"}', 'lender_shortlisted', '2026-04-11 06:41:40', '2026-04-11 06:42:12', 0),
(9, 4, 'Rakesh ji', '8888888888', '', 'Loan Against Property (LAP)', 4000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 10000000, 'ITR Filed', 25000, 30000, 650, 'Jaipur', 'Business Owner / Director', 40.00, 120.00, 'Ku ch patta', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":4000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":10000000,\"income\":25000,\"existing_emi\":30000,\"cibil\":650,\"city\":\"Jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":40,\"foir_pct\":120,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"ITR Filed\",\"remarks\":\"Ku ch patta\"}', 'lender_shortlisted', '2026-04-11 06:47:08', '2026-04-11 06:47:39', 0),
(10, 3, 'hgdhg', '8787787877', '', 'Home Loan', 4000000, 'Society Patta', 2017, 'Residential – Self Occupied', 'hjgdg', 10000000, 'Salary Slip', 75000, 10000, 750, 'Kota', 'Business Owner / Director', 40.00, 13.33, 'jfh', '{\"loan_type\":\"Home Loan\",\"amount\":4000000,\"property_title\":\"Society Patta\",\"patta_year\":\"2017\",\"valuation\":10000000,\"income\":75000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Kota\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":40,\"foir_pct\":13.33,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"jfh\"}', 'lender_shortlisted', '2026-04-11 13:09:47', '2026-04-11 13:10:23', 0),
(11, 4, 'saurabh acharya', '8582582888', '', 'Loan Against Property (LAP)', 4000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 1500000, 'Salary Slip', 50000, 30000, 650, 'Jaipur', 'Salaried – Government', 266.67, 60.00, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":4000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":1500000,\"income\":50000,\"existing_emi\":30000,\"cibil\":650,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Government\",\"ltv_pct\":266.67,\"foir_pct\":60,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-11 13:14:34', '2026-04-11 13:15:55', 0),
(12, 4, 'saurabh acharya', '8582582888', '', 'Loan Against Property (LAP)', 4000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 1500000, 'Salary Slip', 50000, 30000, 650, 'Jaipur', 'Salaried – Government', 266.67, 60.00, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":4000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":1500000,\"income\":50000,\"existing_emi\":30000,\"cibil\":650,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Government\",\"ltv_pct\":266.67,\"foir_pct\":60,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-11 13:15:19', '2026-04-18 14:44:25', 0),
(13, 5, 'Prashant Mathur', '9982213330', 'mathurprashant1982@gmail.com', 'Home Loan', 3000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 4000000, 'Salary Slip', 100000, 0, 725, 'Jaipur', 'Salaried – Private', 75.00, NULL, 'Request BT from Bajaj', '{\"loan_type\":\"Home Loan\",\"amount\":3000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":4000000,\"income\":100000,\"existing_emi\":0,\"cibil\":725,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":75,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"Request BT from Bajaj\"}', 'new', '2026-04-13 04:40:04', '2026-04-13 04:40:04', 0),
(14, 3, 'hdhdhdgh', '7777777777', '', 'Home Loan', 1500000, 'Society Patta', 2015, 'Residential – Rented', '', 1000000, 'Salary Slip', 15000, 0, 725, 'Jaipur', 'Salaried – Private', 150.00, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":1500000,\"property_title\":\"Society Patta\",\"patta_year\":\"2015\",\"valuation\":1000000,\"income\":15000,\"existing_emi\":0,\"cibil\":725,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":150,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Rented\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-13 07:32:24', '2026-04-13 07:32:45', 0),
(15, 3, 'hfghjfjh', '9797890700', '', 'Loan Against Property (LAP)', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 5000000, 'Salary Slip', 25000, 0, 725, 'Jaipur', 'Business Owner / Director', 100.00, NULL, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":5000000,\"income\":25000,\"existing_emi\":0,\"cibil\":725,\"city\":\"Jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":100,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-13 07:39:00', '2026-04-13 07:39:38', 0),
(16, 6, 'Rahul Sharma', '9311046526', '', 'Loan Against Property (LAP)', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', 'Naman residency Jaipur', 5000000, 'Salary Slip', 75000, 30000, 750, 'Jaipur', 'Salaried – Private', 100.00, 40.00, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":5000000,\"income\":75000,\"existing_emi\":30000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":100,\"foir_pct\":40,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-13 07:39:08', '2026-04-13 07:39:37', 0),
(17, 3, 'radhey', '7676767676', '', 'Home Loan', 500000, 'Society Patta', 2016, 'Residential – Rented', '', 1000000, 'No Income Proof', 15000, 10000, 750, 'Jaipur', 'Salaried – Private', 50.00, 66.67, '', '{\"loan_type\":\"Home Loan\",\"amount\":500000,\"property_title\":\"Society Patta\",\"patta_year\":\"2016\",\"valuation\":1000000,\"income\":15000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":50,\"foir_pct\":66.67,\"property_usage\":\"Residential \\u2013 Rented\",\"income_type\":\"No Income Proof\",\"remarks\":\"\"}', 'new', '2026-04-14 06:06:49', '2026-04-14 06:06:49', 0),
(18, 3, 'wawa', '8989898989', '', 'Home Loan', 500000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 1000000, 'Salary Slip', 15000, 0, 750, 'Jaipur', 'Salaried – Government', 50.00, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":500000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":1000000,\"income\":15000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Government\",\"ltv_pct\":50,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'new', '2026-04-14 06:08:56', '2026-04-14 06:08:56', 0),
(19, 3, 'dadad', '9698696969', '', 'Home Loan', 500000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 1000000, 'Salary Slip', 25000, 0, 750, 'Jaipur', 'Salaried – Private', 50.00, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":500000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":1000000,\"income\":25000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":50,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-14 06:10:58', '2026-04-14 06:34:51', 0),
(20, 3, 'dadad', '9698696969', '', 'Home Loan', 500000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 1000000, 'Salary Slip', 25000, 0, 750, 'Jaipur', 'Salaried – Private', 50.00, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":500000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":1000000,\"income\":25000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":50,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-14 06:35:01', '2026-04-14 06:35:15', 0),
(21, 3, 'che', '9898989898', '', 'Home Loan', 500000, 'Registry', NULL, 'Residential – Rented', '', 1000000, 'Salary Slip', 15000, 5000, 750, 'Jodhpur', 'Salaried – Private', 50.00, 33.33, '', '{\"loan_type\":\"Home Loan\",\"amount\":500000,\"property_title\":\"Registry\",\"patta_year\":null,\"valuation\":1000000,\"income\":15000,\"existing_emi\":5000,\"cibil\":750,\"city\":\"Jodhpur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":50,\"foir_pct\":33.33,\"property_usage\":\"Residential \\u2013 Rented\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-14 08:05:33', '2026-04-14 08:05:50', 0),
(22, 7, 'Chetan', '7014349187', '', 'Institutional Loan', 10000000, 'Society Patta', 2018, 'Residential – Vacant', '', 50000000, 'No Income Proof', 200000, 50000, 750, 'Jaipur', 'Business Owner / Director', 20.00, 25.00, '', '{\"loan_type\":\"Institutional Loan\",\"amount\":10000000,\"property_title\":\"Society Patta\",\"patta_year\":\"2018\",\"valuation\":50000000,\"income\":200000,\"existing_emi\":50000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Business Owner \\/ Director\",\"ltv_pct\":20,\"foir_pct\":25,\"property_usage\":\"Residential \\u2013 Vacant\",\"income_type\":\"No Income Proof\",\"remarks\":\"\"}', 'new', '2026-04-14 10:12:57', '2026-04-14 10:12:57', 0),
(23, 3, 'Abc', '8888888888', '', 'Loan Against Property (LAP)', 1000000, 'Freehold', NULL, 'Residential – Vacant', '', 20000000, 'No Income Proof', 200000, 10000, 750, 'Kota', 'Retired', 5.00, 5.00, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":1000000,\"property_title\":\"Freehold\",\"patta_year\":null,\"valuation\":20000000,\"income\":200000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Kota\",\"occupation\":\"Retired\",\"ltv_pct\":5,\"foir_pct\":5,\"property_usage\":\"Residential \\u2013 Vacant\",\"income_type\":\"No Income Proof\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-14 10:13:03', '2026-04-14 10:13:31', 0),
(24, 3, 'Abc', '8888888888', '', 'Loan Against Property (LAP)', 1000000, 'Freehold', NULL, 'Residential – Vacant', '', 20000000, 'No Income Proof', 200000, 10000, 750, 'Kota', 'Retired', 5.00, 5.00, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":1000000,\"property_title\":\"Freehold\",\"patta_year\":null,\"valuation\":20000000,\"income\":200000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Kota\",\"occupation\":\"Retired\",\"ltv_pct\":5,\"foir_pct\":5,\"property_usage\":\"Residential \\u2013 Vacant\",\"income_type\":\"No Income Proof\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-14 10:14:50', '2026-04-14 10:15:09', 0),
(25, 7, 'Mahesh', '7014349187', '', 'Loan Against Property (LAP)', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 15000000, 'Salary Slip', 150000, 0, 750, 'Jaipur', 'Salaried – Private', 33.33, NULL, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":15000000,\"income\":150000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":33.33,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-14 10:16:16', '2026-04-14 10:16:37', 0),
(26, 3, 'chat gpt', '8080808080', '', 'Home Loan', 10000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 20000000, 'Salary Slip', 25000, 5000, 750, 'Jaipur', 'Salaried – Private', 50.00, 20.00, '', '{\"loan_type\":\"Home Loan\",\"amount\":10000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":20000000,\"income\":25000,\"existing_emi\":5000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":50,\"foir_pct\":20,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-15 11:02:50', '2026-04-15 11:03:07', 0),
(27, 3, 'raju', '9090909090', '', 'Home Loan', 1000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 15000000, 'Salary Slip', 50000, 0, 750, 'Jaipur', 'Salaried – Private', 6.67, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":1000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":15000000,\"income\":50000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":6.67,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-16 07:02:06', '2026-04-16 07:02:22', 0),
(28, 3, 'raju22', '9090909090', '', 'Home Loan', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 15000000, 'Salary Slip', 15000, 0, 750, 'Jaipur', 'Salaried – Private', 33.33, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":15000000,\"income\":15000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":33.33,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'new', '2026-04-16 07:11:28', '2026-04-16 07:11:28', 0),
(29, 3, 'raju22', '9090909090', '', 'Home Loan', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 15000000, 'Salary Slip', 15000, 0, 750, 'Jaipur', 'Salaried – Private', 33.33, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":15000000,\"income\":15000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":33.33,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-16 07:12:37', '2026-04-16 07:12:52', 0),
(30, 3, 'raju22', '9090909090', '', 'Home Loan', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 15000000, 'Salary Slip', 15000, 0, 750, 'Jaipur', 'Salaried – Private', 33.33, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":15000000,\"income\":15000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":33.33,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-16 07:29:49', '2026-04-21 20:34:30', 0),
(31, 4, 'kaju', '9090909090', '', 'Home Loan', 5000000, 'Society Patta', NULL, 'Commercial', '', 15000000, 'ITR Filed', 15000, 5000, 750, 'Jaipur', 'Salaried – Government', 33.33, 33.33, '', '{\"loan_type\":\"Home Loan\",\"amount\":5000000,\"property_title\":\"Society Patta\",\"patta_year\":null,\"valuation\":15000000,\"income\":15000,\"existing_emi\":5000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Government\",\"ltv_pct\":33.33,\"foir_pct\":33.33,\"property_usage\":\"Commercial\",\"income_type\":\"ITR Filed\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-16 13:15:32', '2026-04-16 13:17:10', 0),
(32, 4, 'mumu', '9999999999', '', 'Home Loan', 1000000, 'JDA Approved', NULL, 'Residential – Rented', '', 5000000, 'Salary Slip', 25000, 5000, 750, 'Jodhpur', 'Salaried – Private', 20.00, 20.00, '', '{\"loan_type\":\"Home Loan\",\"amount\":1000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":5000000,\"income\":25000,\"existing_emi\":5000,\"cibil\":750,\"city\":\"Jodhpur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":20,\"foir_pct\":20,\"property_usage\":\"Residential \\u2013 Rented\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-16 17:06:58', '2026-04-16 17:07:16', 0),
(33, 4, 'raju', '9898989898', '', 'Home Loan', 10000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 20000000, 'Salary Slip', 75000, 10000, 750, 'Jaipur', 'Salaried – Private', 50.00, 13.33, '', '{\"loan_type\":\"Home Loan\",\"amount\":10000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":20000000,\"income\":75000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":50,\"foir_pct\":13.33,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-18 07:58:39', '2026-04-18 07:58:58', 0),
(34, 4, 'Chet', '9219219219', '', 'Home Loan', 2000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 20000000, 'Salary Slip', 50000, 0, 750, 'Jaipur', 'Salaried – Private', 10.00, NULL, '', '{\"loan_type\":\"Home Loan\",\"amount\":2000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":20000000,\"income\":50000,\"existing_emi\":0,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":10,\"foir_pct\":null,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-18 14:02:03', '2026-04-18 14:02:24', 0),
(35, 4, 'chetram', '9879879879', '', 'Home Loan', 5000000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 20000000, 'Salary Slip', 25000, 10000, 750, 'Jaipur', 'Salaried – Private', 25.00, 40.00, '', '{\"loan_type\":\"Home Loan\",\"amount\":5000000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":20000000,\"income\":25000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":25,\"foir_pct\":40,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'follow_up', '2026-04-18 14:03:52', '2026-04-18 14:10:33', 0),
(36, 3, 'Chet singh', '8290435477', '', 'Loan Against Property (LAP)', 7500000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 10000000, 'Salary Slip', 25000, 5000, 725, 'Jaipur', 'Salaried – Government', 75.00, 20.00, '', '{\"loan_type\":\"Loan Against Property (LAP)\",\"amount\":7500000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":10000000,\"income\":25000,\"existing_emi\":5000,\"cibil\":725,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Government\",\"ltv_pct\":75,\"foir_pct\":20,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-21 15:34:19', '2026-04-21 15:35:31', 0),
(37, 4, 'chet singh', '9191919191', '', 'Home Loan', 500000, 'JDA Approved', NULL, 'Residential – Self Occupied', '', 7500000, 'Salary Slip', 25000, 10000, 750, 'Jaipur', 'Salaried – Private', 6.67, 40.00, '', '{\"loan_type\":\"Home Loan\",\"amount\":500000,\"property_title\":\"JDA Approved\",\"patta_year\":null,\"valuation\":7500000,\"income\":25000,\"existing_emi\":10000,\"cibil\":750,\"city\":\"Jaipur\",\"occupation\":\"Salaried \\u2013 Private\",\"ltv_pct\":6.67,\"foir_pct\":40,\"property_usage\":\"Residential \\u2013 Self Occupied\",\"income_type\":\"Salary Slip\",\"remarks\":\"\"}', 'lender_shortlisted', '2026-04-21 21:01:50', '2026-04-21 21:02:12', 0);

-- --------------------------------------------------------

--
-- Table structure for table `diva_conversations`
--

CREATE TABLE `diva_conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `customer_name` varchar(120) DEFAULT NULL,
  `customer_mobile` varchar(15) DEFAULT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `loan_type` varchar(60) DEFAULT NULL,
  `loan_amount` bigint(20) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `cibil` smallint(6) DEFAULT NULL,
  `monthly_income` bigint(20) DEFAULT NULL,
  `existing_emi` bigint(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `property_title` varchar(100) DEFAULT NULL,
  `income_source` varchar(100) DEFAULT NULL,
  `profile_json` mediumtext DEFAULT NULL,
  `conversation_json` mediumtext DEFAULT NULL,
  `lender_results_json` mediumtext DEFAULT NULL,
  `total_lenders_found` smallint(6) DEFAULT 0,
  `top_lender` varchar(120) DEFAULT NULL,
  `top_lender_roi` decimal(5,2) DEFAULT NULL,
  `status` enum('started','in_progress','contact_shared','results_shown','no_results','abandoned') DEFAULT 'started',
  `lang` varchar(5) DEFAULT 'en',
  `steps_completed` tinyint(4) DEFAULT 0,
  `source` varchar(60) DEFAULT 'website',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contact_shared_at` timestamp NULL DEFAULT NULL,
  `results_shown_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diva_leads_v2`
--

CREATE TABLE `diva_leads_v2` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `product_type` varchar(50) DEFAULT NULL,
  `loan_amount` int(11) DEFAULT 0,
  `property_value` int(11) DEFAULT 0,
  `monthly_income` int(11) DEFAULT 0,
  `business_turnover` int(11) DEFAULT 0,
  `existing_emi` int(11) DEFAULT 0,
  `cibil_score` int(11) DEFAULT 0,
  `property_type` varchar(80) DEFAULT NULL,
  `lead_score` int(11) DEFAULT 0,
  `lead_status` varchar(30) DEFAULT NULL,
  `last_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `alert_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diva_leads_v2`
--

INSERT INTO `diva_leads_v2` (`id`, `session_id`, `name`, `mobile`, `city`, `product_type`, `loan_amount`, `property_value`, `monthly_income`, `business_turnover`, `existing_emi`, `cibil_score`, `property_type`, `lead_score`, `lead_status`, `last_message`, `ip_address`, `created_at`, `updated_at`, `alert_sent`) VALUES
(1, 'diva_mny8zvvd_aocgse', '', '9898989898', 'Jaipur', 'Home Loan', 3000000, 0, 60000, 0, 0, 750, '', 100, 'qualified', '??', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:36:14', '2026-04-14 06:38:44', 0),
(6, 'diva_mny9i6ot_iwcent', '', '', '', '', 0, 0, 0, 0, 0, 0, '', 0, 'partial', 'Try again', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:51:47', '2026-04-14 06:51:47', 0),
(7, 'diva_mny9k9ef_pcwcgc', '', '', '', 'lap', 0, 0, 0, 0, 0, 0, '', 10, 'partial', 'Other', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:52:06', '2026-04-14 06:52:21', 0),
(10, 'diva_mny9n411_xuu0ox', 'saur', '', 'Jaipur', 'lap', 2500000, 2500000, 0, 0, 0, 0, '', 65, 'partial', '₹25-50 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 06:54:16', '2026-04-14 06:55:12', 0),
(18, 'diva_mny9zmd3_61casj', 'saur', '', 'bhilwara', 'home', 25000000, 50000000, 100000, 0, 1, 780, 'society', 100, 'partial', 'saur', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:04:04', '2026-04-14 07:05:26', 0),
(28, 'diva_mnyadpwk_frtmlp', 'saur', '', 'jaipur', 'home', 3750000, 1750000, 37500, 0, 1, 780, 'society', 95, 'partial', 'saur', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:14:56', '2026-04-14 07:16:09', 0),
(39, 'diva_mnyahi3r_td1zin', 'saur', '', 'bhilwara', 'home', 60000000, 150000000, 250000, 0, 0, 725, 'freehold', 90, 'partial', 'saur', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:17:54', '2026-04-14 07:19:04', 0),
(49, 'diva_mnyb9ukf_ububuh', 'saur', '', 'jodhpur', 'home', 3750000, 7500000, 75000, 0, 0, 0, 'freehold', 80, 'partial', 'saur', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:39:59', '2026-04-14 07:41:16', 0),
(59, 'diva_mnybdbq0_rfbhn3', 'saur', '', 'jodhpur', 'business', 3750000, 7500000, 150000, 0, 0, 0, '', 75, 'partial', 'saur', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:42:38', '2026-04-14 07:43:12', 0),
(66, 'diva_mnybnkbn_i6aye2', '', '', 'bhilwara', 'car', 0, 0, 150000, 0, 0, 0, '', 40, 'new', '₹1-2 Lakh', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:50:35', '2026-04-14 07:50:53', 0),
(70, 'diva_mnybybmr_uvrlx4', '', '', '', '', 0, 0, 0, 0, 0, 0, '', 0, 'new', '🏠 Home Loan', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:58:54', '2026-04-14 07:58:54', 0),
(71, 'diva_mnybyqcx_xp47oy', '', '', '', '', 0, 0, 0, 0, 0, 0, '', 0, 'new', '🏢 Loan Against Property', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 07:59:11', '2026-04-14 07:59:11', 0),
(72, 'diva_mnyc2gel_dlgizm', '', '', '', '', 0, 0, 0, 0, 0, 0, '', 0, 'new', '🏠 Home Loan', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:02:07', '2026-04-14 08:02:07', 0),
(73, 'diva_mnyc8br2_xbnajg', 'saur', '8888888888', 'jalore', 'home', 3750000, 7500000, 37500, 0, 0, 780, 'jda', 100, 'qualified', '8888888888', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:06:40', '2026-04-14 08:07:23', 0),
(87, 'diva_mnyca7y9_y9y36g', 'chu', '7777777777', 'jaipur', 'lap', 3750000, 7500000, 37500, 0, 17500, 780, 'freehold', 100, 'qualified', '7777777777', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:08:10', '2026-04-14 08:08:36', 0),
(99, 'diva_mnycca7j_6fm8fp', 'vishal', '9898989898', 'jalore', 'lap', 3750000, 60000000, 250000, 0, 17500, 725, 'rhb', 100, 'qualified', '9898989898', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:09:43', '2026-04-14 08:18:10', 0),
(111, 'diva_mnycupug_sxs3zj', 'num', '8989999999', 'jalore', 'lap', 60000000, 60000000, 75000, 0, 7500, 780, 'freehold', 100, 'qualified', '98989898989999999', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:24:04', '2026-04-14 08:25:28', 0),
(132, 'diva_mnyd2fyn_y4jghy', 'VISHUUUU', '9898989889', 'sikar', 'home', 6700000, 60000000, 78000, 0, 17500, 780, 'rada', 100, 'qualified', '98989898989889', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 08:30:05', '2026-04-14 08:31:16', 0),
(147, 'diva_mnyewwu4_ahj346', '', '', 'jaipur', 'lap', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Jaipur', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 09:21:45', '2026-04-14 09:21:46', 0),
(149, 'diva_mnyex095_te08wy', 'dudaram', '7878787878', 'dudu', 'lap', 7000000, 15000000, 75000, 0, 60000, 780, 'duda', 100, 'qualified', '7878787878', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 09:21:49', '2026-04-14 09:22:55', 0),
(163, 'diva_mnyf5waf_bk7eju', 'trump', '8989898989', 'usa', 'lap', 7000000, 60000000, 150000, 0, 7500, 780, 'federal', 100, 'qualified', 'option2', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 09:28:46', '2026-04-14 09:32:46', 0),
(183, 'diva_mnyfbbfw_ta7ckn', 'chunaram', '9999999999', 'bikaner', 'home', 35000000, 60000000, 150000, 0, 37500, 780, 'panchayat', 100, 'qualified', 'ok', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 09:32:57', '2026-04-14 09:36:39', 0),
(197, 'diva_mnyfx8xg_cg7n3j', 'bhuo', '8989898989', 'jodhpur', 'lap', 1750000, 7500000, 150000, 0, 17500, 725, 'nagar_nigam', 100, 'qualified', '💬 WhatsApp Now', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 09:50:00', '2026-04-14 09:51:36', 0),
(215, 'diva_mnyhrvdo_q0vm0m', 'amit', '9999999999', 'jaipur', 'lap', 7500000, 3750000, 75000, 0, 17500, 725, 'jda', 100, 'qualified', 'rate kya h', '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9', '2026-04-14 10:41:52', '2026-04-14 12:04:20', 0),
(241, 'diva_mnym0ii5_wgro48', '', '', 'jaipur', 'hi', 0, 0, 0, 0, 0, 0, '', 25, 'new', '💼 Salaried - Private', '2405:201:5c08:d17e:908c:ce81:b35d:4c8a', '2026-04-14 12:41:27', '2026-04-14 12:41:32', 0),
(244, 'diva_mnzhvcfg_isw12u', 'bhu', '8787878787', 'jaipur', 'car', 1750000, 0, 150000, 0, 37500, 675, '', 100, 'qualified', '📞 Expert se baat', '2405:201:5c08:d17e:908c:ce81:b35d:4c8a', '2026-04-15 03:32:21', '2026-04-15 03:33:18', 0),
(255, 'diva_mnzw1k3u_7st80w', 'ggggg', '9898989898', 'jodhpur', 'home', 1750000, 3750000, 9889889, 0, 0, 780, 'freehold', 100, 'qualified', '9898989898', '2405:201:5c08:d17e:9998:3a69:7deb:f3ef', '2026-04-15 10:09:04', '2026-04-15 10:09:31', 0),
(268, 'diva_mnzx7zqk_ikrrty', 'chatgpt', '8080808080', 'jaipur', 'lap', 60000000, 60000000, 250000, 0, 60000, 780, 'jda', 100, 'qualified', '8080808080', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 10:42:06', '2026-04-15 10:42:54', 0),
(280, 'diva_mnzygqpt_sstqs0', 'chatu', '8989898989', 'jaipur', 'home', 1750000, 3750000, 75000, 0, 0, 725, 'freehold', 100, 'qualified', 'LTV zyada kiska?', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 11:18:39', '2026-04-15 11:58:10', 0),
(297, 'diva_mnzzya2x_rj40g4', 'damru', '7777777777', 'ajmer', 'car', 1750000, 0, 20000, 0, 0, 780, '', 100, 'qualified', 'Rate 8% pe?', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 11:58:32', '2026-04-15 11:59:04', 0),
(308, 'diva_mo000t14_msog2v', 'damru', '7777777777', 'jaipur', 'home', 15000000, 3750000, 75000, 0, 0, 780, 'jda', 100, 'qualified', 'Start over', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 12:00:28', '2026-04-15 12:25:14', 0),
(321, 'diva_mo00x8k9_jmery7', 'chat2', '8989898989', 'jaipur', 'home', 60000000, 60000000, 150000, 0, 7500, 725, 'freehold', 100, 'qualified', 'Best rate kaun dega?', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 12:25:40', '2026-04-15 12:29:16', 0),
(334, 'diva_mo012jja_6tbq6y', 'chatuuuuuuuu', '8989898989', 'jaipur', 'home', 60000000, 7500000, 250000, 0, 0, 780, 'jda', 100, 'qualified', '8989898989', '2405:201:5c08:d17e:e500:b691:2586:c3a7', '2026-04-15 12:29:47', '2026-04-15 12:30:16', 0),
(346, 'diva_mo0xjdny_ezhsk7', 'gggg', '7777777777', 'jaipur', 'home', 60000000, 60000000, 20000, 0, 0, 780, 'jda', 100, 'qualified', 'Best ROI kaun?', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 03:38:43', '2026-04-16 03:40:25', 0),
(360, 'diva_mo0xy9ew_w5z1hy', 'mmmmmmm', '9999999999', 'jaipur', 'home', 1750000, 3750000, 75000, 0, 0, 780, 'freehold', 100, 'qualified', '9999999999', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 03:50:13', '2026-04-16 03:50:38', 0),
(372, 'diva_mo0z2y7n_5zvlgw', 'newzaaa', '8787878787', 'alwar', 'home', 3750000, 3750000, 20000, 0, 0, 780, 'freehold', 100, 'qualified', 'Start over', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 04:21:52', '2026-04-16 06:26:49', 0),
(385, 'diva_mo13jtju_np1z4l', 'viewzaaaaaa', '8989898989', 'jaipur', 'home', 60000000, 3750000, 150000, 0, 0, 725, 'jda', 100, 'qualified', '8989898989', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 06:26:57', '2026-04-16 06:27:23', 0),
(397, 'diva_mo14q4l9_1ox8tj', 'raju', '9090909090', 'jaipur', 'home', 1750000, 7500000, 150000, 0, 0, 780, 'freehold', 100, 'qualified', '9090909090', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 06:59:53', '2026-04-16 07:00:18', 0),
(409, 'diva_mo15x3xa_1kh00o', 'Gadura', '7777777777', 'jaipur', 'home', 60000000, 60000000, 150000, 0, 0, 780, 'jda', 100, 'qualified', '7777777777', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 07:33:18', '2026-04-16 07:33:43', 0),
(421, 'diva_mo1hvtci_5j5fih', '', '', 'jodhpur', 'home', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Jodhpur', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:08:13', '2026-04-16 13:08:14', 0),
(423, 'diva_mo1i31sv_0tghq5', '', '', 'jodhpur', 'home', 0, 0, 0, 0, 0, 0, '', 25, 'new', '🌾 Agriculture Income', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:13:54', '2026-04-16 13:14:15', 0),
(426, 'diva_mo1ig084_to8loh', '', '', '', 'car', 0, 0, 0, 0, 0, 0, '', 10, 'new', '🚗 Car Loan', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:23:55', '2026-04-16 13:23:55', 0),
(427, 'diva_mo1ivyar_3cj8n2', 'pakah', '9090909090', 'jaipur', 'home', 60000000, 7500000, 150000, 0, 0, 780, 'freehold', 100, 'qualified', '9090909090', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 13:39:17', '2026-04-16 13:39:56', 0),
(439, 'diva_mo1mntdi_55j99a', '', '', 'jaipur', 'car', 0, 0, 0, 0, 0, 0, '', 25, 'new', '🏭 Business Owner', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 16:37:25', '2026-04-16 16:37:27', 0),
(442, 'diva_mo1po6lq_vgapod', '', '', 'jodhpur', 'home', 0, 0, 0, 0, 0, 0, '', 25, 'new', '🏭 Business Owner', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 16:46:15', '2026-04-16 16:46:17', 0),
(445, 'diva_mo1q1x66_at4sih', 'huhu', '9999999999', 'jaipur', 'home', 1750000, 7500000, 75000, 0, 0, 780, 'jda', 100, 'qualified', '9999999999', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 16:58:08', '2026-04-16 16:58:28', 0),
(457, 'diva_mo1qmqel_isbkjl', 'huhuhuh', '9999999999', 'jaipur', 'home', 1750000, 3750000, 75000, 0, 17500, 725, 'jda', 100, 'qualified', '9999999999', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 17:44:25', '2026-04-16 17:44:42', 0),
(469, 'diva_mo1rzu9t_9xrgyx', 'jaju', '9999999999', 'jaipur', 'home', 60000000, 60000000, 250000, 0, 0, 780, 'jda', 100, 'qualified', '9999999999', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 17:51:20', '2026-04-16 17:51:46', 0),
(481, 'diva_mo1s3wd1_i8e9tp', 'prac', '9090909090', 'jaipur', 'home', 60000000, 60000000, 20000, 0, 0, 780, 'jda', 100, 'qualified', '9090909090', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 17:54:24', '2026-04-16 17:54:47', 0),
(493, 'diva_mo1sbadd_5pkni3', 'prachu', '9090909090', 'jaipur', 'home', 1750000, 3750000, 75000, 0, 17500, 725, 'jda', 100, 'qualified', '9090909090', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:00:10', '2026-04-16 18:00:33', 0),
(505, 'diva_mo1sg0oe_kya5uz', 'prachi', '9090909090', 'jaipur', 'home', 1750000, 3750000, 20000, 0, 0, 780, 'jda', 100, 'qualified', '9090909090', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:03:50', '2026-04-16 18:04:15', 0),
(517, 'diva_mo1smy3a_pm8adi', 'prachi', '9879879879', 'jaipur', 'home', 60000000, 60000000, 250000, 0, 0, 780, 'jda', 100, 'qualified', '9879879879', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:09:14', '2026-04-16 18:09:36', 0),
(529, 'diva_mo1st4ej_xam4vw', 'srk', '9879879879', 'jaipur', 'home', 60000000, 60000000, 20000, 0, 0, 780, 'jda', 100, 'qualified', '9879879879', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:14:01', '2026-04-16 18:14:24', 0),
(541, 'diva_mo1ua4xj_5ce4mb', '', '', '', 'home', 0, 0, 0, 0, 0, 0, '', 10, 'new', '🏠 Home Loan', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:55:20', '2026-04-16 18:55:20', 0),
(542, 'diva_mo1ucphf_gioc8l', '', '', 'jaipur', 'home', 60000000, 3750000, 250000, 0, 0, 675, 'jda', 85, 'partial', 'Below 650 (Low 🔴)', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:57:58', '2026-04-16 18:58:36', 0),
(552, 'diva_mo1ucphf_meagp2', 'radhey', '9090909090', 'jaipur', 'panchi', 60000000, 60000000, 20000, 0, 0, 780, 'jda', 100, 'qualified', '9090909090', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 18:58:44', '2026-04-16 18:59:26', 0),
(564, 'diva_mo1uia0w_d7vx4f', 'sattar singh', '8989898989', 'jaipur', 'home', 1750000, 60000000, 700000, 0, 60000, 600, 'jda', 100, 'qualified', '8989898989', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:01:42', '2026-04-16 19:02:28', 0),
(577, 'diva_mo1ulhz6_c875ha', '', '', 'sikar', 'home', 15000000, 60000000, 250000, 0, 60000, 675, 'panchayat', 85, 'partial', 'Below 650 (Low 🔴)', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:04:45', '2026-04-16 19:05:28', 0),
(587, 'diva_mo1ulhz6_bo29ox', '', '', '', 'hi', 0, 0, 0, 0, 0, 0, '', 10, 'new', 'hi', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:05:43', '2026-04-16 19:05:43', 0),
(588, 'diva_mo1uqt44_zxcx6j', '', '', 'jaipur', 'home', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Jaipur', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:08:13', '2026-04-16 19:08:15', 0),
(590, 'diva_mo1uqza9_cg0umt', '', '', '', '', 0, 0, 0, 0, 0, 0, '', 0, 'new', '', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:17:44', '2026-04-16 19:17:44', 0),
(591, 'diva_mo1v3ckh_ntarmk', '', '', 'jaipur', 'home', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Jaipur', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:17:59', '2026-04-16 19:18:02', 0),
(593, 'diva_mo1v4gl9_bsxt7o', 'Gujjar', '9090809090', 'jaipur', 'home', 35000000, 60000000, 250000, 0, 60000, 780, 'jda', 100, 'qualified', '9090809090', '185.165.242.130', '2026-04-16 19:19:10', '2026-04-16 19:20:08', 0),
(605, 'diva_mo1v3wxw_qgj6v6', '', '', '', 'मुझे लोन के लिए मुझे होम लोन जाएंगे', 0, 0, 0, 0, 0, 0, '', 10, 'new', 'मुझे लोन के लिए मुझे होम लोन जाएंगे', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:20:58', '2026-04-16 19:20:58', 0),
(606, 'diva_mo1v7tnb_lawl62', '', '', 'जयपर', 'मुझे लोन चाहिए', 60000000, 60000000, 150000, 0, 60000, 780, 'रक', 85, 'partial', '750+ (Excellent 🟢)', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:21:37', '2026-04-16 19:24:48', 0),
(620, 'diva_mo1vcstv_dshnai', '', '', 'jaipur', 'car', 1750000, 0, 20000, 0, 0, 780, '', 70, 'partial', '750+ (Excellent 🟢)', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 19:25:25', '2026-04-16 19:25:34', 0),
(627, 'diva_mo1xbses_aeiaps', '', '', '', 'home', 0, 0, 0, 0, 0, 0, '', 10, 'new', '🏠 Home Loan', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-16 20:20:42', '2026-04-16 20:20:42', 0),
(628, 'diva_mo2fxbxn_aw3z0j', '', '', '', '', 0, 0, 0, 0, 0, 0, '', 0, 'new', '', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-17 05:04:40', '2026-04-17 05:04:40', 0),
(629, 'diva_mo2g90pp_7g85z4', '', '', 'dilli ncr', 'personal', 35000000, 0, 150000, 0, 60000, 600, '', 70, 'partial', 'Pata nahi', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-17 05:10:35', '2026-04-17 05:10:51', 0),
(636, 'diva_mo2i35m4_by4wls', '', '', '', 'home', 0, 0, 0, 0, 0, 0, '', 10, 'new', '🏠 Home Loan', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-17 06:05:36', '2026-04-17 06:05:36', 0),
(637, 'diva_mo2ru1wp_dqwxk7', 'panchi2', '8908908908', 'jaipur', 'home', 1750000, 3750000, 20000, 0, 7500, 780, 'jda', 100, 'qualified', 'consent_given', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-17 10:34:34', '2026-04-17 10:35:42', 0),
(653, 'diva_mo2ryqcy_8sojl1', '', '', 'jaipur', 'home', 1750000, 3750000, 20000, 0, 0, 780, 'jda', 85, 'partial', '750+ (Excellent 🟢)', '2405:201:5c08:d17e:172:98a5:2f74:5b79', '2026-04-17 10:38:28', '2026-04-17 10:38:40', 0),
(663, 'diva_mo2uw9zf_1m7w34', '', '', 'hi', 'hi', 0, 0, 0, 0, 0, 0, 'hi', 30, 'new', 'hi', '2405:201:5c08:d17e:449e:e9eb:24df:4c05', '2026-04-17 12:01:04', '2026-04-17 12:01:14', 0),
(667, 'diva_mo2wnc5r_twov1d', '', '', 'jaipur', 'home', 1750000, 3750000, 75000, 0, 37500, 725, 'jda', 85, 'partial', '650-700 (Average 🟠)', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-17 12:49:18', '2026-04-17 12:49:27', 0),
(677, 'diva_mo36cxuo_d56m2n', '', '', 'jaipur', 'home', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Jaipur', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-17 17:21:27', '2026-04-17 17:21:29', 0),
(679, 'diva_mo3s6ucb_nikd46', '', '', 'dilli ncr', 'home', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Dilli NCR', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 03:32:32', '2026-04-18 03:32:35', 0),
(681, 'diva_mo3szg9l_pjcq9y', 'butra', '8989898989', 'jaipur', 'home', 1750000, 3750000, 75000, 0, 0, 780, 'jda', 100, 'qualified', '📋 Documents list chahiye', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 03:54:38', '2026-04-18 03:55:25', 0),
(694, 'diva_mo3t2fis_qpgn3x', '', '', 'jaipur', 'home', 0, 0, 0, 0, 0, 0, 'jda', 30, 'new', 'Residential - Self Occupied', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 03:56:52', '2026-04-18 03:56:57', 0),
(699, 'diva_mo3vi2k3_rycsg2', '', '', 'ajmer', 'car', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'Ajmer', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 05:05:02', '2026-04-18 05:05:04', 0),
(701, 'diva_mo40acwv_bjjth0', '', '', 'jaipur', 'lap', 15000000, 35000000, 150000, 0, 17500, 780, 'jda', 85, 'partial', '750+ (Excellent 🟢)', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 07:24:46', '2026-04-18 07:25:18', 0),
(711, 'diva_mo40jj6j_m5yn7w', 'raje', '', 'jaipur', 'lap', 15000000, 35000000, 37500, 0, 17500, 780, 'jda', 95, 'partial', 'raje', '2405:201:5c08:d17e:504a:7de4:d58c:9ef8', '2026-04-18 07:26:07', '2026-04-18 07:26:36', 0),
(722, 'diva_mon9tep6_kdclxl', '', '', 'jaipur', 'car', 0, 0, 0, 0, 0, 0, '', 25, 'new', '💼 Salaried - Private', '2405:201:5c08:d19a:9700:f6ad:16d2:2248', '2026-05-01 18:54:15', '2026-05-01 18:54:18', 0),
(725, 'diva_moo179f2_0ojkgz', '', '', 'jalore', 'home', 0, 0, 0, 0, 0, 0, '', 20, 'new', 'jalore', '2405:201:5c08:d19a:adf0:9426:bf01:77c', '2026-05-02 07:43:15', '2026-05-02 07:43:24', 0);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `document_type` varchar(60) NOT NULL DEFAULT '',
  `status` enum('pending','received','verified','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `file_name` varchar(300) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size_kb` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`document_id`, `lead_id`, `partner_id`, `document_type`, `status`, `remarks`, `file_name`, `file_path`, `file_size_kb`, `mime_type`, `uploaded_at`, `updated_at`) VALUES
(1, 34, 4, 'PAN Card', 'received', '', '', '', NULL, NULL, '2026-04-18 14:14:25', '2026-04-18 14:14:27'),
(3, 34, 4, 'Aadhaar Card', 'received', '', '', '', NULL, NULL, '2026-04-18 14:14:29', '2026-04-18 14:14:29'),
(4, 34, 4, 'Photograph', 'received', '', '', '', NULL, NULL, '2026-04-18 14:14:31', '2026-04-18 14:14:31'),
(5, 34, 4, 'ITR (2 years)', 'received', '', '', '', NULL, NULL, '2026-04-18 14:14:35', '2026-04-18 14:14:35');

-- --------------------------------------------------------

--
-- Table structure for table `document_analysis`
--

CREATE TABLE `document_analysis` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `document_id` int(10) UNSIGNED DEFAULT NULL,
  `document_type` varchar(50) NOT NULL,
  `analysis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis_json`)),
  `summary_text` text DEFAULT NULL,
  `itr_turnover` bigint(20) DEFAULT NULL,
  `itr_net_income` bigint(20) DEFAULT NULL,
  `itr_profit` bigint(20) DEFAULT NULL,
  `itr_depreciation` int(11) DEFAULT NULL,
  `itr_dir_remun` int(11) DEFAULT NULL,
  `bs_abb` bigint(20) DEFAULT NULL,
  `bs_avg_credits` bigint(20) DEFAULT NULL,
  `bs_emi_bounces` tinyint(4) DEFAULT NULL,
  `bs_cheque_returns` tinyint(4) DEFAULT NULL,
  `bs_surrogate_eligible` tinyint(1) DEFAULT NULL,
  `prop_title_type` varchar(100) DEFAULT NULL,
  `prop_patta_year` smallint(6) DEFAULT NULL,
  `prop_ownership` varchar(200) DEFAULT NULL,
  `prop_legal_flags` text DEFAULT NULL,
  `prop_market_value` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followups`
--

CREATE TABLE `followups` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `type` enum('call','whatsapp','doc_request','lender_followup','internal') DEFAULT 'call',
  `notes` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','done','snoozed') DEFAULT 'pending',
  `done_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `followups`
--

INSERT INTO `followups` (`id`, `lead_id`, `partner_id`, `type`, `notes`, `due_date`, `status`, `done_at`, `created_at`, `updated_at`) VALUES
(1, 34, 4, 'call', 'call again', '2026-04-18', 'pending', NULL, '2026-04-18 14:16:18', '2026-04-18 14:16:18'),
(2, 34, 4, 'call', 'asked to call again', '2026-04-23', 'pending', NULL, '2026-04-18 14:16:40', '2026-04-18 14:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `followup_tasks`
--

CREATE TABLE `followup_tasks` (
  `task_id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `assigned_to` varchar(150) DEFAULT 'admin',
  `task_type` enum('callback','con_call','doc_collection','lender_submission','sanction_followup','disbursement','general') DEFAULT 'callback',
  `status` enum('pending','in_progress','done','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `callback_time` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `followup_tasks`
--

INSERT INTO `followup_tasks` (`task_id`, `lead_id`, `partner_id`, `assigned_to`, `task_type`, `status`, `priority`, `callback_time`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-09 20:26:00', '2026-04-09 20:26:00'),
(2, 2, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-09 20:27:22', '2026-04-09 20:27:22'),
(3, 3, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-09 20:28:07', '2026-04-09 20:28:07'),
(4, 4, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-09 20:28:15', '2026-04-09 20:28:15'),
(5, 5, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-09 20:44:39', '2026-04-09 20:44:39'),
(6, 6, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-09 20:55:06', '2026-04-09 20:55:06'),
(7, 7, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-10 07:12:18', '2026-04-10 07:12:18'),
(8, 8, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-11 06:41:40', '2026-04-11 06:41:40'),
(9, 9, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-11 06:47:08', '2026-04-11 06:47:08'),
(10, 10, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-11 13:09:47', '2026-04-11 13:09:47'),
(11, 11, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-11 13:14:34', '2026-04-11 13:14:34'),
(12, 12, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-11 13:15:19', '2026-04-11 13:15:19'),
(13, 13, 5, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-13 04:40:04', '2026-04-13 04:40:04'),
(14, 14, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-13 07:32:24', '2026-04-13 07:32:24'),
(15, 15, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-13 07:39:00', '2026-04-13 07:39:00'),
(16, 16, 6, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-13 07:39:08', '2026-04-13 07:39:08'),
(17, 17, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 06:06:49', '2026-04-14 06:06:49'),
(18, 18, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 06:08:56', '2026-04-14 06:08:56'),
(19, 19, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 06:10:58', '2026-04-14 06:10:58'),
(20, 20, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 06:35:01', '2026-04-14 06:35:01'),
(21, 21, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 08:05:33', '2026-04-14 08:05:33'),
(22, 22, 7, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 10:12:57', '2026-04-14 10:12:57'),
(23, 23, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 10:13:03', '2026-04-14 10:13:03'),
(24, 24, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 10:14:50', '2026-04-14 10:14:50'),
(25, 25, 7, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-14 10:16:16', '2026-04-14 10:16:16'),
(26, 26, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-15 11:02:50', '2026-04-15 11:02:50'),
(27, 27, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-16 07:02:06', '2026-04-16 07:02:06'),
(28, 28, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-16 07:11:28', '2026-04-16 07:11:28'),
(29, 29, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-16 07:12:37', '2026-04-16 07:12:37'),
(30, 30, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-16 07:29:50', '2026-04-16 07:29:50'),
(31, 31, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-16 13:15:33', '2026-04-16 13:15:33'),
(32, 32, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-16 17:06:59', '2026-04-16 17:06:59'),
(33, 33, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-18 07:58:40', '2026-04-18 07:58:40'),
(34, 34, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-18 14:02:04', '2026-04-18 14:02:04'),
(35, 35, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-18 14:03:53', '2026-04-18 14:03:53'),
(36, 36, 3, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-21 15:34:20', '2026-04-21 15:34:20'),
(37, 37, 4, 'admin', 'callback', 'pending', 'high', NULL, NULL, '2026-04-21 21:01:51', '2026-04-21 21:01:51');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_mobile` varchar(15) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `city` varchar(60) NOT NULL,
  `occupation` varchar(60) DEFAULT NULL,
  `loan_type` varchar(60) NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `property_title` varchar(60) DEFAULT NULL,
  `patta_year` year(4) DEFAULT NULL,
  `property_usage` varchar(60) DEFAULT NULL,
  `property_location` varchar(255) DEFAULT NULL,
  `valuation` decimal(14,2) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT NULL,
  `monthly_income` decimal(12,2) DEFAULT NULL,
  `existing_emi` decimal(10,2) DEFAULT 0.00,
  `cibil` smallint(6) DEFAULT NULL,
  `income_type` varchar(50) DEFAULT NULL,
  `business_turnover_monthly` decimal(12,2) DEFAULT NULL,
  `ltv_pct` decimal(5,2) DEFAULT NULL,
  `foir_pct` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'new',
  `next_action` varchar(150) DEFAULT NULL,
  `next_action_date` date DEFAULT NULL,
  `last_followup_at` datetime DEFAULT NULL,
  `last_status_updated_at` datetime DEFAULT NULL,
  `doc_completion_pct` tinyint(4) DEFAULT 0,
  `disbursed_amount` decimal(14,2) DEFAULT NULL,
  `disbursed_at` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_documents`
--

CREATE TABLE `lead_documents` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `doc_type` varchar(60) NOT NULL,
  `status` enum('pending','received','verified','rejected') DEFAULT 'pending',
  `file_url` varchar(500) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_status_history`
--

CREATE TABLE `lead_status_history` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lenders_products`
--

CREATE TABLE `lenders_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lender_name` varchar(255) DEFAULT NULL,
  `lender_key` varchar(120) DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `product_type` varchar(255) DEFAULT NULL,
  `products_supported` text DEFAULT NULL,
  `loan_amount_min` int(11) DEFAULT NULL,
  `loan_amount_max` int(11) DEFAULT NULL,
  `loan_min_lakh` int(11) DEFAULT NULL,
  `loan_max_lakh` int(11) DEFAULT NULL,
  `roi_min` decimal(6,2) DEFAULT NULL,
  `roi_max` decimal(6,2) DEFAULT NULL,
  `interest_rate_start` decimal(6,2) DEFAULT NULL,
  `max_ltv` int(11) DEFAULT NULL,
  `max_tenure_months` int(11) DEFAULT NULL,
  `min_cibil` int(11) DEFAULT NULL,
  `cibil_policy` text DEFAULT NULL,
  `income_type_allowed` text DEFAULT NULL,
  `income_types` text DEFAULT NULL,
  `bank_statement_months` int(11) DEFAULT NULL,
  `profile_allowed` text DEFAULT NULL,
  `special_profiles` text DEFAULT NULL,
  `property_allowed` text DEFAULT NULL,
  `property_type` text DEFAULT NULL,
  `geo_limit` text DEFAULT NULL,
  `city_allowed` text DEFAULT NULL,
  `prepayment_charges` text DEFAULT NULL,
  `foreclosure_charges` text DEFAULT NULL,
  `guarantor_required` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `programs` text DEFAULT NULL,
  `sales_manager_name` text DEFAULT NULL,
  `sales_manager_designation` text DEFAULT NULL,
  `sales_manager_mobile` varchar(20) DEFAULT NULL,
  `sales_manager_email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lenders_products`
--

INSERT INTO `lenders_products` (`id`, `lender_name`, `lender_key`, `product`, `product_type`, `products_supported`, `loan_amount_min`, `loan_amount_max`, `loan_min_lakh`, `loan_max_lakh`, `roi_min`, `roi_max`, `interest_rate_start`, `max_ltv`, `max_tenure_months`, `min_cibil`, `cibil_policy`, `income_type_allowed`, `income_types`, `bank_statement_months`, `profile_allowed`, `special_profiles`, `property_allowed`, `property_type`, `geo_limit`, `city_allowed`, `prepayment_charges`, `foreclosure_charges`, `guarantor_required`, `notes`, `programs`, `sales_manager_name`, `sales_manager_designation`, `sales_manager_mobile`, `sales_manager_email`) VALUES
(1, 'Poonawalla Fincorp', 'poonawalla_fincorp_lap', 'Loan Against Property (LAP)', 'Secured', 'LAP, BT, Commercial/Industrial Purchase, LRD, Plot Funding, BL (add-on)', 5100000, 250000000, 51, 2500, 0.00, 0.00, 0.00, 0, 180, 0, NULL, 'Business programs: EBITDA/Gross Profit/Avg Banking upto 7.5Cr/Pure Rental upto 7.5Cr/RTR/Low LTV/LIP', 'EBITDA,Gross Profit,Avg Banking,Pure Rental,RTR,Low LTV,LIP', 12, 'Self-Employed,Salaried (policy not explicit)', 'Plot funding (authority approved), LRD, commercial/industrial purchase', 'All approved & freehold properties; Residential/Commercial/Industrial/Warehouse/Land/Hotel/Hospital/PG/Gym/Hostel/Mix Use/Vacant/Restaurant', 'Approved Freehold; Residential/Commercial/Industrial/Mixed/Plot/Land', 'Jaipur+60KM; also listed cities', 'Jaipur,Jodhpur,Jhunjhunu,Udaipur,Sikar,Churu,Kota,Bhiwadi,Alwar,Ajmer,Kishangarh,Bikaner', 'Nil', 'Nil', 'Case to Case', 'Add-on business loan upto 75L for 7 years on selected collaterals; digital processing; login fee zero.', 'EBITDA,Gross Profit,Avg Banking,Pure Rental,RTR,Low LTV,LIP', 'Anshuman Nariya', 'Senior BSM', '7229982229', NULL),
(2, 'IndusInd Bank', 'indusind_bank_ahl_hl_lap', 'Home Loan + Loan Against Property', 'Secured', 'HL purchase (new/resale), self construction, refinance (upto 12m), BT+Topup, Seller BT, improvement/renovation, Home Equity/Micro LAP', 0, 0, 0, 0, 0.00, 0.00, 0.00, NULL, 0, 0, 'CIBIL calls case to case; \'NO ITR\' upto 25L; 1 ITR for 25-50L', 'AIP/LIP/NIP, Banking Surrogate/RTR, Cash Salaried, Agri/Dairy clubbing, Rental income', 'AIP,LIP,NIP,Banking Surrogate,RTR,Cash Salaried,Agri/Dairy,Rental', 12, 'Salaried,Self-Employed,Cash Salaried', 'Cash salaried; Agri/Dairy; Non-income programs', 'Self-occupied residential / mixed use / rental with SORP; Govt/Authority/Municipal/UIT approvals; Gram Panchayat patta; 90A; society patta (current mention incomplete)', 'SORP,Mixed,Rental,Gram Panchayat,Authority approved,Society', '60 KM geo-limit from AHL branches; spoke locations', 'Semi-urban/rural spokes around AHL branches (Jaipur mentioned)', 'Nil', 'Nil', 'Case to Case', 'Login fee 590', 'AIP,LIP,NIP,Banking Surrogate,RTR', 'Gaurav Kumar', 'Relationship Manager (AHL)', '8875848209', NULL),
(3, 'Edelweiss Finance', 'edelweiss_finance_lap', 'Loan Against Property (LAP)', 'Secured', 'LAP', 5000000, 100000000, 50, 1000, 12.50, 14.50, 12.50, 100, 180, 675, '675', 'Documented income + clubbing (rental/agri), banking surrogates/low LTV/GST surrogate, RTR', 'Documented,Rental,Agri,Banking Surrogate,Low LTV,GST Surrogate,RTR', 12, 'Self-Employed,Self-Employed Professionals', 'RTR 12/18/24 EMIs multiplier; Unsecured BT EMI add', 'Panchayati patta (registered), JDA/Nagar Nigam/Housing Board/Converted agri, approved open land, industrial, SORP/SOCP', 'SORP,SOCP,Open Land,Industrial,Panchayat,JDA', '75 KM from branch', 'Jaipur,Kishangarh,Jodhpur', 'Nil', 'Nil', 'Case to Case', 'USP: MAX LTV 100% on LAP (SORP/SOCP).', 'Income Program,Banking Surrogate,Low LTV,GST Surrogate,RTR', 'Devki Sabal', 'SM', '6375695023', 'devki.sabal@eclf.com'),
(4, 'DMI Housing Finance', 'dmi_housing_hl_lap', 'Home Loan / LAP', 'Secured', 'HL,LAP,Mortgage', 0, 0, 0, 0, 10.00, 0.00, 10.00, NULL, 0, 0, NULL, 'Cash salaried upto 15L; special profiles incl temporary setups; M profile; legal chain missing; road widening', 'Cash Salaried,Non-standard profiles,M profile', 12, 'Salaried,Self-employed,Special informal profiles', 'Thadi/thela, temporary setup, legal chain missing, road widening, non-transfer properties on lease, agri gift deed built-up', 'Society patta open land/tin shed purchase & mortgage; stilt parking/penthouse; current date patta upto 2026; areas incl Jaisinghpura Khod/Agra Road/Delhi Road', 'Society Patta,Open Land,Tin Shed,Stilt,Penthouse,Agri gift deed', '50 KM', 'Jaipur (incl Agra Road/Delhi Road/Jaisinghpura Khod)', 'Nil', 'Nil', 'Case to Case', 'Working with Andro/Ravikash/RU loans/RKPL/Urban money/Saarathi; DSA code possible', NULL, 'Tapendra Dixit', 'Sales Manager', '9887000994', NULL),
(5, 'AU Small Finance Bank', 'au_sfb_mbl_lap', 'MSME/MBL - LAP', 'Secured', 'LAP', 1500000, 15000000, 15, 150, 11.00, 0.00, 11.00, 75, 180, 0, NULL, 'Documented & non-documented assessment, cash rental, family income', 'Documented,Non-Documented,Cash Rental,Family Income', 12, 'Salaried,SEP,SENP', 'Society patta; open land JDA/Nagar Palika; Ricco patta upto 50L; stilt/penthouse', 'SORP/SOCP/SOIP/rented/open land/vacant/mix use/hotel/hostel/school/society/panchayat/JDA/RHB/Nagar', 'Residential,Commercial,Industrial,Mixed,Open Land', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'Payout 1.5%+GST', 'Assessment Income,Non-Doc,Cash Rental,Family Income', 'Arun Mishra', 'Portfolio Sales Manager', '7222049249', NULL),
(6, 'AU Small Finance Bank', 'au_sfb_agri_sme_working_capital', 'Working Capital (CC/OD/DLOD/Term Loan)', 'Secured', 'CC,OD,DLOD,Term Loan,Project Funding', 5000000, 50000000, 50, 500, 0.00, 0.00, 0.00, 100, 0, 0, NULL, 'Turnover based; no ratio analysis; no obligation', 'Sole Banking,Multiple Banking', 12, 'SME/Agri SME businesses', 'HL convert to term loan + OD/CC on rest LTV', 'Industrial,Residential,Gram Panchayat,Commercial,Nagar Nigam/Nagar Parishad,Open Land JDA,Society', 'Industrial,Residential,Commercial,Open Land', 'Branch coverage', NULL, 'Nil', 'Nil', 'Case to Case', 'Eligibility: 30% of last 4 completed quarters turnover; min turnover 1Cr; vintage 3 years.', 'Sole Banking,Multiple Banking,Project Funding', 'Dhairya Sharma', 'RM - Agri SME', '7891907975', NULL),
(7, 'Cholamandalam', 'cholamandalam_hl_lap_general', 'Home Loan + LAP', 'Secured', 'HL,LAP', 0, 0, 0, 0, 11.00, 16.00, 11.00, NULL, 0, 0, 'CIBIL calls taken (not willful); cash salaried accepted; non-income profiles (NIP)', 'NIP income, bank salaried, cash salaried', 'NIP,Bank Salaried,Cash Salaried', 12, 'Bank salaried,Cash salaried,NIP', 'Society patta current date till 2025; agri funded; P+C cases; commercial society patta; negative areas allowed', 'Society till 2025, JDA, Nagar Nigam, RHB, Agriculture, Gift deed; Agra Road/Jaisinghpura Khor', 'Society Patta,JDA,Nagar Nigam,RHB,Agriculture', '80 KM', 'Jaipur + 80KM; Agra Road; Jaisinghpura Khor', 'Nil', 'Nil', 'Case to Case', 'ROI HL 11-13; LAP 13-16 (as shared).', 'NIP', 'Sunder Lal Samriya', 'Chola (DSA contact)', '7014588355', NULL),
(8, 'Cholamandalam', 'cholamandalam_sme_lap', 'SME LAP Term Loan', 'Secured', 'SME LAP Term Loan', 3000000, 50000000, 30, 500, 0.00, 0.00, 0.00, 75, 0, 700, 'Cibil 700+; CMR upto 7', 'Standard income, GST & banking, repayment surrogate, BT & Topup, low LTV', 'Standard,GST,Banking,Repayment Surrogate,BT,Topup,Low LTV', 12, 'SME borrowers', 'Hospital/school/college properties & income acceptable', 'Residential/Commercial/Industrial/Self-occupied/Rented/Vacant/Open plots/Hospital/School/College/Hotel/Resort; JDA/RHB/Nagar Nigam/Parishad/Industrial/Converted/Gram Panchayat/Society/Agriculture', 'SORP,SOCP,SOIP,Plot,Mixed', '100 km from Jaipur branch', 'Ajmer,Kishangarh,Sikar,Tonk,Dausa,Alwar,Sahapura,Kotputli,Behror,Chomu,Kaladera,Bassi', 'Nil', 'Nil', 'Case to Case', 'Geo-limit list provided.', 'Standard,GST,Banking,Repayment Surrogate,BT&Topup,Low LTV', 'Gourav Sharma', 'BSM', '7976211957', 'gouravsr@chola.murugappa.com'),
(9, 'IIFL Home Loans', 'iifl_home_loans_hl_lap', 'Home Loan + LAP', 'Secured', 'HL,LAP,Builder Project Funding', 0, 150000000, 0, 1500, 8.65, 16.50, 8.65, NULL, 0, 0, 'As per CIBIL score (rate slabs)', 'Salaried, Cash salaried, SENP, Banking surrogate, NIP', 'Salaried,Cash Salaried,SENP,Banking Surrogate,NIP', 12, 'Salaried,SENP,Cash salaried', 'Builder funding upto 15cr; cash salary 50k consider', 'Society title funding', 'Society title', '50 km', 'Jaipur (geo)', 'Nil', 'Nil', 'Case to Case', 'HL ROI: salaried 8.65; cash salaried 11.70; SENP 8.95; banking surrogate 9.70; NIP 12.10. LAP ROI 12 to 16.50.', 'Banking Surrogate,NIP', 'Jitendra Sharma', 'Location Head (DSA vertical)', '9314623734', NULL),
(10, 'IDFC FIRST Bank', 'idfc_first_bank_lap_eil', 'LAP + Education Infrastructure Loan (EIL)', 'Secured', 'LAP,EIL', 0, 200000000, 0, 2000, 0.00, 0.00, 0.00, 75, 240, 0, NULL, 'Assessment base; LAP & EIL programs', 'Assessment', 12, 'SME/Institutions', 'Nil stamping; Nil FC; LAP tenure upto 20y; EIL all Rajasthan', 'LAP collateral: Residential/Commercial/Industrial/Mixed/Hotel/Hospital/PG/Hostel/Banquet/Godown/Converted Farmhouse; EIL: Schools/Colleges', 'Residential,Commercial,Industrial,Mixed,Institutional', '100 KM from Jaipur/Jodhpur/Udaipur/Kota/Ajmer (LAP); Entire Rajasthan (EIL)', 'Jaipur,Jodhpur,Udaipur,Kota,Ajmer (LAP); Rajasthan (EIL)', 'Nil', 'Nil', 'Case to Case', 'Processing fee 1% for LAP; Loan amt upto 10Cr LAP, upto 20Cr EIL; Student strength norms shared.', 'Assessment', 'Rahul Jain', 'Bank RM', '6376459713', NULL),
(11, 'IDFC FIRST Bank', 'idfc_first_bank_st_lap', 'LAP (ST LAP) - Permanent login proposition', 'Secured', 'LAP', 0, 7500000, 0, 75, 0.00, 0.00, 0.00, 80, 300, 0, NULL, NULL, NULL, 12, NULL, '0 Rs IMD at login (deduct at disb); login fee 2500+GST flat', 'As per LTV snapshot categories', 'Residential,Commercial,Industrial,Mixed,Hospital,School,Hotel,Godown,Vacant', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'No rush/no expiry proposition; IMD 0 at login; ₹2,500+GST login fee.', NULL, 'Shagun Khurana', 'Sales Manager - ST LAP', '9983945673', NULL),
(12, 'Aditya Birla Capital', 'aditya_birla_capital_prime_lap', 'Prime LAP', 'Secured', 'LAP, BT, LRD, Builder funding', 5000000, 150000000, 50, 1500, 10.00, 12.00, 10.00, 75, 180, 0, NULL, 'Cash profit/banking/CA assessment; low LTV; LRD; SBL booster', 'Cash Profit,Banking,CA Assessment,LRD,Low LTV', 12, 'Salaried,SENP,SEP', 'Society patta up to 1Cr (N-2 yrs); sanction in 48 hours; +10% extra LTV with SBL (5 yrs)', 'Hotel/Hospital; group housing land (non-builder); builder funding (SORP/SOCP)', 'Residential,Commercial,Industrial,Mixed,Land', '50 KM', 'Jaipur (implied)', 'Nil', 'Nil', 'Case to Case', 'Additional sheet-based detailed LTVs for approved/unapproved may apply (see Yash Grover sheet) — keep in notes if needed.', 'Cash Profit,Banking,CA Assessment,LRD,Low LTV,SBL Booster', 'Praveen Joshi', 'SM/DSA contact', '7742761501', 'Praveen.joshi2@adityabirlacapital.com'),
(13, 'Repco Home Finance', 'repco_home_finance_hl_lap', 'Home Loan + LAP', 'Secured', 'HL,LAP,BT,Topup', 1000000, 30000000, 10, 300, 9.25, 15.00, 9.25, NULL, 0, 0, 'Based on CIBIL score', 'Bank & cash salary; SENP; NIP/AIP; banking/BT/RTR surrogates', 'Bank Salaried,Cash Salaried,SENP,AIP,NIP,Banking,BT,RTR', 12, 'Bank salaried,Cash salaried,SENP,NIP', 'BT offer: HL BT @9.25 + 20% top up; LAP BT @11.25 + topup; clear track 18m; PF low in BT', 'Society patta considered (fresh till Jan-2014; transfer till 2020); agri title plot; all kind approved/unapproved; Agra road funded', 'Society Patta,Agri,Approved/Unapproved', '50 KM', 'Jaipur (Agra Road)', 'Nil', 'Nil', 'Case to Case', 'IMD 1770; PF 1-1.5% varies; BT PF 0.50%/15000 whichever low.', 'Banking,BT,RTR,AIP,NIP', 'Ajay Kashyap', 'BM', '8829011330', NULL),
(14, 'TruHome Finance (Shriram Housing Finance)', 'truhome_finance_hl_lap', 'HL + LAP', 'Secured', 'HL,LAP,BT', 1000000, 10000000, 10, 100, 11.00, 15.00, 11.00, 65, 0, 0, 'CIBIL calls taken; M profiles considered', 'NIP,LIP,Banking,RTR,LRD', 'NIP,LIP,Banking,RTR,LRD', 12, 'All profiles; M profiles', 'Society open land till 2018; society built-up till 2024; agri built-up gift deed/registry upto 80L; 90B tech approved; tin shed/godowns; ecological Agra Road BT allowed', 'Approved properties; society open land; built-up; agri; RIICO; panchayat registered; RHB; freehold wall city', 'Residential,Commercial,Industrial,Mixed,Land,Society,Agri', '90 km from Jaipur', 'Jaipur + 90km; Jobner,Renwal,Shahapura,Niwai,Malpura,Khatushyamji,Shree Modhupur (as shared)', 'Nil', 'Nil', 'Case to Case', 'IMD 1770 in earlier msg; PF 1.25%+GST.', 'NIP,LIP,Banking,RTR,LRD', 'Dinesh Soni / Prashant Singh Shekhawat', 'RM/Contact', '8619191503 / 7792902', NULL),
(15, 'DCB Bank', 'dcb_bank_sme_od_lap', 'OD/LAP/DLOD Secured (SME Vertical)', 'Secured', 'OD,LAP,DLOD,BT', 1100000, 30000000, 11, 300, 0.00, 0.00, 0.00, 95, 0, 0, 'M profile cases accepted', 'Banking surrogate and other surrogate programs', 'Banking Surrogate', 12, 'SME', 'OD eligibility up to 50% of turnover; convert HL/LAP to OD', 'Residential,Commercial,Mixed,City area,Society till 2018,Panchayat registered patta', 'Residential,Commercial,Mixed,Industrial,Open land', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'Contact missing in one message; no phone given.', 'Banking Surrogate', 'Mohammed Shoaib', NULL, NULL, NULL),
(16, 'Capri Global Capital', 'capri_global_lap', 'Mortgage/LAP + BT + Topup + Commercial Purchase + School funding', 'Secured', 'LAP,BT,Topup,Commercial Purchase,School Funding', 0, 0, 0, 0, 0.00, 0.00, 0.00, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'JDA, Society patta till 2018, Panchayat (registered), Nagar Nigam, Agriculture gift deed', 'Residential,Commercial', '60km from Capri branch', 'Jaipur (branch radius)', 'Nil', 'Nil', 'Case to Case', 'Login fees ZERO; \'0 Zero IMD/Login Fees\'', NULL, 'Akhilesh Saxena', NULL, '6378240998', NULL),
(17, 'Grihum Housing Finance', 'grihum_housing_hf', 'Home Loan + LAP', 'Secured', 'HL,LAP', 0, 0, 0, 0, 0.00, 0.00, 0.00, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society till 2024/2025, JDA, Nagar Nigam, RHB, Agriculture; blacklisted societies also; technical/legal deviations; non-transfer patta; Agra road/Jaisinghpura khor', 'Residential,Commercial,Society,Agri', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'IMD 1180; payout HL 1.25 / LAP 1.50 (revised for JFM).', 'NIP (non-income), bank salaried, cash salaried', 'Shiv Sagar Prajapati', NULL, '8875606669 / 9214302', NULL),
(18, 'Muthoot Finance', 'muthoot_finance_business_loan', 'Business Loan (Surrogate: GST & ABB)', 'Secured', 'Unsecured/Surrogate BL', 1000000, 2000000, 10, 20, 18.00, 22.00, 18.00, NULL, 36, 0, NULL, NULL, NULL, 12, NULL, NULL, 'NA (own house anywhere in India required as comfort)', NULL, 'Jaipur', 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'ABB: 10L/36M ROI 22%; GST: 20L/36M ROI 18%; Udyam mandatory; complete digital; vintage 3 yrs.', 'GST,ABB', 'Lav Jaiswal', 'Sales Manager', '9024556504', 'Lav.jaiswal@muthootgroup.com'),
(19, 'Capital India Finance', 'capital_india_finance_lap', 'Loan Against Property (LAP)', 'Secured', 'LAP', 2000000, 50000000, 20, 500, 0.00, 0.00, 0.00, 75, 180, 0, NULL, NULL, NULL, 12, NULL, NULL, 'All property types incl school/college/hospital/open land; society patta till 2018; panchayat; JDA/RHB/Nagar; agriculture title', 'Residential,Commercial,Industrial,Mixed,Open Land,Institutional', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'DSA payout 1.75%.', 'LIP,NIP,Banking,RTR,GST Turnover,Low LTV', 'Surendra Singh', NULL, '9928938682', NULL),
(20, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 'HL + LAP', 'Secured', 'HL,LAP,BT,Seller BT,Home improvement,Mortgage', 500000, 5000000, 5, 50, 9.99, 16.00, 9.99, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'All working/rejected/liquidated society patta till date, JDA, Nagar Nigam/Palika, RHB, Gram Panchayat, agriculture; legal/technical issues; stilt/high tension/nala cases', 'Residential,Commercial,Industrial,Mixed,Society,Agri', 'Within 60 km from Jaipur', 'Jaipur,Agra Road,Jaisinghpura Khor,Khora Bisal', 'Nil', 'Nil', 'Case to Case', 'Loan amount 5-50L; HL 9.99-13; LAP 13-16.', 'Saral program, Kacchi puccki income, Cash salaried', 'Labhya Singh', 'Relationship Manager Jaipur', '9983609608', NULL),
(21, 'HDFC Bank', 'hdfc_bank_meg_gst_od', 'Working Capital - GST OD (CGTMSE, unsecured)', 'Secured', 'GST OD', 500000, 10000000, 5, 100, 0.00, 0.00, 0.00, 15, 0, 725, 'Minimum CIBIL 725+ (as shared)', NULL, NULL, 12, NULL, NULL, 'No collateral required', 'Unsecured', 'Jaipur + nearby locations', 'Jaipur,Chomu,Alwar,Dausa,Bagru,Chaksu,Shahpura,Phaagi,Bassi,Jaitpura,Kaladera,Sikar', 'Nil', 'Nil', 'Case to Case', 'Vintage 3 yrs; min turnover 50L; only GST firms; proprietorship/partnership (v2 says not Pvt Ltd).', 'GST 3B based assessment', 'Jay Prakash Soni / Sumit Sharma', 'Relationship Manager', '7014848548 / 9785306', NULL),
(22, 'Protium Finance', 'protium_finance_lap_school', 'LAP + School/College funding + Purchase/Construction', 'Secured', 'LAP,Mortgage,Commercial/Industrial Purchase,School/College LAP,P+C', 5000000, 50000000, 50, 500, 12.50, 14.50, 12.50, 75, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'All society/agri constructed/approved JDA/RHB/Nagar; RIICO/Gram Panchayat; 90B/converted; hotel/hospital/hostel/PG/multi-tenanted', 'Residential,Commercial,Industrial,Mixed,Land,Institutional', 'Jaipur 100km (LAP); 200km (School funding); also other cities listed', 'Jaipur,Jodhpur,Sikar,Alwar,Bikaner,Ajmer', 'Nil', 'Nil', 'Case to Case', 'Nil login fees; if PD positive then legal+tech fees 5900; EDI 11800.', 'LIP,NIP,Banking ABB,GST Turnover', 'Anurag Saini', 'Area Sales Head', '9414518248', NULL),
(23, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 'MSME LAP', 'Secured', 'LAP', 0, 30000000, 0, 300, 10.50, 0.00, 10.50, 80, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta (3-year old with passion), JDA, Nagar Nigam, RHB, city, GP, industrial, agri (SORP/SOCP/SOIP), open land society clear demarcation, PG/hospital', 'Residential,Commercial,Industrial,Mixed,Agri,Open land', '75 km surrounding', 'Jaipur region', 'Nil', 'Nil', 'Case to Case', 'Comfortable funding upto 300L; ROI from 10.5 as per cibil.', NULL, 'GL Khinchi', NULL, '9680041760', NULL),
(24, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 'HL + LAP + Open Land Purchase + Agri Purchase + Agreement Construction', 'Secured', 'HL,LAP,Plot/Agri loans', 0, 30000000, 0, 300, 9.75, 12.00, 9.75, 75, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta till 2022 (HL/LAP/P+C); society patta till 2024 ready house purchase; JDA, Nagar Nigam/Palika, Panchayat, Housing Board, Agriculture', 'Residential,Commercial,Society,Agri', NULL, 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'Special: 30L without ITR; 70L on single ITR. L&T 2950; payout 1.20%.', 'NIP,Agri allied', 'Manish Kumar Acharya', 'Sales Manager', '9057955940', NULL),
(25, 'CLIX Capital Services', 'clix_capital_education_loans', 'Education Infrastructure Financing (Secured/Unsecured)', 'Secured', 'K12 secured, small ticket, unsecured, higher education', 1000000, 100000000, 10, 1000, 0.00, 0.00, 0.00, NULL, 144, 0, NULL, NULL, NULL, 12, NULL, NULL, 'School/college property or self-occupied commercial/resi collateral', 'Institutional,Residential,Commercial', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'K12: 50L-10Cr, tenor 12y, min 400 students; Small ticket 25L-1Cr; Unsecured 10L-75L; Higher Ed 50L-7.5Cr.', 'Assessed/Reported', 'Rajesh Kumar', NULL, '9828109833', NULL),
(26, 'Vastu Finserv India Pvt Ltd', 'vastu_finserv_lap', 'LAP', 'Secured', 'LAP', 1000000, 7500000, 10, 75, 0.00, 0.00, 14.00, 70, 180, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta (latest till 2023), panchayat, school, hotel/hostel, JDA/RHB/Nagar, agriculture title', 'Residential,Commercial,Industrial,Mixed', 'Jaipur to 70km; specific cities list', 'Jaipur,Tonk,Kotputli,Sikar,Jama Ramgarh,Churu,Dausa,Dudu', 'Nil', 'Nil', 'Case to Case', 'Old DPD consider; latest DPD not.', 'Assessment (doc/non-doc), cash rental, family income', 'Sajju Khan', NULL, '7737427817', NULL),
(27, 'UGRO Capital', 'ugro_capital_secured_lap', 'Secured LAP (Prime LAP) + Secured/SBL combo', 'Secured', 'LAP, SBL combo, School/Hotel/Hospital funding, Open land industrial/commercial', 5000000, 50000000, 50, 500, 0.00, 0.00, 13.00, 100, 180, 0, NULL, NULL, NULL, 12, NULL, NULL, 'SORP,SOCP,Mixed,Industrial (RICCO),Society patta 90B approved, Registered Panchayat, Converted agri (SDM), Agricultural land; open land industrial/commercial', 'Residential,Commercial,Industrial,Mixed,Land', NULL, 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'Financials not required up to 300L in GST/banking programs; physical docs not required for login.', 'Banking,LIP,GST T/O,Low LTV,ABB', 'Shivpal Choudhary', 'Manager', '9783002989', 'shivpal.choudhary1@ugrocapital.com'),
(28, 'Hiranandani Financial Services', 'hiranandani_financial_lap', 'LAP', 'Secured', 'LAP', 500000, 10000000, 5, 100, 0.00, 0.00, 14.00, 70, 144, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta till 2021, panchayat, school, hotel/hostel, JDA/RHB/Nagar, agriculture title, old registry, private khatedar', 'Residential,Commercial,Industrial,Mixed', 'Jaipur to 80 km', 'Jaipur', 'Nil', 'Nil', 'Case to Case', NULL, 'Assessment (doc/non-doc), cash rental, family income', 'Deepak Khandelwal', NULL, '9887713583', NULL),
(29, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 'Home Loan (No LAP)', 'Secured', 'HL purchase, construction, seller BT, BT top-up', 0, 0, 0, 0, 11.20, 12.00, 11.20, 90, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta, JDA, Nagar Nigam, commercial shop society, panchayati patta', 'Residential,Commercial', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'IMD 2360; PF 0.65% HL; payout HL 1.0%.', 'Higher FOIR upto 150%, banking, LIP x3, cash profit', 'Dinesh Sharma', NULL, '9413563675 / 7014489', NULL),
(30, 'Axis Bank', 'axis_bank_hl_lap', 'Home Loan + LAP', 'Secured', 'HL,LAP,P+C,P+C+E,OD programs via surrogates', 1000000, 20000000, 10, 200, 9.00, 0.00, 9.00, 90, 360, 0, NULL, NULL, NULL, 12, NULL, NULL, 'JDA,Nagar Nigam, Nagar Palika, Freehold, RHB, RICCO, Society before 1999 (JDA list submit), Society 90B tech approved, registered Gram Panchayat within 10km of branch', 'Residential,Commercial,Industrial,Mixed,Open land', 'Rajasthan (property within 10 km of any Axis branch)', 'Rajasthan (all approved locations)', 'Nil', 'Nil', 'Case to Case', 'Login fee NIL; PF up to 1%.', 'ITR,Banking Surrogate,GST,Pure Rental,IMGC,Cash Salaried,RTR,GPR,Low LTV,LIB', 'Kuldeep Sharma', 'Sales Manager', '8740062156', NULL),
(31, 'Godrej Capital', 'godrej_capital_b_lap', 'Business LAP (DOD/Flexi)', 'Secured', 'LAP,DOD,FLEXI', 2000000, 75000000, 20, 750, 10.75, 13.00, 10.75, 0, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'JDA,RHB,Nagar Nigam/Palika,Panchayat, Society till 2024 (incl Agra road/rejected/liquidated), Agri industrial', 'Residential,Commercial,Industrial,Converted land,Hospital (25 beds),Open land approved,Commercial purchase', '80 km from branch', 'Jaipur (branch radius)', 'Nil', 'Nil', 'Case to Case', 'ITR not required upto 50L.', 'ITR not required upto 50L; single lady loan', 'Mahendra Dhirawat', 'ASM - Business LAP', '9783666649', 'Mahendra.Dhirawat@godrejcapital.com'),
(32, 'Avanse Finance', 'avanse_finance_lap_bt_multiplier', 'LAP BT Multiplier', 'Secured', 'LAP,BT,Topup (as per multiplier)', 3000000, 30000000, 30, 300, 11.50, 13.00, 11.50, 70, 180, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Residential/Commercial/Industrial; Gram panchayat; RHB; educational institutions (individual); company owned; hospitals/hotel; society patta', 'Residential,Commercial,Industrial,Mixed', '75 km from branch', 'Jaipur (implied)', 'Nil', 'Nil', 'Case to Case', 'Eligibility multiplier based on completed 12/24/36 months track.', 'NIP without ITR till 50L, ITR, rental/agri/interest/dividend, ABB, net profit, gross profit, pure rental, LIP', 'Omveer Gaud', NULL, '9509801287', NULL),
(33, 'HSBC Bank', 'hsbc_mortgages_hl_lap_dlod', 'HL + LAP + Drop-line OD (DLOD)', 'Secured', 'HL BT/Topup, LAP, Smart LAP DLOD', 0, 0, 0, 0, 7.25, 8.50, 7.25, NULL, 300, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Only residential property (SORP,rented,vacant)', 'Residential only', NULL, 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'Rates: LAP 7.75 PSL/8.20 non-PSL; Smart LAP DLOD 8.00/8.50; HL 7.25; Smart HL DLOD 7.70/7.85; tenure HL 25y, LAP 20y; account opened zero balance; insurance mandatory 5yr.', 'Gross margin (3Cr), Gross receipts (3Cr), Cash profit (7.5Cr), RTR (5Cr), Low LTV, ABL', 'Niranjan Gupta', 'AVP Mortgages Jaipur', '9828547440', 'niranjan.gupta@hsbc.co.in'),
(34, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 'Home Loan + Mortgage/LAP', 'Secured', 'HL,LAP,Construction (3 tranches)', 0, 0, 0, 0, 9.00, 11.00, 9.00, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Neeji khatedari, society patta, nagar palika, panchayat, JDA ready house, current date patta; all profiles incl cash salaried/SENP/salaried', 'Residential,Commercial,Agri registry', '75 km (+ Agra Road)', 'Jaipur,Agra Road', 'Nil', 'Nil', 'Case to Case', 'HL ROI 9.5; JDA HL 9.0; mortgage 11.0.', NULL, 'Vikram Singh', 'TSM', '7011757168', NULL),
(35, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 'HL + LAP + BT', 'Secured', 'HL,LAP,BT', 300000, 5000000, 3, 50, 9.75, 18.00, 10.50, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta till date, Nagar Nigam/Palika, JDA approved; G+2/G+3', 'Residential,Commercial', 'Around 60 km; Agra road/Jaisinghpura khor included', 'Jaipur,Agra Road,Jaisinghpura Khor', 'Nil', 'Nil', 'Case to Case', 'HL ROI 10.50-14; LAP 14-18; BT ROI 9.75 salaried / 10.50 self employed.', 'Non-income proof; cash salaried', 'Kajal Sharma', NULL, '9672476508', NULL),
(36, 'Jio Finance / Credit Ltd', 'jio_finance_lap', 'LAP (Income programs + LRD + Open plot)', 'Secured', 'LAP,LRD,Open Plot Funding,DOD etc', 2500000, 500000000, 25, 5000, 9.00, 9.75, 9.00, 70, 180, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Residential/commercial/industrial/open plot; industrial property (only normal income); warehouse under LRD', 'Residential,Commercial,Industrial,Land', '50 km from branch', 'Jaipur (branch)', 'Nil', 'Nil', 'Case to Case', 'Loan upto 50Cr income program; LRD upto 100Cr; FOIR up to 140% under income multiplier (upto 2Cr); PF 0.75% upto 10Cr, 0.50% above 10Cr.', 'Normal Income,Banking,BT RTR,GST,Gross Receipt,Gross Profit,Income Multiplier,LRD,Low LTV,Director/Partner income', 'Ankit Jain', 'Sales Manager', '7976924831', NULL),
(37, 'Bajaj Finserv', 'bajaj_finserv_lap', 'LAP (Affordable + Prime) + OD/Limit', 'Secured', 'LAP,OD/Limit', 500000, 100000000, 5, 1000, 9.15, 14.00, 9.15, NULL, 180, 0, NULL, NULL, NULL, 12, NULL, NULL, 'JDA,Nagar Palika/Nigam,Society patta (latest),Agriculture gift deed/registry,Panchayat', 'Residential,Commercial,Industrial,Mixed,Agri', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'Affordable 5L-1Cr ROI 11-14; Prime 1-10Cr ROI 9.15-10.50; payout 1-1.75.', 'Banking Surrogate,BT RTR,Low LTV,LIP/NIP', 'Narender Sharma', 'Business Sales Manager', '9782879713', NULL),
(38, 'Mahindra Finance', 'mahindra_finance_sme_lap', 'SME LAP', 'Secured', 'LAP', 0, 50000000, 0, 500, 10.75, 12.00, 10.75, 80, 180, 650, 'Minimum CIBIL 650+ (as shared)', NULL, NULL, 12, NULL, NULL, 'JDA,Nagar Nigam,RHB,90A/B,Industrial,Society till 1998,DM converted,RIICO,pvt builder industrial', 'Residential,Commercial,Industrial,Plot', '50km Jaipur; other cities listed', 'Jaipur,Sikar,Khatushyamji,Ajmer,Kishangarh,Jodhpur,Udaipur', 'Nil', 'Nil', 'Case to Case', 'Funding up to 5Cr Jaipur; other locations 3Cr; PF 1%; PF sharing 70% above 1%.', 'Cash Profit,GST,Banking,RTR,Low LTV', 'Jagdish Mali', NULL, '9352562316', NULL),
(39, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 'Business HL + LAP + Micro LAP + OD + Builder funding', 'Secured', 'HL,LAP,Micro LAP,OD,Builder funding', 500000, 10000000, 5, 100, 9.99, 16.00, 9.99, 90, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'All type of property; society patta Dec 2024; builder funding upto 15Cr (JDA & society)', 'Residential,Commercial,Industrial,Society', 'Jaipur + 60 km', 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'Login fee HL 1180; LAP 999; PF 1.5%+GST.', 'Non-income up to 75L; cash salary 30k', 'Nemichand Regar', NULL, '9799415005', NULL),
(40, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 'HL + LAP', 'Secured', 'HL,LAP', 500000, 7500000, 5, 75, 9.50, 15.50, 9.50, 80, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta before 2024 (90B list submitted), registered panchayat, JDA, Nagar Nigam, agriculture', 'Residential,Agri', '70 km', 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'HL 9.5-10 (declared); HL 12.5 (assessed); LAP 14.5-15.5.', 'Declared income, Assessed income', 'Mahendra Yadav', NULL, '9887750459', NULL),
(41, 'InCred Financial Services', 'incred_financial_edi_lap', 'Educational Property LAP (EDI)', 'Secured', 'School/College LAP up to 10Cr', 0, 100000000, 0, 1000, 12.00, 0.00, 12.00, 40, 0, 650, 'Minimum CIBIL 650+ (as shared)', NULL, NULL, 12, NULL, NULL, 'Institutional/commercial/residential/agriculture collateral accepted', 'Institutional,Commercial,Residential,Agri', '250 km from Jaipur & Jodhpur', 'Rajasthan (except Ganganagar & Hanumangarh)', 'Nil', 'Nil', 'Case to Case', 'CIBIL calls case to case incl CMR 8-9.', 'Assessment basis, Industry margin', 'Mukesh Sharma', 'RSM', '8209144854 / 9829332', 'mukesh.sharma@incred.com'),
(42, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 'HL + LAP (Affordable profiles)', 'Secured', 'HL,LAP', 0, 0, 0, 0, 9.50, 15.00, 9.50, 70, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta till 2024; 2025 patta basis development; JDA/RHB/panchayat/city; agriculture with 40% development; niji khatedari 90b; stilt/penthouse/3rd floor; lost backchain with FIR/pub; ecological/Agra road; HT line upto 15L; ready commercial purchase', 'Residential,Commercial,Agri,Society', NULL, 'Jaipur (cluster)', 'Nil', 'Nil', 'Case to Case', 'Login fee HL 799+GST; NHL 1299+GST; various ROI slabs.', 'Cash salaried, bank salaried, SENP; cash+bank combo; non ITR upto 50L', 'Sakshi Gupta', 'Cluster Manager', '8949137450', 'sakshi.gupta@pnbhousing.com'),
(43, 'YES Bank', 'yes_bank_working_capital_mib', 'Working Capital (CC/OD/DLOD/Term Loan, CGTMSE, BG/LC)', 'Secured', 'CC,OD,TL,DLOD,CGTMSE,BG,LC,Export limits', 5000000, 100000000, 50, 1000, 0.00, 0.00, 0.00, 100, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Industrial,Residential,Commercial,Nagar Nigam/Parishad', 'Industrial,Residential,Commercial', '50 km', 'Jaipur', 'Nil', 'Nil', 'Case to Case', 'Min vintage 3 years; min turnover 2.5Cr; CMR 6-7 acceptable.', 'Sole Banking,Multiple Banking', 'Prahalad Upadhyay', NULL, '9784477027', NULL),
(44, 'Bandhan Bank', 'bandhan_bank_sme_lap', 'SME-LAP + DOD + LRD + Property Purchase', 'Secured', 'LAP TL,DOD,LRD,Industrial/Commercial purchase', 2500000, 150000000, 25, 1500, 0.00, 0.00, 0.00, NULL, 0, 650, 'Minimum CIBIL 650+ (as shared)', NULL, NULL, 12, NULL, NULL, 'Residential,Commercial,Industrial,plots', 'Residential,Commercial,Industrial,Plot', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'SME-LAP specialists', 'Net income, BT multiplier, ABB, GTP, GST based', 'Kamalshree Jain', NULL, '9828020091', NULL),
(45, 'Arka Fincap Ltd', 'arka_fincap_lap_dod', 'LAP + Dropline OD (DOD) + Society Patta product', 'Secured', 'LAP,DOD,Society Patta LAP', 2500000, 30000000, 25, 300, 11.00, 12.50, 11.00, 80, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Residence/Commercial/Industrial/Commercial purchase; society technically approved (till 2018 for valuation)', 'Residential,Commercial,Industrial,Plot', '70 km', NULL, 'Nil', 'Nil', 'Case to Case', 'DOD ROI 12%; Society patta ROI 11.5-12.5.', NULL, 'Mahesh Nayak', 'SM- LAP (DSA Vertical)', '7976343881', NULL),
(46, 'Anand Rathi Global Finance', 'anand_rathi_global_finance_prime_lap', 'Prime LAP', 'Secured', 'LAP, Open Land LAP, Hotel/Commercial/Industrial', 5000000, 45000000, 50, 450, 12.00, 14.00, 12.00, 70, 180, 650, 'Minimum CIBIL 650+ (as shared)', NULL, NULL, 12, NULL, NULL, 'JDA/Nagar Nigam/RHB/90A, approved open land, hotel, commercial & industrial, technically approved property', 'Residential,Commercial,Industrial,Land,Hotel', '50 km from branch', 'Jaipur,Jodhpur', 'Nil', 'Nil', 'Case to Case', 'CMR up to 8.', 'FOIR 150% on PAT', 'Sudhir Nayak', NULL, '9571618151', NULL),
(47, 'Kogta Financial India Ltd', 'kogta_financial_lap', 'LAP + Residential/Commercial Purchase', 'Secured', 'LAP,Purchase', 1000000, 10000000, 10, 100, 13.00, 0.00, 13.00, 70, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Gram Panchayat, Industrial, Commercial, Mixed, RHB, JDA, Nagar Nigam, freehold, society patta till date; school/college/hotel/hospital/PG; agri title via registered sale deed/gift deed', 'Residential,Commercial,Industrial,Mixed,Agri,Institutional', '60 km', 'Jaipur (branch c-scheme)', 'Nil', 'Nil', 'Case to Case', 'Login fees 2000; PF 1-2%+GST; payout 1.75-2%+GST corporate DSAs.', 'LIP,NIP,GST multiplier, ABB, assessed income', 'Ravi Bhakher', 'Cluster Sales Manager (C-Scheme Branch)', '8387906093', NULL),
(48, 'Sundaram Home Finance', 'sundaram_home_finance_hl_lap_plot', 'Home Loan + LAP + Plot Loan', 'Secured', 'HL,LAP,Plot Loan', 0, 80000000, 0, 800, 8.85, 11.00, 8.85, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Residential,Commercial,Under construction,Open NA plot', 'Residential,Commercial,Plot', NULL, NULL, 'Nil', 'Nil', 'Case to Case', 'Login fee 590; HL 8.85/9.10; LAP 11; plot 9.5; PF varies.', 'Salaried, NRI, Net Profit, Gross Receipt, Gross Margin, ABB, GST, RTR, LIP, LRD, Low LTV, Rental, Vehicle income', 'Mahendra Singh Rathore', NULL, '7062316784', NULL),
(49, 'ORIX Leasing & Financial Services', 'orix_equipment_finance', 'Equipment & Machinery Finance', 'Secured', 'Equipment loans, machine refinance', 2000000, 75000000, 20, 750, 0.00, 0.00, 0.00, NULL, 60, 0, NULL, NULL, NULL, 12, NULL, NULL, NULL, 'Equipment collateral', 'Pan India (Rajasthan,UP,MP,Gujarat etc)', 'Rajasthan,UP,MP,Gujarat,Pan India', 'Nil', 'Nil', 'Case to Case', 'Min turnover 5Cr; login fee zero; equipment list includes IT/solar/medical etc.', NULL, 'Ashish Jain', 'Area Sales Manager', '9694939845', NULL),
(50, 'Sammaan Capital (Indiabulls)', 'sammaan_capital_hl_lap', 'HL + LAP (High value cases)', 'Secured', 'HL,LAP,Open land LAP,Commercial purchase,P+C', 0, 200000000, 0, 2000, 8.75, 10.45, 8.75, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'SORP/SOCP, RIICO/industrial, hotel/resort/farmhouse/hostel, open land, commercial purchase, P+C, society patta till Dec2024', 'Residential,Commercial,Industrial,Land', 'Rajasthan maximum locations open', 'Rajasthan', 'Nil', 'Nil', 'Case to Case', 'Payout: LAP 1.35%; HL 0.90%.', 'Normal income,Gross profit,Banking surrogate,BT-RTR,LIP', 'Nitesh Sharma', 'Sales Manager', '9782122295', 'nitesh.s17@sammaancapital.com'),
(51, 'Altumcredo Home Finance', 'altumcredo_home_finance_hl_lap', 'HL + LAP', 'Secured', 'HL,LAP', 1000000, 5000000, 10, 50, 12.00, 13.00, 12.00, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta till date, Agriculture current gift deed, GP', 'Residential,Agri', 'All locations within 60km from Jaipur', 'Jaipur (no negative areas incl Jamwaramgarh/Delhi road/Agra road etc are excluded)', 'Nil', 'Nil', 'Case to Case', 'Insurance nil; channel payout 1.25%; negative areas NOT funded (Agra road/Jaisinghpura khor/Jamwaramgarh/Delhi road).', 'Cash salary, contractor, dairy income, daily wages', 'Suraj Singh', NULL, '7732852493', NULL),
(52, 'Bajaj Markets (Business Loan)', 'bajaj_markets_business_loan_bikaner', 'Self Employed Business Loan (Term/Flexi)', 'Unsecured', 'Business Loan', 200000, 3000000, 2, 30, 20.00, 22.00, 20.00, NULL, 96, 685, 'Minimum CIBIL 685+ (as shared)', NULL, NULL, 12, NULL, NULL, 'Unsecured', 'Unsecured', 'Bikaner', 'Bikaner', 'Nil', 'Nil', 'Case to Case', 'Vintage min 6 months; ABB 10K/50K; turnover norms; docs KYC+business registration+6M bank stmt.', 'Banking program/STBL/One eligibility', 'Krishan Kumar Acharya', 'Bikaner', '6375203741', 'Krishan.acharya3@bajajfinserv.in'),
(53, 'SMFG Grihashakti India Home Finance', 'smfg_grihashakti_hl_lap', 'HL + LAP', 'Secured', 'HL,LAP', 500000, 10000000, 5, 100, 10.00, 15.00, 10.00, NULL, 0, 0, NULL, NULL, NULL, 12, NULL, NULL, 'Society patta till 2023, Nagar Nigam/Palika, Panchayat, private builder patta (90B), agri registry; Agra road/Jaisinghpura khor', 'Residential,Commercial,Agri,Society', '50 km from Jaipur (and listed locations)', 'Jaipur,Kalwad,Bagru,Chomu,Bassi,Kanota,Jaisingpura Khor,Kotputli,Sikar,Ajmer/Bhiwadi', 'Nil', 'Nil', 'Case to Case', 'Ticket size 5L-1Cr; HL 10-13; LAP 12-15.', 'Non-income proof cases', 'Ramovatar Yadav', 'BSM DSA', '9667163040', NULL),
(54, 'Neogrowth Credit Pvt Ltd', 'neogrowth_credit_pvt_ltd_secured_lap', 'Loan Against Property (LAP)', 'Secured', 'Secured LAP (Jaipur Branch); ITR income-based; Cashflow program; Banking surrogate', 2000000, 7500000, 20, 75, NULL, NULL, NULL, 100, 180, 650, '650+', 'Business', 'Business holder/Proprietor/Retailer/Wholesaler', NULL, 'Business holder; Proprietor; Retailer; Wholesaler', NULL, 'Panchayati Patta (Registered); JDA/Nagar Nigam/Housing Board; 90B Society Patta; Industrial Property; SORP; SOCP; Vacant constructed property', 'Panchayati Patta Registered, JDA, Nagar Nigam, Housing Board, Society Patta (90B), Industrial, SORP, SOCP, Vacant constructed', '40 KM from Jaipur branch', 'Jaipur (within 40km from branch)', NULL, NULL, 'Case to Case', 'LTV 60% to 100% (SORP/SOCP up to 100%); FOIR up to 100% in Banking & ITR-based products.', 'ITR Income Based (20-75L); Cashflow program (20-40L); Banking Surrogate (20-50L)', 'Arjun Singh', NULL, '9511560027', NULL),
(55, 'Shubham Housing Finance', 'shubham_housing_finance_hl_lap', 'Home Loan; Loan Against Property (LAP)', 'Secured', 'Home Loan; Home Loan Balance Transfer (BT); LAP', 500000, 5000000, 5, 50, 10.25, 14.50, 10.25, 87, NULL, NULL, NULL, 'Salaried; Self-employed', 'Salaried; Cash salaried; SENP; SEP; Kaccha-pakka income considered', NULL, 'Salaried; SENP; SEP; Cash salaried; Kaccha-pakka income', NULL, 'Residential; Commercial; Industrial & Warehouse; Society patta (2025); Agriculture with one registered deed; Registered Gram Panchayat Patta; Agriculture-to-sale deed commercial/industrial use properties', 'Residential, Commercial, Industrial/Warehouse, Society patta, Agriculture (registered deed), Gram Panchayat Patta', NULL, 'Jaipur (Delhi/Agra Road, Jaisinghpura Khor) — per note', NULL, NULL, 'Case to Case', 'HL ROI 11.99%–13.75%; HL BT 10.25%–11.25%; LAP ROI 14.5% and above. Highest LTV claim; LTV up to 87% for land purchase (P+C). Kaccha-pakka income & cash salaried profiles considered. Doing M-profile areas incl. Delhi/Agra Road & Jaisinghpura Khor.', 'Home Loan; Home Loan BT; LAP', 'Rahul Gupta', NULL, '7014271763', NULL),
(56, 'Piramal Capital and Housing Finance Ltd', 'piramal_capital_housing_finance_ltd_lap', 'Loan Against Property (LAP)', 'Secured', 'LAP (Normal Income; Banking ABB; LIP/NIP; RTR; Low LTV; Pure Rental/Cash Rental)', 1000000, 40000000, 10, 400, 11.60, 14.50, 11.60, 70, NULL, NULL, NULL, 'Salaried; Self-employed', 'Normal income; Banking ABB; LIP/NIP; RTR; Pure rental/cash rental', NULL, 'Standard; Jewellers; Police; Advocate/Lawyer; Multi-tenant (labour quarters) etc.', 'Jewellers; Police; Advocate; Lawyer', 'Society patta; Plot in MC limits; Gram panchayat properties; Sub-divided; Industrial; Mixed use; Amalgamated; School/Play School (in individual\'s name); Farm house; Hospital/Nursing Home up to 20 beds; Multi-tenant; Godown', 'Society patta, MC plot, Gram Panchayat, Sub-divided, Industrial, Mixed-use, Amalgamated, School/Play School, Farm house, Hospital/Nursing Home, Multi-tenant, Godown', NULL, NULL, NULL, NULL, 'Case to Case', 'Login fee ₹2360. LTV: SORP 70%, Commercial 65%, Mixed-use 65%, Industrial 50%, School 50%, Hospital 50%, Residential Plot 50%. Other acceptance: loan up to 20L on single registry with >1yr vintage; latest transfer deed title; commercial purchase under low LTV; plot LAP; pure rental (incl. cash rental); additional LTV (10%) on certain LAP cases under business loan up to 5 yrs (per note).', 'Normal Income; Banking ABB; LIP/NIP; RTR; Low LTV; Pure Rental; Cash Rental', 'Vishnu Sain', 'BSM - MSME', '7014485319', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lender_results`
--

CREATE TABLE `lender_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `lender_name` varchar(200) NOT NULL,
  `lender_key` varchar(100) DEFAULT NULL,
  `rank` tinyint(4) NOT NULL,
  `score` tinyint(4) DEFAULT NULL,
  `roi_min` decimal(5,2) DEFAULT NULL,
  `roi_max` decimal(5,2) DEFAULT NULL,
  `max_ltv` decimal(5,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `ai_explanation` text DEFAULT NULL,
  `score_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`score_breakdown`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lender_results`
--

INSERT INTO `lender_results` (`id`, `lead_id`, `lender_name`, `lender_key`, `rank`, `score`, `roi_min`, `roi_max`, `max_ltv`, `reason`, `ai_explanation`, `score_breakdown`, `created_at`) VALUES
(1, 5, 'Axis Bank', 'axis_bank_hl_lap', 1, 97, 9.00, NULL, 90.00, 'Best overall match for your profile; competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Axis Bank is recommended as the top lender for this customer due to its competitive interest rate of 9% and a maximum LTV of 90%, which exceeds the customer&#039;s requirement of 50% LTV. Additionally, the bank accepts JDA properties, aligning perfectly with the customer&#039;s property type, and offers flexibility for borrowers with a CIBIL score of 650 through various special programs like Cash Salaried and Low LTV, making it an ideal choice for their financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+14\",\"note\":\"Covers your 50% need\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-09 20:44:52'),
(2, 5, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 2, 97, 9.50, 15.50, 80.00, 'competitive ROI from 9.5%; LTV up to 80% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Hinduja Housing Finance is recommended as the second lender for this customer due to its flexible Loan-to-Value (LTV) ratio of up to 80%, which exceeds the customer&#039;s requirement of 50%, allowing for a higher loan amount if needed. With a CIBIL score of 650, the customer can benefit from the assessed income program, potentially securing a rate between 12.5% and 15.5% for the loan against their JDA property in Jaipur, which meets the lender&#039;s property acceptance criteria.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+14\",\"note\":\"Covers your 50% need\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-09 20:44:52'),
(3, 5, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 3, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; LTV up to 70% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #3 lender for this customer due to its maximum LTV of 70%, which exceeds the customer&#039;s requirement of 50% for a ₹40L loan, allowing for greater borrowing potential. With a CIBIL score of 650, the customer can benefit from the flexible ROI range of 9.5%–15%, and the acceptance of JDA properties aligns perfectly with the customer&#039;s asset type. Additionally, the lender&#039;s special programs for cash and bank salaried individuals provide tailored options that suit the customer&#039;s income profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+14\",\"note\":\"Covers your 50% need\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-09 20:44:52'),
(4, 5, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 97, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; LTV up to 75% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:44:52'),
(5, 5, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 5, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; LTV up to 90% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:44:52'),
(6, 5, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 97, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; LTV up to 80% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:44:52'),
(7, 5, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-09 20:44:52'),
(8, 5, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-09 20:44:52'),
(9, 5, 'AU Small Finance Bank', 'au_sfb_mbl_lap', 9, 93, 11.00, NULL, 75.00, 'ROI 11% — reasonable for this profile; LTV up to 75% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:44:52'),
(10, 5, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 10, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-09 20:44:52'),
(11, 6, 'Axis Bank', 'axis_bank_hl_lap', 1, 97, 9.00, NULL, 90.00, 'Best overall match for your profile; competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Axis Bank is the top recommendation for your loan against property due to its competitive interest rate of 9% and the high maximum LTV of 90%, which provides significant leverage against your ₹35L loan requirement. Your CIBIL score of 750 aligns well with their flexible lending criteria, and they accept your JDA property type, ensuring a smooth approval process. Additionally, their special programs, such as Pure Rental and Cash Salaried, cater specifically to your income profile, enhancing your eligibility further.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+14\",\"note\":\"Covers your 50% need\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-09 20:55:18'),
(12, 6, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 2, 97, 9.50, 15.50, 80.00, 'competitive ROI from 9.5%; LTV up to 80% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Hinduja Housing Finance is recommended as the second lender for this customer due to its competitive LAP interest rates of 14.5-15.5%, which align well with the customer&#039;s CIBIL score of 750, allowing for favorable terms despite the higher ROI. The lender accepts JDA properties, matching the customer&#039;s property type, and offers a maximum LTV of 80%, which provides flexibility beyond the customer&#039;s 50% requirement, enhancing the overall loan potential.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+14\",\"note\":\"Covers your 50% need\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-09 20:55:18'),
(13, 6, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 3, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; LTV up to 70% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #3 lender for this customer due to its maximum LTV of 70%, which exceeds the customer&#039;s requirement of 50% for a ₹35L loan, allowing for greater borrowing potential. With a CIBIL score of 750, the customer is well-positioned to secure a competitive ROI starting at 9.5%, and the acceptance of JDA properties aligns perfectly with the customer&#039;s asset type. Additionally, the lender&#039;s special programs for cash salaried individuals provide flexibility that can cater to the customer&#039;s financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+14\",\"note\":\"Covers your 50% need\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-09 20:55:18'),
(14, 6, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 97, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; LTV up to 75% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:55:18'),
(15, 6, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 5, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; LTV up to 90% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:55:18'),
(16, 6, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 97, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; LTV up to 80% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:55:18'),
(17, 6, 'Mahindra Finance', 'mahindra_finance_sme_lap', 7, 95, 10.75, 12.00, 80.00, 'ROI 10.75% — reasonable for this profile; LTV up to 80% covers your requirement; CIBIL 750 comfortably exceeds min 650; Jda title is listed in policy.', '', 'null', '2026-04-09 20:55:18'),
(18, 6, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 8, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-09 20:55:18'),
(19, 6, 'Bajaj Finserv', 'bajaj_finserv_lap', 9, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-09 20:55:18'),
(20, 6, 'AU Small Finance Bank', 'au_sfb_mbl_lap', 10, 93, 11.00, NULL, 75.00, 'ROI 11% — reasonable for this profile; LTV up to 75% covers your requirement; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-09 20:55:18'),
(21, 8, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive interest rate range of 9.5% to 10% for declared income, which aligns well with the customer&#039;s CIBIL score of 725 and monthly income of ₹15,000. Additionally, the lender&#039;s maximum LTV of 80% is favorable for the ₹10 lakh loan amount, and they accept JDA properties, making them a perfect match for the customer&#039;s property type and financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 06:42:12'),
(22, 8, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second option for this customer due to its flexible interest rates starting at 9.5%, which can accommodate a CIBIL score of 725, and a maximum LTV of 70%, allowing for a loan amount of ₹7 lakh on the ₹10 lakh property value. Additionally, the lender accepts JDA properties, aligning perfectly with the customer&#039;s property type, and offers special programs for cash salaried individuals, which is beneficial given the customer&#039;s monthly income of ₹15,000.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 06:42:12'),
(23, 8, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the third option for this customer due to its flexible loan-to-value (LTV) ratio of up to 90%, which is beneficial given the ₹10L loan amount against a JDA property in Jaipur. With a CIBIL score of 725, the customer falls within the acceptable range for Tata&#039;s interest rates starting at 9.99%, allowing for competitive borrowing costs. Additionally, Tata Capital&#039;s acceptance of all property types and their special programs for non-income earners provide a unique advantage for customers with cash salaries, making it a suitable choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 06:42:12'),
(24, 8, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-11 06:42:12'),
(25, 8, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-11 06:42:12'),
(26, 8, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 6, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:42:12'),
(27, 8, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 7, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:42:12'),
(28, 8, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 8, 93, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:42:12'),
(29, 8, 'Cholamandalam', 'cholamandalam_hl_lap_general', 9, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:42:12'),
(30, 8, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 10, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-11 06:42:12'),
(31, 9, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its flexible loan-to-value (LTV) ratio of 80%, which aligns well with the ₹40 lakh loan amount against a JDA property, and its acceptance of properties with society patta, making it a suitable match. With a CIBIL score of 650, the customer can benefit from a competitive rate of 14.5-15.5% for a loan against property, and the lender&#039;s assessed income program can accommodate the declared monthly income of ₹25,000, ensuring a tailored financing solution.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 06:47:39'),
(32, 9, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second option for this customer due to its flexibility in accepting properties like JDA and society patta, which aligns with the customer&#039;s JDA property in Jaipur. With a maximum LTV of 70%, the customer can secure ₹28L against their ₹40L loan request, and the interest rate range of 9.5% to 15% is suitable given the CIBIL score of 650, which may allow for better terms compared to other lenders. Additionally, PNB&#039;s special programs for non-ITR applicants and cash salaried individuals provide a tailored solution for the customer&#039;s income profile of ₹25,000 per month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 06:47:39'),
(33, 9, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its flexible loan-to-value (LTV) ratio of up to 90%, which is advantageous given the customer&#039;s ₹40L loan requirement against a JDA property. With a CIBIL score of 650, the customer can benefit from a competitive interest rate starting at 9.99%, and Tata Capital&#039;s acceptance of all property types aligns perfectly with the customer&#039;s JDA property, making it a suitable choice despite the income level of ₹25,000/month. Additionally, the lender&#039;s special programs for non-income verification up to ₹75L provide further flexibility for the customer&#039;s financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 06:47:39'),
(34, 9, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-11 06:47:39'),
(35, 9, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-11 06:47:39'),
(36, 9, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-11 06:47:39'),
(37, 9, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:47:39'),
(38, 9, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-11 06:47:39'),
(39, 9, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 9, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:47:39'),
(40, 9, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 10, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 06:47:39'),
(41, 10, 'Axis Bank', 'axis_bank_hl_lap', 1, 96, 9.00, NULL, 90.00, 'Best overall match for your profile; competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', 'Axis Bank is recommended as the top lender for your home loan of ₹40 lakh due to its competitive interest rate of 9% and a maximum LTV of 90%, which aligns perfectly with your CIBIL score of 750 and monthly income of ₹75,000. Additionally, they accept society properties, which matches your property type in Kota, and offer flexible programs like Pure Rental and Cash Salaried that can accommodate your financial profile effectively.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-11 13:10:23'),
(42, 10, 'Sammaan Capital (Indiabulls)', 'sammaan_capital_hl_lap', 2, 92, 8.75, 10.45, NULL, 'competitive ROI from 8.75%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; Speak to: Nitesh Sharma (9782122295).', 'Sammaan Capital (Indiabulls) is recommended as the second choice for this customer due to its competitive interest rate range of 8.75% to 10.45%, which aligns well with the customer&#039;s CIBIL score of 750, indicating a good credit profile. Additionally, the lender accepts society properties, which matches the customer&#039;s property type in Kota, and offers a loan-to-value (LTV) ratio as per policy, providing flexibility in financing options. The special programs available, including normal income and banking surrogate, can further enhance the customer&#039;s eligibility and loan terms.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.75%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-11 13:10:23'),
(43, 10, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 3, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', 'Nido Home Finance is recommended as the third option for this customer due to its competitive interest rate of 11.2%–12% and a maximum LTV of 90%, which aligns well with the ₹40L loan amount needed for a society property in Kota. With a CIBIL score of 750, the customer qualifies for their flexible terms, including a higher FOIR of up to 150%, which can enhance borrowing capacity, making it a suitable choice for their financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+8\",\"note\":\"Higher at 11.2%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-11 13:10:23'),
(44, 10, 'Grihum Housing Finance', 'grihum_housing_hf', 4, 75, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302).', '', 'null', '2026-04-11 13:10:23'),
(45, 10, 'Sundaram Home Finance', 'sundaram_home_finance_hl_lap_plot', 5, 64, 8.85, 11.00, NULL, 'competitive ROI from 8.85%; flexible CIBIL policy — case-to-case evaluation; Speak to: Mahendra Singh Rathore (7062316784); Login fee 590; HL 8.85/9.10; LAP 11; plot 9.5; PF varies..', '', 'null', '2026-04-11 13:10:23'),
(46, 11, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to their maximum loan-to-value (LTV) ratio of 80%, which aligns well with the ₹40 lakh loan amount against the JDA property in Jaipur. With a CIBIL score of 650, the customer can benefit from a competitive interest rate of 14.5-15.5% for the loan against property (LAP), and the lender&#039;s acceptance of society patta properties provides a perfect match for the customer&#039;s asset type. Additionally, the option for assessed income programs allows for flexibility in income declaration, making it a suitable choice for the customer&#039;s financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 13:15:55'),
(47, 11, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second lender for this customer due to its flexibility with a CIBIL score of 650, allowing access to loans with a maximum LTV of 70%, which translates to ₹28 lakh for the ₹40 lakh LAP loan. The property type, being JDA approved, aligns well with their acceptance criteria, and the interest rate range of 9.5% to 15% offers competitive options, especially for cash salaried individuals, making it a suitable choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 13:15:55'),
(48, 11, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its flexible loan-to-value (LTV) ratio of up to 90%, which is advantageous given the ₹40L loan amount against a JDA property. With a CIBIL score of 650, the customer can benefit from a competitive interest rate starting at 9.99%, and the lender&#039;s acceptance of all property types aligns well with the customer&#039;s asset. Additionally, Tata Capital&#039;s special program for non-income earners allows for loans up to ₹75L, providing further options for financial flexibility.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-11 13:15:55'),
(49, 11, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-11 13:15:55'),
(50, 11, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-11 13:15:55'),
(51, 11, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-11 13:15:55'),
(52, 11, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 13:15:55'),
(53, 11, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-11 13:15:55'),
(54, 11, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 9, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 13:15:55'),
(55, 11, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 10, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-11 13:15:55'),
(56, 14, 'IIFL Home Loans', 'iifl_home_loans_hl_lap', 1, 97, 8.65, 16.50, NULL, 'Best overall match for your profile; competitive ROI from 8.65%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', 'IIFL Home Loans is recommended as the top choice for this customer due to their competitive interest rate of 8.65% for salaried individuals, which aligns well with the customer&#039;s CIBIL score of 725. Additionally, they accept society title properties, making them a suitable match for the customer&#039;s property type, and their flexible loan-to-value (LTV) policy can accommodate the ₹15L loan amount effectively.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.65%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:32:45'),
(57, 14, 'Repco Home Finance', 'repco_home_finance_hl_lap', 2, 97, 9.25, 15.00, NULL, 'competitive ROI from 9.25%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', 'Repco Home Finance is recommended as the second lender for this customer due to its flexible interest rate starting at 9.25%, which is beneficial given the CIBIL score of 725, allowing for competitive terms. The lender accepts society properties, aligning perfectly with the customer&#039;s property type, and offers a special balance transfer program that could provide a 20% top-up, enhancing the loan amount if needed. Additionally, the low processing fee of 1-1.5% makes it a cost-effective choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.25%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:32:45'),
(58, 14, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 3, 97, 9.50, 15.50, 80.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', 'Hinduja Housing Finance is recommended as the #3 lender for this customer due to its competitive interest rate range of 9.5% to 15.5%, which aligns well with the customer&#039;s CIBIL score of 725, allowing for potential access to lower rates. The lender&#039;s maximum LTV of 80% on society properties, along with acceptance of the customer&#039;s property type, ensures that the ₹15L loan amount can be comfortably accommodated. Additionally, the option for both declared and assessed income programs provides flexibility in meeting the income verification requirements.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:32:45'),
(59, 14, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 4, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', '', 'null', '2026-04-13 07:32:45'),
(60, 14, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 5, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', '', 'null', '2026-04-13 07:32:45'),
(61, 14, 'Axis Bank', 'axis_bank_hl_lap', 6, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-13 07:32:45'),
(62, 14, 'Shubham Housing Finance', 'shubham_housing_finance_hl_lap', 7, 96, 10.25, 14.50, 87.00, 'ROI 10.25% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:32:45'),
(63, 14, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 8, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', '', 'null', '2026-04-13 07:32:45'),
(64, 14, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 9, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:32:45'),
(65, 14, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 10, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:32:45'),
(76, 15, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended for this customer due to its competitive LAP interest rate of 14.5-15.5%, which aligns well with the customer’s CIBIL score of 725, allowing for flexibility in loan approval. Additionally, the lender accepts JDA properties, which matches the customer&#039;s property type, and offers a maximum LTV of 80%, providing a substantial loan amount of ₹50L based on the declared income of ₹25,000/month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:39:38'),
(77, 15, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second option for this customer due to its flexibility in accepting JDA properties, which aligns with the customer&#039;s property type in Jaipur. With a maximum LTV of 70%, the customer can secure up to ₹35 lakh against their ₹50 lakh loan request, and their CIBIL score of 725 fits well within the lender&#039;s acceptable range for competitive ROI starting at 9.5%. Additionally, the lender&#039;s special programs for non-ITR borrowers and cash salaried individuals provide a tailored solution for the customer&#039;s income profile of ₹25,000 per month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:39:38'),
(78, 15, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹50L loan amount against the JDA property in Jaipur. With a CIBIL score of 725, the customer can benefit from a competitive interest rate starting at 9.99%, and the lender&#039;s acceptance of all property types, including society patta, ensures compatibility with the customer&#039;s asset. Additionally, Tata Capital&#039;s special program for non-income earners allows for loans up to ₹75L, providing flexibility for the customer&#039;s financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:39:38'),
(79, 15, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-13 07:39:38'),
(80, 15, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-13 07:39:38'),
(81, 15, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-13 07:39:38'),
(82, 15, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:39:38'),
(83, 15, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-13 07:39:38'),
(84, 15, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 9, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:39:38'),
(85, 15, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 10, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:39:38'),
(86, 16, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive LAP interest rate of 14.5-15.5%, which aligns well with the customer&#039;s CIBIL score of 750, allowing for flexibility in terms. Additionally, the lender accepts JDA properties, matching the customer&#039;s property type, and offers a maximum LTV of 80%, which supports the ₹50L loan amount effectively.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:39:47'),
(87, 16, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second lender for this customer due to its maximum loan-to-value (LTV) ratio of 70%, which aligns well with the ₹50 lakh loan amount against the JDA property in Jaipur. With a CIBIL score of 750, the customer can benefit from competitive interest rates starting at 9.5%, and the lender&#039;s acceptance of society patta properties until 2024 fits perfectly with the customer&#039;s property type. Additionally, PNB&#039;s special programs for cash and bank salaried individuals provide flexibility for income verification, making it a suitable choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:39:47'),
(88, 16, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its competitive ROI range of 9.99%–16% and a maximum LTV of 90%, which aligns well with the ₹50L LAP requirement against the JDA property in Jaipur. With a CIBIL score of 750, the customer is well-positioned to secure favorable terms, and Tata&#039;s acceptance of all property types, including society patta, enhances compatibility. Additionally, the lender&#039;s special program for non-income verification up to ₹75L offers flexibility that could benefit the customer if needed.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-13 07:39:47'),
(89, 16, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-13 07:39:47'),
(90, 16, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-13 07:39:47'),
(91, 16, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-13 07:39:47'),
(92, 16, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:39:47'),
(93, 16, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-13 07:39:47'),
(94, 16, 'Mahindra Finance', 'mahindra_finance_sme_lap', 9, 91, 10.75, 12.00, 80.00, 'ROI 10.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; CIBIL 750 comfortably exceeds min 650; Jda title is listed in policy.', '', 'null', '2026-04-13 07:39:47'),
(95, 16, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 10, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-13 07:39:47');
INSERT INTO `lender_results` (`id`, `lead_id`, `lender_name`, `lender_key`, `rank`, `score`, `roi_min`, `roi_max`, `max_ltv`, `reason`, `ai_explanation`, `score_breakdown`, `created_at`) VALUES
(96, 19, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive interest rate starting at 9.5% for home loans, which aligns well with the customer’s CIBIL score of 750, indicating a good credit profile. Additionally, they accept JDA properties, which matches the customer\'s property type, and offer a maximum LTV of 80%, allowing the customer to secure a ₹5L loan comfortably within their income of ₹25,000 per month. The option for both declared and assessed income', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 06:34:51'),
(97, 19, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as lender #2 for this customer due to its flexibility with a maximum loan-to-value (LTV) ratio of 70%, which aligns well with the JDA property type in Jaipur. With a CIBIL score of 750, the customer can secure a competitive interest rate starting at 9.5%, and the lender\'s special programs for cash salaried individuals make it a suitable option for their ₹5 lakh loan requirement. Additionally, the acceptance of society patta until 2024 ensures that the property ', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 06:34:51'),
(98, 19, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its competitive interest rate range of 9.99% to 16% and a maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹5L loan amount needed. Additionally, the lender accepts JDA properties, making it suitable for the customer\'s property type, and their flexibility with a CIBIL score of 750 allows for better loan terms, especially with special programs accommodating non-income earners up to ₹75L.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 06:34:51'),
(99, 19, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-14 06:34:51'),
(100, 19, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 5, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:34:51'),
(101, 19, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 6, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:34:51'),
(102, 19, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 7, 93, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:34:51'),
(103, 19, 'Cholamandalam', 'cholamandalam_hl_lap_general', 8, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:34:51'),
(104, 19, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 9, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-14 06:34:51'),
(105, 19, 'HSBC Bank', 'hsbc_mortgages_hl_lap_dlod', 10, 76, 7.25, 8.50, NULL, 'excellent ROI starting at just 7.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Niranjan Gupta (9828547440).', '', 'null', '2026-04-14 06:34:51'),
(106, 20, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top choice for this customer due to its competitive interest rate starting at 9.5%, which aligns well with the customer\'s CIBIL score of 750, allowing for favorable terms. The lender\'s maximum LTV of 80% is suitable for the ₹5L loan amount, and their acceptance of JDA properties ensures that the customer\'s property type is eligible, making it a tailored fit for their financial profile and needs.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 06:35:15'),
(107, 20, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second option for this customer due to its flexible loan-to-value (LTV) ratio of 70%, which aligns well with the ₹5 lakh loan amount needed for a JDA property in Jaipur. With a CIBIL score of 750, the customer can benefit from competitive interest rates starting at 9.5%, and the lender\'s acceptance of society patta properties until 2024 makes it a suitable choice for this specific profile. Additionally, the special programs for cash salaried individuals ', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 06:35:15'),
(108, 20, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for your home loan due to its flexible 90% LTV, which aligns well with your ₹5L loan requirement, and its acceptance of JDA properties, ensuring your property type is eligible. With a CIBIL score of 750, you can benefit from competitive interest rates starting at 9.99%, and their special programs allow for non-income verification up to ₹75L, providing additional options should your financial situation change.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 06:35:15'),
(109, 20, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-14 06:35:15'),
(110, 20, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 5, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:35:15'),
(111, 20, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 6, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:35:15'),
(112, 20, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 7, 93, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:35:15'),
(113, 20, 'Cholamandalam', 'cholamandalam_hl_lap_general', 8, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 06:35:15'),
(114, 20, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 9, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-14 06:35:15'),
(115, 20, 'HSBC Bank', 'hsbc_mortgages_hl_lap_dlod', 10, 76, 7.25, 8.50, NULL, 'excellent ROI starting at just 7.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Niranjan Gupta (9828547440).', '', 'null', '2026-04-14 06:35:15'),
(116, 21, 'Sammaan Capital (Indiabulls)', 'sammaan_capital_hl_lap', 1, 68, 8.75, 10.45, NULL, 'Best overall match for your profile; competitive ROI from 8.75%; flexible CIBIL policy — case-to-case evaluation; Speak to: Nitesh Sharma (9782122295).', 'Sammaan Capital (Indiabulls) is recommended as the top lender for this customer due to its competitive interest rate range of 8.75%–10.45%, which aligns well with the customer\'s CIBIL score of 750, offering flexibility in loan approval. Additionally, the lender accepts registry properties in Jodhpur, which matches the customer\'s property type, and provides various special programs that can cater to their income profile, ensuring a tailored loan solution.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.75%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-14 08:05:50'),
(117, 21, 'Sundaram Home Finance', 'sundaram_home_finance_hl_lap_plot', 2, 64, 8.85, 11.00, NULL, 'competitive ROI from 8.85%; flexible CIBIL policy — case-to-case evaluation; Speak to: Mahendra Singh Rathore (7062316784); Login fee 590; HL 8.85/9.10; LAP 11; plot 9.5; PF varies..', 'Sundaram Home Finance is recommended as the second choice for your home loan due to its competitive interest rate starting at 8.85%, which aligns well with your CIBIL score of 750, indicating a good credit profile. The lender accepts residential properties, which matches your registry property in Jodhpur, and offers flexible programs that can accommodate your income level of ₹15,000/month, making it a suitable option for your financial situation.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.85%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-14 08:05:50'),
(118, 21, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 3, 57, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Speak to: Dinesh Sharma (9413563675 / 7014489); IMD 2360; PF 0.65% HL; payout HL 1.0%..', 'Nido Home Finance is recommended as the #3 lender for this customer due to its competitive ROI of 11.2%–12% and a high LTV of 90%, which aligns well with the ₹5L loan requirement. Given the customer\'s CIBIL score of 750, they can benefit from Nido\'s flexibility in FOIR, allowing for a higher repayment capacity, and the acceptance of registry properties in Jodhpur ensures that their property type is suitable for financing. Additionally, the special programs like banking and cash profit can provid', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+8\",\"note\":\"Higher at 11.2%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-14 08:05:50'),
(119, 21, 'Grihum Housing Finance', 'grihum_housing_hf', 4, 51, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302); IMD 1180; payout HL 1.25 / LAP 1.50 (revised for JFM)..', '', 'null', '2026-04-14 08:05:50'),
(120, 23, 'Axis Bank', 'axis_bank_hl_lap', 1, 96, 9.00, NULL, 90.00, 'Best overall match for your profile; competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Freehold title is listed in policy.', 'Axis Bank is recommended as the top lender for this customer due to its competitive interest rate of 9% and a maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹10 lakh loan requirement against the freehold property in Kota. With a CIBIL score of 750, the customer qualifies for favorable terms, and Axis Bank\'s acceptance of freehold properties and various income types, including cash salaried and rental income, makes it an ideal match for this profile. Additionally, the absenc', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Freehold accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-14 10:13:31'),
(121, 23, 'Bajaj Finserv', 'bajaj_finserv_lap', 2, 69, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Speak to: Narender Sharma (9782879713); Affordable 5L-1Cr ROI 11-14; Prime 1-10Cr ROI 9.15-10.50; payout 1-1.75..', 'Bajaj Finserv is recommended as the #2 lender for this customer due to its competitive interest rate range of 9.15% to 14%, which aligns well with the customer\'s CIBIL score of 750, allowing for favorable terms. The lender accepts freehold properties, including those under JDA and Nagar Palika, which matches the customer\'s property type in Kota. Additionally, the option for a loan amount between ₹5L to ₹1Cr with a payout of 1-1.75 times the property value provides flexibility that suits the cust', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.15%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-14 10:13:31'),
(122, 23, 'Sammaan Capital (Indiabulls)', 'sammaan_capital_hl_lap', 3, 68, 8.75, 10.45, NULL, 'competitive ROI from 8.75%; flexible CIBIL policy — case-to-case evaluation; Speak to: Nitesh Sharma (9782122295); Payout: LAP 1.35%; HL 0.90%..', 'Sammaan Capital (Indiabulls) is recommended as the #3 lender for this customer due to its competitive interest rate range of 8.75%–10.45%, which aligns well with the customer\'s CIBIL score of 750, indicating good creditworthiness. Additionally, the lender accepts freehold properties, including commercial purchases, which matches the customer\'s asset type, and offers flexible programs like Gross Profit and Banking Surrogate that can cater to the customer\'s income profile of ₹200,000/month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.75%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-14 10:13:31'),
(123, 23, 'Piramal Capital and Housing Finance Ltd', 'piramal_capital_housing_finance_ltd_lap', 4, 65, 11.60, 14.50, 70.00, 'ROI 11.6% — flexible eligibility lender; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Speak to: Vishnu Sain (7014485319).', '', 'null', '2026-04-14 10:13:31'),
(124, 23, 'Sundaram Home Finance', 'sundaram_home_finance_hl_lap_plot', 5, 64, 8.85, 11.00, NULL, 'competitive ROI from 8.85%; flexible CIBIL policy — case-to-case evaluation; Speak to: Mahendra Singh Rathore (7062316784); Login fee 590; HL 8.85/9.10; LAP 11; plot 9.5; PF varies..', '', 'null', '2026-04-14 10:13:31'),
(125, 23, 'IDFC FIRST Bank', 'idfc_first_bank_lap_eil', 6, 62, NULL, NULL, 75.00, 'ROI disclosed on application — offers customised pricing; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; active branch in Kota.', '', 'null', '2026-04-14 10:13:31'),
(126, 23, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 7, 57, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Speak to: Dinesh Sharma (9413563675 / 7014489); IMD 2360; PF 0.65% HL; payout HL 1.0%..', '', 'null', '2026-04-14 10:13:31'),
(127, 23, 'InCred Financial Services', 'incred_financial_edi_lap', 8, 52, 12.00, NULL, 40.00, 'ROI 12% — flexible eligibility lender; your requested LTV is very conservative — easily fundable; CIBIL 750 comfortably exceeds min 650; Speak to: Mukesh Sharma (8209144854 / 9829332).', '', 'null', '2026-04-14 10:13:31'),
(128, 23, 'Grihum Housing Finance', 'grihum_housing_hf', 9, 51, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302); IMD 1180; payout HL 1.25 / LAP 1.50 (revised for JFM)..', '', 'null', '2026-04-14 10:13:31'),
(129, 24, 'Axis Bank', 'axis_bank_hl_lap', 1, 96, 9.00, NULL, 90.00, 'Best overall match for your profile; competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Freehold title is listed in policy.', 'Axis Bank is the recommended lender for your ₹10L LAP loan due to its competitive interest rate of 9% and a high maximum LTV of 90%, which aligns well with your freehold property in Kota. With a CIBIL score of 750, you meet their criteria for favorable terms, and the absence of a login fee, along with their acceptance of various income proofs including cash salaried and rental income, makes them particularly suitable for your financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Freehold accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-14 10:15:09'),
(130, 24, 'Bajaj Finserv', 'bajaj_finserv_lap', 2, 69, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Speak to: Narender Sharma (9782879713); Affordable 5L-1Cr ROI 11-14; Prime 1-10Cr ROI 9.15-10.50; payout 1-1.75..', 'Bajaj Finserv is recommended as the #2 lender for this customer due to their competitive interest rate range of 9.15% to 14%, which aligns well with the customer\'s CIBIL score of 750, providing flexibility in securing a favorable rate. Additionally, they accept the freehold property type in Kota, and with the customer\'s income of ₹200,000/month, they can comfortably meet the eligibility criteria for a loan amount of ₹10L, especially under their affordable program offering loans from ₹5L to ₹1Cr ', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.15%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-14 10:15:09'),
(131, 24, 'Sammaan Capital (Indiabulls)', 'sammaan_capital_hl_lap', 3, 68, 8.75, 10.45, NULL, 'competitive ROI from 8.75%; flexible CIBIL policy — case-to-case evaluation; Speak to: Nitesh Sharma (9782122295); Payout: LAP 1.35%; HL 0.90%..', 'Sammaan Capital is recommended as the third lender for this customer due to its competitive interest rate range of 8.75%–10.45%, which aligns well with the customer\'s strong CIBIL score of 750, allowing for favorable terms. The lender accepts freehold properties, including commercial purchases, which matches the customer\'s asset type, and offers a Loan Against Property (LAP) payout of 1.35%, making it a financially viable option for leveraging their ₹10L loan requirement. Additionally, the avail', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.75%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"-12\",\"note\":\"Not explicitly listed\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-14 10:15:09'),
(132, 24, 'Piramal Capital and Housing Finance Ltd', 'piramal_capital_housing_finance_ltd_lap', 4, 65, 11.60, 14.50, 70.00, 'ROI 11.6% — flexible eligibility lender; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Speak to: Vishnu Sain (7014485319).', '', 'null', '2026-04-14 10:15:09'),
(133, 24, 'Sundaram Home Finance', 'sundaram_home_finance_hl_lap_plot', 5, 64, 8.85, 11.00, NULL, 'competitive ROI from 8.85%; flexible CIBIL policy — case-to-case evaluation; Speak to: Mahendra Singh Rathore (7062316784); Login fee 590; HL 8.85/9.10; LAP 11; plot 9.5; PF varies..', '', 'null', '2026-04-14 10:15:09'),
(134, 24, 'IDFC FIRST Bank', 'idfc_first_bank_lap_eil', 6, 62, NULL, NULL, 75.00, 'ROI disclosed on application — offers customised pricing; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; active branch in Kota.', '', 'null', '2026-04-14 10:15:09'),
(135, 24, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 7, 57, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Speak to: Dinesh Sharma (9413563675 / 7014489); IMD 2360; PF 0.65% HL; payout HL 1.0%..', '', 'null', '2026-04-14 10:15:09'),
(136, 24, 'InCred Financial Services', 'incred_financial_edi_lap', 8, 52, 12.00, NULL, 40.00, 'ROI 12% — flexible eligibility lender; your requested LTV is very conservative — easily fundable; CIBIL 750 comfortably exceeds min 650; Speak to: Mukesh Sharma (8209144854 / 9829332).', '', 'null', '2026-04-14 10:15:09'),
(137, 24, 'Grihum Housing Finance', 'grihum_housing_hf', 9, 51, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302); IMD 1180; payout HL 1.25 / LAP 1.50 (revised for JFM)..', '', 'null', '2026-04-14 10:15:09'),
(138, 25, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive LAP interest rate of 14.5-15.5%, which aligns well with the customer\'s CIBIL score of 750, ensuring favorable terms. Additionally, the lender accepts JDA properties, matching the customer\'s property type, and offers a maximum LTV of 80%, allowing access to ₹40L against the ₹50L loan amount requested. Their programs for both declared and assessed income provide flexibility that suits the customer\'s m', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 10:16:37'),
(139, 25, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second lender for this customer due to its competitive interest rate range of 9.5% to 15% and a maximum loan-to-value (LTV) ratio of 70%, which aligns well with the ₹50 lakh loan requirement against a JDA property in Jaipur. With a CIBIL score of 750, the customer is likely to secure a favorable rate within this range, and the lender\'s acceptance of society patta properties until 2024 makes it a suitable option for this profile. Additionally, the special', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 10:16:37'),
(140, 25, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its competitive ROI range of 9.99% to 16%, which aligns well with the customer\'s CIBIL score of 750, providing flexibility in interest rates. The lender\'s maximum LTV of 90% is advantageous for a ₹50 lakh loan against a JDA property in Jaipur, and their acceptance of all property types, along with the ability to fund up to ₹75 lakh for non-income earners, makes them a suitable choice given the customer\'s income', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-14 10:16:37'),
(141, 25, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-14 10:16:37'),
(142, 25, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-14 10:16:37'),
(143, 25, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-14 10:16:37'),
(144, 25, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 10:16:37'),
(145, 25, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-14 10:16:37'),
(146, 25, 'Mahindra Finance', 'mahindra_finance_sme_lap', 9, 91, 10.75, 12.00, 80.00, 'ROI 10.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; CIBIL 750 comfortably exceeds min 650; Jda title is listed in policy.', '', 'null', '2026-04-14 10:16:37'),
(147, 25, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 10, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-14 10:16:37'),
(148, 26, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 1, 97, 9.50, 15.00, 70.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'PNB Housing Finance is recommended for this customer due to its acceptance of JDA properties, aligning perfectly with the customer\'s property type. With a maximum LTV of 70%, the customer can secure ₹70L against their ₹100L loan request, and their CIBIL score of 750 qualifies them for competitive interest rates starting at 9.5%. Additionally, the lender\'s special programs for cash salaried individuals offer flexibility that suits the customer\'s income profile of ₹25,000/month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-15 11:03:07'),
(149, 26, 'Axis Bank', 'axis_bank_hl_lap', 2, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', 'Axis Bank is recommended as the second lender for this customer due to its competitive interest rate of 9% and a maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹100L loan amount needed for a JDA property in Jaipur. Additionally, with a CIBIL score of 750, the customer meets the bank\'s criteria for favorable terms, and the absence of a login fee makes it a cost-effective option. The bank\'s acceptance of various income proofs, including cash salaried and rental income, furthe', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-15 11:03:07'),
(150, 26, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 96, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the third option for this customer due to its flexible loan-to-value (LTV) ratio of up to 90%, which aligns well with the ₹100L home loan requirement for a JDA property in Jaipur. With a CIBIL score of 750, the customer can benefit from competitive interest rates starting at 9.99%, and the lender\'s acceptance of all property types, including society patta, makes it a suitable choice for this profile. Additionally, Tata Capital\'s special programs cat', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-15 11:03:07'),
(151, 26, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-15 11:03:07'),
(152, 26, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 5, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-15 11:03:07'),
(153, 26, 'Cholamandalam', 'cholamandalam_hl_lap_general', 6, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-15 11:03:07'),
(154, 26, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 7, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-15 11:03:07'),
(155, 26, 'HSBC Bank', 'hsbc_mortgages_hl_lap_dlod', 8, 76, 7.25, 8.50, NULL, 'excellent ROI starting at just 7.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Niranjan Gupta (9828547440).', '', 'null', '2026-04-15 11:03:07'),
(156, 26, 'Grihum Housing Finance', 'grihum_housing_hf', 9, 75, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302).', '', 'null', '2026-04-15 11:03:07'),
(157, 26, 'Repco Home Finance', 'repco_home_finance_hl_lap', 10, 74, 9.25, 15.00, NULL, 'competitive ROI from 9.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Ajay Kashyap (8829011330).', '', 'null', '2026-04-15 11:03:07'),
(158, 27, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top choice for this customer due to its competitive interest rate range of 9.5% to 10% for declared income, which aligns well with the customer\'s CIBIL score of 750 and monthly income of ₹50,000. Additionally, the lender\'s maximum loan-to-value (LTV) ratio of 80% is favorable for the JDA property in Jaipur, ensuring that the customer can secure the full ₹10 lakh loan amount while benefiting from the flexibility of their special programs for both decl', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 07:02:22'),
(159, 27, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance (Roshni) is recommended as the #2 lender for your home loan of ₹10L due to its maximum LTV of 70%, which aligns well with your property type (JDA) and CIBIL score of 750, providing a good chance for approval. The interest rate range of 9.5%–15% offers flexibility, and their special programs for cash salaried individuals can cater to your income of ₹50,000/month, making it a suitable option for your financial profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 07:02:22'),
(160, 27, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its competitive interest rate range of 9.99% to 16% and a maximum LTV of 90%, which aligns well with the ₹10L loan amount needed. The acceptance of JDA properties and the flexibility for CIBIL scores like 750 make it a suitable choice, especially since the customer’s monthly income of ₹50,000 meets the lender\'s criteria for cash salary programs. Additionally, the low login fee of ₹1,180 for home loans adds to t', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 07:02:22'),
(161, 27, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-16 07:02:22'),
(162, 27, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-16 07:02:22'),
(163, 27, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 6, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:02:22'),
(164, 27, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 7, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:02:22'),
(165, 27, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 8, 93, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:02:22'),
(166, 27, 'Cholamandalam', 'cholamandalam_hl_lap_general', 9, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:02:22'),
(167, 27, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 10, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-16 07:02:22'),
(168, 29, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive interest rate range of 9.5% to 10% for declared income, which aligns well with the customer\'s CIBIL score of 750 and monthly income of ₹15,000. Additionally, the lender accepts JDA properties, making it a perfect match for the customer\'s property type, and offers a maximum LTV of 80%, allowing for a substantial loan amount of ₹50L.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 07:12:52'),
(169, 29, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #2 lender for this customer due to its acceptance of JDA properties, which aligns with the customer\'s property type, and its maximum LTV of 70%, allowing for a loan amount of ₹35 lakh against the ₹50 lakh requested. With a CIBIL score of 750, the customer can benefit from competitive ROI starting at 9.5%, and the lender\'s special programs for non-ITR applicants make it a suitable choice given the customer\'s monthly income of ₹15,000.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 07:12:52'),
(170, 29, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the third option for this customer due to its flexible maximum loan-to-value (LTV) ratio of 90%, which is beneficial given the ₹50L loan amount against a JDA property in Jaipur. With a CIBIL score of 750, the customer falls within the acceptable range for Tata\'s interest rates starting at 9.99%, and the lender\'s acceptance of all property types, including JDA, aligns perfectly with the customer\'s needs. Additionally, Tata\'s special program for non-i', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 07:12:52'),
(171, 29, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-16 07:12:52'),
(172, 29, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-16 07:12:52'),
(173, 29, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 6, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:12:52'),
(174, 29, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 7, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:12:52'),
(175, 29, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 8, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:12:52'),
(176, 29, 'Cholamandalam', 'cholamandalam_hl_lap_general', 9, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 07:12:52'),
(177, 29, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 10, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-16 07:12:52'),
(178, 31, 'IIFL Home Loans', 'iifl_home_loans_hl_lap', 1, 97, 8.65, 16.50, NULL, 'Best overall match for your profile; competitive ROI from 8.65%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', 'IIFL Home Loans is recommended as the top choice for this customer due to its competitive interest rate of 8.65% for salaried individuals, which aligns well with the customer\'s CIBIL score of 750. Additionally, IIFL accepts society title properties, making it a perfect match for the customer\'s property type, and their flexible LTV policies can accommodate the ₹50L loan amount effectively.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 8.65%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 13:17:10');
INSERT INTO `lender_results` (`id`, `lead_id`, `lender_name`, `lender_key`, `rank`, `score`, `roi_min`, `roi_max`, `max_ltv`, `reason`, `ai_explanation`, `score_breakdown`, `created_at`) VALUES
(179, 31, 'Repco Home Finance', 'repco_home_finance_hl_lap', 2, 97, 9.25, 15.00, NULL, 'competitive ROI from 9.25%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', 'Repco Home Finance is recommended as the second lender for this customer due to its competitive interest rate starting at 9.25%, which is beneficial given the CIBIL score of 750. The lender accepts society properties, aligning perfectly with the customer\'s property type, and offers a top-up option that can enhance the loan amount if needed. Additionally, the flexibility in processing fees and the potential for a balance transfer at a lower rate makes it a suitable choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.25%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 13:17:10'),
(180, 31, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 3, 97, 9.50, 15.50, 80.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', 'Hinduja Housing Finance is recommended as the third option for this customer due to its competitive interest rate range of 9.5% to 10% for declared income, which aligns well with the customer\'s CIBIL score of 750 and monthly income of ₹15,000. Additionally, the lender\'s maximum LTV of 80% is suitable for the ₹50 lakh loan amount, and they accept society properties with the necessary documentation, making them a viable choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Society accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-16 13:17:10'),
(181, 31, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 4, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', '', 'null', '2026-04-16 13:17:10'),
(182, 31, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 5, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', '', 'null', '2026-04-16 13:17:10'),
(183, 31, 'Axis Bank', 'axis_bank_hl_lap', 6, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-16 13:17:10'),
(184, 31, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 7, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy.', '', 'null', '2026-04-16 13:17:10'),
(185, 31, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 8, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 13:17:10'),
(186, 31, 'SMFG Grihashakti India Home Finance', 'smfg_grihashakti_hl_lap', 9, 93, 10.00, 15.00, NULL, 'ROI 10% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 13:17:10'),
(187, 31, 'Shubham Housing Finance', 'shubham_housing_finance_hl_lap', 10, 93, 10.25, 14.50, 87.00, 'ROI 10.25% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Society title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-16 13:17:10'),
(188, 32, 'Axis Bank', 'axis_bank_hl_lap', 1, 96, 9.00, NULL, 90.00, 'Best overall match for your profile; competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Axis Bank is recommended as the top lender for your home loan of ₹10 lakh due to its competitive interest rate of 9% and a high maximum LTV of 90%, which aligns perfectly with your JDA property in Jodhpur. With your CIBIL score of 750, you meet their criteria for favorable terms, and their special programs like Pure Rental and Cash Salaried can accommodate your income profile of ₹25,000 per month effectively. Additionally, the absence of a login fee further enhances the affordability of this opt', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-16 17:07:16'),
(189, 32, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 2, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', 'Nido Home Finance is recommended as the second option for this customer due to its competitive interest rate of 11.2%–12% and a maximum LTV of 90%, which aligns well with the ₹10L loan amount needed for a JDA property in Jodhpur. With a CIBIL score of 750, the customer can benefit from Nido\'s flexibility in FOIR, allowing for higher eligibility, and the special programs such as LIP x3 can enhance their financial options, making it a suitable choice for their profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+8\",\"note\":\"Higher at 11.2%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-16 17:07:16'),
(190, 32, 'Grihum Housing Finance', 'grihum_housing_hf', 3, 75, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302).', 'Grihum Housing Finance is recommended as the third option for this customer due to its flexibility in accepting JDA properties and a variety of property types, including those with technical/legal deviations. With a CIBIL score of 750, the customer is likely to benefit from competitive interest rates on request, and the lender\'s maximum LTV policy aligns well with the ₹10L loan amount needed. Additionally, Grihum\'s special programs, including options for non-income and cash salaried individuals,', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+5\",\"note\":\"On request\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+4\",\"note\":\"LTV as per policy\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"All cities covered\"}}', '2026-04-16 17:07:16'),
(191, 32, 'Sammaan Capital (Indiabulls)', 'sammaan_capital_hl_lap', 4, 68, 8.75, 10.45, NULL, 'competitive ROI from 8.75%; flexible CIBIL policy — case-to-case evaluation; Speak to: Nitesh Sharma (9782122295); Payout: LAP 1.35%; HL 0.90%..', '', 'null', '2026-04-16 17:07:16'),
(192, 32, 'Sundaram Home Finance', 'sundaram_home_finance_hl_lap_plot', 5, 64, 8.85, 11.00, NULL, 'competitive ROI from 8.85%; flexible CIBIL policy — case-to-case evaluation; Speak to: Mahendra Singh Rathore (7062316784); Login fee 590; HL 8.85/9.10; LAP 11; plot 9.5; PF varies..', '', 'null', '2026-04-16 17:07:16'),
(193, 33, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 1, 97, 9.50, 15.00, 70.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'PNB Housing Finance is recommended as the top choice for this customer due to its acceptance of JDA properties, which aligns perfectly with the customer\'s property type in Jaipur. With a competitive ROI starting at 9.5% and a maximum LTV of 70%, this lender can offer a loan amount of ₹70L based on the ₹100L request, which is feasible given the customer\'s CIBIL score of 750 and monthly income of ₹75,000. Additionally, their special programs for cash salaried individuals provide flexibility that c', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 07:58:58'),
(194, 33, 'Axis Bank', 'axis_bank_hl_lap', 2, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', 'Axis Bank is recommended as the #2 lender for this customer due to its competitive interest rate of 9% and a maximum LTV of 90%, which aligns well with the ₹100L loan amount needed for a JDA property in Jaipur. With a CIBIL score of 750, the customer meets the bank\'s criteria, and the absence of a login fee enhances affordability. Additionally, Axis Bank\'s special programs, such as Pure Rental and Cash Salaried, cater to diverse income sources, making it a suitable choice for this profile.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-18 07:58:58'),
(195, 33, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 96, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as #3 for this customer due to its competitive interest rate range of 9.99% to 16% and a maximum LTV of 90%, which aligns well with the ₹100L loan amount needed for the JDA property in Jaipur. With a CIBIL score of 750, the customer can benefit from favorable terms, and Tata Capital\'s acceptance of all property types, including society patta, ensures compatibility with the customer\'s requirements. Additionally, their special program for non-income proo', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 07:58:58'),
(196, 33, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-18 07:58:58'),
(197, 33, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 5, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 07:58:58'),
(198, 33, 'Cholamandalam', 'cholamandalam_hl_lap_general', 6, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 07:58:58'),
(199, 33, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 7, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-18 07:58:58'),
(200, 33, 'HSBC Bank', 'hsbc_mortgages_hl_lap_dlod', 8, 76, 7.25, 8.50, NULL, 'excellent ROI starting at just 7.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Niranjan Gupta (9828547440).', '', 'null', '2026-04-18 07:58:58'),
(201, 33, 'Grihum Housing Finance', 'grihum_housing_hf', 9, 75, NULL, NULL, NULL, 'ROI disclosed on application — offers customised pricing; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Shiv Sagar Prajapati (8875606669 / 9214302).', '', 'null', '2026-04-18 07:58:58'),
(202, 33, 'Repco Home Finance', 'repco_home_finance_hl_lap', 10, 74, 9.25, 15.00, NULL, 'competitive ROI from 9.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Ajay Kashyap (8829011330).', '', 'null', '2026-04-18 07:58:58'),
(203, 34, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive interest rate of 9.5% for declared income, which aligns well with the customer\'s CIBIL score of 750 and monthly income of ₹50,000. Additionally, the lender offers a maximum LTV of 80%, making it suitable for the ₹20L home loan on a JDA property in Jaipur, while also accommodating the property type with its acceptance of society patta and registered panchayat documents.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:02:24'),
(204, 34, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #2 lender for your home loan due to its flexible interest rates ranging from 9.5% to 15%, which can accommodate your CIBIL score of 750. With a maximum LTV of 70%, you can secure up to ₹14 lakh against your ₹20 lakh loan request, and their acceptance of JDA properties aligns perfectly with your property type. Additionally, their special programs for cash and bank salaried individuals provide options that suit your income profile of ₹50,000 per month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:02:24'),
(205, 34, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its competitive interest rate range of 9.99% to 16% and a maximum LTV of 90%, which aligns well with the ₹20L loan amount needed for a JDA property in Jaipur. Additionally, the lender\'s acceptance of all property types and flexibility with CIBIL scores, given the customer\'s score of 750, makes it a suitable option, especially since they offer special programs for non-income earners and cash salaries starting fr', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:02:24'),
(206, 34, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-18 14:02:24'),
(207, 34, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-18 14:02:24'),
(208, 34, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 6, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:02:24'),
(209, 34, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 7, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:02:24'),
(210, 34, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 8, 93, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:02:24'),
(211, 34, 'Cholamandalam', 'cholamandalam_hl_lap_general', 9, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:02:24'),
(212, 34, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 10, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-18 14:02:24'),
(213, 35, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive interest rate of 9.5% for declared income, which aligns well with the customer\'s CIBIL score of 750, ensuring favorable terms. Additionally, the lender\'s maximum LTV of 80% on JDA properties matches the customer\'s ₹50L loan requirement, making it a suitable option for financing their home in Jaipur.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:04:35'),
(214, 35, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #2 lender for this customer due to its acceptance of JDA properties and a maximum LTV of 70%, which aligns well with the ₹50L loan requirement. With a CIBIL score of 750, the customer can benefit from competitive interest rates starting at 9.5%, and the lender\'s flexibility in offering loans to non-ITR individuals up to ₹50L further enhances suitability for their income profile of ₹25,000/month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:04:35'),
(215, 35, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the third option for this customer due to its competitive interest rate range of 9.99% to 16% and a maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹50L loan requirement. Given the customer\'s CIBIL score of 750, they are likely to qualify for favorable terms, and the lender\'s acceptance of JDA properties ensures compatibility with the customer\'s property type. Additionally, Tata Capital\'s special program for non-income earners a', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:04:35'),
(216, 35, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-18 14:04:35'),
(217, 35, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-18 14:04:35'),
(218, 35, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 6, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:04:35'),
(219, 35, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 7, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:04:35'),
(220, 35, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 8, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:04:35'),
(221, 35, 'Cholamandalam', 'cholamandalam_hl_lap_general', 9, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:04:35'),
(222, 35, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 10, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-18 14:04:35'),
(223, 12, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its flexibility with a CIBIL score of 650, allowing for a loan against property (LAP) at an interest rate of 14.5-15.5%, which is competitive given the profile. The lender accepts JDA properties, aligning perfectly with the customer\'s property type, and offers a maximum loan-to-value (LTV) ratio of 80%, enabling access to ₹32L of the ₹40L loan amount needed. Additionally, the option for assessed income programs can', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:44:25'),
(224, 12, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second option for this customer due to its acceptance of JDA properties and a maximum LTV of 70%, allowing for a loan amount of ₹28L on the ₹40L request. With a CIBIL score of 650, the customer can benefit from a competitive ROI starting at 9.5%, and the lender\'s special programs cater to non-ITR applicants, making it a suitable choice for the customer\'s income profile of ₹50,000/month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:44:25'),
(225, 12, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the third option for this customer due to its flexible Loan-to-Value (LTV) ratio of up to 90%, which is advantageous for a ₹40L loan against a JDA property. With a CIBIL score of 650, the customer can benefit from a competitive interest rate starting at 9.99%, and the lender\'s acceptance of all property types aligns well with the customer\'s asset. Additionally, Tata Capital’s special program for non-income earners allows for loans up to ₹75L, provid', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-18 14:44:25'),
(226, 12, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-18 14:44:25'),
(227, 12, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-18 14:44:25'),
(228, 12, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-18 14:44:25'),
(229, 12, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:44:25'),
(230, 12, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-18 14:44:25'),
(231, 12, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 9, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:44:25'),
(232, 12, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 10, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-18 14:44:25'),
(233, 36, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended for this customer due to its competitive LAP interest rate of 14.5-15.5%, which aligns well with the customer\'s CIBIL score of 725, allowing for flexibility in terms. Additionally, the lender accepts JDA properties, which matches the customer\'s property type, and offers a maximum LTV of 80%, enabling access to a substantial loan amount of ₹75L based on their declared income.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 15:35:31'),
(234, 36, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the second option for this customer due to its maximum loan-to-value (LTV) ratio of 70%, which aligns well with the ₹75 lakh loan amount against a JDA property. With a CIBIL score of 725, the customer can benefit from competitive interest rates starting at 9.5%, and the lender\'s acceptance of society patta properties until 2024 provides added security for the loan. Additionally, the special programs for cash salaried individuals can cater to the customer\'s i', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 15:35:31'),
(235, 36, 'Axis Bank', 'axis_bank_hl_lap', 3, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', 'Axis Bank is recommended as the #3 lender for this customer due to its competitive interest rate of 9% and a maximum LTV of 90%, which aligns well with the ₹75L loan amount against the JDA property in Jaipur. With a CIBIL score of 725, the customer meets the bank\'s flexibility criteria, and the absence of a login fee, combined with special programs like Pure Rental and Cash Salaried, enhances the chances of approval based on their income of ₹25,000/month.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+5\",\"note\":\"State coverage\"}}', '2026-04-21 15:35:31'),
(236, 36, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 4, 96, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-21 15:35:31'),
(237, 36, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-21 15:35:31'),
(238, 36, 'Ujjivan Small Finance Bank', 'ujjivan_sfb_msme_lap', 6, 94, 10.50, NULL, 80.00, 'ROI 10.5% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-21 15:35:31'),
(239, 36, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 7, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 15:35:31'),
(240, 36, 'Bajaj Finserv', 'bajaj_finserv_lap', 8, 93, 9.15, 14.00, NULL, 'competitive ROI from 9.15%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Narender Sharma (9782879713).', '', 'null', '2026-04-21 15:35:31'),
(241, 36, 'Anand Rathi Global Finance', 'anand_rathi_global_finance_prime_lap', 9, 93, 12.00, 14.00, 70.00, 'ROI 12% — flexible eligibility lender; your requested LTV is very conservative — easily fundable; CIBIL 725 meets required 650; Jda title is listed in policy.', '', 'null', '2026-04-21 15:35:31'),
(242, 36, 'Protium Finance', 'protium_finance_lap_school', 10, 90, 12.50, 14.50, 75.00, 'ROI 12.5% — flexible eligibility lender; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-21 15:35:31'),
(243, 30, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended as the top lender for this customer due to its competitive interest rate range of 9.5% to 10% for declared income, which aligns well with the customer\'s CIBIL score of 750, indicating good creditworthiness. Additionally, the lender\'s maximum LTV of 80% suits the ₹50L loan requirement, and their acceptance of JDA properties ensures that the customer\'s property type is eligible for financing.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 20:34:30'),
(244, 30, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #2 lender for this customer due to its acceptance of JDA properties and a maximum LTV of 70%, which aligns well with the ₹50L loan amount needed. With a CIBIL score of 750, the customer can benefit from competitive interest rates starting at 9.5%, and the lender\'s special programs cater to non-ITR applicants, making it a suitable option given the income of ₹15,000/month. The flexibility in loan options, including cash and bank salaried programs, further ', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 20:34:30'),
(245, 30, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the #3 lender for this customer due to its maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹50L loan amount needed for a JDA property in Jaipur. With a CIBIL score of 750, the customer can benefit from a competitive rate of interest starting at 9.99%, and Tata Capital\'s acceptance of all property types, including society patta, makes it a suitable choice. Additionally, their special program for non-income earners allows for loan', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 20:34:30'),
(246, 30, 'Axis Bank', 'axis_bank_hl_lap', 4, 96, 9.00, NULL, 90.00, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Kuldeep Sharma (8740062156).', '', 'null', '2026-04-21 20:34:30'),
(247, 30, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 5, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-21 20:34:30'),
(248, 30, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 6, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 20:34:30'),
(249, 30, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 7, 90, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 20:34:30'),
(250, 30, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 8, 90, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 20:34:30'),
(251, 30, 'Cholamandalam', 'cholamandalam_hl_lap_general', 9, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 20:34:30'),
(252, 30, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 10, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-21 20:34:30'),
(253, 37, 'Hinduja Housing Finance', 'hinduja_housing_hf_hl_lap', 1, 97, 9.50, 15.50, 80.00, 'Best overall match for your profile; competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation.', 'Hinduja Housing Finance is recommended for this customer due to its competitive interest rate of 9.5% for declared income, which aligns well with the customer\'s CIBIL score of 750 and monthly income of ₹25,000. Additionally, the lender\'s maximum LTV of 80% is suitable for the ₹5L loan amount against the JDA property, ensuring that the customer can secure the necessary funding while meeting the property acceptance criteria. The flexibility in assessing income further enhances the chances of appro', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 80%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 21:02:12'),
(254, 37, 'PNB Housing Finance (Roshni)', 'pnb_housing_roshni_hl_lap', 2, 97, 9.50, 15.00, 70.00, 'competitive ROI from 9.5%; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'PNB Housing Finance is recommended as the #2 lender for this customer due to its acceptance of JDA properties, which aligns with the customer\'s property type, and its maximum LTV of 70%, allowing for a ₹3.5L loan against the ₹5L requested. With a CIBIL score of 750, the customer can benefit from competitive interest rates starting at 9.5%, and the lender\'s special programs for cash salaried individuals can accommodate their income of ₹25,000/month effectively.', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+18\",\"note\":\"Competitive at 9.5%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 70%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 21:02:12'),
(255, 37, 'Tata Capital Housing Finance', 'tata_capital_hf_hl_lap', 3, 97, 9.99, 16.00, 90.00, 'ROI 9.99% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', 'Tata Capital Housing Finance is recommended as the third option for this customer due to its competitive interest rate range of 9.99% to 16% and a maximum loan-to-value (LTV) ratio of 90%, which aligns well with the ₹5 lakh loan requirement. Given the customer\'s CIBIL score of 750, they can benefit from Tata Capital\'s flexibility in accepting various property types, including JDA properties, and the lender\'s special programs that accommodate non-income earners up to ₹75 lakh, making it a suitabl', '{\"roi\":{\"label\":\"ROI\",\"points\":\"+13\",\"note\":\"Fair at 9.99%\"},\"ltv\":{\"label\":\"LTV\",\"points\":\"+7\",\"note\":\"Good LTV at 90%\"},\"cibil\":{\"label\":\"CIBIL\",\"points\":\"+6\",\"note\":\"Case-to-case (flexible)\"},\"property\":{\"label\":\"Property\",\"points\":\"+12\",\"note\":\"Jda accepted\"},\"city\":{\"label\":\"City\",\"points\":\"+10\",\"note\":\"Jaipur explicitly listed\"}}', '2026-04-21 21:02:12'),
(256, 37, 'Jana Small Finance Bank', 'jana_sfb_hl_lap', 4, 94, 9.75, 12.00, 75.00, 'ROI 9.75% — reasonable for this profile; your requested LTV is very conservative — easily fundable; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy.', '', 'null', '2026-04-21 21:02:12'),
(257, 37, 'Aadhar Housing Finance', 'aadhar_housing_hl_lap', 5, 93, 9.00, 11.00, NULL, 'competitive ROI from 9%; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 21:02:12'),
(258, 37, 'Niwas Housing Finance', 'niwas_housing_finance_hl_lap', 6, 93, 9.75, 18.00, NULL, 'ROI 9.75% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 21:02:12'),
(259, 37, 'Motilal Oswal Home Finance', 'motilal_oswal_hf_hl_lap', 7, 93, 9.99, 16.00, NULL, 'ROI 9.99% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 21:02:12'),
(260, 37, 'Cholamandalam', 'cholamandalam_hl_lap_general', 8, 83, 11.00, 16.00, NULL, 'ROI 11% — reasonable for this profile; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; active branch in Jaipur.', '', 'null', '2026-04-21 21:02:12'),
(261, 37, 'Nido Home Finance (formerly Edelweiss Housing Finance)', 'nido_home_finance_hl', 9, 81, 11.20, 12.00, 90.00, 'ROI 11.2% — flexible eligibility lender; flexible CIBIL policy — case-to-case evaluation; Jda title is listed in policy; Speak to: Dinesh Sharma (9413563675 / 7014489).', '', 'null', '2026-04-21 21:02:12'),
(262, 37, 'HSBC Bank', 'hsbc_mortgages_hl_lap_dlod', 10, 76, 7.25, 8.50, NULL, 'excellent ROI starting at just 7.25%; flexible CIBIL policy — case-to-case evaluation; active branch in Jaipur; Speak to: Niranjan Gupta (9828547440).', '', 'null', '2026-04-21 21:02:12');

-- --------------------------------------------------------

--
-- Table structure for table `lender_shares`
--

CREATE TABLE `lender_shares` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `lender_name` varchar(100) NOT NULL,
  `status` enum('pending','shared','quote_received','login_done','sanctioned','declined') DEFAULT 'pending',
  `offered_roi` decimal(5,2) DEFAULT NULL,
  `offered_pf` decimal(10,2) DEFAULT NULL,
  `offered_ltv` decimal(5,2) DEFAULT NULL,
  `conditions` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `shared_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lender_shares`
--

INSERT INTO `lender_shares` (`id`, `lead_id`, `partner_id`, `lender_name`, `status`, `offered_roi`, `offered_pf`, `offered_ltv`, `conditions`, `remarks`, `shared_at`, `updated_at`, `created_at`) VALUES
(1, 35, 4, 'Hinduja Housing Finance', 'shared', NULL, NULL, NULL, NULL, NULL, '2026-04-18 14:09:58', '2026-04-18 14:09:58', '2026-04-18 14:09:58'),
(2, 35, 4, 'PNB Housing Finance (Roshni)', 'shared', NULL, NULL, NULL, NULL, NULL, '2026-04-18 14:10:03', '2026-04-18 14:10:03', '2026-04-18 14:10:03'),
(3, 35, 4, 'Tata Capital Housing Finance', 'shared', NULL, NULL, NULL, NULL, NULL, '2026-04-18 14:10:08', '2026-04-18 14:10:08', '2026-04-18 14:10:08'),
(4, 35, 4, 'Tata Capital Housing Finance', 'quote_received', 8.25, NULL, 70.00, NULL, '', '2026-04-18 14:10:33', '2026-04-18 14:10:33', '2026-04-18 14:10:33'),
(5, 37, 4, 'Niwas Housing Finance', 'shared', NULL, NULL, NULL, NULL, NULL, '2026-04-21 21:03:55', '2026-04-21 21:03:55', '2026-04-21 21:03:55'),
(6, 37, 4, 'Motilal Oswal Home Finance', 'shared', NULL, NULL, NULL, NULL, NULL, '2026-04-21 21:24:27', '2026-04-21 21:24:27', '2026-04-21 21:24:27');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(10) UNSIGNED NOT NULL,
  `partner_name` varchar(150) NOT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `branch_city` varchar(100) DEFAULT NULL,
  `partner_code` varchar(30) NOT NULL,
  `user_id` varchar(12) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `is_registered` tinyint(1) DEFAULT 0,
  `login_method` enum('otp_only','password','both') DEFAULT 'otp_only',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `partner_name`, `first_name`, `last_name`, `mobile`, `email`, `company_name`, `branch_city`, `partner_code`, `user_id`, `password_hash`, `is_registered`, `login_method`, `last_login_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin Demo', NULL, NULL, '9999999999', 'admin@wealthmetre.com', 'WealthMetre Finserve', 'Jaipur', 'WM-ADMIN-001', NULL, NULL, 0, 'otp_only', NULL, 'active', '2026-04-02 15:17:40', '2026-04-02 15:17:40'),
(2, 'Vishnu Sharma', NULL, NULL, '9876543210', 'vishnu@wealthmetre.com', 'WealthMetre Corporate Channel', 'Jaipur', 'WM001', NULL, NULL, 0, 'otp_only', NULL, 'active', '2026-04-02 15:20:41', '2026-04-02 15:20:41'),
(3, 'Saur acha', 'Saur', 'acha', '8290435477', NULL, NULL, NULL, 'WM-5477-994', 'WM782597', '$2y$10$pJ6vpda6tAG9FeA20d61f.dWEj71tb7HuipKRPwnDXLQtCTrJ8KTu', 1, 'both', '2026-04-21 13:49:09', 'active', '2026-04-02 19:57:23', '2026-04-21 13:49:09'),
(4, 'saurabh acharya', 'saurabh', 'acharya', '7976218596', NULL, NULL, NULL, 'WM-8596-200', 'WM179074', '$2y$10$gyVNOHMhPhFkL5X5XH4ocuoOAh4E2Fr42RkPqbEtUrvyUO6TngECy', 1, 'both', '2026-04-16 13:14:47', 'active', '2026-04-10 07:11:23', '2026-04-16 13:14:47'),
(5, 'Prashant Mathur', 'Prashant', 'Mathur', '9982213330', NULL, NULL, NULL, 'WM-3330-451', 'WM399725', '$2y$10$Fq10KJO4aPdTNn1Fgrw3p.iBfvv0DMGSKXt.voGzhV13y/pTY0mia', 1, 'both', '2026-04-13 04:35:50', 'active', '2026-04-13 04:35:50', '2026-04-13 04:35:50'),
(6, 'Puneet Mathur', 'Puneet', 'Mathur', '9311104623', NULL, NULL, NULL, 'WM-4623-193', 'WM180369', '$2y$10$LXGfSqWQd2ubTJw7Jt9SuOoXb6Ed7jgmVPHrKR69/hWEd1o35zexS', 1, 'both', '2026-04-13 07:36:38', 'active', '2026-04-13 07:36:38', '2026-04-13 07:36:38'),
(7, 'Chetan So', 'Chetan', 'So', '7665012121', NULL, NULL, NULL, 'WM-2121-892', 'WM532200', '$2y$10$699Dl.vGMlTsDQF/ulQ5R.s5P9IvT0r1LZiDQaSasTh/ayFGD5idG', 1, 'both', '2026-04-14 10:11:10', 'active', '2026-04-14 10:11:10', '2026-04-14 10:11:10');

-- --------------------------------------------------------

--
-- Table structure for table `partner_otps`
--

CREATE TABLE `partner_otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partner_otps`
--

INSERT INTO `partner_otps` (`id`, `mobile`, `otp`, `expires_at`, `used`, `created_at`) VALUES
(25, '9982213330', '375852', '2026-04-13 04:45:11', 1, '2026-04-13 04:35:11'),
(26, '9311104623', '382159', '2026-04-13 07:45:54', 1, '2026-04-13 07:35:54'),
(27, '7014349187', '219130', '2026-04-14 09:57:16', 0, '2026-04-14 09:47:16'),
(44, '', '350174', '2026-04-14 10:16:49', 0, '2026-04-14 10:06:49'),
(46, '7665012121', '228945', '2026-04-14 10:20:48', 1, '2026-04-14 10:10:48'),
(48, '7976218596', '306645', '2026-04-16 13:24:45', 1, '2026-04-16 13:14:45'),
(49, '8290435477', '289666', '2026-04-21 13:59:08', 1, '2026-04-21 13:49:08');

-- --------------------------------------------------------

--
-- Table structure for table `partner_sessions`
--

CREATE TABLE `partner_sessions` (
  `session_id` varchar(64) NOT NULL,
  `partner_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(128) NOT NULL,
  `login_time` timestamp NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partner_sessions`
--

INSERT INTO `partner_sessions` (`session_id`, `partner_id`, `token`, `login_time`, `logout_time`, `is_active`, `ip_address`) VALUES
('0a6c57673c239f9c77260f528b7d1d62', 7, '62d7303d9f22771afc84e5233d2f971ebd49b4281a6bee0253de7c823e23dd0c', '2026-04-14 10:11:10', NULL, 1, '2401:4900:c4c6:f188:7123:3ac8:e32a:8433'),
('3af633cd270ad31e5057e83a0b618144', 4, '480617d00faf37df1b1dac5f3e3c7f3447f5dcec140a24346e5024bfd2aba008', '2026-04-16 13:14:47', NULL, 1, '2405:201:5c08:d17e:449e:e9eb:24df:4c05'),
('3f5678a7e1378d80ab355c8816fc4b6e', 3, 'a85d6370280ee47eb322bdca4042d469cbdd27493b5069a5470c6e2162cc0f3a', '2026-04-14 10:07:02', '2026-04-16 13:14:37', 0, '2405:201:5c08:d17e:cdb8:1a2a:b126:37a9'),
('4f4da9e8f9bbf62c0053b906c287482e', 3, '16f86ab848e0aa0d72c94d9d673136b2b913c91b9312d2463b9efb45fe8464a0', '2026-04-14 10:11:07', NULL, 1, '2405:201:5c08:d17e:f848:8cc:4e5b:fca4'),
('68a07c19fdb91688dec57ab1e4fadb46', 3, 'a01eb49c83b7889a4b6ce2626686331c1ee20fbb657aa74a29ab4b3e24dc695e', '2026-04-21 13:49:09', NULL, 1, '2405:201:5c08:d17e:2cdc:cefe:b114:d3f3'),
('8c97f65067cf6927497c8b1bb47daf0c', 3, 'c643ad3c29f4716cd4ea8d490c59f81c297b72d10d62b4b5672b3d828e159e09', '2026-04-02 19:57:23', '2026-04-02 20:03:43', 0, '2405:201:5c08:d17e:6016:a203:6f4b:6083'),
('97e675c75c724561fef12142fca70027', 4, '83249f031804815ad7327a761e34093d4a734f8b37dc112e54f5904bb413c232', '2026-04-11 06:45:37', NULL, 1, '2405:201:5c1d:d801:bd17:2fc1:5654:5384'),
('af2ec95f04e24e5e0e0984f5c5e8aa83', 5, 'b0f7b36f2ea357798ce940880fed2c2492f535cd28a746b0565a769d19fc49f3', '2026-04-13 04:35:50', NULL, 1, '2402:e280:231a:2f8:44cb:695b:4ce8:3824'),
('b041211e17236ac960ba24b41f997a80', 3, '16c0b6fab14f42a763cf05aa91281c5b7f797b1a1ff8a4049fd90dc40ad5d930', '2026-04-11 06:21:32', '2026-04-14 09:48:29', 0, '2405:201:5c1d:d801:852d:1ac8:90ed:1211'),
('ce4d07de08275c70c79acc96548b3d75', 4, '47f181a041c46f8035599a579e1c319f1877cf91671faa05ce958c85e9de824d', '2026-04-10 07:11:23', NULL, 1, '2405:201:5c1d:d801:de4f:de0e:8a0a:4271'),
('eb7f867f449fc6567553ff667160deac', 6, 'c978b2a37bfea8f34fdb7f693b331926b944cb8d14710653cdb1d83f72bea735', '2026-04-13 07:36:38', NULL, 1, '2409:40d4:12b:5858:6495:beff:feb0:b12'),
('fe4d4f341a31aa4bc606d6b258716ca1', 3, '81c4e04e6c6b5baf8bb9a9217b6faf1e4d6460c8615792f84f3eb3e890d9d8ec', '2026-04-03 06:54:04', NULL, 1, '2405:201:5c08:d17e:6016:a203:6f4b:6083');

-- --------------------------------------------------------

--
-- Table structure for table `partner_users`
--

CREATE TABLE `partner_users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('partner','team_lead','admin') DEFAULT 'partner',
  `status` enum('active','suspended','pending') DEFAULT 'active',
  `auth_token` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `policy_master`
--

CREATE TABLE `policy_master` (
  `id` int(11) NOT NULL,
  `lender_name` varchar(255) DEFAULT NULL,
  `raw_message_hash` varchar(255) DEFAULT NULL,
  `raw_message_text` longtext DEFAULT NULL,
  `parsed_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parsed_json`)),
  `policy_version` int(11) DEFAULT 1,
  `is_update` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `policy_master`
--

INSERT INTO `policy_master` (`id`, `lender_name`, `raw_message_hash`, `raw_message_text`, `parsed_json`, `policy_version`, `is_update`, `created_at`) VALUES
(1, 'Cholamandalam', 'f6bcad616bed3df6e74b30eb35cdd48ab1668994dce1cc209abfa52236d6630f', 'Hi Greetings of the day Company Cholamandalam SME Term Loan LTV 75 Geo limit 100 km Jaipur', '{\"raw_message_text\":\"Hi Greetings of the day Company Cholamandalam SME Term Loan LTV 75 Geo limit 100 km Jaipur\",\"sender_number\":919999999999,\"sender_name\":\"SM Team\",\"source\":\"whatsapp\",\"received_at\":\"2026-04-14T22:54:22.268+05:30\",\"normalized_message\":\"hi greetings of the day company cholamandalam sme term loan ltv 75 geo limit 100 km jaipur\",\"raw_message_hash\":\"f6bcad616bed3df6e74b30eb35cdd48ab1668994dce1cc209abfa52236d6630f\"}', 1, 0, '2026-04-14 18:24:08');

-- --------------------------------------------------------

--
-- Table structure for table `website_leads`
--

CREATE TABLE `website_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) DEFAULT '',
  `phone` varchar(20) NOT NULL,
  `email` varchar(120) DEFAULT '',
  `loan_type` varchar(60) DEFAULT '',
  `city` varchar(60) DEFAULT '',
  `message` text DEFAULT NULL,
  `source` varchar(40) DEFAULT 'website',
  `utm` varchar(200) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_leads`
--

INSERT INTO `website_leads` (`id`, `name`, `phone`, `email`, `loan_type`, `city`, `message`, `source`, `utm`, `created_at`) VALUES
(1, 'gggg', '7777777777', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-16 03:39:39'),
(2, 'mmmmmmm', '9999999999', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-16 03:50:38'),
(3, 'newzaaa', '8787878787', '', 'home', 'alwar', NULL, 'diva_ai_v3', '', '2026-04-16 06:21:30'),
(4, 'viewzaaaaaa', '8989898989', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-16 06:27:23'),
(5, 'raju', '9090909090', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-16 07:00:19'),
(6, 'prachi', '9879879879', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-16 18:09:37'),
(7, 'Gujjar', '9090809090', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-16 19:20:09'),
(8, 'panchi2', '8908908908', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-17 10:35:09'),
(9, 'butra', '8989898989', '', 'home', 'jaipur', NULL, 'diva_ai_v3', '', '2026-04-18 03:55:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_partner` (`partner_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_alerts`
--
ALTER TABLE `admin_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `bureau_reports`
--
ALTER TABLE `bureau_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Indexes for table `customer_leads`
--
ALTER TABLE `customer_leads`
  ADD PRIMARY KEY (`lead_id`),
  ADD KEY `idx_partner` (`partner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `diva_conversations`
--
ALTER TABLE `diva_conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_mobile` (`customer_mobile`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_loan_type` (`loan_type`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_contact_given` (`customer_mobile`,`status`);

--
-- Indexes for table `diva_leads_v2`
--
ALTER TABLE `diva_leads_v2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `uq_lead_doc` (`lead_id`,`document_type`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_type` (`document_type`);

--
-- Indexes for table `document_analysis`
--
ALTER TABLE `document_analysis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Indexes for table `followups`
--
ALTER TABLE `followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_due` (`due_date`,`status`);

--
-- Indexes for table `followup_tasks`
--
ALTER TABLE `followup_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_partner` (`partner_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`lead_id`),
  ADD KEY `idx_partner` (`partner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_mobile` (`customer_mobile`),
  ADD KEY `idx_next_action_date` (`next_action_date`);

--
-- Indexes for table `lead_documents`
--
ALTER TABLE `lead_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lead_doc` (`lead_id`,`doc_type`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Indexes for table `lead_status_history`
--
ALTER TABLE `lead_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Indexes for table `lenders_products`
--
ALTER TABLE `lenders_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_lender_key` (`lender_key`),
  ADD KEY `idx_lender_name` (`lender_name`);

--
-- Indexes for table `lender_results`
--
ALTER TABLE `lender_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_rank` (`rank`);

--
-- Indexes for table `lender_shares`
--
ALTER TABLE `lender_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD UNIQUE KEY `partner_code` (`partner_code`),
  ADD UNIQUE KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_mobile` (`mobile`),
  ADD KEY `idx_code` (`partner_code`);

--
-- Indexes for table `partner_otps`
--
ALTER TABLE `partner_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mobile` (`mobile`);

--
-- Indexes for table `partner_sessions`
--
ALTER TABLE `partner_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_partner` (`partner_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `partner_users`
--
ALTER TABLE `partner_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `policy_master`
--
ALTER TABLE `policy_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `raw_message_hash` (`raw_message_hash`);

--
-- Indexes for table `website_leads`
--
ALTER TABLE `website_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_alerts`
--
ALTER TABLE `admin_alerts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `bureau_reports`
--
ALTER TABLE `bureau_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_leads`
--
ALTER TABLE `customer_leads`
  MODIFY `lead_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `diva_conversations`
--
ALTER TABLE `diva_conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diva_leads_v2`
--
ALTER TABLE `diva_leads_v2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=727;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `document_analysis`
--
ALTER TABLE `document_analysis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followups`
--
ALTER TABLE `followups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `followup_tasks`
--
ALTER TABLE `followup_tasks`
  MODIFY `task_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `lead_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_documents`
--
ALTER TABLE `lead_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_status_history`
--
ALTER TABLE `lead_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lenders_products`
--
ALTER TABLE `lenders_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `lender_results`
--
ALTER TABLE `lender_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- AUTO_INCREMENT for table `lender_shares`
--
ALTER TABLE `lender_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `partner_otps`
--
ALTER TABLE `partner_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `partner_users`
--
ALTER TABLE `partner_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `policy_master`
--
ALTER TABLE `policy_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `website_leads`
--
ALTER TABLE `website_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

-- --------------------------------------------------------

--
-- Structure for view `all_leads`
--
DROP TABLE IF EXISTS `all_leads`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u909650871_wm_user`@`127.0.0.1` SQL SECURITY DEFINER VIEW `all_leads`  AS SELECT `diva_leads_v2`.`id` AS `id`, convert(`diva_leads_v2`.`name` using utf8mb4) AS `name`, convert(`diva_leads_v2`.`mobile` using utf8mb4) AS `mobile`, convert(`diva_leads_v2`.`city` using utf8mb4) AS `city`, convert(`diva_leads_v2`.`product_type` using utf8mb4) AS `product`, `diva_leads_v2`.`loan_amount` AS `loan_amount`, `diva_leads_v2`.`property_value` AS `property_value`, `diva_leads_v2`.`monthly_income` AS `monthly_income`, `diva_leads_v2`.`cibil_score` AS `cibil_score`, NULL AS `employment_type`, 'Diva AI' AS `source`, `diva_leads_v2`.`created_at` AS `lead_date`, 'diva_leads_v2' AS `lead_table` FROM `diva_leads_v2` WHERE `diva_leads_v2`.`mobile` is not null AND `diva_leads_v2`.`mobile` <> ''union all select `customer_leads`.`lead_id` AS `id`,convert(`customer_leads`.`customer_name` using utf8mb4) AS `name`,convert(`customer_leads`.`customer_mobile` using utf8mb4) AS `mobile`,convert(`customer_leads`.`city` using utf8mb4) AS `city`,convert(`customer_leads`.`loan_type` using utf8mb4) AS `product`,`customer_leads`.`amount` AS `loan_amount`,`customer_leads`.`valuation` AS `property_value`,`customer_leads`.`monthly_income` AS `monthly_income`,`customer_leads`.`cibil` AS `cibil_score`,convert(`customer_leads`.`occupation` using utf8mb4) AS `employment_type`,'Partner Portal' AS `source`,`customer_leads`.`created_at` AS `lead_date`,'customer_leads' AS `lead_table` from `customer_leads` where `customer_leads`.`customer_mobile` is not null and `customer_leads`.`customer_mobile` <> ''  ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bureau_reports`
--
ALTER TABLE `bureau_reports`
  ADD CONSTRAINT `bureau_reports_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `customer_leads` (`lead_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_leads`
--
ALTER TABLE `customer_leads`
  ADD CONSTRAINT `customer_leads_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `customer_leads` (`lead_id`) ON DELETE CASCADE;

--
-- Constraints for table `document_analysis`
--
ALTER TABLE `document_analysis`
  ADD CONSTRAINT `document_analysis_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `customer_leads` (`lead_id`) ON DELETE CASCADE;

--
-- Constraints for table `lender_results`
--
ALTER TABLE `lender_results`
  ADD CONSTRAINT `lender_results_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `customer_leads` (`lead_id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_sessions`
--
ALTER TABLE `partner_sessions`
  ADD CONSTRAINT `partner_sessions_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
