-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 04, 2026 at 03:35 AM
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
-- Database: `laundry_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_ons`
--

CREATE TABLE `add_ons` (
  `addon_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `addon_name` varchar(100) NOT NULL,
  `addon_price` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_ons`
--

INSERT INTO `add_ons` (`addon_id`, `service_id`, `addon_name`, `addon_price`, `description`) VALUES
(1, 1, 'Extra Dry', 15.00, 'Additional drying time for extra crisp laundry'),
(2, 2, 'Extra Dry', 15.00, 'Additional drying time for self-service'),
(3, 3, 'Extra Dry', 15.00, 'Additional drying time for dry-only service'),
(4, 4, 'Extra Dry', 15.00, 'Additional drying time for thick items'),
(5, 5, 'Extra Dry', 15.00, 'Additional drying time for comforters'),
(6, 1, 'Liquid Detergent per Cup', 10.00, 'Add liquid detergent per cup'),
(7, 2, 'Liquid Detergent per Cup', 10.00, 'Add liquid detergent per cup'),
(8, 1, 'Fabric Conditioner', 10.00, 'Add fabric conditioner to enhance softness'),
(9, 2, 'Fabric Conditioner', 10.00, 'Add fabric conditioner to enhance softness');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `Admin_ID` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`Admin_ID`, `username`, `password`) VALUES
(1, 'mangTV_landry', 'laundryShop12');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `Booking_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Admin_ID` int(11) DEFAULT NULL,
  `Schedule_ID` int(11) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `add_ons` varchar(255) DEFAULT NULL,
  `pick_deliver` varchar(50) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `booking_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`Booking_ID`, `Customer_ID`, `Admin_ID`, `Schedule_ID`, `service`, `add_ons`, `pick_deliver`, `special_instructions`, `status`, `total_amount`, `booking_date`) VALUES
(1, 1, 1, 1, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 638.80, '2025-11-02 13:25:35'),
(2, 1, 1, 2, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 536.80, '2025-11-02 13:25:35'),
(3, 1, 1, 3, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(4, 2, 1, 4, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(5, 1, 1, 5, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 465.14, '2025-11-02 13:25:35'),
(6, 2, 1, 6, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 421.20, '2025-11-02 13:25:35'),
(7, 1, 1, 7, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 529.50, '2025-11-02 13:25:35'),
(8, 2, 1, 8, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(9, 3, 1, 9, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(10, 1, 1, 10, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 212.86, '2025-11-02 13:25:35'),
(11, 2, 1, 11, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 576.00, '2025-11-02 13:25:35'),
(12, 4, 1, 12, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 552.20, '2025-11-02 13:25:35'),
(13, 1, 1, 13, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(14, 2, 1, 14, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(15, 3, 1, 15, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 240.86, '2025-11-02 13:25:35'),
(16, 1, 1, 16, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 557.00, '2025-11-02 13:25:35'),
(17, 4, 1, 17, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 382.10, '2025-11-02 13:25:35'),
(18, 2, 1, 18, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(19, 5, 1, 19, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(20, 4, 1, 20, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 342.14, '2025-11-02 13:25:35'),
(21, 1, 1, 21, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 413.20, '2025-11-02 13:25:35'),
(22, 2, 1, 22, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 533.70, '2025-11-02 13:25:35'),
(23, 3, 1, 23, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(24, 1, 1, 24, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(25, 4, 1, 25, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 274.00, '2025-11-02 13:25:35'),
(26, 2, 1, 26, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 488.00, '2025-11-02 13:25:35'),
(27, 6, 1, 27, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 391.60, '2025-11-02 13:25:35'),
(28, 4, 1, 28, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(29, 1, 1, 29, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(30, 2, 1, 30, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 276.29, '2025-11-02 13:25:35'),
(31, 3, 1, 31, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 642.00, '2025-11-02 13:25:35'),
(32, 4, 1, 32, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 487.40, '2025-11-02 13:25:35'),
(33, 1, 1, 33, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(34, 7, 1, 34, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(35, 2, 1, 35, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 249.71, '2025-11-02 13:25:35'),
(36, 6, 1, 36, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 513.80, '2025-11-02 13:25:35'),
(37, 5, 1, 37, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 547.00, '2025-11-02 13:25:35'),
(38, 7, 1, 38, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(39, 4, 1, 39, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(40, 1, 1, 40, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 432.71, '2025-11-02 13:25:35'),
(41, 2, 1, 41, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 672.80, '2025-11-02 13:25:35'),
(42, 3, 1, 42, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 551.90, '2025-11-02 13:25:35'),
(43, 7, 1, 43, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(44, 8, 1, 44, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(45, 4, 1, 45, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 267.43, '2025-11-02 13:25:35'),
(46, 1, 1, 46, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 684.40, '2025-11-02 13:25:35'),
(47, 2, 1, 47, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 465.40, '2025-11-02 13:25:35'),
(48, 6, 1, 48, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(49, 7, 1, 49, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(50, 9, 1, 50, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 288.86, '2025-11-02 13:25:35'),
(51, 4, 1, 51, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 508.40, '2025-11-02 13:25:35'),
(52, 1, 1, 52, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 499.70, '2025-11-02 13:25:35'),
(53, 2, 1, 53, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(54, 3, 1, 54, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(55, 7, 1, 55, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 378.86, '2025-11-02 13:25:35'),
(56, 10, 1, 56, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 529.40, '2025-11-02 13:25:35'),
(57, 4, 1, 57, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 424.50, '2025-11-02 13:25:35'),
(58, 1, 1, 58, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(59, 2, 1, 59, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(60, 6, 1, 60, 'Full Service', 'Extra Dry', 'Pickup', NULL, 'Completed', 224.71, '2025-11-02 13:25:35'),
(61, 7, 1, 61, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 382.00, '2025-11-02 13:25:35'),
(62, 5, 1, 62, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 547.30, '2025-11-02 13:25:35'),
(63, 10, 1, 63, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(64, 4, 1, 64, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(65, 1, 1, 65, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 307.43, '2025-11-02 13:25:35'),
(66, 2, 1, 66, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 646.80, '2025-11-02 13:25:35'),
(67, 3, 1, 67, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 348.20, '2025-11-02 13:25:35'),
(68, 11, 1, 68, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 515.00, '2025-11-02 13:25:35'),
(69, 7, 1, 69, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(70, 10, 1, 70, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 263.71, '2025-11-02 13:25:35'),
(71, 4, 1, 71, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 432.00, '2025-11-02 13:25:35'),
(72, 1, 1, 72, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 545.20, '2025-11-02 13:25:35'),
(73, 2, 1, 73, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(74, 6, 1, 74, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(75, 12, 1, 75, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 200.57, '2025-11-02 13:25:35'),
(76, 7, 1, 76, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 564.20, '2025-11-02 13:25:35'),
(77, 10, 1, 77, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 526.30, '2025-11-02 13:25:35'),
(78, 4, 1, 78, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(79, 1, 1, 79, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(80, 2, 1, 80, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 490.43, '2025-11-02 13:25:35'),
(81, 3, 1, 81, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 503.60, '2025-11-02 13:25:35'),
(82, 11, 1, 82, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 535.10, '2025-11-02 13:25:35'),
(83, 9, 1, 83, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(84, 12, 1, 84, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(85, 13, 1, 85, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 247.71, '2025-11-02 13:25:35'),
(86, 7, 1, 86, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 444.00, '2025-11-02 13:25:35'),
(87, 10, 1, 87, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 458.10, '2025-11-02 13:25:35'),
(88, 4, 1, 88, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(89, 1, 1, 89, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(90, 2, 1, 90, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 242.00, '2025-11-02 13:25:35'),
(91, 6, 1, 91, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 514.00, '2025-11-02 13:25:35'),
(92, 12, 1, 92, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 543.40, '2025-11-02 13:25:35'),
(93, 7, 1, 93, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(94, 5, 1, 94, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(95, 14, 1, 95, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 475.14, '2025-11-02 13:25:35'),
(96, 10, 1, 96, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 635.40, '2025-11-02 13:25:35'),
(97, 4, 1, 97, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 486.10, '2025-11-02 13:25:35'),
(98, 1, 1, 98, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(99, 2, 1, 99, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(100, 3, 1, 100, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 254.14, '2025-11-02 13:25:35'),
(101, 11, 1, 101, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 542.40, '2025-11-02 13:25:35'),
(102, 12, 1, 102, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 409.80, '2025-11-02 13:25:35'),
(103, 7, 1, 103, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(104, 14, 1, 104, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(105, 10, 1, 105, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 377.14, '2025-11-02 13:25:35'),
(106, 4, 1, 106, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 516.40, '2025-11-02 13:25:35'),
(107, 1, 1, 107, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 436.00, '2025-11-02 13:25:35'),
(108, 15, 1, 108, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 465.00, '2025-11-02 13:25:35'),
(109, 2, 1, 109, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(110, 6, 1, 110, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 448.86, '2025-11-02 13:25:35'),
(111, 12, 1, 111, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 451.60, '2025-11-02 13:25:35'),
(112, 7, 1, 112, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 516.50, '2025-11-02 13:25:35'),
(113, 10, 1, 113, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(114, 4, 1, 114, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(115, 1, 1, 115, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 215.43, '2025-11-02 13:25:35'),
(116, 2, 1, 116, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 739.00, '2025-11-02 13:25:35'),
(117, 3, 1, 117, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 599.50, '2025-11-02 13:25:35'),
(118, 11, 1, 118, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(119, 16, 1, 119, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(120, 12, 1, 120, 'Full Service', 'Extra Dry', 'Pickup', NULL, 'Completed', 439.29, '2025-11-02 13:25:35'),
(121, 9, 1, 121, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 613.20, '2025-11-02 13:25:35'),
(122, 8, 1, 122, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 513.70, '2025-11-02 13:25:35'),
(123, 7, 1, 123, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(124, 10, 1, 124, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(125, 4, 1, 125, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 307.71, '2025-11-02 13:25:35'),
(126, 1, 1, 126, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 412.40, '2025-11-02 13:25:35'),
(127, 15, 1, 127, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 439.90, '2025-11-02 13:25:35'),
(128, 2, 1, 128, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(129, 6, 1, 129, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(130, 16, 1, 130, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 266.00, '2025-11-02 13:25:35'),
(131, 12, 1, 131, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 608.00, '2025-11-02 13:25:35'),
(132, 17, 1, 132, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 449.30, '2025-11-02 13:25:35'),
(133, 7, 1, 133, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(134, 10, 1, 134, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(135, 4, 1, 135, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 257.71, '2025-11-02 13:25:35'),
(136, 1, 1, 136, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 402.60, '2025-11-02 13:25:35'),
(137, 2, 1, 137, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 382.10, '2025-11-02 13:25:35'),
(138, 3, 1, 138, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(139, 11, 1, 139, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(140, 16, 1, 140, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 301.57, '2025-11-02 13:25:35'),
(141, 12, 1, 141, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 438.80, '2025-11-02 13:25:35'),
(142, 18, 1, 142, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 482.60, '2025-11-02 13:25:35'),
(143, 7, 1, 143, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(144, 5, 1, 144, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(145, 10, 1, 145, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 369.14, '2025-11-02 13:25:35'),
(146, 4, 1, 146, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 499.20, '2025-11-02 13:25:35'),
(147, 1, 1, 147, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 430.80, '2025-11-02 13:25:35'),
(148, 15, 1, 148, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(149, 2, 1, 149, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(150, 16, 1, 150, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 389.43, '2025-11-02 13:25:35'),
(151, 6, 1, 151, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 663.60, '2025-11-02 13:25:35'),
(152, 12, 1, 152, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 446.10, '2025-11-02 13:25:35'),
(153, 19, 1, 153, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(154, 7, 1, 154, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(155, 14, 1, 155, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 282.57, '2025-11-02 13:25:35'),
(156, 10, 1, 156, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 529.80, '2025-11-02 13:25:35'),
(157, 4, 1, 157, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 573.60, '2025-11-02 13:25:35'),
(158, 1, 1, 158, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(159, 2, 1, 159, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(160, 3, 1, 160, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 368.14, '2025-11-02 13:25:35'),
(161, 11, 1, 161, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 487.20, '2025-11-02 13:25:35'),
(162, 16, 1, 162, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 496.60, '2025-11-02 13:25:35'),
(163, 12, 1, 163, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(164, 19, 1, 164, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(165, 9, 1, 165, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 221.14, '2025-11-02 13:25:35'),
(166, 7, 1, 166, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 617.20, '2025-11-02 13:25:35'),
(167, 10, 1, 167, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 625.00, '2025-11-02 13:25:35'),
(168, 4, 1, 168, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(169, 1, 1, 169, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(170, 20, 1, 170, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 277.43, '2025-11-02 13:25:35'),
(171, 15, 1, 171, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 628.40, '2025-11-02 13:25:35'),
(172, 2, 1, 172, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 577.40, '2025-11-02 13:25:35'),
(173, 6, 1, 173, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(174, 16, 1, 174, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(175, 12, 1, 175, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 203.43, '2025-11-02 13:25:35'),
(176, 19, 1, 176, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 466.20, '2025-11-02 13:25:35'),
(177, 7, 1, 177, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 361.50, '2025-11-02 13:25:35'),
(178, 10, 1, 178, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(179, 4, 1, 179, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(180, 1, 1, 180, 'Full Service', 'Extra Dry', 'Pickup', NULL, 'Completed', 258.43, '2025-11-02 13:25:35'),
(181, 2, 1, 181, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 672.40, '2025-11-02 13:25:35'),
(182, 3, 1, 182, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 646.70, '2025-11-02 13:25:35'),
(183, 11, 1, 183, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(184, 21, 1, 184, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(185, 16, 1, 185, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 323.71, '2025-11-02 13:25:35'),
(186, 12, 1, 186, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 553.20, '2025-11-02 13:25:35'),
(187, 19, 1, 187, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 338.40, '2025-11-02 13:25:35'),
(188, 7, 1, 188, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(189, 5, 1, 189, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(190, 10, 1, 190, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 263.14, '2025-11-02 13:25:35'),
(191, 4, 1, 191, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 552.00, '2025-11-02 13:25:35'),
(192, 1, 1, 192, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 511.60, '2025-11-02 13:25:35'),
(193, 20, 1, 193, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(194, 15, 1, 194, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(195, 18, 1, 195, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 235.43, '2025-11-02 13:25:35'),
(196, 2, 1, 196, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 413.80, '2025-11-02 13:25:35'),
(197, 16, 1, 197, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 648.10, '2025-11-02 13:25:35'),
(198, 6, 1, 198, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(199, 22, 1, 199, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(200, 19, 1, 200, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 264.71, '2025-11-02 13:25:35'),
(201, 12, 1, 201, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 451.60, '2025-11-02 13:25:35'),
(202, 7, 1, 202, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 339.80, '2025-11-02 13:25:35'),
(203, 14, 1, 203, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(204, 10, 1, 204, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(205, 4, 1, 205, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 213.71, '2025-11-02 13:25:35'),
(206, 1, 1, 206, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 420.80, '2025-11-02 13:25:35'),
(207, 22, 1, 207, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 409.10, '2025-11-02 13:25:35'),
(208, 2, 1, 208, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(209, 3, 1, 209, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(210, 11, 1, 210, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 357.43, '2025-11-02 13:25:35'),
(211, 16, 1, 211, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 586.80, '2025-11-02 13:25:35'),
(212, 12, 1, 212, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 625.30, '2025-11-02 13:25:35'),
(213, 23, 1, 213, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(214, 19, 1, 214, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(215, 17, 1, 215, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 320.00, '2025-11-02 13:25:35'),
(216, 7, 1, 216, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 553.80, '2025-11-02 13:25:35'),
(217, 10, 1, 217, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 591.10, '2025-11-02 13:25:35'),
(218, 4, 1, 218, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(219, 1, 1, 219, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(220, 20, 1, 220, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 364.43, '2025-11-02 13:25:35'),
(221, 15, 1, 221, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 495.20, '2025-11-02 13:25:35'),
(222, 22, 1, 222, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 559.60, '2025-11-02 13:25:35'),
(223, 2, 1, 223, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(224, 6, 1, 224, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(225, 16, 1, 225, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 239.14, '2025-11-02 13:25:35'),
(226, 9, 1, 226, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 510.00, '2025-11-02 13:25:35'),
(227, 12, 1, 227, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 537.50, '2025-11-02 13:25:35'),
(228, 19, 1, 228, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(229, 24, 1, 229, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(230, 7, 1, 230, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 311.71, '2025-11-02 13:25:35'),
(231, 21, 1, 231, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 378.00, '2025-11-02 13:25:35'),
(232, 10, 1, 232, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 545.20, '2025-11-02 13:25:35'),
(233, 4, 1, 233, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(234, 1, 1, 234, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(235, 22, 1, 235, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 253.71, '2025-11-02 13:25:35'),
(236, 2, 1, 236, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 578.20, '2025-11-02 13:25:35'),
(237, 3, 1, 237, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 359.40, '2025-11-02 13:25:35'),
(238, 11, 1, 238, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(239, 13, 1, 239, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(240, 16, 1, 240, 'Full Service', 'Extra Dry', 'Pickup', NULL, 'Completed', 237.29, '2025-11-02 13:25:35'),
(241, 12, 1, 241, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 512.40, '2025-11-02 13:25:35'),
(242, 19, 1, 242, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 435.30, '2025-11-02 13:25:35'),
(243, 25, 1, 243, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(244, 7, 1, 244, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(245, 5, 1, 245, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 294.00, '2025-11-02 13:25:35'),
(246, 10, 1, 246, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 386.80, '2025-11-02 13:25:35'),
(247, 4, 1, 247, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 463.70, '2025-11-02 13:25:35'),
(248, 1, 1, 248, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(249, 20, 1, 249, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(250, 15, 1, 250, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 354.86, '2025-11-02 13:25:35'),
(251, 22, 1, 251, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 504.80, '2025-11-02 13:25:35'),
(252, 18, 1, 252, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 575.30, '2025-11-02 13:25:35'),
(253, 2, 1, 253, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(254, 6, 1, 254, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(255, 16, 1, 255, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 248.86, '2025-11-02 13:25:35'),
(256, 12, 1, 256, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 537.00, '2025-11-02 13:25:35'),
(257, 19, 1, 257, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 498.30, '2025-11-02 13:25:35'),
(258, 24, 1, 258, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(259, 7, 1, 259, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(260, 25, 1, 260, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 439.57, '2025-11-02 13:25:35'),
(261, 14, 1, 261, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 498.00, '2025-11-02 13:25:35'),
(262, 10, 1, 262, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 605.10, '2025-11-02 13:25:35'),
(263, 4, 1, 263, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(264, 1, 1, 264, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(265, 22, 1, 265, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 383.43, '2025-11-02 13:25:35'),
(266, 26, 1, 266, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 604.00, '2025-11-02 13:25:35'),
(267, 2, 1, 267, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 503.60, '2025-11-02 13:25:35'),
(268, 3, 1, 268, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(269, 11, 1, 269, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(270, 16, 1, 270, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 198.00, '2025-11-02 13:25:35'),
(271, 12, 1, 271, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 434.00, '2025-11-02 13:25:35'),
(272, 19, 1, 272, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 623.90, '2025-11-02 13:25:35'),
(273, 7, 1, 273, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(274, 25, 1, 274, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(275, 10, 1, 275, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 275.14, '2025-11-02 13:25:35'),
(276, 4, 1, 276, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 540.20, '2025-11-02 13:25:35'),
(277, 1, 1, 277, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 411.90, '2025-11-02 13:25:35'),
(278, 20, 1, 278, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(279, 15, 1, 279, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(280, 22, 1, 280, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 434.71, '2025-11-02 13:25:35'),
(281, 2, 1, 281, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 456.00, '2025-11-02 13:25:35'),
(282, 8, 1, 282, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 430.10, '2025-11-02 13:25:35'),
(283, 6, 1, 283, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(284, 16, 1, 284, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(285, 27, 1, 285, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 223.14, '2025-11-02 13:25:35'),
(286, 12, 1, 286, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 374.00, '2025-11-02 13:25:35'),
(287, 19, 1, 287, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 380.70, '2025-11-02 13:25:35'),
(288, 24, 1, 288, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 465.00, '2025-11-02 13:25:35'),
(289, 9, 1, 289, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(290, 7, 1, 290, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 269.71, '2025-11-02 13:25:35'),
(291, 25, 1, 291, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 508.40, '2025-11-02 13:25:35'),
(292, 21, 1, 292, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 407.30, '2025-11-02 13:25:35'),
(293, 10, 1, 293, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(294, 4, 1, 294, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(295, 1, 1, 295, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 254.00, '2025-11-02 13:25:35'),
(296, 22, 1, 296, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 691.00, '2025-11-02 13:25:35'),
(297, 2, 1, 297, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 572.90, '2025-11-02 13:25:35'),
(298, 3, 1, 298, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(299, 11, 1, 299, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(300, 16, 1, 300, 'Full Service', 'Extra Dry', 'Pickup', NULL, 'Completed', 271.57, '2025-11-02 13:25:35'),
(301, 27, 1, 301, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 663.60, '2025-11-02 13:25:35'),
(302, 12, 1, 302, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 480.10, '2025-11-02 13:25:35'),
(303, 28, 1, 303, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(304, 19, 1, 304, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(305, 7, 1, 305, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 262.00, '2025-11-02 13:25:35'),
(306, 25, 1, 306, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 547.60, '2025-11-02 13:25:35'),
(307, 10, 1, 307, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 358.70, '2025-11-02 13:25:35'),
(308, 4, 1, 308, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 515.00, '2025-11-02 13:25:35'),
(309, 1, 1, 309, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(310, 20, 1, 310, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 228.57, '2025-11-02 13:25:35'),
(311, 15, 1, 311, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 739.20, '2025-11-02 13:25:35'),
(312, 22, 1, 312, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 548.70, '2025-11-02 13:25:35'),
(313, 2, 1, 313, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(314, 6, 1, 314, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(315, 16, 1, 315, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 267.71, '2025-11-02 13:25:35'),
(316, 27, 1, 316, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 417.80, '2025-11-02 13:25:35'),
(317, 12, 1, 317, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 632.00, '2025-11-02 13:25:35'),
(318, 19, 1, 318, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(319, 24, 1, 319, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(320, 29, 1, 320, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 284.14, '2025-11-02 13:25:35'),
(321, 7, 1, 321, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 604.40, '2025-11-02 13:25:35'),
(322, 25, 1, 322, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 537.20, '2025-11-02 13:25:35'),
(323, 5, 1, 323, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(324, 10, 1, 324, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(325, 4, 1, 325, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 203.43, '2025-11-02 13:25:35'),
(326, 1, 1, 326, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 610.40, '2025-11-02 13:25:35'),
(327, 22, 1, 327, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 500.10, '2025-11-02 13:25:35'),
(328, 18, 1, 328, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(329, 2, 1, 329, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(330, 3, 1, 330, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 202.29, '2025-11-02 13:25:35'),
(331, 11, 1, 331, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 393.20, '2025-11-02 13:25:35'),
(332, 16, 1, 332, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 404.80, '2025-11-02 13:25:35'),
(333, 27, 1, 333, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(334, 12, 1, 334, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(335, 19, 1, 335, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 253.14, '2025-11-02 13:25:35'),
(336, 27, 1, 336, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 497.80, '2025-11-02 13:25:35'),
(337, 7, 1, 337, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 443.40, '2025-11-02 13:25:35'),
(338, 25, 1, 338, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(339, 14, 1, 339, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(340, 17, 1, 340, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 253.86, '2025-11-02 13:25:35'),
(341, 30, 1, 341, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 583.20, '2025-11-02 13:25:35'),
(342, 10, 1, 342, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 591.80, '2025-11-02 13:25:35'),
(343, 4, 1, 343, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(344, 1, 1, 344, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(345, 20, 1, 345, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 253.71, '2025-11-02 13:25:35'),
(346, 15, 1, 346, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 464.40, '2025-11-02 13:25:35'),
(347, 22, 1, 347, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 478.70, '2025-11-02 13:25:35'),
(348, 2, 1, 348, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 465.00, '2025-11-02 13:25:35'),
(349, 26, 1, 349, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(350, 6, 1, 350, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 488.00, '2025-11-02 13:25:35'),
(351, 16, 1, 351, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 550.00, '2025-11-02 13:25:35'),
(352, 12, 1, 352, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 380.70, '2025-11-02 13:25:35'),
(353, 19, 1, 353, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(354, 24, 1, 354, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(355, 29, 1, 355, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 210.00, '2025-11-02 13:25:35'),
(356, 27, 1, 356, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 687.00, '2025-11-02 13:25:35'),
(357, 7, 1, 357, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 422.40, '2025-11-02 13:25:35'),
(358, 25, 1, 358, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(359, 10, 1, 359, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(360, 4, 1, 360, 'Full Service', 'Extra Dry', 'Pickup', NULL, 'Completed', 277.00, '2025-11-02 13:25:35'),
(361, 1, 1, 361, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 598.00, '2025-11-02 13:25:35'),
(362, 22, 1, 362, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 419.90, '2025-11-02 13:25:35'),
(363, 3, 1, 363, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(364, 11, 1, 364, 'Comforter', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(365, 30, 1, 365, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 312.86, '2025-11-02 13:25:35'),
(366, 31, 1, 366, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 412.40, '2025-11-02 13:25:35'),
(367, 23, 1, 367, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 388.80, '2025-11-02 13:25:35'),
(368, 16, 1, 368, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 515.00, '2025-11-02 13:25:35'),
(369, 9, 1, 369, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(370, 12, 1, 370, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 236.86, '2025-11-02 13:25:35'),
(371, 19, 1, 371, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 637.60, '2025-11-02 13:25:35'),
(372, 27, 1, 372, 'Self Service - Dry Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 586.50, '2025-11-02 13:25:35'),
(373, 7, 1, 373, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(374, 25, 1, 374, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(375, 21, 1, 375, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 204.86, '2025-11-02 13:25:35'),
(376, 10, 1, 376, 'Self Service - Wash Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 515.40, '2025-11-02 13:25:35'),
(377, 4, 1, 377, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 551.50, '2025-11-02 13:25:35'),
(378, 1, 1, 378, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(379, 20, 1, 379, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(380, 15, 1, 380, 'Full Service', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 330.43, '2025-11-02 13:25:35'),
(381, 22, 1, 381, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 379.60, '2025-11-02 13:25:35'),
(382, 31, 1, 382, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 501.50, '2025-11-02 13:25:35'),
(383, 30, 1, 383, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 500.00, '2025-11-02 13:25:35'),
(384, 6, 1, 384, 'Comforter', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(385, 16, 1, 385, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 222.00, '2025-11-02 13:25:35'),
(386, 32, 1, 386, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 584.80, '2025-11-02 13:25:35'),
(387, 12, 1, 387, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 485.40, '2025-11-02 13:25:35'),
(388, 19, 1, 388, 'Blanket/Bedsheet', 'Extra Dry', 'Delivery', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(389, 24, 1, 389, 'Comforter', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(390, 29, 1, 390, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 248.57, '2025-11-02 13:25:35'),
(391, 27, 1, 391, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 510.00, '2025-11-02 13:25:35'),
(392, 7, 1, 392, 'Self Service - Dry Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 460.80, '2025-11-02 13:25:35'),
(393, 25, 1, 393, 'Blanket/Bedsheet', 'None', 'Pickup', NULL, 'Completed', 450.00, '2025-11-02 13:25:35'),
(394, 5, 1, 394, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(395, 10, 1, 395, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 253.14, '2025-11-02 13:25:35'),
(396, 4, 1, 396, 'Self Service - Wash Only', 'Extra Dry', 'Pickup', NULL, 'Completed', 633.00, '2025-11-02 13:25:35'),
(397, 1, 1, 397, 'Self Service - Dry Only', 'None', 'Delivery', NULL, 'Completed', 472.10, '2025-11-02 13:25:35'),
(398, 22, 1, 398, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(399, 18, 1, 399, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(400, 11, 1, 400, 'Full Service', 'Extra Dry', 'Delivery', NULL, 'Completed', 250.43, '2025-11-02 13:25:35'),
(401, 33, 1, 401, 'Self Service - Wash Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 560.00, '2025-11-02 13:25:35'),
(402, 30, 1, 402, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 526.00, '2025-11-02 13:25:35'),
(403, 16, 1, 403, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(404, 12, 1, 404, 'Comforter', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 315.00, '2025-11-02 13:25:35'),
(405, 24, 1, 405, 'Full Service', 'None', 'Pickup', NULL, 'Completed', 267.14, '2025-11-02 13:25:35'),
(406, 19, 1, 406, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Completed', 682.00, '2025-11-02 13:25:35'),
(407, 29, 1, 407, 'Self Service - Dry Only', 'None', 'Pickup and Delivery', NULL, 'Completed', 456.30, '2025-11-02 13:25:35'),
(408, 34, 1, 408, 'Blanket/Bedsheet', 'Extra Dry', 'Pickup', NULL, 'Completed', 265.00, '2025-11-02 13:25:35'),
(409, 28, 1, 409, 'Comforter', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(410, 25, 1, 410, 'Full Service', 'None', 'Pickup and Delivery', NULL, 'Completed', 319.14, '2025-11-02 13:25:35'),
(411, 21, 1, 411, 'Self Service - Wash Only', 'None', 'Pickup', NULL, 'Completed', 566.80, '2025-11-02 13:25:35'),
(412, 32, 1, 412, 'Self Service - Dry Only', 'Extra Dry', 'Delivery', NULL, 'Completed', 460.50, '2025-11-02 13:25:35'),
(413, 26, 1, 413, 'Blanket/Bedsheet', 'None', 'Pickup and Delivery', NULL, 'Completed', 300.00, '2025-11-02 13:25:35'),
(414, 1, 1, 414, 'Comforter', 'None', 'Pickup', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(415, 20, 1, 415, 'Full Service', 'None', 'Delivery', NULL, 'Completed', 259.14, '2025-11-02 13:25:35'),
(416, 30, 1, 416, 'Self Service - Wash Only', 'Extra Dry', 'Pickup and Delivery', NULL, 'Completed', 604.60, '2025-11-02 13:25:35'),
(417, 31, 1, 417, 'Self Service - Dry Only', 'None', 'Pickup', NULL, 'Completed', 424.50, '2025-11-02 13:25:35'),
(418, 35, 1, 418, 'Blanket/Bedsheet', 'None', 'Delivery', NULL, 'Completed', 250.00, '2025-11-02 13:25:35'),
(419, 2, NULL, 419, 'Full Service', 'Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 0.00, '2025-11-11 17:40:46'),
(420, 2, NULL, 419, 'Full Service', 'Liquid Detergent per Cup (+₱10)', 'walkin', '', 'Pending', 210.00, '2025-11-11 17:40:46'),
(421, 60, NULL, 420, 'Comforter, Blanket/Bedsheet', 'Extra Dry (+₱15)', 'delivery', NULL, 'Pending', 0.00, '2025-11-12 12:47:56'),
(422, 60, NULL, 420, 'Comforter, Blanket/Bedsheet', 'Extra Dry (+₱15)', 'delivery', 'Palinis po ng panty ko.', 'Pending', 435.00, '2025-11-12 12:47:56'),
(423, 61, NULL, 421, 'Dry Only', 'Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 0.00, '2025-11-12 12:49:24'),
(424, 61, NULL, 421, 'Dry Only', 'Liquid Detergent per Cup (+₱10)', 'walkin', 'Wag niyo po sirain ang Tback ko.', 'Pending', 80.00, '2025-11-12 12:49:24'),
(425, 62, NULL, 422, 'Full Service', 'Fabric Conditioner (+₱10)', 'delivery', NULL, 'Pending', 0.00, '2025-11-12 12:51:51'),
(426, 62, NULL, 422, 'Full Service', 'Fabric Conditioner (+₱10)', 'delivery', 'Wag pong lagyan ng puti sa itim.', 'Pending', 230.00, '2025-11-12 12:51:51'),
(427, 63, NULL, 423, 'Comforter', '', 'walkin', NULL, 'Pending', 0.00, '2025-11-25 03:47:41'),
(428, 63, NULL, 423, 'Comforter', '', 'walkin', '', 'Pending', 200.00, '2025-11-25 03:47:41'),
(429, 65, NULL, 424, 'Comforter', 'Extra Dry (+₱15), Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 0.00, '2025-11-29 14:01:13'),
(430, 65, NULL, 424, 'Comforter', 'Extra Dry (+₱15), Liquid Detergent per Cup (+₱10)', 'walkin', '', 'Pending', 225.00, '2025-11-29 14:01:13'),
(431, 1, NULL, 425, 'Self Service - Wash Only', 'None', 'Delivery', NULL, 'Pending', 0.00, '2025-11-29 14:56:00'),
(432, 1, NULL, 426, 'Wash & Fold', 'Fabric Softener', 'Pickup', NULL, 'Pending', 0.00, '2025-11-29 14:56:00'),
(433, 2, NULL, 427, 'Premium Wash', 'Bleach, Softener', 'Delivery', NULL, 'Pending', 0.00, '2025-11-29 14:56:00'),
(434, 61, NULL, 428, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 210.00, '2026-04-19 18:32:08'),
(435, 61, NULL, 428, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10)', 'walkin', '', 'Pending', 210.00, '2026-04-19 18:32:08'),
(436, 61, NULL, 429, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 210.00, '2026-04-19 18:32:28'),
(437, 61, NULL, 429, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10)', 'walkin', '', 'Pending', 210.00, '2026-04-19 18:32:28'),
(438, 61, NULL, 430, 'Dry Only', 'Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 80.00, '2026-04-19 18:37:00'),
(439, 61, NULL, 430, 'Dry Only', 'Liquid Detergent per Cup (+₱10)', 'walkin', '', 'Pending', 80.00, '2026-04-19 18:37:00'),
(442, 61, NULL, 432, 'Blanket/Bedsheet', '', 'walkin', NULL, 'Pending', 200.00, '2026-04-19 18:49:45'),
(443, 61, NULL, 432, 'Blanket/Bedsheet', '', 'walkin', '', 'Pending', 200.00, '2026-04-19 18:49:45'),
(444, 61, NULL, 433, 'Blanket/Bedsheet', '', 'walkin', NULL, 'Pending', 200.00, '2026-04-19 18:51:31'),
(445, 61, NULL, 433, 'Blanket/Bedsheet', '', 'walkin', '', 'Pending', 200.00, '2026-04-19 18:51:31'),
(446, 61, NULL, 434, 'Blanket/Bedsheet', '', 'walkin', NULL, 'Pending', 200.00, '2026-04-19 18:53:52'),
(447, 61, NULL, 434, 'Blanket/Bedsheet', '', 'walkin', '', 'Pending', 200.00, '2026-04-19 18:53:52');
INSERT INTO `booking` (`Booking_ID`, `Customer_ID`, `Admin_ID`, `Schedule_ID`, `service`, `add_ons`, `pick_deliver`, `special_instructions`, `status`, `total_amount`, `booking_date`) VALUES
(448, 61, NULL, 436, 'Blanket/Bedsheet', 'Fabric Conditioner (+₱10)', 'walkin', NULL, 'Pending', 210.00, '2026-04-19 19:25:57'),
(449, 61, NULL, 436, 'Blanket/Bedsheet', 'Fabric Conditioner (+₱10)', 'walkin', '', 'Pending', 210.00, '2026-04-19 19:25:57'),
(450, 36, NULL, 437, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10), Fabric Conditione', 'walkin', NULL, 'Pending', 220.00, '2026-04-19 19:48:58'),
(451, 36, NULL, 437, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10), Fabric Conditioner (+₱10)', 'walkin', '', 'Pending', 220.00, '2026-04-19 19:48:58'),
(452, 38, NULL, 438, 'Full Service', 'Fabric Conditioner (+₱10)', 'walkin', NULL, 'Pending', 210.00, '2026-04-19 21:14:22'),
(453, 38, NULL, 438, 'Full Service', 'Fabric Conditioner (+₱10)', 'walkin', '', 'Pending', 210.00, '2026-04-19 21:14:22'),
(454, 61, NULL, 439, 'Blanket/Bedsheet, Full Service', 'Extra Dry (+₱15), Liquid Detergent per Cup (+₱10)', 'walkin', NULL, 'Pending', 0.00, '2026-05-28 05:14:29'),
(455, 61, NULL, 439, 'Blanket/Bedsheet, Full Service', 'Extra Dry (+₱15), Liquid Detergent per Cup (+₱10)', 'walkin', '', 'Pending', 425.00, '2026-05-28 05:14:29');

--
-- Triggers `booking`
--
DELIMITER $$
CREATE TRIGGER `after_booking_complete_inventory` AFTER UPDATE ON `booking` FOR EACH ROW BEGIN
  IF NEW.status = 'Completed' AND OLD.status <> 'Completed' THEN
    -- Example: Deduct 1 unit of detergent and 1 packaging per completed booking
    UPDATE products_inventory
    SET stock = stock - 1
    WHERE product_name = 'Detergent';

    UPDATE products_inventory
    SET stock = stock - 1
    WHERE product_name = 'Packaging';

    INSERT INTO inventory_logs (inventory_type, item_name, action, quantity, previous_quantity, new_quantity, remarks)
    SELECT 'product', product_name, 'Deducted', 1, stock + 1, stock, 'Auto deduction after booking completion'
    FROM products_inventory
    WHERE product_name IN ('Detergent', 'Packaging');
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_completed` AFTER UPDATE ON `booking` FOR EACH ROW BEGIN
  IF NEW.status = 'Completed' THEN
    UPDATE delivery
    SET delivery_status = 'Out for Delivery',
        delivery_date = CURDATE()
    WHERE booking_id = NEW.Booking_ID;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_confirmed` AFTER UPDATE ON `booking` FOR EACH ROW BEGIN
  IF NEW.status = 'Confirmed' AND OLD.status <> 'Confirmed' THEN
    INSERT INTO tracking (Customer_ID, Schedule_ID, laundry_status, tracking_date)
    VALUES (NEW.Customer_ID, NEW.Schedule_ID, 'Scheduled', CURDATE());
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_insert` AFTER INSERT ON `booking` FOR EACH ROW BEGIN
  INSERT INTO payments (customer_id, booking_id, amount, payment_status, payment_date)
  VALUES (NEW.Customer_ID, NEW.Booking_ID, NEW.total_amount, 'Pending', NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_booking_update` AFTER UPDATE ON `booking` FOR EACH ROW BEGIN
  IF OLD.status <> NEW.status THEN
    INSERT INTO system_logs (admin_id, action, description)
    VALUES (
      NEW.Admin_ID,
      'Booking Status Update',
      CONCAT('Booking ID ', NEW.Booking_ID, ' changed from ', OLD.status, ' to ', NEW.status)
    );
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sync_payment_after_booking_status` AFTER UPDATE ON `booking` FOR EACH ROW BEGIN
    -- Only run if status changed
    IF NEW.status <> OLD.status THEN
        
        -- If booking is Confirmed → mark payment as Paid
        IF NEW.status = 'Confirmed' THEN
            UPDATE payments
            SET payment_status = 'Paid'
            WHERE booking_id = NEW.Booking_ID;
        END IF;

        -- If booking is Cancelled → mark payment as Refunded
        IF NEW.status = 'Cancelled' THEN
            UPDATE payments
            SET payment_status = 'Refunded'
            WHERE booking_id = NEW.Booking_ID;
        END IF;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `booking_online`
--

CREATE TABLE `booking_online` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `delivery_option` varchar(50) NOT NULL,
  `dropoff_date` date NOT NULL,
  `dropoff_time` varchar(50) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `addons` text DEFAULT NULL,
  `service` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Processing','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `completed_at` datetime DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_online`
--

INSERT INTO `booking_online` (`id`, `customer_id`, `admin_id`, `schedule_id`, `customer_name`, `contact_number`, `address`, `delivery_option`, `dropoff_date`, `dropoff_time`, `special_instructions`, `addons`, `service`, `timestamp`, `status`, `completed_at`, `total_amount`, `discount`, `payment_status`) VALUES
(1, 1, 1, 1, 'Maria Santos', '+639876543221', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '5-6', '', 'Liquid Detergent', 'Full Service, Wash Only', '2025-11-27 22:01:43', '', '2025-11-28 14:35:30', 310.00, 0.00, 'Paid'),
(2, 1, 1, 1, 'Maria Santos', '+639676051714', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '1-3', '', 'Extra Dry', 'Full Service, Blanket/Bedsheet', '2025-11-27 22:21:49', '', '2025-11-28 14:35:30', 435.00, 0.00, 'Paid'),
(3, 1, 1, 1, 'Maria Santos', '+639877665432', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '5-6', '', 'Extra Dry', 'Full Service', '2025-11-27 23:01:23', '', '2025-11-28 14:35:30', 235.00, 0.00, 'Paid'),
(4, 1, 1, 1, 'Maria Santos', '+639877665432', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '5-6', '', 'Extra Dry', 'Full Service, Blanket/Bedsheet', '2025-11-27 23:09:08', '', '2025-11-28 14:35:30', 435.00, 0.00, 'Paid'),
(5, 1, 1, 1, 'Maria Santos', '+639876543221', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-delivery', '2025-11-28', '3-5', '', 'Fabric Conditioner', 'Full Service, Blanket/Bedsheet', '2025-11-27 23:14:55', '', '2025-11-28 14:35:30', 445.00, 0.00, 'Paid'),
(6, 1, 1, 1, 'Maria Santos', '+639876543213', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '1-3', '', 'Extra Dry, Liquid Detergent', 'Full Service, Blanket/Bedsheet', '2025-11-27 23:18:42', '', '2025-11-28 14:35:30', 445.00, 0.00, 'Unpaid'),
(7, 1, 1, 1, 'Maria Santos', '+639876543213', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '1-3', '', 'Extra Dry, Liquid Detergent', 'Full Service', '2025-11-27 23:21:56', '', '2025-11-28 14:35:30', 245.00, 0.00, 'Unpaid'),
(8, 1, 1, 1, 'Maria Santos', '+639877665432', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '5-6', '', 'Extra Dry, Liquid Detergent', 'Full Service', '2025-11-27 23:23:02', '', '2025-11-28 14:35:30', 245.00, 0.00, 'Unpaid'),
(9, 1, 1, 1, 'Maria Santos', '+639877665432', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '5-6', '', 'Extra Dry, Liquid Detergent', 'Full Service', '2025-11-27 23:23:07', '', '2025-11-28 14:35:30', 245.00, 0.00, 'Unpaid'),
(10, 1, 1, 1, 'Maria Santos', '+639676051714', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'pickup-only', '2025-11-28', '3-5', '', 'Extra Dry', 'Full Service', '2025-11-28 00:05:23', 'Processing', NULL, 235.00, 0.00, 'Paid');

--
-- Triggers `booking_online`
--
DELIMITER $$
CREATE TRIGGER `after_online_booking_completed` AFTER UPDATE ON `booking_online` FOR EACH ROW BEGIN
    IF NEW.status = 'Completed' AND OLD.status <> 'Completed' THEN
        UPDATE delivery
        SET delivery_status = 'Delivered',
            delivery_date = CURDATE()
        WHERE online_booking_id = NEW.id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_online_booking_insert` AFTER INSERT ON `booking_online` FOR EACH ROW BEGIN
    INSERT INTO payments_online (
        booking_id,
        amount,
        payment_method,
        payment_status,
        payment_date
    )
    VALUES (
        NEW.id,
        NEW.total_amount,
        'GCash',          -- default, pwede mo baguhin
        'Pending',
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_online_booking_insert_tracking` AFTER INSERT ON `booking_online` FOR EACH ROW BEGIN
    INSERT INTO tracking (
        Customer_ID, Schedule_ID, laundry_status, tracking_date
    ) VALUES (
        NEW.customer_id, NEW.schedule_id, 'Scheduled', CURDATE()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_online_booking_ready` AFTER UPDATE ON `booking_online` FOR EACH ROW BEGIN
    IF NEW.status = 'Ready' AND OLD.status <> 'Ready' THEN
        UPDATE delivery
        SET delivery_status = 'Out for Delivery',
            delivery_date = CURDATE()
        WHERE online_booking_id = NEW.id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `issue_description` varchar(255) NOT NULL,
  `status` enum('Pending','Resolved','In Progress') DEFAULT 'Pending',
  `date_reported` datetime DEFAULT current_timestamp(),
  `date_resolved` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `handled_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `complaints`
--
DELIMITER $$
CREATE TRIGGER `after_complaint_insert` AFTER INSERT ON `complaints` FOR EACH ROW BEGIN
  INSERT INTO financial_records (`date`, `description`, `category`, `type`, `amount`)
  VALUES (
    DATE(NEW.date_reported),
    CONCAT('Customer complaint filed ID#', NEW.complaint_id),
    'Other',
    'Expense',
    0.00
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_complaint_update` AFTER UPDATE ON `complaints` FOR EACH ROW BEGIN
    INSERT INTO system_logs (admin_id, action, description)
    VALUES (
        0,
        'Complaint Updated',
        CONCAT('Complaint ID ', NEW.complaint_id, 
               ' status changed from ', OLD.status, 
               ' to ', NEW.status)
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `customer_info`
--

CREATE TABLE `customer_info` (
  `Customer_ID` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `register_date` date DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `account_type` enum('regular','student') NOT NULL DEFAULT 'regular',
  `student_id_path` varchar(255) DEFAULT NULL,
  `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_info`
--

INSERT INTO `customer_info` (`Customer_ID`, `first_name`, `last_name`, `email`, `register_date`, `contact_number`, `Address`, `account_type`, `student_id_path`, `discount_rate`, `created_at`, `updated_at`, `password`) VALUES
(1, 'Maria', 'Santos', 'maria.santos@gmail.com', '2024-12-08', '09171234567', 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:15:41', '$2y$10$kBMcUxoqf.mNFSwyNMXUSu2f.T0vTeHV7X2V.ULzkCPfu6XAI1NKW'),
(2, 'Juan', 'Dela Cruz', 'juan.delacruz@yahoo.com', '2024-12-22', '09281234568', 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'secure456'),
(3, 'Ana', 'Reyes', 'ana.reyes@gmail.com', '2025-01-05', '09351234569', 'Brgy. Aga, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'ana2025'),
(4, 'Pedro', 'Garcia', 'pedro.garcia@hotmail.com', '2025-01-18', '09171234570', 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'pedro@123'),
(5, 'Rosa', 'Bautista', 'rosa.bautista@gmail.com', '2025-01-29', '09281234571', 'Brgy. Calayo, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'rosa_pass'),
(6, 'Jose', 'Gonzales', 'jose.gonzales@yahoo.com', '2025-02-10', '09351234572', 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'jose789'),
(7, 'Luz', 'Torres', 'luz.torres@gmail.com', '2025-02-23', '09171234573', 'Brgy. Papaya, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'luz#2025'),
(8, 'Miguel', 'Ramos', 'miguel.ramos@gmail.com', '2025-03-07', '09281234574', 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'miguel456'),
(9, 'Carmen', 'Fernandez', 'carmen.fernandez@hotmail.com', '2025-03-14', '09351234575', 'Brgy. Looc, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'carmen@pass'),
(10, 'Ricardo', 'Lopez', 'ricardo.lopez@gmail.com', '2025-03-22', '09171234576', 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'rico123'),
(11, 'Elena', 'Villanueva', 'elena.villanueva@yahoo.com', '2025-03-30', '09281234577', 'Brgy. Bulihan, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'elena_2025'),
(12, 'Antonio', 'Cruz', 'antonio.cruz@gmail.com', '2025-04-08', '09351234578', 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'tony@123'),
(13, 'Sofia', 'Mendoza', 'sofia.mendoza@gmail.com', '2025-04-17', '09171234579', 'Brgy. Munting Indang, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'sofia456'),
(14, 'Ramon', 'Santiago', 'ramon.santiago@hotmail.com', '2025-04-25', '09281234580', 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'ramon789'),
(15, 'Teresa', 'Navarro', 'teresa.navarro@gmail.com', '2025-05-03', '09351234581', 'Brgy. Natipuan, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'tere@2025'),
(16, 'Carlos', 'Morales', 'carlos.morales@yahoo.com', '2025-05-12', '09171234582', 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'carlos123'),
(17, 'Linda', 'Castillo', 'linda.castillo@gmail.com', '2025-05-21', '09281234583', 'Brgy. Pantalan, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'linda_pass'),
(18, 'Fernando', 'Aguilar', 'fernando.aguilar@hotmail.com', '2025-05-29', '09351234584', 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'fer@123'),
(19, 'Gloria', 'Herrera', 'gloria.herrera@gmail.com', '2025-06-05', '09171234585', 'Brgy. Bilaran, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'gloria456'),
(20, 'Rodrigo', 'Jimenez', 'rodrigo.jimenez@yahoo.com', '2025-06-14', '09281234586', 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'rodrigo789'),
(21, 'Angelica', 'Valdez', 'angelica.valdez@gmail.com', '2025-06-22', '09351234587', 'Brgy. Kayrilaw, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'angel@2025'),
(22, 'Roberto', 'Medina', 'roberto.medina@hotmail.com', '2025-06-30', '09171234588', 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'robert123'),
(23, 'Beatriz', 'Romero', 'beatriz.romero@gmail.com', '2025-07-08', '09281234589', 'Brgy. Banilad, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'bea_pass'),
(24, 'Enrique', 'Gutierrez', 'enrique.gutierrez@yahoo.com', '2025-07-16', '09351234590', 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'kiko@123'),
(25, 'Rosario', 'Ortiz', 'rosario.ortiz@gmail.com', '2025-07-24', '09171234591', 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'rosie456'),
(26, 'Alfredo', 'Alvarez', 'alfredo.alvarez@hotmail.com', '2025-08-02', '09281234592', 'Brgy. Wawa, Nasugbu, B	atangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'fred789'),
(27, 'Cristina', 'Flores', 'cristina.flores@gmail.com', '2025-08-11', '09351234593', 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'cris@2025'),
(28, 'Eduardo', 'Vargas', 'eduardo.vargas@yahoo.com', '2025-08-19', '09171234594', 'Brgy. Malapad na Bato, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'eddie123'),
(29, 'Patricia', 'Campos', 'patricia.campos@gmail.com', '2025-08-27', '09281234595', 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'patty_pass'),
(30, 'Manuel', 'Diaz', 'manuel.diaz@hotmail.com', '2025-09-05', '09351234596', 'Brgy. Kaylaway, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'manny@123'),
(31, 'Veronica', 'Marquez', 'veronica.marquez@gmail.com', '2025-09-14', '09171234597', 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'veron456'),
(32, 'Ignacio', 'Ramirez', 'ignacio.ramirez@yahoo.com', '2025-09-22', '09281234598', 'Brgy. Latag, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'nacho789'),
(33, 'Cecilia', 'Perez', 'cecilia.perez@gmail.com', '2025-09-28', '09351234599', 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'cecil@2025'),
(34, 'Salvador', 'Rivera', 'salvador.rivera@hotmail.com', '2025-10-02', '09171234600', 'Brgy. Papaya, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'sal123'),
(35, 'Dolores', 'Sanchez', 'dolores.sanchez@gmail.com', '2025-10-04', '09281234601', 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', 'dolor_pass'),
(36, 'Princess', 'Iris Gayos', 'gayosprincessiris@gmail.com', NULL, '9353348987', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$m3RJwPUb3rNoz9VHR/i3X.wcKlFI6934liD.XUDw1VWEQLJUDOt86'),
(38, 'Princess', 'Iris Gayos', 'princessiris@gmail.com', NULL, '9353348987', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$hiZ71KGz8.5VFp3IJbCZqePK7l75fASLm2tLoB.yR8YjhDK46l3ly'),
(40, 'Rey', 'Gayos', 'gayosrey@gmail.com', NULL, '9652817691', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$pm9tfvfN2XsytcWQ6T5w9exV/9uemYI17dv4I8pLFlP5Q2dFzWEhy'),
(45, 'Jennie', 'Kim', 'jennie@gmail.com', NULL, '9652817692', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$Ma8IM/u0DCR/w3cpTDLqUOtyo3x2e2yjrxWdX1ZKH2zOZ19FHTfL2'),
(46, 'Reymarc', 'Aquillano', 'joko@gmail.com', NULL, '9353348922', 'Sitio Bulihan, Barangay Tala, Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$YIzjOsX8IbdvQTFeKqq8SOgmQbmuWfyYsbiyPDq0bmKgH9.B23pci'),
(47, '', '', 'laiza@gmail.com', NULL, '9827726525', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$7/BhfBl4jV9CqKJnkU/m/u0TgBaCU9n6LwSLXHIGloXXfu96dLvMC'),
(51, 'Anniera', 'Gayos', 'ara@gmail.com', NULL, '9827726528', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$W.NL4zoP6LW8EgF.aSAbT.BXTiZqxAp3jaG4hkbVJl7OKNqJQ4HEa'),
(52, 'Daisy', 'Agbulos', '', NULL, '9827726626', 'Barangay 11, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$TOlzSZCP5szVL8HSKroSVeiSe.7bEW9/8XFPbGaaCkHCJYaiH53L.'),
(54, 'Andrea', 'Ogol', 'adrs@gmail.com', NULL, '9876551772', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$StvZ89WitnyI95bbC6X7Kevls/kNaTXjRdKWbfnWdos2/igophCKO'),
(57, 'Von', 'Agbulos', 'von@gmail.com', NULL, '+639876672773', 'Barangay 11, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$CsaLsNiOl.Q1kNLly1ZgyetrkzysuobH6847DYzpgDMq1Y/0NkGq.'),
(58, 'Emma', 'Gayos', 'em@gmail.com', NULL, '+639716255267', 'Sitio Pingkian, Barangay Reparo Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$Cq7DDyTx95hgVvCacvKCye462dfbMeFH3J.dUlNtsHixMSkGdEyvK'),
(59, 'Janelle', 'De Torres', 'jaja@gmail.com', NULL, '+639876555123', 'Sitio Abilo, Barangay Latag Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$/eyfRRIQpr0EY66O7AlSNu4bFFnVCghB1iZvXVDvYLetCdaadBSCm'),
(60, 'Alessandra Mae', 'Perey', 'ale@gmail.com', NULL, '+639625515627', 'Sitio Talisay, Barangay Banilad Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$4gI5rf6xXdv73xdF0cTbiOrNHvY2B1edW6uuSpx8v7QkzaOQ8osTi'),
(61, 'Evina', 'Atie', 'eve@gmail.com', NULL, '+639872663576', 'Barangay 9, Nasugbu, Batangas', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$9Yd23H0j/UJwfTUC79jp3eK6COfqX7zgHgVXeT/0AJQrLJjvIit/a'),
(62, 'Jolo', 'Suanes', 'joloo@gmail.com', NULL, '+639000212377', 'Sitio Bulihan, Barangay Tala Nasugbu, Batangas, Philippines', 'regular', NULL, 0.00, '2025-11-18 17:11:10', '2025-11-18 17:11:10', '$2y$10$MEz.wpCcApm8.EOisBBWBOILmFlrRxXs9omWGP3cw5NeXcgBvBbLa'),
(63, 'Alessandra', 'Perey', 'alessandramaeperey7@gmail.com', NULL, '+639876543211', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'regular', NULL, 0.00, '2025-11-25 03:47:22', '2025-11-25 03:47:22', '$2y$10$guek1Uf68peA6ysEp8EVr.H6UsEY4wv6Nxe4/Z1G63JhJ8CVf5zZm'),
(65, 'Alessandra', 'Perey', 'alessandramaepereyy@gmail.com', NULL, '+639765645434', '4231 Sitio Talisay Banilad Nasugbu Batangas Banilad', 'regular', NULL, 0.00, '2025-11-29 14:00:54', '2025-11-29 14:00:54', '$2y$10$Z3jvCQ2m8wNC3vyNsgUE.OxuTeaevLrqjku8ChOJjYpVzB3235fI2'),
(67, 'Alessandra', 'Perey', 'alessandramaeperey@gmail.com', NULL, '+630917123456', 'fxz', 'regular', NULL, 0.00, '2026-04-19 18:55:12', '2026-04-19 18:55:12', '$2y$10$KjfdTcXzwbll8inceP0qku9ktFcY9qDn.6up4afMOPqiTjEcPcFWy'),
(70, 'Alessandra', 'Perey', 'alessandramaepere@gmail.com', '2026-05-27', '09677051714', 'Reparo Nasugbu Batangas', 'regular', NULL, 0.00, '2026-05-27 23:12:29', '2026-05-27 23:12:29', '$2y$10$333GPztlVXnQfyZ5fPNoPOL80SqxFwKtTkHIuemzT.q7E3CWgYKci'),
(73, 'Alessand', 'Perey', 'alessandramae@gmail.com', '2026-08-10', '09677051714', 'Reparo Nasugbu Batangas', 'regular', NULL, 0.00, '2026-08-10 09:48:51', '2026-08-10 09:48:51', '$2y$10$Ou0B7DAio0F7wILdYbcTEeAEHae9OemfInEtb7dIOvk4xzC1yeuAy'),
(74, 'Alessandra', 'Perey', 'aless@gmail.com', '2026-08-10', '0987654432', 'Barangay 1 Nasugbu Batangas', 'regular', NULL, 0.00, '2026-08-10 09:49:34', '2026-08-10 09:49:34', '$2y$10$Siu81ZsOKTTQjn1vJhH3suPh7CPS8xxknC4DbVIeuMfoAYNynMHRy');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `online_booking_id` int(11) DEFAULT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `delivery_status` enum('Pending','Out for Delivery','Delivered') DEFAULT 'Pending',
  `delivery_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery`
--

INSERT INTO `delivery` (`delivery_id`, `booking_id`, `online_booking_id`, `delivery_address`, `delivery_status`, `delivery_date`) VALUES
(1, 266, NULL, 'Brgy. Wawa, Nasugbu, B	atangas', 'Delivered', '2025-11-07'),
(2, 349, NULL, 'Brgy. Wawa, Nasugbu, B	atangas', 'Delivered', '2025-11-07'),
(3, 413, NULL, 'Brgy. Wawa, Nasugbu, B	atangas', 'Delivered', '2025-11-07'),
(4, 9, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(5, 15, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(6, 23, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(7, 31, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(8, 42, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(9, 54, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(10, 67, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(11, 81, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(12, 100, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(13, 117, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(14, 138, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(15, 160, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(16, 182, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(17, 209, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(18, 237, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(19, 268, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(20, 298, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(21, 330, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(22, 363, NULL, 'Brgy. Aga, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(23, 184, NULL, 'Brgy. Kayrilaw, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(24, 231, NULL, 'Brgy. Kayrilaw, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(25, 292, NULL, 'Brgy. Kayrilaw, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(26, 375, NULL, 'Brgy. Kayrilaw, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(27, 411, NULL, 'Brgy. Kayrilaw, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(28, 75, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(29, 84, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(30, 92, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(31, 102, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(32, 111, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(33, 120, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(34, 131, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(35, 141, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(36, 152, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(37, 163, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(38, 175, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(39, 186, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(40, 201, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(41, 212, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(42, 227, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(43, 241, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(44, 256, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(45, 271, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(46, 286, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(47, 302, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(48, 317, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(49, 334, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(50, 352, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(51, 370, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(52, 387, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(53, 404, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(54, 213, NULL, 'Brgy. Banilad, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(55, 367, NULL, 'Brgy. Banilad, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(56, 119, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(57, 130, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(58, 140, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(59, 150, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(60, 162, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(61, 174, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(62, 185, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(63, 197, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(64, 211, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(65, 225, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(66, 240, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(67, 255, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(68, 270, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(69, 284, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(70, 300, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(71, 315, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(72, 332, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(73, 351, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(74, 368, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(75, 385, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(76, 403, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(77, 50, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(78, 83, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(79, 121, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(80, 165, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(81, 226, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(82, 289, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(83, 369, NULL, 'Brgy. Looc, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(84, 401, NULL, 'Purok 7, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(85, 285, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(86, 301, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(87, 316, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(88, 333, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(89, 336, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(90, 356, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(91, 372, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(92, 391, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(93, 418, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(94, 303, NULL, 'Brgy. Malapad na Bato, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(95, 409, NULL, 'Brgy. Malapad na Bato, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(96, 68, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(97, 82, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(98, 101, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(99, 118, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(100, 139, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(101, 161, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(102, 183, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(103, 210, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(104, 238, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(105, 269, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(106, 299, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(107, 331, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(108, 364, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(109, 400, NULL, 'Brgy. Bulihan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(110, 229, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(111, 258, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(112, 288, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(113, 319, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(114, 354, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(115, 389, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(116, 405, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(117, 142, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(118, 195, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(119, 252, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(120, 328, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(121, 399, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(122, 153, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(123, 164, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(124, 176, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(125, 187, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(126, 200, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(127, 214, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(128, 228, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(129, 242, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(130, 257, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(131, 272, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(132, 287, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(133, 304, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(134, 318, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(135, 335, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(136, 353, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(137, 371, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(138, 388, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(139, 406, NULL, 'Brgy. Bilaran, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(140, 386, NULL, 'Brgy. Latag, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(141, 412, NULL, 'Brgy. Latag, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(142, 27, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(143, 36, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(144, 48, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(145, 60, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(146, 74, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(147, 91, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(148, 110, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(149, 129, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(150, 151, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(151, 173, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(152, 198, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(153, 224, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(154, 254, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(155, 283, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(156, 314, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(157, 350, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(158, 384, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(159, 4, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(160, 6, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(161, 8, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(162, 11, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(163, 14, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(164, 18, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(165, 22, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(166, 26, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(167, 30, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(168, 35, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(169, 41, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(170, 47, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(171, 53, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(172, 59, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(173, 66, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(174, 73, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(175, 80, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(176, 90, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(177, 99, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(178, 109, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(179, 116, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(180, 128, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(181, 137, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(182, 149, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(183, 159, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(184, 172, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(185, 181, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(186, 196, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(187, 208, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(188, 223, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(189, 236, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(190, 253, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(191, 267, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(192, 281, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(193, 297, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(194, 313, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(195, 329, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(196, 348, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(197, 132, NULL, 'Brgy. Pantalan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(198, 215, NULL, 'Brgy. Pantalan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(199, 340, NULL, 'Brgy. Pantalan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(200, 34, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(201, 38, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(202, 43, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(203, 49, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(204, 55, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(205, 61, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(206, 69, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(207, 76, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(208, 86, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(209, 93, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(210, 103, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(211, 112, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(212, 123, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(213, 133, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(214, 143, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(215, 154, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(216, 166, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(217, 177, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(218, 188, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(219, 202, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(220, 216, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(221, 230, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(222, 244, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(223, 259, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(224, 273, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(225, 290, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(226, 305, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(227, 321, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(228, 337, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(229, 357, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(230, 373, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(231, 392, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(232, 341, NULL, 'Brgy. Kaylaway, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(233, 365, NULL, 'Brgy. Kaylaway, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(234, 383, NULL, 'Brgy. Kaylaway, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(235, 402, NULL, 'Brgy. Kaylaway, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(236, 416, NULL, 'Brgy. Kaylaway, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(237, 1, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(238, 2, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(239, 3, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(240, 5, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(241, 7, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(242, 10, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(243, 13, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(244, 16, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(245, 21, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(246, 24, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(247, 29, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(248, 33, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(249, 40, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(250, 46, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(251, 52, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(252, 58, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(253, 65, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(254, 72, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(255, 79, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(256, 89, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(257, 98, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(258, 107, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(259, 115, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(260, 126, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(261, 136, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(262, 147, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(263, 158, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(264, 169, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(265, 180, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(266, 192, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(267, 206, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(268, 219, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(269, 234, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(270, 248, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(271, 264, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(272, 277, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(273, 295, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(274, 309, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(275, 326, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(276, 344, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(277, 361, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(278, 378, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(279, 397, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(280, 414, NULL, 'Purok 1, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(281, 44, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(282, 122, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(283, 282, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(284, 320, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(285, 355, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(286, 390, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(287, 407, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(288, 12, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(289, 17, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(290, 20, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(291, 25, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(292, 28, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(293, 32, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(294, 39, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(295, 45, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(296, 51, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(297, 57, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(298, 64, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(299, 71, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(300, 78, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(301, 88, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(302, 97, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(303, 106, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(304, 114, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(305, 125, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(306, 135, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(307, 146, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(308, 157, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(309, 168, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(310, 179, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(311, 191, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(312, 205, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(313, 218, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(314, 233, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(315, 247, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(316, 263, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(317, 276, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(318, 294, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(319, 308, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(320, 325, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(321, 343, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(322, 360, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(323, 377, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(324, 396, NULL, 'Sitio Bayabasan, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(325, 95, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(326, 104, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(327, 155, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(328, 203, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(329, 261, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(330, 339, NULL, 'Purok 6, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(331, 56, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(332, 63, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(333, 70, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(334, 77, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(335, 87, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(336, 96, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(337, 105, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(338, 113, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(339, 124, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(340, 134, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(341, 145, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(342, 156, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(343, 167, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(344, 178, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(345, 190, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(346, 204, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(347, 217, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(348, 232, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(349, 246, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(350, 262, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(351, 275, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(352, 293, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(353, 307, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(354, 324, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(355, 342, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(356, 359, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(357, 376, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(358, 395, NULL, 'Purok 5, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(359, 199, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(360, 207, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(361, 222, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(362, 235, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(363, 251, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(364, 265, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(365, 280, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(366, 296, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(367, 312, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(368, 327, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(369, 347, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(370, 362, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(371, 381, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(372, 398, NULL, 'Purok 3, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(373, 170, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(374, 193, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(375, 220, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(376, 249, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(377, 278, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(378, 310, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(379, 345, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(380, 379, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(381, 415, NULL, 'Purok 2, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(382, 19, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(383, 37, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(384, 62, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(385, 94, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(386, 144, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(387, 189, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(388, 245, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(389, 323, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(390, 394, NULL, 'Brgy. Calayo, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(391, 243, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(392, 260, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(393, 274, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(394, 291, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(395, 306, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(396, 322, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(397, 338, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(398, 358, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(399, 374, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(400, 393, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(401, 410, NULL, 'Purok 4, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(402, 408, NULL, 'Brgy. Papaya, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(403, 85, NULL, 'Brgy. Munting Indang, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(404, 239, NULL, 'Brgy. Munting Indang, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(405, 108, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(406, 127, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(407, 148, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(408, 171, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(409, 194, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(410, 221, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(411, 250, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(412, 279, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(413, 311, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(414, 346, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(415, 380, NULL, 'Brgy. Natipuan, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(416, 366, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(417, 382, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07'),
(418, 417, NULL, 'Sitio Tulay, Brgy. Bucana, Nasugbu, Batangas', 'Delivered', '2025-11-07');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `contact` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `employees`
--
DELIMITER $$
CREATE TRIGGER `after_employee_insert` AFTER INSERT ON `employees` FOR EACH ROW BEGIN
  INSERT INTO employee_salaries (employee_id, days_worked, total_salary)
  VALUES (NEW.id, 0, 0.00);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `employee_salaries`
--

CREATE TABLE `employee_salaries` (
  `salary_id` int(11) NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `days_worked` int(11) NOT NULL,
  `total_salary` decimal(10,2) NOT NULL,
  `salary_date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `employee_salaries`
--
DELIMITER $$
CREATE TRIGGER `after_employee_salary_update` AFTER UPDATE ON `employee_salaries` FOR EACH ROW BEGIN
    INSERT INTO system_logs (admin_id, action, description)
    VALUES (
        0,
        'Employee Salary Updated',
        CONCAT('Salary updated for Employee ID ', NEW.employee_id,
               ': Days Worked ', OLD.days_worked, ' → ', NEW.days_worked,
               ', Salary ', OLD.total_salary, ' → ', NEW.total_salary)
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('Working','Under Maintenance','Sira') DEFAULT 'Working',
  `notes` text DEFAULT NULL,
  `last_checked` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `status`, `notes`, `last_checked`, `updated_at`, `created_at`) VALUES
(1, 'Washing Machine', 'Working', 'Heavy-duty, 15kg capacity', NULL, '2026-04-09 05:58:24', '2026-04-09 05:58:24'),
(2, 'Dryer', 'Working', 'Gas-powered dryer', NULL, '2026-04-09 05:59:55', '2026-04-09 05:58:24'),
(3, 'Plantsa (Iron)', 'Working', 'Industrial iron', NULL, '2026-04-09 05:58:24', '2026-04-09 05:58:24'),
(4, 'Timbangan (Weighing scale)', 'Working', 'Digital scale, max 50kg', NULL, '2026-04-09 05:58:24', '2026-04-09 05:58:24'),
(5, 'Laundry baskets', 'Working', 'Plastic baskets, set of 10', NULL, '2026-04-09 05:58:24', '2026-04-09 05:58:24'),
(6, 'Tables / racks', 'Working', 'Folding tables and drying racks', NULL, '2026-04-09 05:58:24', '2026-04-09 05:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `expense_inventory`
--

CREATE TABLE `expense_inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'pcs',
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `date_added` date NOT NULL DEFAULT curdate(),
  `remarks` varchar(255) DEFAULT NULL,
  `financial_record_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `expense_inventory`
--
DELIMITER $$
CREATE TRIGGER `after_expense_insert` AFTER INSERT ON `expense_inventory` FOR EACH ROW BEGIN
  INSERT INTO financial_records (date, description, category, type, amount)
  VALUES (NEW.date_added, NEW.item_name, 'Supplies', 'Expense', NEW.total_cost);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_expense_inventory_update` BEFORE UPDATE ON `expense_inventory` FOR EACH ROW BEGIN
  DECLARE action_type ENUM('Added','Updated','Deducted');

  IF NEW.quantity > OLD.quantity THEN
    SET action_type = 'Added';
  ELSEIF NEW.quantity < OLD.quantity THEN
    SET action_type = 'Deducted';
  ELSE
    SET action_type = 'Updated';
  END IF;

  INSERT INTO inventory_logs (
    inventory_type, item_name, action, quantity, previous_quantity, new_quantity, remarks
  )
  VALUES (
    'expense',
    NEW.item_name,
    action_type,
    ABS(NEW.quantity - OLD.quantity),
    OLD.quantity,
    NEW.quantity,
    CONCAT('Expense item ', action_type)
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `rating` int(1) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `response` text DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `booking_id`, `rating`, `comment`, `created_at`, `response`, `admin_response`, `responded_at`) VALUES
(1, 59, 266, 5, 'Sobrang linis ng damit, very fresh amoy!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(2, 22, 349, 5, 'Neat folding at walang gusot, ang galing!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(3, 31, 413, 5, 'Soft at smooth ang tela pagkatapos labhan.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(4, 61, 9, 5, 'Fresh scent na hindi matapang, perfect!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(5, 45, 15, 5, 'Ang bango lalo ng laba ninyo!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(6, 57, 23, 5, 'Sobrang lambot ng damit, parang bago ulit.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(7, 35, 31, 5, 'Malinis at maayos pagkakalaba, salamat!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(8, 26, 42, 5, 'Ang sarap ng amoy, hindi nakakahilo.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(9, 29, 54, 5, 'Fresh na fresh ang amoy, solid!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(10, 1, 67, 5, 'Napakaganda ng fold, ang linis tingnan!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(11, 9, 81, 5, 'Perfect yung pagka-press!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(12, 21, 100, 5, 'Pulido ang gawa, as always!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(13, 12, 117, 5, 'Hindi nag-amoy kulob, good job!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(14, 62, 138, 5, 'Very satisfied sa quality!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(15, 8, 160, 5, 'Super neat ang pagkakalinis, very satisfied!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(16, 20, 182, 5, 'Soft fabric at walang amoy, ang sarap isuot.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(17, 13, 209, 5, 'Consistent quality, lagi akong happy sa service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(18, 46, 237, 5, 'Ang ganda ng folding, parang boutique-style.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(19, 10, 268, 5, 'Sobrang bango ng clothes, long-lasting scent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(20, 32, 298, 5, 'Pulidong pressing, crisp at maayos tingnan.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(21, 14, 330, 5, 'Fresh at malinis ang outcome, super good.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(22, 16, 363, 5, 'Very gentle sa fabric, no damages at all.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(23, 38, 184, 5, 'Smooth service at mabilis ibalik ang items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(24, 30, 231, 5, 'Ang ayos ng packing, ready-to-store agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(25, 51, 292, 5, 'Super linis lalo na sa white clothes, impressive.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(26, 40, 375, 5, 'Laging soft at hindi naninigas ang fabric.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(27, 23, 411, 5, 'Mabango pero hindi matapang—perfect balance.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(28, 52, 75, 5, 'Crisp folding at organized lahat ng items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(29, 18, 84, 5, 'Quality wash, walang mantsa at sobrang linis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(30, 24, 92, 5, 'Very reliable service, hindi ako nabibigo.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(31, 19, 102, 5, 'Soft towels and sheets, parang hotel-quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(32, 10, 111, 5, 'Fast turnaround time, sobrang convenient.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(33, 36, 120, 5, 'Super gentle kahit sa delicate fabrics.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(34, 11, 131, 5, 'Fresh scent all day, hindi agad nawawala.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(35, 29, 141, 5, 'Maayos ang arrangement ng clothes—very neat.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(36, 15, 152, 5, 'Smooth pressing, walang gusot kahit manipis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(37, 19, 163, 5, 'Very clean wash, parang brand new ulit.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(38, 25, 175, 5, 'Ang ganda ng outcome, sobrang pulido.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(39, 21, 186, 5, 'Professional at clean service, highly recommended.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(40, 47, 201, 5, 'Soft fabrics, no harsh smell—ang sarap isuot.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(41, 34, 212, 5, 'Organized folding, very satisfying makita.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(42, 28, 227, 5, 'Fresh ang amoy kahit after ilang araw.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(43, 13, 241, 5, 'Consistent quality every time, love it.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(44, 17, 256, 5, 'Smooth fabric at walang lint, sobrang neat.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(45, 33, 271, 5, 'Fast delivery at sobrang linis ng laundry.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(46, 58, 286, 5, 'Excellent folding style, parang boutique.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(47, 54, 302, 5, 'Fresh scent and soft feel, very comfy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(48, 3, 317, 5, 'Neat and clean results, hassle-free service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(49, 27, 334, 5, 'Very professional handling, walang damage.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(50, 60, 352, 5, 'Ang bango ng damit kahit whole day suot.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(51, 12, 370, 5, 'Soft and fresh clothes—super satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(52, 32, 387, 5, 'Amazing quality, laging maayos ang wash.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(53, 31, 404, 5, 'Neat folding, perfect arrangement lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(54, 21, 213, 5, 'Fresh, clean, at sobrang soft—love it!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(55, 13, 367, 5, 'Fast service, hindi nakakabalam sa schedule.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(56, 30, 119, 5, 'Very clean outcome, walang stains kahit isa.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(57, 38, 130, 5, 'Soft towels and blankets—super lambot.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(58, 11, 140, 5, 'Consistent scent and softness every visit.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(59, 58, 150, 5, 'Neat folding and organized packing, solid!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(60, 52, 162, 5, 'Clean and fresh clothes, ready-to-wear agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(61, 59, 174, 5, 'Ang bango ng outcome, hindi nakakasawa.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(62, 27, 185, 5, 'Smooth fabric and soft feel—love the result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(63, 45, 197, 5, 'Professional folding, napaka-ayos tingnan.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(64, 16, 211, 5, 'Clean wash, very effective sa stains.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(65, 10, 225, 5, 'Fast at reliable, sobrang convenient gamitin.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(66, 61, 240, 5, 'Fresh scent, super soft clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(67, 26, 255, 5, 'Neat at detailed folding, impressive output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(68, 22, 270, 5, 'Consistent cleanliness every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(69, 40, 284, 5, 'Soft fabric, no damage—quality wash.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(70, 47, 300, 5, 'Organized packing at sobrang linis ng items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(71, 40, 315, 5, 'Fresh clothes na long-lasting ang scent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(72, 36, 332, 5, 'Crisp pressing, ang ganda ng form ng clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(73, 61, 351, 5, 'Very gentle sa delicate fabrics, no issues.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(74, 34, 368, 5, 'Clean and soft clothes—very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(75, 8, 385, 5, 'Fast service at very friendly staff.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(76, 54, 403, 5, 'Soft towels and neat wash—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(77, 17, 50, 5, 'Consistent freshness every wash.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(78, 60, 83, 5, 'Fresh scent and neat folding, solid service!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(79, 29, 121, 5, 'Clean and crisp pressing, love the quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(80, 24, 165, 5, 'Soft fabrics and great smell—perfect combo.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(81, 19, 226, 5, 'Fresh and smooth clothes—excellent work.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(82, 23, 289, 5, 'Neat and clean finish lagi, thank you!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(83, 57, 369, 5, 'Soft at fluffy ang towels after wash.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(84, 46, 401, 5, 'Consistent quality, reliable service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(85, 35, 285, 5, 'Organized and presentable ang laundry output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(86, 14, 301, 5, 'Fresh clothes na sobrang soft—love it!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(87, 60, 316, 5, 'Clean wash and neat folding, perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(88, 20, 333, 5, 'Fast, smooth, and very convenient.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(89, 20, 336, 5, 'Soft fabrics, gentle wash—high quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(90, 9, 356, 5, 'Crisp and neat clothes after pressing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(91, 25, 372, 5, 'Fresh scent that lasts the whole day.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(92, 62, 391, 5, 'Soft sheets and towels—super relaxing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(93, 27, 418, 5, 'Clean and organized packing—very neat.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(94, 15, 303, 5, 'Very fresh clothes, parang new ulit.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(95, 28, 409, 5, 'Smooth, soft, and clean fabrics—excellent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(96, 18, 68, 5, 'Neat folding, perfect alignment lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(97, 28, 82, 5, 'Consistent washing quality every week.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(98, 33, 101, 5, 'Soft fabric and great scent—very good service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(99, 51, 118, 5, 'Clean clothes na walang leftover dirt.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(100, 22, 139, 5, 'Fresh, neat, and clean laundry—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(101, 12, 161, 5, 'Lambot ng fabric, parang bago bili.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(102, 32, 183, 5, 'Ang ganda ng press, crisp at malinis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(103, 31, 210, 5, 'Consistent at gentle sa delicate items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(104, 21, 238, 5, 'Clean and fresh smell—very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(105, 13, 269, 5, 'Neat folding, super organized items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(106, 30, 299, 5, 'Soft sheets at fresh towels—hotel feel.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(107, 38, 331, 5, 'Fast turnaround, super convenient service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(108, 11, 364, 5, 'Fresh scent at very clean wash—love it.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(109, 58, 400, 5, 'Soft fabric and neat fold—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(110, 52, 229, 5, 'Organized, clean, at very fresh outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(111, 59, 258, 5, 'Clean wash, super bright ng whites.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(112, 27, 288, 5, 'Neat folding, parang boutique display.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(113, 45, 319, 5, 'Soft, smooth, and fresh—excellent quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(114, 16, 354, 5, 'Fresh and clean laundry—very impressive.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(115, 10, 389, 5, 'Crisp press at neat fold always.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(116, 61, 405, 5, 'Soft fabrics, light scent—perfect combo.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(117, 26, 142, 5, 'Clean wash na walang kahit konting dumi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(118, 22, 195, 5, 'Neatly arranged clothes—ang sarap tingnan.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(119, 40, 252, 5, 'Soft linens at fresh towels—so good!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(120, 47, 328, 5, 'Consistently fresh smell lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(121, 2, 399, 5, 'Soft outcome and neat folding.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(122, 36, 153, 5, 'Clean and fresh at hindi malansang amoy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(123, 47, 164, 5, 'Organized packing, ready-to-store agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(124, 34, 176, 5, 'Fresh fabrics at sobrang soft feel.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(125, 8, 187, 5, 'Crisp pressing, linis tingnan ng garments.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(126, 54, 200, 5, 'Very clean wash at sobrang bango.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(127, 17, 214, 5, 'Soft and smooth fabrics—great service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(128, 7, 228, 5, 'Neat fold at organized clothes—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(129, 29, 242, 5, 'Fresh scent, hindi overpowering.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(130, 24, 257, 5, 'Clean sa lahat ng sulok—pulido trabaho.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(131, 19, 272, 5, 'Soft towels, fresh sheets—excellent!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(132, 23, 287, 5, 'Fast service at consistent quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(133, 57, 304, 5, 'Fresh, neat, and ready-to-wear clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(134, 46, 318, 5, 'Clean and soft fabrics—very relaxing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(135, 35, 335, 5, 'Organized folding, sobrang neat.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(136, 14, 353, 5, 'Fresh scent at soft texture—love it.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(137, 60, 371, 5, 'Very clean wash, walang residue.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(138, 20, 388, 5, 'Neat at crisp result lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(139, 32, 406, 5, 'Soft and fluffy towels—perfect quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(140, 9, 386, 5, 'Clean and bright fabrics—very good output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(141, 25, 412, 5, 'Fresh smell na hindi nakakasawa.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(142, 62, 27, 5, 'Soft linens at smooth fabrics—high quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(143, 59, 36, 5, 'Neat and organized wash, sobrang linis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(144, 15, 48, 5, 'Consistent freshness, very good results.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(145, 4, 60, 5, 'Smooth fabrics, clean wash—very nice.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(146, 18, 74, 5, 'Organized packing at crisp folds.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(147, 28, 91, 5, 'Fresh and clean laundry—super happy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(148, 33, 110, 5, 'Soft fabric, neat pressing—great job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(149, 51, 129, 5, 'Clean wash at tamang press—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(150, 38, 151, 5, 'Neat folding at soft outcome—love it.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(151, 12, 173, 5, 'Very clean linens, sobrang relaxing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(152, 32, 198, 5, 'Fresh scent at crisp fold—excellent work.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(153, 31, 224, 5, 'Soft fabrics, clean wash, very satisfying.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(154, 21, 254, 5, 'Organized folding and fresh clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(155, 13, 283, 5, 'Clean at sobrang neat ang outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(156, 30, 314, 5, 'Soft towels at fresh linens—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(157, 38, 350, 5, 'Consistently soft and fresh ang clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(158, 11, 384, 5, 'Neat fold, walang gusot—super linis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(159, 58, 4, 5, 'Clean wash at sobrang bright ng colors.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(160, 52, 6, 5, 'Fresh scent and soft fabric—nice result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(161, 59, 8, 5, 'Soft sheets, crisp press—hotel quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(162, 27, 11, 5, 'Neat and organized laundry—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(163, 45, 14, 5, 'Clean, fresh wash, walang kahit anong amoy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(164, 16, 18, 5, 'Soft and neat, sobrang pulido.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(165, 10, 22, 5, 'Fresh scent and smooth texture.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(166, 61, 26, 5, 'Organized packing, clean folding.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(167, 26, 30, 5, 'Clean wash with great scent—love it.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(168, 22, 35, 5, 'Soft linens, neat finish—very good.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(169, 40, 41, 5, 'Fresh wash, soft fabrics, amazing quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(170, 47, 47, 5, 'Neat folding, organized arrangement.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(171, 16, 53, 5, 'Clean wash and soft fabric—excellent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(172, 36, 59, 5, 'Soft towels, crisp pressing—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(173, 57, 66, 5, 'Fresh at gentle wash—very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(174, 34, 73, 5, 'Clean laundry, neat folding—consistent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(175, 8, 80, 5, 'Soft fabrics, bright colors—great job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(176, 54, 90, 5, 'Fresh scent at soft touch—perfect combo.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(177, 17, 99, 5, 'Clean wash, well-handled fabrics.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(178, 7, 109, 5, 'Neat folding and clean smell—very nice.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(179, 29, 116, 5, 'Soft linens, smooth texture—lovely.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(180, 24, 128, 5, 'Fresh wash, crisp press—excellent output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(181, 19, 137, 5, 'Clean laundry at soft outcome—satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(182, 23, 149, 5, 'Neat fold and clean scent—very organized.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(183, 57, 159, 5, 'Soft fabrics, clean wash—very good.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(184, 46, 172, 5, 'Fresh scent that lasts—great result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(185, 35, 181, 5, 'Clean wash, gentle sa tela—good job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(186, 14, 196, 5, 'Neat pressing at organized folding.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(187, 60, 208, 5, 'Soft linens and fresh scent—excellent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(188, 20, 223, 5, 'Clean wash na mataas ang quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(189, 26, 236, 5, 'Fresh scent, neat arrangement—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(190, 9, 253, 5, 'Soft towels, smooth fabrics—very impressed.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(191, 25, 267, 5, 'Clean pressing and neat folding always.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(192, 62, 281, 5, 'Fresh and clean finish—super ayos.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(193, 5, 297, 5, 'Soft fabrics, gentle wash—love the outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(194, 15, 313, 5, 'Neat folding and crisp lines—excellent work.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(195, 45, 329, 5, 'Clean clothes, perfect scent—very good.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(196, 18, 348, 5, 'Soft linens, fresh wash—high quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(197, 28, 132, 5, 'Neat fold, malinis lahat ng edges.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(198, 33, 215, 5, 'Fresh wash at smooth texture—very nice.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(199, 51, 340, 5, 'Clean wash with soft feel—solid service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(200, 18, 34, 5, 'Soft towels, neat sheets—excellent laundry.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(201, 12, 38, 5, 'Fresh scent, organized folding—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(202, 32, 43, 5, 'Clean wash at crisp pressing—amazing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(203, 31, 49, 5, 'Soft and smooth clothes—very happy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(204, 21, 55, 5, 'Neat fold, fresh smell—great result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(205, 13, 61, 5, 'Clean fabrics, gentle wash—excellent job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(206, 30, 69, 5, 'Soft linens, light scent—sobrang relaxing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(207, 38, 76, 5, 'Fresh and neat laundry—good output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(208, 11, 86, 5, 'Clean wash, good scent—great quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(209, 58, 93, 5, 'Neat folding, clean outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(210, 52, 103, 5, 'Soft fabrics at fresh scent—very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(211, 59, 112, 5, 'Clean wash with neat folding—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(212, 27, 123, 5, 'Soft linens and fresh towels—very comfy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(213, 45, 133, 5, 'Fresh and clean finish—high quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(214, 16, 143, 5, 'Clean, crisp pressing—professional output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(215, 10, 154, 5, 'Soft and fresh clothes—very impressive.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(216, 61, 166, 5, 'Neat folding at sobrang organized result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(217, 26, 177, 5, 'Clean wash and soft texture—excellent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(218, 22, 188, 5, 'Fresh scent at neat finish—super good.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(219, 40, 202, 5, 'Soft fabrics, gentle care—high-quality wash.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(220, 47, 216, 5, 'Clean wash, smooth folds—love it.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(221, 35, 230, 5, 'Fresh linens, soft towels—great laundry service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(222, 36, 244, 5, 'Clean pressing and organized folding—solid output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(223, 11, 259, 5, 'Soft and fresh fabric—amazing quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(224, 34, 273, 5, 'Fresh scent, clean wash—very satisfying.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(225, 8, 290, 5, 'Neat arrangement and soft fabric—perfect.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(226, 54, 305, 5, 'Clean wash, consistent softness—great job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(227, 17, 321, 5, 'Fresh and neat laundry—excellent service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(228, 7, 337, 5, 'Soft sheets at crisp press—super linis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(229, 29, 357, 5, 'Clean fabric, soft texture—super nice.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(230, 24, 373, 5, 'Fresh smell and neat folding—love the results.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(231, 19, 392, 5, 'Soft towels, clean wash—great quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(232, 23, 341, 5, 'Clean and crisp clothes—very organized.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(233, 57, 365, 5, 'Fresh scent at smooth fabric—nice feel.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(234, 46, 383, 5, 'Soft fabric and neat folds—great laundry.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(235, 35, 402, 5, 'Clean wash na walang stains—excellent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(236, 14, 416, 5, 'Neat pressing and organized packing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(237, 60, 1, 5, 'Soft linens and fresh wash—great job.', '2025-11-07 00:31:48', NULL, 'Thank you!', '2025-11-09 15:39:55'),
(238, 20, 2, 5, 'Clean and fresh laundry—sobrang ayos.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(239, 7, 3, 5, 'Soft and light scent—very pleasant.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(240, 9, 5, 5, 'Fresh scent at neat results—love the service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(241, 25, 7, 5, 'Clean wash, smooth fabrics—excellent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(242, 62, 10, 5, 'Soft towels and fresh clothes—high quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(243, 14, 13, 5, 'Clean fabric, neat fold—very organized.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(244, 15, 16, 5, 'Fresh scent and soft texture—great outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(245, 31, 21, 5, 'Soft linens, clean scent—very relaxing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(246, 18, 24, 5, 'Clean wash at neat fold—perfect result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(247, 28, 29, 5, 'Soft shirts, fresh smell—super good.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(248, 33, 33, 5, 'Clean towels and crisp sheets—solid quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(249, 51, 40, 5, 'Fresh, soft, and neatly folded clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(250, 46, 46, 5, 'Clean wash and soft feel—very impressive.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(251, 12, 52, 5, 'Super linis at bango ng clothes, very satisfied!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(252, 32, 58, 5, 'Ang ganda ng folding, organized at ready-to-store agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(253, 31, 65, 5, 'Very smooth fabrics after washing, love it!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(254, 21, 72, 5, 'Consistent quality sa bawat visit, highly recommended.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(255, 13, 79, 5, 'Fresh scent all day, hindi matapang pero effective.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(256, 30, 89, 5, 'Soft and fluffy towels, sobrang comfy gamitin.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(257, 38, 98, 5, 'Perfect cleaning, walang stains o leftover marks.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(258, 11, 107, 5, 'Fast and convenient service, very happy!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(259, 58, 115, 5, 'Excellent folding and neat packing, professional service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(260, 52, 126, 5, 'Ang linis at amoy fresh pa rin kahit ilang araw.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(261, 59, 136, 5, 'Very gentle sa fabric, walang damage or shrinkage.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(262, 27, 147, 5, 'Perfect pressing, parang hotel-quality ang output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(263, 45, 158, 5, 'Reliable service, laging on time at consistent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(264, 16, 169, 5, 'Soft, comfortable, at fresh ang clothes every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(265, 10, 180, 5, 'Super neat folding, ready-to-wear agad ang garments.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(266, 61, 192, 5, 'Clothes feel refreshed and renewed after washing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(267, 26, 206, 5, 'Ang bango at linis, parang bagong bili clothes!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(268, 22, 219, 5, 'Fast, efficient, and professional service, very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(269, 40, 234, 5, 'Consistent high-quality washing, walang fail.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(270, 47, 248, 5, 'Soft, fluffy, and clean—ang sarap isuot!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(271, 58, 264, 5, 'Very clean and organized, ready-to-store agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(272, 36, 277, 5, 'Impressive stain removal, very satisfied!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(273, 30, 295, 5, 'Professional handling ng delicate fabrics, no damage.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(274, 34, 309, 5, 'Consistently fresh and soft clothes—thank you!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(275, 8, 326, 5, 'Neat folding, crisp pressing, perfect result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(276, 54, 344, 5, 'Soft towels, clean bedsheets, sobrang saya gamitin.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(277, 17, 361, 5, 'Quick and hassle-free service, very convenient.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(278, 7, 378, 5, 'Amazing results—fresh scent, soft fabric, very satisfied!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(279, 29, 397, 5, 'Very reliable service, consistent sa linis at quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(280, 24, 414, 5, 'Ang galing ng service, always satisfied sa outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(281, 19, 44, 5, 'Soft fabrics and fresh scent, sobrang happy ako!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(282, 23, 122, 5, 'Perfect folding and neat packing, very organized.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(283, 57, 282, 5, 'Fast, efficient, and professional service—thank you!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(284, 46, 320, 5, 'Clothes feel soft, fresh, and clean every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(285, 35, 355, 5, 'Very gentle sa fabric, walang pinsala or shrinkage.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(286, 14, 390, 5, 'Top-notch service, highly recommend to everyone.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(287, 60, 407, 5, 'Amazing folding, crisp pressing, very neat!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(288, 20, 12, 5, 'Soft and fluffy towels, sobrang comfortable gamitin.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(289, 25, 17, 5, 'Consistent quality at freshness, sobrang satisfied!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(290, 9, 20, 5, 'Ang linis at bango ng clothes—perfect every time!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(291, 25, 25, 5, 'Very clean, soft, and freshly washed clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(292, 62, 28, 5, 'Excellent service, organized and professional.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(293, 12, 32, 5, 'Fast and convenient, hassle-free experience.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(294, 15, 39, 5, 'Consistently high-quality washing and folding.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(295, 54, 45, 5, 'Ang bango ng clothes, hindi overpowering pero fresh.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(296, 18, 51, 5, 'Soft and comfortable fabrics every time, love it!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(297, 28, 57, 5, 'Neat and professional handling of all items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(298, 33, 64, 5, 'Consistently fresh, clean, at ready-to-wear clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(299, 51, 71, 5, 'Soft towels, crisp sheets, very satisfied with service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(300, 8, 78, 5, 'Ang sarap ng outcome, fresh scent, soft fabric, very happy!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(301, 12, 88, 5, 'Super linis ng clothes, sobrang bango at fresh!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(302, 32, 97, 5, 'Ang ganda ng folding, very neat at organized.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(303, 31, 106, 5, 'Soft fabrics, walang gasgas or damage—great job!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(304, 21, 114, 5, 'Consistent good quality washing every visit.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(305, 13, 125, 5, 'Fresh scent na tumatagal, hindi nakakasawa.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(306, 30, 135, 5, 'Fluffy towels and soft sheets, perfect quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(307, 38, 146, 5, 'Clean wash, no stains, sobrang pulido result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(308, 11, 157, 5, 'Fast and smooth service, hassle-free lahat.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(309, 58, 168, 5, 'Neat packing and excellent folding every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(310, 52, 179, 5, 'Ang fresh pa rin ng clothes after several days.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(311, 59, 191, 5, 'Very gentle handling on fabrics, no shrinkage.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(312, 27, 205, 5, 'Perfect pressing, ang crisp ng damit.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(313, 45, 218, 5, 'Reliable laundry service, laging consistent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(314, 16, 233, 5, 'Soft, clean, and fresh—exactly what I want.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(315, 10, 247, 5, 'Neat folding, parang store-quality ang ayos.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(316, 61, 263, 5, 'Clothes feel brand new after washing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(317, 26, 276, 5, 'Ang bango, ang linis—quality service lagi!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(318, 22, 294, 5, 'Fast, professional, and very efficient service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(319, 40, 308, 5, 'High-quality washing, walang amoy at super linis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(320, 47, 325, 5, 'Soft clothes and fresh scent, solid quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(321, 24, 343, 5, 'Very organized and clean folding every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(322, 36, 360, 5, 'Impressive stain removal, super satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(323, 52, 377, 5, 'Careful sa delicate fabrics, no damage at all.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(324, 34, 396, 5, 'Consistently soft, clean, and fresh clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(325, 8, 95, 5, 'Crisp pressing and neat folding, perfect output.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(326, 54, 104, 5, 'Soft towels and clean sheets, hotel-like feel.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(327, 17, 155, 5, 'Quick service, very convenient and easy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(328, 7, 203, 5, 'Amazing results—soft, fresh, and clean items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(329, 29, 261, 5, 'Very reliable service at high-quality lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(330, 24, 339, 5, 'Excellent service, laging satisfying ang result.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(331, 19, 56, 5, 'Soft fabrics, fresh scent—very happy with service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(332, 23, 63, 5, 'Perfect folding and clean packing, very neat.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(333, 57, 70, 5, 'Fast, efficient, and organized—great service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(334, 46, 77, 5, 'Fresh, soft, and clean clothes every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(335, 35, 87, 5, 'Delicate fabrics handled so well, no issues.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(336, 14, 96, 5, 'Top-quality service, highly recommended.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(337, 60, 105, 5, 'Crisp pressing and neat folding, ang linis tingnan.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(338, 20, 113, 5, 'Soft towels and smooth fabrics, very comfy.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(339, 17, 124, 5, 'Consistent fresh quality, very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(340, 9, 134, 5, 'Ang bango at linis ng clothes—perfect as always.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(341, 25, 145, 5, 'Very clean output, soft and fresh clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(342, 62, 156, 5, 'Excellent and professional laundry work.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(343, 6, 167, 5, 'Fast service and smooth transaction lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(344, 15, 178, 5, 'Consistently clean, fresh-smelling clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(345, 33, 190, 5, 'Mild but fresh scent, very pleasant gamitin.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(346, 18, 204, 5, 'Soft fabrics, no damage—perfect care.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(347, 28, 217, 5, 'Neat folding, well-organized items always.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(348, 33, 232, 5, 'Fresh clothes, ready-to-wear agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(349, 51, 246, 5, 'Soft towels and crisp sheets, high-quality job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(350, 34, 262, 5, 'Fresh scent, soft fabric—sobrang sarap isuot.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(351, 12, 275, 5, 'Clean and fresh clothes, walang stains.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(352, 32, 293, 5, 'Excellent laundry quality, laging maaasahan.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(353, 31, 307, 5, 'Fast turn-around and very convenient.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(354, 21, 324, 5, 'High-quality cleaning, neat folding lagi.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(355, 13, 342, 5, 'Fresh scent that lasts long, very nice.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(356, 30, 359, 5, 'Soft and comfy fabrics, well taken care of.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(357, 38, 376, 5, 'Organized, clean, and neatly folded clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(358, 11, 395, 5, 'Always fresh, always soft—great service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(359, 58, 199, 5, 'Soft towels and neatly washed items.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(360, 52, 207, 5, 'Perfect outcome—fresh, soft, and clean clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(361, 59, 222, 5, 'Very clean wash, sobrang pulido!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(362, 27, 235, 5, 'Highly professional and organized service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(363, 45, 251, 5, 'Fast service, no delays at all.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(364, 16, 265, 5, 'Consistent cleanliness and nice scent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(365, 10, 280, 5, 'Soft fabrics, light scent—very pleasant.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(366, 61, 296, 5, 'No damage sa clothes, very gentle washing.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(367, 26, 312, 5, 'Neatly folded and packed items, excellent job.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(368, 22, 327, 5, 'Fresh clothes every time, very reliable.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(369, 40, 347, 5, 'Soft towels and clean items—great quality.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(370, 47, 362, 5, 'Ang linis, ang bango—super satisfied!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(371, 15, 381, 5, 'Clothes feel new and fresh, perfect wash.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(372, 36, 398, 5, 'Excellent folding, organized talaga.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(373, 23, 170, 5, 'Fast and very efficient laundry service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(374, 34, 193, 5, 'Soft and fresh fabrics, laging maayos.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(375, 8, 220, 5, 'Gentle wash, no fading or damage.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(376, 54, 249, 5, 'Top-tier service, highly recommended.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(377, 17, 278, 5, 'Neat and crisp pressing, super linis.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(378, 7, 310, 5, 'Fluffy towels, clean sheets—love it!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(379, 29, 345, 5, 'Consistent fresh quality, no fail.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(380, 24, 379, 5, 'Fresh, soft, and clean—perfect laundry service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(381, 19, 415, 5, 'Clean clothes, soft fabric—sobrang sarap isuot.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(382, 23, 19, 5, 'Professional service, neat and organized.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(383, 57, 37, 5, 'Fast and smooth service, no hassle.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(384, 46, 62, 5, 'Fresh scent and soft feel every time.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(385, 35, 94, 5, 'Clothes handled well, no damage at all.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(386, 14, 144, 5, 'High quality service, sobrang recommended.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(387, 60, 189, 5, 'Neat folding, crisp and clean ang outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(388, 20, 245, 5, 'Soft towels and fresh clothes—very comforting.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(389, 9, 323, 5, 'Consistently fresh and clean—love the service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(390, 9, 394, 5, 'Perfect scent, perfect softness—very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(391, 25, 243, 5, 'Super linis and soft, sobrang ganda ng outcome.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(392, 62, 260, 5, 'Excellent organization and clean results.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(393, 62, 274, 5, 'Fast, reliable, at very convenient service.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(394, 15, 291, 5, 'Quality wash every single time, very impressed.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(395, 51, 306, 5, 'Fresh-smelling clothes, hindi overpowering scent.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(396, 18, 322, 5, 'Soft fabrics and clean wash—very satisfied.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(397, 28, 338, 5, 'Neat folding and packing, ready-to-store agad.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(398, 33, 358, 5, 'Fresh and clean clothes always, thank you!', '2025-11-07 00:31:48', NULL, NULL, NULL),
(399, 51, 374, 5, 'Soft towels and crisp sheets, hotel-like feel.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(400, 36, 393, 5, 'Perfect quality—soft, clean, and fresh-smelling clothes.', '2025-11-07 00:31:48', NULL, NULL, NULL),
(401, 12, 410, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(402, 32, 408, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(403, 31, 85, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(404, 21, 239, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(405, 13, 108, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(406, 30, 127, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(407, 38, 148, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(408, 11, 171, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(409, 58, 194, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(410, 52, 221, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(411, 59, 250, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(412, 27, 279, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(413, 45, 311, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(414, 16, 346, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(415, 10, 380, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(416, 61, 366, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(417, 26, 382, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL),
(418, 22, 417, 5, 'Auto feedback entry', '2025-11-07 00:31:48', NULL, NULL, NULL);

--
-- Triggers `feedback`
--
DELIMITER $$
CREATE TRIGGER `after_feedback_update` AFTER UPDATE ON `feedback` FOR EACH ROW BEGIN
    INSERT INTO system_logs (admin_id, action, description)
    VALUES (
        NEW.user_id,
        'Feedback Updated',
        CONCAT('Feedback ID ', NEW.feedback_id, ' updated.')
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `financial_records`
--

CREATE TABLE `financial_records` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` enum('Supplies','Staff Wages','Utilities','Other') DEFAULT 'Other',
  `type` enum('Revenue','Expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `financial_records`
--

INSERT INTO `financial_records` (`id`, `date`, `description`, `category`, `type`, `amount`) VALUES
(1, '2025-11-07', 'Customer Payment - Booking ID: 266', 'Other', 'Revenue', 604.00),
(2, '2025-11-07', 'Customer Payment - Booking ID: 349', 'Other', 'Revenue', 250.00),
(3, '2025-11-07', 'Customer Payment - Booking ID: 413', 'Other', 'Revenue', 300.00),
(4, '2025-11-07', 'Customer Payment - Booking ID: 9', 'Other', 'Revenue', 250.00),
(5, '2025-11-07', 'Customer Payment - Booking ID: 15', 'Other', 'Revenue', 240.86),
(6, '2025-11-07', 'Customer Payment - Booking ID: 23', 'Other', 'Revenue', 300.00),
(7, '2025-11-07', 'Customer Payment - Booking ID: 31', 'Other', 'Revenue', 642.00),
(8, '2025-11-07', 'Customer Payment - Booking ID: 42', 'Other', 'Revenue', 551.90),
(9, '2025-11-07', 'Customer Payment - Booking ID: 54', 'Other', 'Revenue', 250.00),
(10, '2025-11-07', 'Customer Payment - Booking ID: 67', 'Other', 'Revenue', 348.20),
(11, '2025-11-07', 'Customer Payment - Booking ID: 81', 'Other', 'Revenue', 503.60),
(12, '2025-11-07', 'Customer Payment - Booking ID: 100', 'Other', 'Revenue', 254.14),
(13, '2025-11-07', 'Customer Payment - Booking ID: 117', 'Other', 'Revenue', 599.50),
(14, '2025-11-07', 'Customer Payment - Booking ID: 138', 'Other', 'Revenue', 450.00),
(15, '2025-11-07', 'Customer Payment - Booking ID: 160', 'Other', 'Revenue', 368.14),
(16, '2025-11-07', 'Customer Payment - Booking ID: 182', 'Other', 'Revenue', 646.70),
(17, '2025-11-07', 'Customer Payment - Booking ID: 209', 'Other', 'Revenue', 300.00),
(18, '2025-11-07', 'Customer Payment - Booking ID: 237', 'Other', 'Revenue', 359.40),
(19, '2025-11-07', 'Customer Payment - Booking ID: 268', 'Other', 'Revenue', 265.00),
(20, '2025-11-07', 'Customer Payment - Booking ID: 298', 'Other', 'Revenue', 450.00),
(21, '2025-11-07', 'Customer Payment - Booking ID: 330', 'Other', 'Revenue', 202.29),
(22, '2025-11-07', 'Customer Payment - Booking ID: 363', 'Other', 'Revenue', 250.00),
(23, '2025-11-07', 'Customer Payment - Booking ID: 184', 'Other', 'Revenue', 265.00),
(24, '2025-11-07', 'Customer Payment - Booking ID: 231', 'Other', 'Revenue', 378.00),
(25, '2025-11-07', 'Customer Payment - Booking ID: 292', 'Other', 'Revenue', 407.30),
(26, '2025-11-07', 'Customer Payment - Booking ID: 375', 'Other', 'Revenue', 204.86),
(27, '2025-11-07', 'Customer Payment - Booking ID: 411', 'Other', 'Revenue', 566.80),
(28, '2025-11-07', 'Customer Payment - Booking ID: 75', 'Other', 'Revenue', 200.57),
(29, '2025-11-07', 'Customer Payment - Booking ID: 84', 'Other', 'Revenue', 265.00),
(30, '2025-11-07', 'Customer Payment - Booking ID: 92', 'Other', 'Revenue', 543.40),
(31, '2025-11-07', 'Customer Payment - Booking ID: 102', 'Other', 'Revenue', 409.80),
(32, '2025-11-07', 'Customer Payment - Booking ID: 111', 'Other', 'Revenue', 451.60),
(33, '2025-11-07', 'Customer Payment - Booking ID: 120', 'Other', 'Revenue', 439.29),
(34, '2025-11-07', 'Customer Payment - Booking ID: 131', 'Other', 'Revenue', 608.00),
(35, '2025-11-07', 'Customer Payment - Booking ID: 141', 'Other', 'Revenue', 438.80),
(36, '2025-11-07', 'Customer Payment - Booking ID: 152', 'Other', 'Revenue', 446.10),
(37, '2025-11-07', 'Customer Payment - Booking ID: 163', 'Other', 'Revenue', 250.00),
(38, '2025-11-07', 'Customer Payment - Booking ID: 175', 'Other', 'Revenue', 203.43),
(39, '2025-11-07', 'Customer Payment - Booking ID: 186', 'Other', 'Revenue', 553.20),
(40, '2025-11-07', 'Customer Payment - Booking ID: 201', 'Other', 'Revenue', 451.60),
(41, '2025-11-07', 'Customer Payment - Booking ID: 212', 'Other', 'Revenue', 625.30),
(42, '2025-11-07', 'Customer Payment - Booking ID: 227', 'Other', 'Revenue', 537.50),
(43, '2025-11-07', 'Customer Payment - Booking ID: 241', 'Other', 'Revenue', 512.40),
(44, '2025-11-07', 'Customer Payment - Booking ID: 256', 'Other', 'Revenue', 537.00),
(45, '2025-11-07', 'Customer Payment - Booking ID: 271', 'Other', 'Revenue', 434.00),
(46, '2025-11-07', 'Customer Payment - Booking ID: 286', 'Other', 'Revenue', 374.00),
(47, '2025-11-07', 'Customer Payment - Booking ID: 302', 'Other', 'Revenue', 480.10),
(48, '2025-11-07', 'Customer Payment - Booking ID: 317', 'Other', 'Revenue', 632.00),
(49, '2025-11-07', 'Customer Payment - Booking ID: 334', 'Other', 'Revenue', 250.00),
(50, '2025-11-07', 'Customer Payment - Booking ID: 352', 'Other', 'Revenue', 380.70),
(51, '2025-11-07', 'Customer Payment - Booking ID: 370', 'Other', 'Revenue', 236.86),
(52, '2025-11-07', 'Customer Payment - Booking ID: 387', 'Other', 'Revenue', 485.40),
(53, '2025-11-07', 'Customer Payment - Booking ID: 404', 'Other', 'Revenue', 315.00),
(54, '2025-11-07', 'Customer Payment - Booking ID: 213', 'Other', 'Revenue', 250.00),
(55, '2025-11-07', 'Customer Payment - Booking ID: 367', 'Other', 'Revenue', 388.80),
(56, '2025-11-07', 'Customer Payment - Booking ID: 119', 'Other', 'Revenue', 300.00),
(57, '2025-11-07', 'Customer Payment - Booking ID: 130', 'Other', 'Revenue', 266.00),
(58, '2025-11-07', 'Customer Payment - Booking ID: 140', 'Other', 'Revenue', 301.57),
(59, '2025-11-07', 'Customer Payment - Booking ID: 150', 'Other', 'Revenue', 389.43),
(60, '2025-11-07', 'Customer Payment - Booking ID: 162', 'Other', 'Revenue', 496.60),
(61, '2025-11-07', 'Customer Payment - Booking ID: 174', 'Other', 'Revenue', 250.00),
(62, '2025-11-07', 'Customer Payment - Booking ID: 185', 'Other', 'Revenue', 323.71),
(63, '2025-11-07', 'Customer Payment - Booking ID: 197', 'Other', 'Revenue', 648.10),
(64, '2025-11-07', 'Customer Payment - Booking ID: 211', 'Other', 'Revenue', 586.80),
(65, '2025-11-07', 'Customer Payment - Booking ID: 225', 'Other', 'Revenue', 239.14),
(66, '2025-11-07', 'Customer Payment - Booking ID: 240', 'Other', 'Revenue', 237.29),
(67, '2025-11-07', 'Customer Payment - Booking ID: 255', 'Other', 'Revenue', 248.86),
(68, '2025-11-07', 'Customer Payment - Booking ID: 270', 'Other', 'Revenue', 198.00),
(69, '2025-11-07', 'Customer Payment - Booking ID: 284', 'Other', 'Revenue', 315.00),
(70, '2025-11-07', 'Customer Payment - Booking ID: 300', 'Other', 'Revenue', 271.57),
(71, '2025-11-07', 'Customer Payment - Booking ID: 315', 'Other', 'Revenue', 267.71),
(72, '2025-11-07', 'Customer Payment - Booking ID: 332', 'Other', 'Revenue', 404.80),
(73, '2025-11-07', 'Customer Payment - Booking ID: 351', 'Other', 'Revenue', 550.00),
(74, '2025-11-07', 'Customer Payment - Booking ID: 368', 'Other', 'Revenue', 515.00),
(75, '2025-11-07', 'Customer Payment - Booking ID: 385', 'Other', 'Revenue', 222.00),
(76, '2025-11-07', 'Customer Payment - Booking ID: 403', 'Other', 'Revenue', 250.00),
(77, '2025-11-07', 'Customer Payment - Booking ID: 50', 'Other', 'Revenue', 288.86),
(78, '2025-11-07', 'Customer Payment - Booking ID: 83', 'Other', 'Revenue', 300.00),
(79, '2025-11-07', 'Customer Payment - Booking ID: 121', 'Other', 'Revenue', 613.20),
(80, '2025-11-07', 'Customer Payment - Booking ID: 165', 'Other', 'Revenue', 221.14),
(81, '2025-11-07', 'Customer Payment - Booking ID: 226', 'Other', 'Revenue', 510.00),
(82, '2025-11-07', 'Customer Payment - Booking ID: 289', 'Other', 'Revenue', 250.00),
(83, '2025-11-07', 'Customer Payment - Booking ID: 369', 'Other', 'Revenue', 250.00),
(84, '2025-11-07', 'Customer Payment - Booking ID: 401', 'Other', 'Revenue', 560.00),
(85, '2025-11-07', 'Customer Payment - Booking ID: 285', 'Other', 'Revenue', 223.14),
(86, '2025-11-07', 'Customer Payment - Booking ID: 301', 'Other', 'Revenue', 663.60),
(87, '2025-11-07', 'Customer Payment - Booking ID: 316', 'Other', 'Revenue', 417.80),
(88, '2025-11-07', 'Customer Payment - Booking ID: 333', 'Other', 'Revenue', 450.00),
(89, '2025-11-07', 'Customer Payment - Booking ID: 336', 'Other', 'Revenue', 497.80),
(90, '2025-11-07', 'Customer Payment - Booking ID: 356', 'Other', 'Revenue', 687.00),
(91, '2025-11-07', 'Customer Payment - Booking ID: 372', 'Other', 'Revenue', 586.50),
(92, '2025-11-07', 'Customer Payment - Booking ID: 391', 'Other', 'Revenue', 510.00),
(93, '2025-11-07', 'Customer Payment - Booking ID: 418', 'Other', 'Revenue', 250.00),
(94, '2025-11-07', 'Customer Payment - Booking ID: 303', 'Other', 'Revenue', 250.00),
(95, '2025-11-07', 'Customer Payment - Booking ID: 409', 'Other', 'Revenue', 250.00),
(96, '2025-11-07', 'Customer Payment - Booking ID: 68', 'Other', 'Revenue', 515.00),
(97, '2025-11-07', 'Customer Payment - Booking ID: 82', 'Other', 'Revenue', 535.10),
(98, '2025-11-07', 'Customer Payment - Booking ID: 101', 'Other', 'Revenue', 542.40),
(99, '2025-11-07', 'Customer Payment - Booking ID: 118', 'Other', 'Revenue', 450.00),
(100, '2025-11-07', 'Customer Payment - Booking ID: 139', 'Other', 'Revenue', 250.00),
(101, '2025-11-07', 'Customer Payment - Booking ID: 161', 'Other', 'Revenue', 487.20),
(102, '2025-11-07', 'Customer Payment - Booking ID: 183', 'Other', 'Revenue', 450.00),
(103, '2025-11-07', 'Customer Payment - Booking ID: 210', 'Other', 'Revenue', 357.43),
(104, '2025-11-07', 'Customer Payment - Booking ID: 238', 'Other', 'Revenue', 250.00),
(105, '2025-11-07', 'Customer Payment - Booking ID: 269', 'Other', 'Revenue', 300.00),
(106, '2025-11-07', 'Customer Payment - Booking ID: 299', 'Other', 'Revenue', 300.00),
(107, '2025-11-07', 'Customer Payment - Booking ID: 331', 'Other', 'Revenue', 393.20),
(108, '2025-11-07', 'Customer Payment - Booking ID: 364', 'Other', 'Revenue', 265.00),
(109, '2025-11-07', 'Customer Payment - Booking ID: 400', 'Other', 'Revenue', 250.43),
(110, '2025-11-07', 'Customer Payment - Booking ID: 229', 'Other', 'Revenue', 250.00),
(111, '2025-11-07', 'Customer Payment - Booking ID: 258', 'Other', 'Revenue', 450.00),
(112, '2025-11-07', 'Customer Payment - Booking ID: 288', 'Other', 'Revenue', 465.00),
(113, '2025-11-07', 'Customer Payment - Booking ID: 319', 'Other', 'Revenue', 250.00),
(114, '2025-11-07', 'Customer Payment - Booking ID: 354', 'Other', 'Revenue', 250.00),
(115, '2025-11-07', 'Customer Payment - Booking ID: 389', 'Other', 'Revenue', 300.00),
(116, '2025-11-07', 'Customer Payment - Booking ID: 405', 'Other', 'Revenue', 267.14),
(117, '2025-11-07', 'Customer Payment - Booking ID: 142', 'Other', 'Revenue', 482.60),
(118, '2025-11-07', 'Customer Payment - Booking ID: 195', 'Other', 'Revenue', 235.43),
(119, '2025-11-07', 'Customer Payment - Booking ID: 252', 'Other', 'Revenue', 575.30),
(120, '2025-11-07', 'Customer Payment - Booking ID: 328', 'Other', 'Revenue', 265.00),
(121, '2025-11-07', 'Customer Payment - Booking ID: 399', 'Other', 'Revenue', 250.00),
(122, '2025-11-07', 'Customer Payment - Booking ID: 153', 'Other', 'Revenue', 250.00),
(123, '2025-11-07', 'Customer Payment - Booking ID: 164', 'Other', 'Revenue', 315.00),
(124, '2025-11-07', 'Customer Payment - Booking ID: 176', 'Other', 'Revenue', 466.20),
(125, '2025-11-07', 'Customer Payment - Booking ID: 187', 'Other', 'Revenue', 338.40),
(126, '2025-11-07', 'Customer Payment - Booking ID: 200', 'Other', 'Revenue', 264.71),
(127, '2025-11-07', 'Customer Payment - Booking ID: 214', 'Other', 'Revenue', 250.00),
(128, '2025-11-07', 'Customer Payment - Booking ID: 228', 'Other', 'Revenue', 265.00),
(129, '2025-11-07', 'Customer Payment - Booking ID: 242', 'Other', 'Revenue', 435.30),
(130, '2025-11-07', 'Customer Payment - Booking ID: 257', 'Other', 'Revenue', 498.30),
(131, '2025-11-07', 'Customer Payment - Booking ID: 272', 'Other', 'Revenue', 623.90),
(132, '2025-11-07', 'Customer Payment - Booking ID: 287', 'Other', 'Revenue', 380.70),
(133, '2025-11-07', 'Customer Payment - Booking ID: 304', 'Other', 'Revenue', 265.00),
(134, '2025-11-07', 'Customer Payment - Booking ID: 318', 'Other', 'Revenue', 250.00),
(135, '2025-11-07', 'Customer Payment - Booking ID: 335', 'Other', 'Revenue', 253.14),
(136, '2025-11-07', 'Customer Payment - Booking ID: 353', 'Other', 'Revenue', 500.00),
(137, '2025-11-07', 'Customer Payment - Booking ID: 371', 'Other', 'Revenue', 637.60),
(138, '2025-11-07', 'Customer Payment - Booking ID: 388', 'Other', 'Revenue', 265.00),
(139, '2025-11-07', 'Customer Payment - Booking ID: 406', 'Other', 'Revenue', 682.00),
(140, '2025-11-07', 'Customer Payment - Booking ID: 386', 'Other', 'Revenue', 584.80),
(141, '2025-11-07', 'Customer Payment - Booking ID: 412', 'Other', 'Revenue', 460.50),
(142, '2025-11-07', 'Customer Payment - Booking ID: 27', 'Other', 'Revenue', 391.60),
(143, '2025-11-07', 'Customer Payment - Booking ID: 36', 'Other', 'Revenue', 513.80),
(144, '2025-11-07', 'Customer Payment - Booking ID: 48', 'Other', 'Revenue', 265.00),
(145, '2025-11-07', 'Customer Payment - Booking ID: 60', 'Other', 'Revenue', 224.71),
(146, '2025-11-07', 'Customer Payment - Booking ID: 74', 'Other', 'Revenue', 300.00),
(147, '2025-11-07', 'Customer Payment - Booking ID: 91', 'Other', 'Revenue', 514.00),
(148, '2025-11-07', 'Customer Payment - Booking ID: 110', 'Other', 'Revenue', 448.86),
(149, '2025-11-07', 'Customer Payment - Booking ID: 129', 'Other', 'Revenue', 250.00),
(150, '2025-11-07', 'Customer Payment - Booking ID: 151', 'Other', 'Revenue', 663.60),
(151, '2025-11-07', 'Customer Payment - Booking ID: 173', 'Other', 'Revenue', 500.00),
(152, '2025-11-07', 'Customer Payment - Booking ID: 198', 'Other', 'Revenue', 250.00),
(153, '2025-11-07', 'Customer Payment - Booking ID: 224', 'Other', 'Revenue', 315.00),
(154, '2025-11-07', 'Customer Payment - Booking ID: 254', 'Other', 'Revenue', 300.00),
(155, '2025-11-07', 'Customer Payment - Booking ID: 283', 'Other', 'Revenue', 250.00),
(156, '2025-11-07', 'Customer Payment - Booking ID: 314', 'Other', 'Revenue', 300.00),
(157, '2025-11-07', 'Customer Payment - Booking ID: 350', 'Other', 'Revenue', 488.00),
(158, '2025-11-07', 'Customer Payment - Booking ID: 384', 'Other', 'Revenue', 265.00),
(159, '2025-11-07', 'Customer Payment - Booking ID: 4', 'Other', 'Revenue', 265.00),
(160, '2025-11-07', 'Customer Payment - Booking ID: 6', 'Other', 'Revenue', 421.20),
(161, '2025-11-07', 'Customer Payment - Booking ID: 8', 'Other', 'Revenue', 315.00),
(162, '2025-11-07', 'Customer Payment - Booking ID: 11', 'Other', 'Revenue', 576.00),
(163, '2025-11-07', 'Customer Payment - Booking ID: 14', 'Other', 'Revenue', 300.00),
(164, '2025-11-07', 'Customer Payment - Booking ID: 18', 'Other', 'Revenue', 250.00),
(165, '2025-11-07', 'Customer Payment - Booking ID: 22', 'Other', 'Revenue', 533.70),
(166, '2025-11-07', 'Customer Payment - Booking ID: 26', 'Other', 'Revenue', 488.00),
(167, '2025-11-07', 'Customer Payment - Booking ID: 30', 'Other', 'Revenue', 276.29),
(168, '2025-11-07', 'Customer Payment - Booking ID: 35', 'Other', 'Revenue', 249.71),
(169, '2025-11-07', 'Customer Payment - Booking ID: 41', 'Other', 'Revenue', 672.80),
(170, '2025-11-07', 'Customer Payment - Booking ID: 47', 'Other', 'Revenue', 465.40),
(171, '2025-11-07', 'Customer Payment - Booking ID: 53', 'Other', 'Revenue', 300.00),
(172, '2025-11-07', 'Customer Payment - Booking ID: 59', 'Other', 'Revenue', 300.00),
(173, '2025-11-07', 'Customer Payment - Booking ID: 66', 'Other', 'Revenue', 646.80),
(174, '2025-11-07', 'Customer Payment - Booking ID: 73', 'Other', 'Revenue', 250.00),
(175, '2025-11-07', 'Customer Payment - Booking ID: 80', 'Other', 'Revenue', 490.43),
(176, '2025-11-07', 'Customer Payment - Booking ID: 90', 'Other', 'Revenue', 242.00),
(177, '2025-11-07', 'Customer Payment - Booking ID: 99', 'Other', 'Revenue', 250.00),
(178, '2025-11-07', 'Customer Payment - Booking ID: 109', 'Other', 'Revenue', 250.00),
(179, '2025-11-07', 'Customer Payment - Booking ID: 116', 'Other', 'Revenue', 739.00),
(180, '2025-11-07', 'Customer Payment - Booking ID: 128', 'Other', 'Revenue', 315.00),
(181, '2025-11-07', 'Customer Payment - Booking ID: 137', 'Other', 'Revenue', 382.10),
(182, '2025-11-07', 'Customer Payment - Booking ID: 149', 'Other', 'Revenue', 300.00),
(183, '2025-11-07', 'Customer Payment - Booking ID: 159', 'Other', 'Revenue', 250.00),
(184, '2025-11-07', 'Customer Payment - Booking ID: 172', 'Other', 'Revenue', 577.40),
(185, '2025-11-07', 'Customer Payment - Booking ID: 181', 'Other', 'Revenue', 672.40),
(186, '2025-11-07', 'Customer Payment - Booking ID: 196', 'Other', 'Revenue', 413.80),
(187, '2025-11-07', 'Customer Payment - Booking ID: 208', 'Other', 'Revenue', 265.00),
(188, '2025-11-07', 'Customer Payment - Booking ID: 223', 'Other', 'Revenue', 250.00),
(189, '2025-11-07', 'Customer Payment - Booking ID: 236', 'Other', 'Revenue', 578.20),
(190, '2025-11-07', 'Customer Payment - Booking ID: 253', 'Other', 'Revenue', 250.00),
(191, '2025-11-07', 'Customer Payment - Booking ID: 267', 'Other', 'Revenue', 503.60),
(192, '2025-11-07', 'Customer Payment - Booking ID: 281', 'Other', 'Revenue', 456.00),
(193, '2025-11-07', 'Customer Payment - Booking ID: 297', 'Other', 'Revenue', 572.90),
(194, '2025-11-07', 'Customer Payment - Booking ID: 313', 'Other', 'Revenue', 450.00),
(195, '2025-11-07', 'Customer Payment - Booking ID: 329', 'Other', 'Revenue', 300.00),
(196, '2025-11-07', 'Customer Payment - Booking ID: 348', 'Other', 'Revenue', 465.00),
(197, '2025-11-07', 'Customer Payment - Booking ID: 132', 'Other', 'Revenue', 449.30),
(198, '2025-11-07', 'Customer Payment - Booking ID: 215', 'Other', 'Revenue', 320.00),
(199, '2025-11-07', 'Customer Payment - Booking ID: 340', 'Other', 'Revenue', 253.86),
(200, '2025-11-07', 'Customer Payment - Booking ID: 34', 'Other', 'Revenue', 250.00),
(201, '2025-11-07', 'Customer Payment - Booking ID: 38', 'Other', 'Revenue', 500.00),
(202, '2025-11-07', 'Customer Payment - Booking ID: 43', 'Other', 'Revenue', 250.00),
(203, '2025-11-07', 'Customer Payment - Booking ID: 49', 'Other', 'Revenue', 250.00),
(204, '2025-11-07', 'Customer Payment - Booking ID: 55', 'Other', 'Revenue', 378.86),
(205, '2025-11-07', 'Customer Payment - Booking ID: 61', 'Other', 'Revenue', 382.00),
(206, '2025-11-07', 'Customer Payment - Booking ID: 69', 'Other', 'Revenue', 250.00),
(207, '2025-11-07', 'Customer Payment - Booking ID: 76', 'Other', 'Revenue', 564.20),
(208, '2025-11-07', 'Customer Payment - Booking ID: 86', 'Other', 'Revenue', 444.00),
(209, '2025-11-07', 'Customer Payment - Booking ID: 93', 'Other', 'Revenue', 250.00),
(210, '2025-11-07', 'Customer Payment - Booking ID: 103', 'Other', 'Revenue', 250.00),
(211, '2025-11-07', 'Customer Payment - Booking ID: 112', 'Other', 'Revenue', 516.50),
(212, '2025-11-07', 'Customer Payment - Booking ID: 123', 'Other', 'Revenue', 250.00),
(213, '2025-11-07', 'Customer Payment - Booking ID: 133', 'Other', 'Revenue', 250.00),
(214, '2025-11-07', 'Customer Payment - Booking ID: 143', 'Other', 'Revenue', 300.00),
(215, '2025-11-07', 'Customer Payment - Booking ID: 154', 'Other', 'Revenue', 250.00),
(216, '2025-11-07', 'Customer Payment - Booking ID: 166', 'Other', 'Revenue', 617.20),
(217, '2025-11-07', 'Customer Payment - Booking ID: 177', 'Other', 'Revenue', 361.50),
(218, '2025-11-07', 'Customer Payment - Booking ID: 188', 'Other', 'Revenue', 315.00),
(219, '2025-11-07', 'Customer Payment - Booking ID: 202', 'Other', 'Revenue', 339.80),
(220, '2025-11-07', 'Customer Payment - Booking ID: 216', 'Other', 'Revenue', 553.80),
(221, '2025-11-07', 'Customer Payment - Booking ID: 230', 'Other', 'Revenue', 311.71),
(222, '2025-11-07', 'Customer Payment - Booking ID: 244', 'Other', 'Revenue', 265.00),
(223, '2025-11-07', 'Customer Payment - Booking ID: 259', 'Other', 'Revenue', 250.00),
(224, '2025-11-07', 'Customer Payment - Booking ID: 273', 'Other', 'Revenue', 250.00),
(225, '2025-11-07', 'Customer Payment - Booking ID: 290', 'Other', 'Revenue', 269.71),
(226, '2025-11-07', 'Customer Payment - Booking ID: 305', 'Other', 'Revenue', 262.00),
(227, '2025-11-07', 'Customer Payment - Booking ID: 321', 'Other', 'Revenue', 604.40),
(228, '2025-11-07', 'Customer Payment - Booking ID: 337', 'Other', 'Revenue', 443.40),
(229, '2025-11-07', 'Customer Payment - Booking ID: 357', 'Other', 'Revenue', 422.40),
(230, '2025-11-07', 'Customer Payment - Booking ID: 373', 'Other', 'Revenue', 250.00),
(231, '2025-11-07', 'Customer Payment - Booking ID: 392', 'Other', 'Revenue', 460.80),
(232, '2025-11-07', 'Customer Payment - Booking ID: 341', 'Other', 'Revenue', 583.20),
(233, '2025-11-07', 'Customer Payment - Booking ID: 365', 'Other', 'Revenue', 312.86),
(234, '2025-11-07', 'Customer Payment - Booking ID: 383', 'Other', 'Revenue', 500.00),
(235, '2025-11-07', 'Customer Payment - Booking ID: 402', 'Other', 'Revenue', 526.00),
(236, '2025-11-07', 'Customer Payment - Booking ID: 416', 'Other', 'Revenue', 604.60),
(237, '2025-11-07', 'Customer Payment - Booking ID: 1', 'Other', 'Revenue', 638.80),
(238, '2025-11-07', 'Customer Payment - Booking ID: 2', 'Other', 'Revenue', 536.80),
(239, '2025-11-07', 'Customer Payment - Booking ID: 3', 'Other', 'Revenue', 250.00),
(240, '2025-11-07', 'Customer Payment - Booking ID: 5', 'Other', 'Revenue', 465.14),
(241, '2025-11-07', 'Customer Payment - Booking ID: 7', 'Other', 'Revenue', 529.50),
(242, '2025-11-07', 'Customer Payment - Booking ID: 10', 'Other', 'Revenue', 212.86),
(243, '2025-11-07', 'Customer Payment - Booking ID: 13', 'Other', 'Revenue', 250.00),
(244, '2025-11-07', 'Customer Payment - Booking ID: 16', 'Other', 'Revenue', 557.00),
(245, '2025-11-07', 'Customer Payment - Booking ID: 21', 'Other', 'Revenue', 413.20),
(246, '2025-11-07', 'Customer Payment - Booking ID: 24', 'Other', 'Revenue', 265.00),
(247, '2025-11-07', 'Customer Payment - Booking ID: 29', 'Other', 'Revenue', 300.00),
(248, '2025-11-07', 'Customer Payment - Booking ID: 33', 'Other', 'Revenue', 250.00),
(249, '2025-11-07', 'Customer Payment - Booking ID: 40', 'Other', 'Revenue', 432.71),
(250, '2025-11-07', 'Customer Payment - Booking ID: 46', 'Other', 'Revenue', 684.40),
(251, '2025-11-07', 'Customer Payment - Booking ID: 52', 'Other', 'Revenue', 499.70),
(252, '2025-11-07', 'Customer Payment - Booking ID: 58', 'Other', 'Revenue', 450.00),
(253, '2025-11-07', 'Customer Payment - Booking ID: 65', 'Other', 'Revenue', 307.43),
(254, '2025-11-07', 'Customer Payment - Booking ID: 72', 'Other', 'Revenue', 545.20),
(255, '2025-11-07', 'Customer Payment - Booking ID: 79', 'Other', 'Revenue', 250.00),
(256, '2025-11-07', 'Customer Payment - Booking ID: 89', 'Other', 'Revenue', 300.00),
(257, '2025-11-07', 'Customer Payment - Booking ID: 98', 'Other', 'Revenue', 300.00),
(258, '2025-11-07', 'Customer Payment - Booking ID: 107', 'Other', 'Revenue', 436.00),
(259, '2025-11-07', 'Customer Payment - Booking ID: 115', 'Other', 'Revenue', 215.43),
(260, '2025-11-07', 'Customer Payment - Booking ID: 126', 'Other', 'Revenue', 412.40),
(261, '2025-11-07', 'Customer Payment - Booking ID: 136', 'Other', 'Revenue', 402.60),
(262, '2025-11-07', 'Customer Payment - Booking ID: 147', 'Other', 'Revenue', 430.80),
(263, '2025-11-07', 'Customer Payment - Booking ID: 158', 'Other', 'Revenue', 500.00),
(264, '2025-11-07', 'Customer Payment - Booking ID: 169', 'Other', 'Revenue', 250.00),
(265, '2025-11-07', 'Customer Payment - Booking ID: 180', 'Other', 'Revenue', 258.43),
(266, '2025-11-07', 'Customer Payment - Booking ID: 192', 'Other', 'Revenue', 511.60),
(267, '2025-11-07', 'Customer Payment - Booking ID: 206', 'Other', 'Revenue', 420.80),
(268, '2025-11-07', 'Customer Payment - Booking ID: 219', 'Other', 'Revenue', 250.00),
(269, '2025-11-07', 'Customer Payment - Booking ID: 234', 'Other', 'Revenue', 250.00),
(270, '2025-11-07', 'Customer Payment - Booking ID: 248', 'Other', 'Revenue', 315.00),
(271, '2025-11-07', 'Customer Payment - Booking ID: 264', 'Other', 'Revenue', 265.00),
(272, '2025-11-07', 'Customer Payment - Booking ID: 277', 'Other', 'Revenue', 411.90),
(273, '2025-11-07', 'Customer Payment - Booking ID: 295', 'Other', 'Revenue', 254.00),
(274, '2025-11-07', 'Customer Payment - Booking ID: 309', 'Other', 'Revenue', 250.00),
(275, '2025-11-07', 'Customer Payment - Booking ID: 326', 'Other', 'Revenue', 610.40),
(276, '2025-11-07', 'Customer Payment - Booking ID: 344', 'Other', 'Revenue', 315.00),
(277, '2025-11-07', 'Customer Payment - Booking ID: 361', 'Other', 'Revenue', 598.00),
(278, '2025-11-07', 'Customer Payment - Booking ID: 378', 'Other', 'Revenue', 450.00),
(279, '2025-11-07', 'Customer Payment - Booking ID: 397', 'Other', 'Revenue', 472.10),
(280, '2025-11-07', 'Customer Payment - Booking ID: 414', 'Other', 'Revenue', 250.00),
(281, '2025-11-07', 'Customer Payment - Booking ID: 44', 'Other', 'Revenue', 315.00),
(282, '2025-11-07', 'Customer Payment - Booking ID: 122', 'Other', 'Revenue', 513.70),
(283, '2025-11-07', 'Customer Payment - Booking ID: 282', 'Other', 'Revenue', 430.10),
(284, '2025-11-07', 'Customer Payment - Booking ID: 320', 'Other', 'Revenue', 284.14),
(285, '2025-11-07', 'Customer Payment - Booking ID: 355', 'Other', 'Revenue', 210.00),
(286, '2025-11-07', 'Customer Payment - Booking ID: 390', 'Other', 'Revenue', 248.57),
(287, '2025-11-07', 'Customer Payment - Booking ID: 407', 'Other', 'Revenue', 456.30),
(288, '2025-11-07', 'Customer Payment - Booking ID: 12', 'Other', 'Revenue', 552.20),
(289, '2025-11-07', 'Customer Payment - Booking ID: 17', 'Other', 'Revenue', 382.10),
(290, '2025-11-07', 'Customer Payment - Booking ID: 20', 'Other', 'Revenue', 342.14),
(291, '2025-11-07', 'Customer Payment - Booking ID: 25', 'Other', 'Revenue', 274.00),
(292, '2025-11-07', 'Customer Payment - Booking ID: 28', 'Other', 'Revenue', 265.00),
(293, '2025-11-07', 'Customer Payment - Booking ID: 32', 'Other', 'Revenue', 487.40),
(294, '2025-11-07', 'Customer Payment - Booking ID: 39', 'Other', 'Revenue', 250.00),
(295, '2025-11-07', 'Customer Payment - Booking ID: 45', 'Other', 'Revenue', 267.43),
(296, '2025-11-07', 'Customer Payment - Booking ID: 51', 'Other', 'Revenue', 508.40),
(297, '2025-11-07', 'Customer Payment - Booking ID: 57', 'Other', 'Revenue', 424.50),
(298, '2025-11-07', 'Customer Payment - Booking ID: 64', 'Other', 'Revenue', 265.00),
(299, '2025-11-07', 'Customer Payment - Booking ID: 71', 'Other', 'Revenue', 432.00),
(300, '2025-11-07', 'Customer Payment - Booking ID: 78', 'Other', 'Revenue', 450.00),
(301, '2025-11-07', 'Customer Payment - Booking ID: 88', 'Other', 'Revenue', 265.00),
(302, '2025-11-07', 'Customer Payment - Booking ID: 97', 'Other', 'Revenue', 486.10),
(303, '2025-11-07', 'Customer Payment - Booking ID: 106', 'Other', 'Revenue', 516.40),
(304, '2025-11-07', 'Customer Payment - Booking ID: 114', 'Other', 'Revenue', 250.00),
(305, '2025-11-07', 'Customer Payment - Booking ID: 125', 'Other', 'Revenue', 307.71),
(306, '2025-11-07', 'Customer Payment - Booking ID: 135', 'Other', 'Revenue', 257.71),
(307, '2025-11-07', 'Customer Payment - Booking ID: 146', 'Other', 'Revenue', 499.20),
(308, '2025-11-07', 'Customer Payment - Booking ID: 157', 'Other', 'Revenue', 573.60),
(309, '2025-11-07', 'Customer Payment - Booking ID: 168', 'Other', 'Revenue', 265.00),
(310, '2025-11-07', 'Customer Payment - Booking ID: 179', 'Other', 'Revenue', 300.00),
(311, '2025-11-07', 'Customer Payment - Booking ID: 191', 'Other', 'Revenue', 552.00),
(312, '2025-11-07', 'Customer Payment - Booking ID: 205', 'Other', 'Revenue', 213.71),
(313, '2025-11-07', 'Customer Payment - Booking ID: 218', 'Other', 'Revenue', 300.00),
(314, '2025-11-07', 'Customer Payment - Booking ID: 233', 'Other', 'Revenue', 300.00),
(315, '2025-11-07', 'Customer Payment - Booking ID: 247', 'Other', 'Revenue', 463.70),
(316, '2025-11-07', 'Customer Payment - Booking ID: 263', 'Other', 'Revenue', 500.00),
(317, '2025-11-07', 'Customer Payment - Booking ID: 276', 'Other', 'Revenue', 540.20),
(318, '2025-11-07', 'Customer Payment - Booking ID: 294', 'Other', 'Revenue', 250.00),
(319, '2025-11-07', 'Customer Payment - Booking ID: 308', 'Other', 'Revenue', 515.00),
(320, '2025-11-07', 'Customer Payment - Booking ID: 325', 'Other', 'Revenue', 203.43),
(321, '2025-11-07', 'Customer Payment - Booking ID: 343', 'Other', 'Revenue', 250.00),
(322, '2025-11-07', 'Customer Payment - Booking ID: 360', 'Other', 'Revenue', 277.00),
(323, '2025-11-07', 'Customer Payment - Booking ID: 377', 'Other', 'Revenue', 551.50),
(324, '2025-11-07', 'Customer Payment - Booking ID: 396', 'Other', 'Revenue', 633.00),
(325, '2025-11-07', 'Customer Payment - Booking ID: 95', 'Other', 'Revenue', 475.14),
(326, '2025-11-07', 'Customer Payment - Booking ID: 104', 'Other', 'Revenue', 315.00),
(327, '2025-11-07', 'Customer Payment - Booking ID: 155', 'Other', 'Revenue', 282.57),
(328, '2025-11-07', 'Customer Payment - Booking ID: 203', 'Other', 'Revenue', 300.00),
(329, '2025-11-07', 'Customer Payment - Booking ID: 261', 'Other', 'Revenue', 498.00),
(330, '2025-11-07', 'Customer Payment - Booking ID: 339', 'Other', 'Revenue', 250.00),
(331, '2025-11-07', 'Customer Payment - Booking ID: 56', 'Other', 'Revenue', 529.40),
(332, '2025-11-07', 'Customer Payment - Booking ID: 63', 'Other', 'Revenue', 450.00),
(333, '2025-11-07', 'Customer Payment - Booking ID: 70', 'Other', 'Revenue', 263.71),
(334, '2025-11-07', 'Customer Payment - Booking ID: 77', 'Other', 'Revenue', 526.30),
(335, '2025-11-07', 'Customer Payment - Booking ID: 87', 'Other', 'Revenue', 458.10),
(336, '2025-11-07', 'Customer Payment - Booking ID: 96', 'Other', 'Revenue', 635.40),
(337, '2025-11-07', 'Customer Payment - Booking ID: 105', 'Other', 'Revenue', 377.14),
(338, '2025-11-07', 'Customer Payment - Booking ID: 113', 'Other', 'Revenue', 300.00),
(339, '2025-11-07', 'Customer Payment - Booking ID: 124', 'Other', 'Revenue', 265.00),
(340, '2025-11-07', 'Customer Payment - Booking ID: 134', 'Other', 'Revenue', 300.00),
(341, '2025-11-07', 'Customer Payment - Booking ID: 145', 'Other', 'Revenue', 369.14),
(342, '2025-11-07', 'Customer Payment - Booking ID: 156', 'Other', 'Revenue', 529.80),
(343, '2025-11-07', 'Customer Payment - Booking ID: 167', 'Other', 'Revenue', 625.00),
(344, '2025-11-07', 'Customer Payment - Booking ID: 178', 'Other', 'Revenue', 250.00),
(345, '2025-11-07', 'Customer Payment - Booking ID: 190', 'Other', 'Revenue', 263.14),
(346, '2025-11-07', 'Customer Payment - Booking ID: 204', 'Other', 'Revenue', 265.00),
(347, '2025-11-07', 'Customer Payment - Booking ID: 217', 'Other', 'Revenue', 591.10),
(348, '2025-11-07', 'Customer Payment - Booking ID: 232', 'Other', 'Revenue', 545.20),
(349, '2025-11-07', 'Customer Payment - Booking ID: 246', 'Other', 'Revenue', 386.80),
(350, '2025-11-07', 'Customer Payment - Booking ID: 262', 'Other', 'Revenue', 605.10),
(351, '2025-11-07', 'Customer Payment - Booking ID: 275', 'Other', 'Revenue', 275.14),
(352, '2025-11-07', 'Customer Payment - Booking ID: 293', 'Other', 'Revenue', 500.00),
(353, '2025-11-07', 'Customer Payment - Booking ID: 307', 'Other', 'Revenue', 358.70),
(354, '2025-11-07', 'Customer Payment - Booking ID: 324', 'Other', 'Revenue', 265.00),
(355, '2025-11-07', 'Customer Payment - Booking ID: 342', 'Other', 'Revenue', 591.80),
(356, '2025-11-07', 'Customer Payment - Booking ID: 359', 'Other', 'Revenue', 300.00),
(357, '2025-11-07', 'Customer Payment - Booking ID: 376', 'Other', 'Revenue', 515.40),
(358, '2025-11-07', 'Customer Payment - Booking ID: 395', 'Other', 'Revenue', 253.14),
(359, '2025-11-07', 'Customer Payment - Booking ID: 199', 'Other', 'Revenue', 250.00),
(360, '2025-11-07', 'Customer Payment - Booking ID: 207', 'Other', 'Revenue', 409.10),
(361, '2025-11-07', 'Customer Payment - Booking ID: 222', 'Other', 'Revenue', 559.60),
(362, '2025-11-07', 'Customer Payment - Booking ID: 235', 'Other', 'Revenue', 253.71),
(363, '2025-11-07', 'Customer Payment - Booking ID: 251', 'Other', 'Revenue', 504.80),
(364, '2025-11-07', 'Customer Payment - Booking ID: 265', 'Other', 'Revenue', 383.43),
(365, '2025-11-07', 'Customer Payment - Booking ID: 280', 'Other', 'Revenue', 434.71),
(366, '2025-11-07', 'Customer Payment - Booking ID: 296', 'Other', 'Revenue', 691.00),
(367, '2025-11-07', 'Customer Payment - Booking ID: 312', 'Other', 'Revenue', 548.70),
(368, '2025-11-07', 'Customer Payment - Booking ID: 327', 'Other', 'Revenue', 500.10),
(369, '2025-11-07', 'Customer Payment - Booking ID: 347', 'Other', 'Revenue', 478.70),
(370, '2025-11-07', 'Customer Payment - Booking ID: 362', 'Other', 'Revenue', 419.90),
(371, '2025-11-07', 'Customer Payment - Booking ID: 381', 'Other', 'Revenue', 379.60),
(372, '2025-11-07', 'Customer Payment - Booking ID: 398', 'Other', 'Revenue', 300.00),
(373, '2025-11-07', 'Customer Payment - Booking ID: 170', 'Other', 'Revenue', 277.43),
(374, '2025-11-07', 'Customer Payment - Booking ID: 193', 'Other', 'Revenue', 450.00),
(375, '2025-11-07', 'Customer Payment - Booking ID: 220', 'Other', 'Revenue', 364.43),
(376, '2025-11-07', 'Customer Payment - Booking ID: 249', 'Other', 'Revenue', 250.00),
(377, '2025-11-07', 'Customer Payment - Booking ID: 278', 'Other', 'Revenue', 300.00),
(378, '2025-11-07', 'Customer Payment - Booking ID: 310', 'Other', 'Revenue', 228.57),
(379, '2025-11-07', 'Customer Payment - Booking ID: 345', 'Other', 'Revenue', 253.71),
(380, '2025-11-07', 'Customer Payment - Booking ID: 379', 'Other', 'Revenue', 250.00),
(381, '2025-11-07', 'Customer Payment - Booking ID: 415', 'Other', 'Revenue', 259.14),
(382, '2025-11-07', 'Customer Payment - Booking ID: 19', 'Other', 'Revenue', 250.00),
(383, '2025-11-07', 'Customer Payment - Booking ID: 37', 'Other', 'Revenue', 547.00),
(384, '2025-11-07', 'Customer Payment - Booking ID: 62', 'Other', 'Revenue', 547.30),
(385, '2025-11-07', 'Customer Payment - Booking ID: 94', 'Other', 'Revenue', 250.00),
(386, '2025-11-07', 'Customer Payment - Booking ID: 144', 'Other', 'Revenue', 265.00),
(387, '2025-11-07', 'Customer Payment - Booking ID: 189', 'Other', 'Revenue', 250.00),
(388, '2025-11-07', 'Customer Payment - Booking ID: 245', 'Other', 'Revenue', 294.00),
(389, '2025-11-07', 'Customer Payment - Booking ID: 323', 'Other', 'Revenue', 300.00),
(390, '2025-11-07', 'Customer Payment - Booking ID: 394', 'Other', 'Revenue', 250.00),
(391, '2025-11-07', 'Customer Payment - Booking ID: 243', 'Other', 'Revenue', 450.00),
(392, '2025-11-07', 'Customer Payment - Booking ID: 260', 'Other', 'Revenue', 439.57),
(393, '2025-11-07', 'Customer Payment - Booking ID: 274', 'Other', 'Revenue', 250.00),
(394, '2025-11-07', 'Customer Payment - Booking ID: 291', 'Other', 'Revenue', 508.40),
(395, '2025-11-07', 'Customer Payment - Booking ID: 306', 'Other', 'Revenue', 547.60),
(396, '2025-11-07', 'Customer Payment - Booking ID: 322', 'Other', 'Revenue', 537.20),
(397, '2025-11-07', 'Customer Payment - Booking ID: 338', 'Other', 'Revenue', 500.00),
(398, '2025-11-07', 'Customer Payment - Booking ID: 358', 'Other', 'Revenue', 250.00),
(399, '2025-11-07', 'Customer Payment - Booking ID: 374', 'Other', 'Revenue', 300.00),
(400, '2025-11-07', 'Customer Payment - Booking ID: 393', 'Other', 'Revenue', 450.00),
(401, '2025-11-07', 'Customer Payment - Booking ID: 410', 'Other', 'Revenue', 319.14),
(402, '2025-11-07', 'Customer Payment - Booking ID: 408', 'Other', 'Revenue', 265.00),
(403, '2025-11-07', 'Customer Payment - Booking ID: 85', 'Other', 'Revenue', 247.71),
(404, '2025-11-07', 'Customer Payment - Booking ID: 239', 'Other', 'Revenue', 300.00),
(405, '2025-11-07', 'Customer Payment - Booking ID: 108', 'Other', 'Revenue', 465.00),
(406, '2025-11-07', 'Customer Payment - Booking ID: 127', 'Other', 'Revenue', 439.90),
(407, '2025-11-07', 'Customer Payment - Booking ID: 148', 'Other', 'Revenue', 265.00),
(408, '2025-11-07', 'Customer Payment - Booking ID: 171', 'Other', 'Revenue', 628.40),
(409, '2025-11-07', 'Customer Payment - Booking ID: 194', 'Other', 'Revenue', 300.00),
(410, '2025-11-07', 'Customer Payment - Booking ID: 221', 'Other', 'Revenue', 495.20),
(411, '2025-11-07', 'Customer Payment - Booking ID: 250', 'Other', 'Revenue', 354.86),
(412, '2025-11-07', 'Customer Payment - Booking ID: 279', 'Other', 'Revenue', 250.00),
(413, '2025-11-07', 'Customer Payment - Booking ID: 311', 'Other', 'Revenue', 739.20),
(414, '2025-11-07', 'Customer Payment - Booking ID: 346', 'Other', 'Revenue', 464.40),
(415, '2025-11-07', 'Customer Payment - Booking ID: 380', 'Other', 'Revenue', 330.43),
(416, '2025-11-07', 'Customer Payment - Booking ID: 366', 'Other', 'Revenue', 412.40),
(417, '2025-11-07', 'Customer Payment - Booking ID: 382', 'Other', 'Revenue', 501.50),
(418, '2025-11-07', 'Customer Payment - Booking ID: 417', 'Other', 'Revenue', 424.50),
(512, '2024-12-08', 'Transaction ID: 1 - Customer 1', 'Other', 'Revenue', 638.80),
(513, '2024-12-14', 'Transaction ID: 2 - Customer 1', 'Other', 'Revenue', 536.80),
(514, '2024-12-21', 'Transaction ID: 3 - Customer 1', 'Other', 'Revenue', 250.00),
(515, '2024-12-22', 'Transaction ID: 4 - Customer 2', 'Other', 'Revenue', 265.00),
(516, '2024-12-28', 'Transaction ID: 5 - Customer 1', 'Other', 'Revenue', 465.14),
(517, '2024-12-29', 'Transaction ID: 6 - Customer 2', 'Other', 'Revenue', 421.20),
(518, '2025-01-04', 'Transaction ID: 7 - Customer 1', 'Other', 'Revenue', 529.50),
(519, '2025-01-05', 'Transaction ID: 8 - Customer 2', 'Other', 'Revenue', 315.00),
(520, '2025-01-05', 'Transaction ID: 9 - Customer 3', 'Other', 'Revenue', 250.00),
(521, '2025-01-11', 'Transaction ID: 10 - Customer 1', 'Other', 'Revenue', 212.86),
(522, '2025-01-12', 'Transaction ID: 11 - Customer 2', 'Other', 'Revenue', 576.00),
(523, '2025-01-18', 'Transaction ID: 12 - Customer 4', 'Other', 'Revenue', 552.20),
(524, '2025-01-18', 'Transaction ID: 13 - Customer 1', 'Other', 'Revenue', 250.00),
(525, '2025-01-19', 'Transaction ID: 14 - Customer 2', 'Other', 'Revenue', 300.00),
(526, '2025-01-19', 'Transaction ID: 15 - Customer 3', 'Other', 'Revenue', 240.86),
(527, '2025-01-25', 'Transaction ID: 16 - Customer 1', 'Other', 'Revenue', 557.00),
(528, '2025-01-25', 'Transaction ID: 17 - Customer 4', 'Other', 'Revenue', 382.10),
(529, '2025-01-26', 'Transaction ID: 18 - Customer 2', 'Other', 'Revenue', 250.00),
(530, '2025-01-29', 'Transaction ID: 19 - Customer 5', 'Other', 'Revenue', 250.00),
(531, '2025-02-01', 'Transaction ID: 20 - Customer 4', 'Other', 'Revenue', 342.14),
(532, '2025-02-01', 'Transaction ID: 21 - Customer 1', 'Other', 'Revenue', 413.20),
(533, '2025-02-02', 'Transaction ID: 22 - Customer 2', 'Other', 'Revenue', 533.70),
(534, '2025-02-02', 'Transaction ID: 23 - Customer 3', 'Other', 'Revenue', 300.00),
(535, '2025-02-08', 'Transaction ID: 24 - Customer 1', 'Other', 'Revenue', 265.00),
(536, '2025-02-08', 'Transaction ID: 25 - Customer 4', 'Other', 'Revenue', 274.00),
(537, '2025-02-09', 'Transaction ID: 26 - Customer 2', 'Other', 'Revenue', 488.00),
(538, '2025-02-10', 'Transaction ID: 27 - Customer 6', 'Other', 'Revenue', 391.60),
(539, '2025-02-15', 'Transaction ID: 28 - Customer 4', 'Other', 'Revenue', 265.00),
(540, '2025-02-15', 'Transaction ID: 29 - Customer 1', 'Other', 'Revenue', 300.00),
(541, '2025-02-16', 'Transaction ID: 30 - Customer 2', 'Other', 'Revenue', 276.29),
(542, '2025-02-16', 'Transaction ID: 31 - Customer 3', 'Other', 'Revenue', 642.00),
(543, '2025-02-22', 'Transaction ID: 32 - Customer 4', 'Other', 'Revenue', 487.40),
(544, '2025-02-22', 'Transaction ID: 33 - Customer 1', 'Other', 'Revenue', 250.00),
(545, '2025-02-23', 'Transaction ID: 34 - Customer 7', 'Other', 'Revenue', 250.00),
(546, '2025-02-23', 'Transaction ID: 35 - Customer 2', 'Other', 'Revenue', 249.71),
(547, '2025-02-24', 'Transaction ID: 36 - Customer 6', 'Other', 'Revenue', 513.80),
(548, '2025-02-28', 'Transaction ID: 37 - Customer 5', 'Other', 'Revenue', 547.00),
(549, '2025-02-28', 'Transaction ID: 38 - Customer 7', 'Other', 'Revenue', 500.00),
(550, '2025-03-01', 'Transaction ID: 39 - Customer 4', 'Other', 'Revenue', 250.00),
(551, '2025-03-01', 'Transaction ID: 40 - Customer 1', 'Other', 'Revenue', 432.71),
(552, '2025-03-02', 'Transaction ID: 41 - Customer 2', 'Other', 'Revenue', 672.80),
(553, '2025-03-02', 'Transaction ID: 42 - Customer 3', 'Other', 'Revenue', 551.90),
(554, '2025-03-07', 'Transaction ID: 43 - Customer 7', 'Other', 'Revenue', 250.00),
(555, '2025-03-07', 'Transaction ID: 44 - Customer 8', 'Other', 'Revenue', 315.00),
(556, '2025-03-08', 'Transaction ID: 45 - Customer 4', 'Other', 'Revenue', 267.43),
(557, '2025-03-08', 'Transaction ID: 46 - Customer 1', 'Other', 'Revenue', 684.40),
(558, '2025-03-09', 'Transaction ID: 47 - Customer 2', 'Other', 'Revenue', 465.40),
(559, '2025-03-10', 'Transaction ID: 48 - Customer 6', 'Other', 'Revenue', 265.00),
(560, '2025-03-14', 'Transaction ID: 49 - Customer 7', 'Other', 'Revenue', 250.00),
(561, '2025-03-14', 'Transaction ID: 50 - Customer 9', 'Other', 'Revenue', 288.86),
(562, '2025-03-15', 'Transaction ID: 51 - Customer 4', 'Other', 'Revenue', 508.40),
(563, '2025-03-15', 'Transaction ID: 52 - Customer 1', 'Other', 'Revenue', 499.70),
(564, '2025-03-16', 'Transaction ID: 53 - Customer 2', 'Other', 'Revenue', 300.00),
(565, '2025-03-16', 'Transaction ID: 54 - Customer 3', 'Other', 'Revenue', 250.00),
(566, '2025-03-21', 'Transaction ID: 55 - Customer 7', 'Other', 'Revenue', 378.86),
(567, '2025-03-22', 'Transaction ID: 56 - Customer 10', 'Other', 'Revenue', 529.40),
(568, '2025-03-22', 'Transaction ID: 57 - Customer 4', 'Other', 'Revenue', 424.50),
(569, '2025-03-22', 'Transaction ID: 58 - Customer 1', 'Other', 'Revenue', 450.00),
(570, '2025-03-23', 'Transaction ID: 59 - Customer 2', 'Other', 'Revenue', 300.00),
(571, '2025-03-24', 'Transaction ID: 60 - Customer 6', 'Other', 'Revenue', 224.71),
(572, '2025-03-28', 'Transaction ID: 61 - Customer 7', 'Other', 'Revenue', 382.00),
(573, '2025-03-28', 'Transaction ID: 62 - Customer 5', 'Other', 'Revenue', 547.30),
(574, '2025-03-29', 'Transaction ID: 63 - Customer 10', 'Other', 'Revenue', 450.00),
(575, '2025-03-29', 'Transaction ID: 64 - Customer 4', 'Other', 'Revenue', 265.00),
(576, '2025-03-29', 'Transaction ID: 65 - Customer 1', 'Other', 'Revenue', 307.43),
(577, '2025-03-30', 'Transaction ID: 66 - Customer 2', 'Other', 'Revenue', 646.80),
(578, '2025-03-30', 'Transaction ID: 67 - Customer 3', 'Other', 'Revenue', 348.20),
(579, '2025-03-30', 'Transaction ID: 68 - Customer 11', 'Other', 'Revenue', 515.00),
(580, '2025-04-04', 'Transaction ID: 69 - Customer 7', 'Other', 'Revenue', 250.00),
(581, '2025-04-05', 'Transaction ID: 70 - Customer 10', 'Other', 'Revenue', 263.71),
(582, '2025-04-05', 'Transaction ID: 71 - Customer 4', 'Other', 'Revenue', 432.00),
(583, '2025-04-05', 'Transaction ID: 72 - Customer 1', 'Other', 'Revenue', 545.20),
(584, '2025-04-06', 'Transaction ID: 73 - Customer 2', 'Other', 'Revenue', 250.00),
(585, '2025-04-07', 'Transaction ID: 74 - Customer 6', 'Other', 'Revenue', 300.00),
(586, '2025-04-08', 'Transaction ID: 75 - Customer 12', 'Other', 'Revenue', 200.57),
(587, '2025-04-11', 'Transaction ID: 76 - Customer 7', 'Other', 'Revenue', 564.20),
(588, '2025-04-12', 'Transaction ID: 77 - Customer 10', 'Other', 'Revenue', 526.30),
(589, '2025-04-12', 'Transaction ID: 78 - Customer 4', 'Other', 'Revenue', 450.00),
(590, '2025-04-12', 'Transaction ID: 79 - Customer 1', 'Other', 'Revenue', 250.00),
(591, '2025-04-13', 'Transaction ID: 80 - Customer 2', 'Other', 'Revenue', 490.43),
(592, '2025-04-13', 'Transaction ID: 81 - Customer 3', 'Other', 'Revenue', 503.60),
(593, '2025-04-13', 'Transaction ID: 82 - Customer 11', 'Other', 'Revenue', 535.10),
(594, '2025-04-15', 'Transaction ID: 83 - Customer 9', 'Other', 'Revenue', 300.00),
(595, '2025-04-15', 'Transaction ID: 84 - Customer 12', 'Other', 'Revenue', 265.00),
(596, '2025-04-17', 'Transaction ID: 85 - Customer 13', 'Other', 'Revenue', 247.71),
(597, '2025-04-18', 'Transaction ID: 86 - Customer 7', 'Other', 'Revenue', 444.00),
(598, '2025-04-19', 'Transaction ID: 87 - Customer 10', 'Other', 'Revenue', 458.10),
(599, '2025-04-19', 'Transaction ID: 88 - Customer 4', 'Other', 'Revenue', 265.00),
(600, '2025-04-19', 'Transaction ID: 89 - Customer 1', 'Other', 'Revenue', 300.00),
(601, '2025-04-20', 'Transaction ID: 90 - Customer 2', 'Other', 'Revenue', 242.00),
(602, '2025-04-21', 'Transaction ID: 91 - Customer 6', 'Other', 'Revenue', 514.00),
(603, '2025-04-22', 'Transaction ID: 92 - Customer 12', 'Other', 'Revenue', 543.40),
(604, '2025-04-25', 'Transaction ID: 93 - Customer 7', 'Other', 'Revenue', 250.00),
(605, '2025-04-25', 'Transaction ID: 94 - Customer 5', 'Other', 'Revenue', 250.00),
(606, '2025-04-25', 'Transaction ID: 95 - Customer 14', 'Other', 'Revenue', 475.14),
(607, '2025-04-26', 'Transaction ID: 96 - Customer 10', 'Other', 'Revenue', 635.40),
(608, '2025-04-26', 'Transaction ID: 97 - Customer 4', 'Other', 'Revenue', 486.10),
(609, '2025-04-26', 'Transaction ID: 98 - Customer 1', 'Other', 'Revenue', 300.00),
(610, '2025-04-27', 'Transaction ID: 99 - Customer 2', 'Other', 'Revenue', 250.00),
(611, '2025-04-27', 'Transaction ID: 100 - Customer 3', 'Other', 'Revenue', 254.14),
(612, '2025-04-27', 'Transaction ID: 101 - Customer 11', 'Other', 'Revenue', 542.40),
(613, '2025-04-29', 'Transaction ID: 102 - Customer 12', 'Other', 'Revenue', 409.80),
(614, '2025-05-02', 'Transaction ID: 103 - Customer 7', 'Other', 'Revenue', 250.00),
(615, '2025-05-02', 'Transaction ID: 104 - Customer 14', 'Other', 'Revenue', 315.00),
(616, '2025-05-03', 'Transaction ID: 105 - Customer 10', 'Other', 'Revenue', 377.14),
(617, '2025-05-03', 'Transaction ID: 106 - Customer 4', 'Other', 'Revenue', 516.40),
(618, '2025-05-03', 'Transaction ID: 107 - Customer 1', 'Other', 'Revenue', 436.00),
(619, '2025-05-03', 'Transaction ID: 108 - Customer 15', 'Other', 'Revenue', 465.00),
(620, '2025-05-04', 'Transaction ID: 109 - Customer 2', 'Other', 'Revenue', 250.00),
(621, '2025-05-05', 'Transaction ID: 110 - Customer 6', 'Other', 'Revenue', 448.86),
(622, '2025-05-06', 'Transaction ID: 111 - Customer 12', 'Other', 'Revenue', 451.60),
(623, '2025-05-09', 'Transaction ID: 112 - Customer 7', 'Other', 'Revenue', 516.50),
(624, '2025-05-10', 'Transaction ID: 113 - Customer 10', 'Other', 'Revenue', 300.00),
(625, '2025-05-10', 'Transaction ID: 114 - Customer 4', 'Other', 'Revenue', 250.00),
(626, '2025-05-10', 'Transaction ID: 115 - Customer 1', 'Other', 'Revenue', 215.43),
(627, '2025-05-11', 'Transaction ID: 116 - Customer 2', 'Other', 'Revenue', 739.00),
(628, '2025-05-11', 'Transaction ID: 117 - Customer 3', 'Other', 'Revenue', 599.50),
(629, '2025-05-11', 'Transaction ID: 118 - Customer 11', 'Other', 'Revenue', 450.00),
(630, '2025-05-12', 'Transaction ID: 119 - Customer 16', 'Other', 'Revenue', 300.00),
(631, '2025-05-13', 'Transaction ID: 120 - Customer 12', 'Other', 'Revenue', 439.29),
(632, '2025-05-14', 'Transaction ID: 121 - Customer 9', 'Other', 'Revenue', 613.20),
(633, '2025-05-15', 'Transaction ID: 122 - Customer 8', 'Other', 'Revenue', 513.70),
(634, '2025-05-16', 'Transaction ID: 123 - Customer 7', 'Other', 'Revenue', 250.00),
(635, '2025-05-17', 'Transaction ID: 124 - Customer 10', 'Other', 'Revenue', 265.00),
(636, '2025-05-17', 'Transaction ID: 125 - Customer 4', 'Other', 'Revenue', 307.71),
(637, '2025-05-17', 'Transaction ID: 126 - Customer 1', 'Other', 'Revenue', 412.40),
(638, '2025-05-17', 'Transaction ID: 127 - Customer 15', 'Other', 'Revenue', 439.90),
(639, '2025-05-18', 'Transaction ID: 128 - Customer 2', 'Other', 'Revenue', 315.00),
(640, '2025-05-19', 'Transaction ID: 129 - Customer 6', 'Other', 'Revenue', 250.00),
(641, '2025-05-19', 'Transaction ID: 130 - Customer 16', 'Other', 'Revenue', 266.00),
(642, '2025-05-20', 'Transaction ID: 131 - Customer 12', 'Other', 'Revenue', 608.00),
(643, '2025-05-21', 'Transaction ID: 132 - Customer 17', 'Other', 'Revenue', 449.30),
(644, '2025-05-23', 'Transaction ID: 133 - Customer 7', 'Other', 'Revenue', 250.00),
(645, '2025-05-24', 'Transaction ID: 134 - Customer 10', 'Other', 'Revenue', 300.00),
(646, '2025-05-24', 'Transaction ID: 135 - Customer 4', 'Other', 'Revenue', 257.71),
(647, '2025-05-24', 'Transaction ID: 136 - Customer 1', 'Other', 'Revenue', 402.60),
(648, '2025-05-25', 'Transaction ID: 137 - Customer 2', 'Other', 'Revenue', 382.10),
(649, '2025-05-25', 'Transaction ID: 138 - Customer 3', 'Other', 'Revenue', 450.00),
(650, '2025-05-25', 'Transaction ID: 139 - Customer 11', 'Other', 'Revenue', 250.00),
(651, '2025-05-26', 'Transaction ID: 140 - Customer 16', 'Other', 'Revenue', 301.57),
(652, '2025-05-27', 'Transaction ID: 141 - Customer 12', 'Other', 'Revenue', 438.80),
(653, '2025-05-29', 'Transaction ID: 142 - Customer 18', 'Other', 'Revenue', 482.60),
(654, '2025-05-30', 'Transaction ID: 143 - Customer 7', 'Other', 'Revenue', 300.00),
(655, '2025-05-30', 'Transaction ID: 144 - Customer 5', 'Other', 'Revenue', 265.00),
(656, '2025-05-31', 'Transaction ID: 145 - Customer 10', 'Other', 'Revenue', 369.14),
(657, '2025-05-31', 'Transaction ID: 146 - Customer 4', 'Other', 'Revenue', 499.20),
(658, '2025-05-31', 'Transaction ID: 147 - Customer 1', 'Other', 'Revenue', 430.80),
(659, '2025-05-31', 'Transaction ID: 148 - Customer 15', 'Other', 'Revenue', 265.00),
(660, '2025-06-01', 'Transaction ID: 149 - Customer 2', 'Other', 'Revenue', 300.00),
(661, '2025-06-02', 'Transaction ID: 150 - Customer 16', 'Other', 'Revenue', 389.43),
(662, '2025-06-02', 'Transaction ID: 151 - Customer 6', 'Other', 'Revenue', 663.60),
(663, '2025-06-03', 'Transaction ID: 152 - Customer 12', 'Other', 'Revenue', 446.10),
(664, '2025-06-05', 'Transaction ID: 153 - Customer 19', 'Other', 'Revenue', 250.00),
(665, '2025-06-06', 'Transaction ID: 154 - Customer 7', 'Other', 'Revenue', 250.00),
(666, '2025-06-06', 'Transaction ID: 155 - Customer 14', 'Other', 'Revenue', 282.57),
(667, '2025-06-07', 'Transaction ID: 156 - Customer 10', 'Other', 'Revenue', 529.80),
(668, '2025-06-07', 'Transaction ID: 157 - Customer 4', 'Other', 'Revenue', 573.60),
(669, '2025-06-07', 'Transaction ID: 158 - Customer 1', 'Other', 'Revenue', 500.00),
(670, '2025-06-08', 'Transaction ID: 159 - Customer 2', 'Other', 'Revenue', 250.00),
(671, '2025-06-08', 'Transaction ID: 160 - Customer 3', 'Other', 'Revenue', 368.14),
(672, '2025-06-08', 'Transaction ID: 161 - Customer 11', 'Other', 'Revenue', 487.20),
(673, '2025-06-09', 'Transaction ID: 162 - Customer 16', 'Other', 'Revenue', 496.60),
(674, '2025-06-10', 'Transaction ID: 163 - Customer 12', 'Other', 'Revenue', 250.00),
(675, '2025-06-11', 'Transaction ID: 164 - Customer 19', 'Other', 'Revenue', 315.00),
(676, '2025-06-13', 'Transaction ID: 165 - Customer 9', 'Other', 'Revenue', 221.14),
(677, '2025-06-13', 'Transaction ID: 166 - Customer 7', 'Other', 'Revenue', 617.20),
(678, '2025-06-14', 'Transaction ID: 167 - Customer 10', 'Other', 'Revenue', 625.00),
(679, '2025-06-14', 'Transaction ID: 168 - Customer 4', 'Other', 'Revenue', 265.00),
(680, '2025-06-14', 'Transaction ID: 169 - Customer 1', 'Other', 'Revenue', 250.00),
(681, '2025-06-14', 'Transaction ID: 170 - Customer 20', 'Other', 'Revenue', 277.43),
(682, '2025-06-14', 'Transaction ID: 171 - Customer 15', 'Other', 'Revenue', 628.40),
(683, '2025-06-15', 'Transaction ID: 172 - Customer 2', 'Other', 'Revenue', 577.40),
(684, '2025-06-16', 'Transaction ID: 173 - Customer 6', 'Other', 'Revenue', 500.00),
(685, '2025-06-16', 'Transaction ID: 174 - Customer 16', 'Other', 'Revenue', 250.00),
(686, '2025-06-17', 'Transaction ID: 175 - Customer 12', 'Other', 'Revenue', 203.43),
(687, '2025-06-18', 'Transaction ID: 176 - Customer 19', 'Other', 'Revenue', 466.20);
INSERT INTO `financial_records` (`id`, `date`, `description`, `category`, `type`, `amount`) VALUES
(688, '2025-06-20', 'Transaction ID: 177 - Customer 7', 'Other', 'Revenue', 361.50),
(689, '2025-06-21', 'Transaction ID: 178 - Customer 10', 'Other', 'Revenue', 250.00),
(690, '2025-06-21', 'Transaction ID: 179 - Customer 4', 'Other', 'Revenue', 300.00),
(691, '2025-06-21', 'Transaction ID: 180 - Customer 1', 'Other', 'Revenue', 258.43),
(692, '2025-06-22', 'Transaction ID: 181 - Customer 2', 'Other', 'Revenue', 672.40),
(693, '2025-06-22', 'Transaction ID: 182 - Customer 3', 'Other', 'Revenue', 646.70),
(694, '2025-06-22', 'Transaction ID: 183 - Customer 11', 'Other', 'Revenue', 450.00),
(695, '2025-06-22', 'Transaction ID: 184 - Customer 21', 'Other', 'Revenue', 265.00),
(696, '2025-06-23', 'Transaction ID: 185 - Customer 16', 'Other', 'Revenue', 323.71),
(697, '2025-06-24', 'Transaction ID: 186 - Customer 12', 'Other', 'Revenue', 553.20),
(698, '2025-06-25', 'Transaction ID: 187 - Customer 19', 'Other', 'Revenue', 338.40),
(699, '2025-06-27', 'Transaction ID: 188 - Customer 7', 'Other', 'Revenue', 315.00),
(700, '2025-06-27', 'Transaction ID: 189 - Customer 5', 'Other', 'Revenue', 250.00),
(701, '2025-06-28', 'Transaction ID: 190 - Customer 10', 'Other', 'Revenue', 263.14),
(702, '2025-06-28', 'Transaction ID: 191 - Customer 4', 'Other', 'Revenue', 552.00),
(703, '2025-06-28', 'Transaction ID: 192 - Customer 1', 'Other', 'Revenue', 511.60),
(704, '2025-06-28', 'Transaction ID: 193 - Customer 20', 'Other', 'Revenue', 450.00),
(705, '2025-06-28', 'Transaction ID: 194 - Customer 15', 'Other', 'Revenue', 300.00),
(706, '2025-06-28', 'Transaction ID: 195 - Customer 18', 'Other', 'Revenue', 235.43),
(707, '2025-06-29', 'Transaction ID: 196 - Customer 2', 'Other', 'Revenue', 413.80),
(708, '2025-06-30', 'Transaction ID: 197 - Customer 16', 'Other', 'Revenue', 648.10),
(709, '2025-06-30', 'Transaction ID: 198 - Customer 6', 'Other', 'Revenue', 250.00),
(710, '2025-06-30', 'Transaction ID: 199 - Customer 22', 'Other', 'Revenue', 250.00),
(711, '2025-07-02', 'Transaction ID: 200 - Customer 19', 'Other', 'Revenue', 264.71),
(712, '2025-07-01', 'Transaction ID: 201 - Customer 12', 'Other', 'Revenue', 451.60),
(713, '2025-07-04', 'Transaction ID: 202 - Customer 7', 'Other', 'Revenue', 339.80),
(714, '2025-07-04', 'Transaction ID: 203 - Customer 14', 'Other', 'Revenue', 300.00),
(715, '2025-07-05', 'Transaction ID: 204 - Customer 10', 'Other', 'Revenue', 265.00),
(716, '2025-07-05', 'Transaction ID: 205 - Customer 4', 'Other', 'Revenue', 213.71),
(717, '2025-07-05', 'Transaction ID: 206 - Customer 1', 'Other', 'Revenue', 420.80),
(718, '2025-07-05', 'Transaction ID: 207 - Customer 22', 'Other', 'Revenue', 409.10),
(719, '2025-07-06', 'Transaction ID: 208 - Customer 2', 'Other', 'Revenue', 265.00),
(720, '2025-07-06', 'Transaction ID: 209 - Customer 3', 'Other', 'Revenue', 300.00),
(721, '2025-07-06', 'Transaction ID: 210 - Customer 11', 'Other', 'Revenue', 357.43),
(722, '2025-07-07', 'Transaction ID: 211 - Customer 16', 'Other', 'Revenue', 586.80),
(723, '2025-07-08', 'Transaction ID: 212 - Customer 12', 'Other', 'Revenue', 625.30),
(724, '2025-07-08', 'Transaction ID: 213 - Customer 23', 'Other', 'Revenue', 250.00),
(725, '2025-07-09', 'Transaction ID: 214 - Customer 19', 'Other', 'Revenue', 250.00),
(726, '2025-07-10', 'Transaction ID: 215 - Customer 17', 'Other', 'Revenue', 320.00),
(727, '2025-07-11', 'Transaction ID: 216 - Customer 7', 'Other', 'Revenue', 553.80),
(728, '2025-07-12', 'Transaction ID: 217 - Customer 10', 'Other', 'Revenue', 591.10),
(729, '2025-07-12', 'Transaction ID: 218 - Customer 4', 'Other', 'Revenue', 300.00),
(730, '2025-07-12', 'Transaction ID: 219 - Customer 1', 'Other', 'Revenue', 250.00),
(731, '2025-07-12', 'Transaction ID: 220 - Customer 20', 'Other', 'Revenue', 364.43),
(732, '2025-07-12', 'Transaction ID: 221 - Customer 15', 'Other', 'Revenue', 495.20),
(733, '2025-07-12', 'Transaction ID: 222 - Customer 22', 'Other', 'Revenue', 559.60),
(734, '2025-07-13', 'Transaction ID: 223 - Customer 2', 'Other', 'Revenue', 250.00),
(735, '2025-07-14', 'Transaction ID: 224 - Customer 6', 'Other', 'Revenue', 315.00),
(736, '2025-07-14', 'Transaction ID: 225 - Customer 16', 'Other', 'Revenue', 239.14),
(737, '2025-07-15', 'Transaction ID: 226 - Customer 9', 'Other', 'Revenue', 510.00),
(738, '2025-07-15', 'Transaction ID: 227 - Customer 12', 'Other', 'Revenue', 537.50),
(739, '2025-07-16', 'Transaction ID: 228 - Customer 19', 'Other', 'Revenue', 265.00),
(740, '2025-07-16', 'Transaction ID: 229 - Customer 24', 'Other', 'Revenue', 250.00),
(741, '2025-07-18', 'Transaction ID: 230 - Customer 7', 'Other', 'Revenue', 311.71),
(742, '2025-07-18', 'Transaction ID: 231 - Customer 21', 'Other', 'Revenue', 378.00),
(743, '2025-07-19', 'Transaction ID: 232 - Customer 10', 'Other', 'Revenue', 545.20),
(744, '2025-07-19', 'Transaction ID: 233 - Customer 4', 'Other', 'Revenue', 300.00),
(745, '2025-07-19', 'Transaction ID: 234 - Customer 1', 'Other', 'Revenue', 250.00),
(746, '2025-07-19', 'Transaction ID: 235 - Customer 22', 'Other', 'Revenue', 253.71),
(747, '2025-07-20', 'Transaction ID: 236 - Customer 2', 'Other', 'Revenue', 578.20),
(748, '2025-07-20', 'Transaction ID: 237 - Customer 3', 'Other', 'Revenue', 359.40),
(749, '2025-07-20', 'Transaction ID: 238 - Customer 11', 'Other', 'Revenue', 250.00),
(750, '2025-07-20', 'Transaction ID: 239 - Customer 13', 'Other', 'Revenue', 300.00),
(751, '2025-07-21', 'Transaction ID: 240 - Customer 16', 'Other', 'Revenue', 237.29),
(752, '2025-07-22', 'Transaction ID: 241 - Customer 12', 'Other', 'Revenue', 512.40),
(753, '2025-07-23', 'Transaction ID: 242 - Customer 19', 'Other', 'Revenue', 435.30),
(754, '2025-07-24', 'Transaction ID: 243 - Customer 25', 'Other', 'Revenue', 450.00),
(755, '2025-07-25', 'Transaction ID: 244 - Customer 7', 'Other', 'Revenue', 265.00),
(756, '2025-07-25', 'Transaction ID: 245 - Customer 5', 'Other', 'Revenue', 294.00),
(757, '2025-07-26', 'Transaction ID: 246 - Customer 10', 'Other', 'Revenue', 386.80),
(758, '2025-07-26', 'Transaction ID: 247 - Customer 4', 'Other', 'Revenue', 463.70),
(759, '2025-07-26', 'Transaction ID: 248 - Customer 1', 'Other', 'Revenue', 315.00),
(760, '2025-07-26', 'Transaction ID: 249 - Customer 20', 'Other', 'Revenue', 250.00),
(761, '2025-07-26', 'Transaction ID: 250 - Customer 15', 'Other', 'Revenue', 354.86),
(762, '2025-07-26', 'Transaction ID: 251 - Customer 22', 'Other', 'Revenue', 504.80),
(763, '2025-07-26', 'Transaction ID: 252 - Customer 18', 'Other', 'Revenue', 575.30),
(764, '2025-07-27', 'Transaction ID: 253 - Customer 2', 'Other', 'Revenue', 250.00),
(765, '2025-07-28', 'Transaction ID: 254 - Customer 6', 'Other', 'Revenue', 300.00),
(766, '2025-07-28', 'Transaction ID: 255 - Customer 16', 'Other', 'Revenue', 248.86),
(767, '2025-07-29', 'Transaction ID: 256 - Customer 12', 'Other', 'Revenue', 537.00),
(768, '2025-07-30', 'Transaction ID: 257 - Customer 19', 'Other', 'Revenue', 498.30),
(769, '2025-07-30', 'Transaction ID: 258 - Customer 24', 'Other', 'Revenue', 450.00),
(770, '2025-08-01', 'Transaction ID: 259 - Customer 7', 'Other', 'Revenue', 250.00),
(771, '2025-08-01', 'Transaction ID: 260 - Customer 25', 'Other', 'Revenue', 439.57),
(772, '2025-08-01', 'Transaction ID: 261 - Customer 14', 'Other', 'Revenue', 498.00),
(773, '2025-08-02', 'Transaction ID: 262 - Customer 10', 'Other', 'Revenue', 605.10),
(774, '2025-08-02', 'Transaction ID: 263 - Customer 4', 'Other', 'Revenue', 500.00),
(775, '2025-08-02', 'Transaction ID: 264 - Customer 1', 'Other', 'Revenue', 265.00),
(776, '2025-08-02', 'Transaction ID: 265 - Customer 22', 'Other', 'Revenue', 383.43),
(777, '2025-08-02', 'Transaction ID: 266 - Customer 26', 'Other', 'Revenue', 604.00),
(778, '2025-08-03', 'Transaction ID: 267 - Customer 2', 'Other', 'Revenue', 503.60),
(779, '2025-08-03', 'Transaction ID: 268 - Customer 3', 'Other', 'Revenue', 265.00),
(780, '2025-08-03', 'Transaction ID: 269 - Customer 11', 'Other', 'Revenue', 300.00),
(781, '2025-08-04', 'Transaction ID: 270 - Customer 16', 'Other', 'Revenue', 198.00),
(782, '2025-08-05', 'Transaction ID: 271 - Customer 12', 'Other', 'Revenue', 434.00),
(783, '2025-08-06', 'Transaction ID: 272 - Customer 19', 'Other', 'Revenue', 623.90),
(784, '2025-08-08', 'Transaction ID: 273 - Customer 7', 'Other', 'Revenue', 250.00),
(785, '2025-08-08', 'Transaction ID: 274 - Customer 25', 'Other', 'Revenue', 250.00),
(786, '2025-08-09', 'Transaction ID: 275 - Customer 10', 'Other', 'Revenue', 275.14),
(787, '2025-08-09', 'Transaction ID: 276 - Customer 4', 'Other', 'Revenue', 540.20),
(788, '2025-08-09', 'Transaction ID: 277 - Customer 1', 'Other', 'Revenue', 411.90),
(789, '2025-08-09', 'Transaction ID: 278 - Customer 20', 'Other', 'Revenue', 300.00),
(790, '2025-08-09', 'Transaction ID: 279 - Customer 15', 'Other', 'Revenue', 250.00),
(791, '2025-08-09', 'Transaction ID: 280 - Customer 22', 'Other', 'Revenue', 434.71),
(792, '2025-08-10', 'Transaction ID: 281 - Customer 2', 'Other', 'Revenue', 456.00),
(793, '2025-08-10', 'Transaction ID: 282 - Customer 8', 'Other', 'Revenue', 430.10),
(794, '2025-08-11', 'Transaction ID: 283 - Customer 6', 'Other', 'Revenue', 250.00),
(795, '2025-08-11', 'Transaction ID: 284 - Customer 16', 'Other', 'Revenue', 315.00),
(796, '2025-08-11', 'Transaction ID: 285 - Customer 27', 'Other', 'Revenue', 223.14),
(797, '2025-08-12', 'Transaction ID: 286 - Customer 12', 'Other', 'Revenue', 374.00),
(798, '2025-08-13', 'Transaction ID: 287 - Customer 19', 'Other', 'Revenue', 380.70),
(799, '2025-08-13', 'Transaction ID: 288 - Customer 24', 'Other', 'Revenue', 465.00),
(800, '2025-08-14', 'Transaction ID: 289 - Customer 9', 'Other', 'Revenue', 250.00),
(801, '2025-08-15', 'Transaction ID: 290 - Customer 7', 'Other', 'Revenue', 269.71),
(802, '2025-08-15', 'Transaction ID: 291 - Customer 25', 'Other', 'Revenue', 508.40),
(803, '2025-08-15', 'Transaction ID: 292 - Customer 21', 'Other', 'Revenue', 407.30),
(804, '2025-08-16', 'Transaction ID: 293 - Customer 10', 'Other', 'Revenue', 500.00),
(805, '2025-08-16', 'Transaction ID: 294 - Customer 4', 'Other', 'Revenue', 250.00),
(806, '2025-08-16', 'Transaction ID: 295 - Customer 1', 'Other', 'Revenue', 254.00),
(807, '2025-08-16', 'Transaction ID: 296 - Customer 22', 'Other', 'Revenue', 691.00),
(808, '2025-08-17', 'Transaction ID: 297 - Customer 2', 'Other', 'Revenue', 572.90),
(809, '2025-08-17', 'Transaction ID: 298 - Customer 3', 'Other', 'Revenue', 450.00),
(810, '2025-08-17', 'Transaction ID: 299 - Customer 11', 'Other', 'Revenue', 300.00),
(811, '2025-08-18', 'Transaction ID: 300 - Customer 16', 'Other', 'Revenue', 271.57),
(812, '2025-08-18', 'Transaction ID: 301 - Customer 27', 'Other', 'Revenue', 663.60),
(813, '2025-08-19', 'Transaction ID: 302 - Customer 12', 'Other', 'Revenue', 480.10),
(814, '2025-08-19', 'Transaction ID: 303 - Customer 28', 'Other', 'Revenue', 250.00),
(815, '2025-08-20', 'Transaction ID: 304 - Customer 19', 'Other', 'Revenue', 265.00),
(816, '2025-08-22', 'Transaction ID: 305 - Customer 7', 'Other', 'Revenue', 262.00),
(817, '2025-08-22', 'Transaction ID: 306 - Customer 25', 'Other', 'Revenue', 547.60),
(818, '2025-08-23', 'Transaction ID: 307 - Customer 10', 'Other', 'Revenue', 358.70),
(819, '2025-08-23', 'Transaction ID: 308 - Customer 4', 'Other', 'Revenue', 515.00),
(820, '2025-08-23', 'Transaction ID: 309 - Customer 1', 'Other', 'Revenue', 250.00),
(821, '2025-08-23', 'Transaction ID: 310 - Customer 20', 'Other', 'Revenue', 228.57),
(822, '2025-08-23', 'Transaction ID: 311 - Customer 15', 'Other', 'Revenue', 739.20),
(823, '2025-08-23', 'Transaction ID: 312 - Customer 22', 'Other', 'Revenue', 548.70),
(824, '2025-08-24', 'Transaction ID: 313 - Customer 2', 'Other', 'Revenue', 450.00),
(825, '2025-08-25', 'Transaction ID: 314 - Customer 6', 'Other', 'Revenue', 300.00),
(826, '2025-08-25', 'Transaction ID: 315 - Customer 16', 'Other', 'Revenue', 267.71),
(827, '2025-08-25', 'Transaction ID: 316 - Customer 27', 'Other', 'Revenue', 417.80),
(828, '2025-08-26', 'Transaction ID: 317 - Customer 12', 'Other', 'Revenue', 632.00),
(829, '2025-08-27', 'Transaction ID: 318 - Customer 19', 'Other', 'Revenue', 250.00),
(830, '2025-08-27', 'Transaction ID: 319 - Customer 24', 'Other', 'Revenue', 250.00),
(831, '2025-08-27', 'Transaction ID: 320 - Customer 29', 'Other', 'Revenue', 284.14),
(832, '2025-08-29', 'Transaction ID: 321 - Customer 7', 'Other', 'Revenue', 604.40),
(833, '2025-08-29', 'Transaction ID: 322 - Customer 25', 'Other', 'Revenue', 537.20),
(834, '2025-08-29', 'Transaction ID: 323 - Customer 5', 'Other', 'Revenue', 300.00),
(835, '2025-08-30', 'Transaction ID: 324 - Customer 10', 'Other', 'Revenue', 265.00),
(836, '2025-08-30', 'Transaction ID: 325 - Customer 4', 'Other', 'Revenue', 203.43),
(837, '2025-08-30', 'Transaction ID: 326 - Customer 1', 'Other', 'Revenue', 610.40),
(838, '2025-08-30', 'Transaction ID: 327 - Customer 22', 'Other', 'Revenue', 500.10),
(839, '2025-08-30', 'Transaction ID: 328 - Customer 18', 'Other', 'Revenue', 265.00),
(840, '2025-08-31', 'Transaction ID: 329 - Customer 2', 'Other', 'Revenue', 300.00),
(841, '2025-08-31', 'Transaction ID: 330 - Customer 3', 'Other', 'Revenue', 202.29),
(842, '2025-08-31', 'Transaction ID: 331 - Customer 11', 'Other', 'Revenue', 393.20),
(843, '2025-09-01', 'Transaction ID: 332 - Customer 16', 'Other', 'Revenue', 404.80),
(844, '2025-09-01', 'Transaction ID: 333 - Customer 27', 'Other', 'Revenue', 450.00),
(845, '2025-09-02', 'Transaction ID: 334 - Customer 12', 'Other', 'Revenue', 250.00),
(846, '2025-09-03', 'Transaction ID: 335 - Customer 19', 'Other', 'Revenue', 253.14),
(847, '2025-09-04', 'Transaction ID: 336 - Customer 27', 'Other', 'Revenue', 497.80),
(848, '2025-09-05', 'Transaction ID: 337 - Customer 7', 'Other', 'Revenue', 443.40),
(849, '2025-09-05', 'Transaction ID: 338 - Customer 25', 'Other', 'Revenue', 500.00),
(850, '2025-09-05', 'Transaction ID: 339 - Customer 14', 'Other', 'Revenue', 250.00),
(851, '2025-09-05', 'Transaction ID: 340 - Customer 17', 'Other', 'Revenue', 253.86),
(852, '2025-09-05', 'Transaction ID: 341 - Customer 30', 'Other', 'Revenue', 583.20),
(853, '2025-09-06', 'Transaction ID: 342 - Customer 10', 'Other', 'Revenue', 591.80),
(854, '2025-09-06', 'Transaction ID: 343 - Customer 4', 'Other', 'Revenue', 250.00),
(855, '2025-09-06', 'Transaction ID: 344 - Customer 1', 'Other', 'Revenue', 315.00),
(856, '2025-09-06', 'Transaction ID: 345 - Customer 20', 'Other', 'Revenue', 253.71),
(857, '2025-09-06', 'Transaction ID: 346 - Customer 15', 'Other', 'Revenue', 464.40),
(858, '2025-09-06', 'Transaction ID: 347 - Customer 22', 'Other', 'Revenue', 478.70),
(859, '2025-09-07', 'Transaction ID: 348 - Customer 2', 'Other', 'Revenue', 465.00),
(860, '2025-09-07', 'Transaction ID: 349 - Customer 26', 'Other', 'Revenue', 250.00),
(861, '2025-09-08', 'Transaction ID: 350 - Customer 6', 'Other', 'Revenue', 488.00),
(862, '2025-09-08', 'Transaction ID: 351 - Customer 16', 'Other', 'Revenue', 550.00),
(863, '2025-09-09', 'Transaction ID: 352 - Customer 12', 'Other', 'Revenue', 380.70),
(864, '2025-09-10', 'Transaction ID: 353 - Customer 19', 'Other', 'Revenue', 500.00),
(865, '2025-09-10', 'Transaction ID: 354 - Customer 24', 'Other', 'Revenue', 250.00),
(866, '2025-09-10', 'Transaction ID: 355 - Customer 29', 'Other', 'Revenue', 210.00),
(867, '2025-09-11', 'Transaction ID: 356 - Customer 27', 'Other', 'Revenue', 687.00),
(868, '2025-09-12', 'Transaction ID: 357 - Customer 7', 'Other', 'Revenue', 422.40),
(869, '2025-09-12', 'Transaction ID: 358 - Customer 25', 'Other', 'Revenue', 250.00),
(870, '2025-09-13', 'Transaction ID: 359 - Customer 10', 'Other', 'Revenue', 300.00),
(871, '2025-09-13', 'Transaction ID: 360 - Customer 4', 'Other', 'Revenue', 277.00),
(872, '2025-09-13', 'Transaction ID: 361 - Customer 1', 'Other', 'Revenue', 598.00),
(873, '2025-09-13', 'Transaction ID: 362 - Customer 22', 'Other', 'Revenue', 419.90),
(874, '2025-09-14', 'Transaction ID: 363 - Customer 3', 'Other', 'Revenue', 250.00),
(875, '2025-09-14', 'Transaction ID: 364 - Customer 11', 'Other', 'Revenue', 265.00),
(876, '2025-09-14', 'Transaction ID: 365 - Customer 30', 'Other', 'Revenue', 312.86),
(877, '2025-09-14', 'Transaction ID: 366 - Customer 31', 'Other', 'Revenue', 412.40),
(878, '2025-09-15', 'Transaction ID: 367 - Customer 23', 'Other', 'Revenue', 388.80),
(879, '2025-09-15', 'Transaction ID: 368 - Customer 16', 'Other', 'Revenue', 515.00),
(880, '2025-09-16', 'Transaction ID: 369 - Customer 9', 'Other', 'Revenue', 250.00),
(881, '2025-09-16', 'Transaction ID: 370 - Customer 12', 'Other', 'Revenue', 236.86),
(882, '2025-09-17', 'Transaction ID: 371 - Customer 19', 'Other', 'Revenue', 637.60),
(883, '2025-09-18', 'Transaction ID: 372 - Customer 27', 'Other', 'Revenue', 586.50),
(884, '2025-09-19', 'Transaction ID: 373 - Customer 7', 'Other', 'Revenue', 250.00),
(885, '2025-09-19', 'Transaction ID: 374 - Customer 25', 'Other', 'Revenue', 300.00),
(886, '2025-09-19', 'Transaction ID: 375 - Customer 21', 'Other', 'Revenue', 204.86),
(887, '2025-09-20', 'Transaction ID: 376 - Customer 10', 'Other', 'Revenue', 515.40),
(888, '2025-09-20', 'Transaction ID: 377 - Customer 4', 'Other', 'Revenue', 551.50),
(889, '2025-09-20', 'Transaction ID: 378 - Customer 1', 'Other', 'Revenue', 450.00),
(890, '2025-09-20', 'Transaction ID: 379 - Customer 20', 'Other', 'Revenue', 250.00),
(891, '2025-09-20', 'Transaction ID: 380 - Customer 15', 'Other', 'Revenue', 330.43),
(892, '2025-09-20', 'Transaction ID: 381 - Customer 22', 'Other', 'Revenue', 379.60),
(893, '2025-09-20', 'Transaction ID: 382 - Customer 31', 'Other', 'Revenue', 501.50),
(894, '2025-09-21', 'Transaction ID: 383 - Customer 30', 'Other', 'Revenue', 500.00),
(895, '2025-09-22', 'Transaction ID: 384 - Customer 6', 'Other', 'Revenue', 265.00),
(896, '2025-09-22', 'Transaction ID: 385 - Customer 16', 'Other', 'Revenue', 222.00),
(897, '2025-09-22', 'Transaction ID: 386 - Customer 32', 'Other', 'Revenue', 584.80),
(898, '2025-09-23', 'Transaction ID: 387 - Customer 12', 'Other', 'Revenue', 485.40),
(899, '2025-09-24', 'Transaction ID: 388 - Customer 19', 'Other', 'Revenue', 265.00),
(900, '2025-09-24', 'Transaction ID: 389 - Customer 24', 'Other', 'Revenue', 300.00),
(901, '2025-09-24', 'Transaction ID: 390 - Customer 29', 'Other', 'Revenue', 248.57),
(902, '2025-09-25', 'Transaction ID: 391 - Customer 27', 'Other', 'Revenue', 510.00),
(903, '2025-09-26', 'Transaction ID: 392 - Customer 7', 'Other', 'Revenue', 460.80),
(904, '2025-09-26', 'Transaction ID: 393 - Customer 25', 'Other', 'Revenue', 450.00),
(905, '2025-09-26', 'Transaction ID: 394 - Customer 5', 'Other', 'Revenue', 250.00),
(906, '2025-09-27', 'Transaction ID: 395 - Customer 10', 'Other', 'Revenue', 253.14),
(907, '2025-09-27', 'Transaction ID: 396 - Customer 4', 'Other', 'Revenue', 633.00),
(908, '2025-09-27', 'Transaction ID: 397 - Customer 1', 'Other', 'Revenue', 472.10),
(909, '2025-09-27', 'Transaction ID: 398 - Customer 22', 'Other', 'Revenue', 300.00),
(910, '2025-09-27', 'Transaction ID: 399 - Customer 18', 'Other', 'Revenue', 250.00),
(911, '2025-09-28', 'Transaction ID: 400 - Customer 11', 'Other', 'Revenue', 250.43),
(912, '2025-09-28', 'Transaction ID: 401 - Customer 33', 'Other', 'Revenue', 560.00),
(913, '2025-09-28', 'Transaction ID: 402 - Customer 30', 'Other', 'Revenue', 526.00),
(914, '2025-09-29', 'Transaction ID: 403 - Customer 16', 'Other', 'Revenue', 250.00),
(915, '2025-09-30', 'Transaction ID: 404 - Customer 12', 'Other', 'Revenue', 315.00),
(916, '2025-10-01', 'Transaction ID: 405 - Customer 24', 'Other', 'Revenue', 267.14),
(917, '2025-10-01', 'Transaction ID: 406 - Customer 19', 'Other', 'Revenue', 682.00),
(918, '2025-10-01', 'Transaction ID: 407 - Customer 29', 'Other', 'Revenue', 456.30),
(919, '2025-10-02', 'Transaction ID: 408 - Customer 34', 'Other', 'Revenue', 265.00),
(920, '2025-10-02', 'Transaction ID: 409 - Customer 28', 'Other', 'Revenue', 250.00),
(921, '2025-10-03', 'Transaction ID: 410 - Customer 25', 'Other', 'Revenue', 319.14),
(922, '2025-10-03', 'Transaction ID: 411 - Customer 21', 'Other', 'Revenue', 566.80),
(923, '2025-10-03', 'Transaction ID: 412 - Customer 32', 'Other', 'Revenue', 460.50),
(924, '2025-10-04', 'Transaction ID: 413 - Customer 26', 'Other', 'Revenue', 300.00),
(925, '2025-10-04', 'Transaction ID: 414 - Customer 1', 'Other', 'Revenue', 250.00),
(926, '2025-10-04', 'Transaction ID: 415 - Customer 20', 'Other', 'Revenue', 259.14),
(927, '2025-10-04', 'Transaction ID: 416 - Customer 30', 'Other', 'Revenue', 604.60),
(928, '2025-10-04', 'Transaction ID: 417 - Customer 31', 'Other', 'Revenue', 424.50),
(929, '2025-10-04', 'Transaction ID: 418 - Customer 35', 'Other', 'Revenue', 250.00);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `unit_type` enum('liters','grams','pieces','kilograms','milliliters') DEFAULT 'pieces',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`category_id`, `category_name`, `description`, `unit_type`, `created_at`) VALUES
(1, 'Detergent', 'Laundry detergent - liquid or powder', 'liters', '2026-04-19 15:18:49'),
(2, 'Fabric Conditioner', 'Fabric softener / conditioner', 'liters', '2026-04-19 15:18:49'),
(3, 'Bleach', 'Chlorine or color-safe bleach', 'liters', '2026-04-19 15:18:49'),
(4, 'Packaging', 'Plastic bags, hangers, tags', 'pieces', '2026-04-19 15:18:49'),
(5, 'Other Supplies', 'Miscellaneous supplies', 'pieces', '2026-04-19 15:18:49');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'pcs',
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock_level` decimal(10,2) NOT NULL DEFAULT 5.00,
  `max_stock_level` decimal(10,2) DEFAULT 100.00,
  `cost_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock') DEFAULT 'In Stock',
  `last_restock_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `category_id`, `item_name`, `item_code`, `description`, `unit`, `current_stock`, `min_stock_level`, `max_stock_level`, `cost_per_unit`, `supplier_id`, `location`, `expiry_date`, `status`, `last_restock_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Liquid Detergent', 'DET-001', NULL, 'liters', 24.40, 5.00, 50.00, 125.00, NULL, 'Shelf A1', NULL, 'In Stock', NULL, '2026-04-19 19:28:17', '2026-04-19 21:14:22'),
(2, 2, 'Fabric Conditioner', 'COND-001', NULL, 'liters', 14.94, 3.00, 30.00, 145.00, NULL, 'Shelf B1', NULL, 'In Stock', NULL, '2026-04-19 19:28:17', '2026-04-19 21:14:22'),
(3, 3, 'Bleach', 'BLCH-001', NULL, 'liters', 8.00, 2.00, 20.00, 85.00, NULL, 'Shelf C1', NULL, 'In Stock', NULL, '2026-04-19 19:28:17', '2026-04-19 19:28:17'),
(4, 4, 'Plastic Bag', 'PKG-001', NULL, 'pieces', 298.00, 50.00, 500.00, 3.00, NULL, 'Storage Room', NULL, 'In Stock', NULL, '2026-04-19 19:28:17', '2026-04-19 21:14:22'),
(5, 4, 'Hanger', 'PKG-002', NULL, 'pieces', 80.00, 20.00, 150.00, 12.00, NULL, 'Storage Room', NULL, 'In Stock', NULL, '2026-04-19 19:28:17', '2026-04-19 19:28:17'),
(6, 5, 'Stain Remover', 'OTH-001', NULL, 'pieces', 1.99, 2.00, 15.00, 150.00, NULL, 'Shelf D1', NULL, 'Low Stock', '2026-04-19', '2026-04-19 19:28:17', '2026-04-19 20:56:33');

--
-- Triggers `inventory_items`
--
DELIMITER $$
CREATE TRIGGER `check_low_stock_notification` AFTER UPDATE ON `inventory_items` FOR EACH ROW BEGIN
    IF NEW.current_stock <= NEW.min_stock_level AND NEW.current_stock > 0 THEN
        IF NOT EXISTS (
            SELECT 1 FROM inventory_notifications 
            WHERE item_id = NEW.item_id 
            AND notification_type = 'Low Stock' 
            AND is_read = 0
            AND DATE(created_at) = CURDATE()
        ) THEN
            INSERT INTO inventory_notifications (item_id, notification_type, message, current_stock, min_stock_level)
            VALUES (NEW.item_id, 'Low Stock', 
                CONCAT(NEW.item_name, ' is running low. Current stock: ', NEW.current_stock, ' ', NEW.unit), 
                NEW.current_stock, NEW.min_stock_level);
        END IF;
    ELSEIF NEW.current_stock <= 0 THEN
        IF NOT EXISTS (
            SELECT 1 FROM inventory_notifications 
            WHERE item_id = NEW.item_id 
            AND notification_type = 'Out of Stock' 
            AND is_read = 0
            AND DATE(created_at) = CURDATE()
        ) THEN
            INSERT INTO inventory_notifications (item_id, notification_type, message, current_stock, min_stock_level)
            VALUES (NEW.item_id, 'Out of Stock', 
                CONCAT(NEW.item_name, ' is OUT OF STOCK! Please restock immediately.'), 
                NEW.current_stock, NEW.min_stock_level);
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_inventory_status` BEFORE UPDATE ON `inventory_items` FOR EACH ROW BEGIN
    IF NEW.current_stock <= 0 THEN
        SET NEW.status = 'Out of Stock';
    ELSEIF NEW.current_stock <= NEW.min_stock_level THEN
        SET NEW.status = 'Low Stock';
    ELSE
        SET NEW.status = 'In Stock';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_notifications`
--

CREATE TABLE `inventory_notifications` (
  `notification_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `notification_type` enum('Low Stock','Out of Stock','Expiring Soon','Expired') NOT NULL,
  `message` text NOT NULL,
  `current_stock` decimal(10,2) NOT NULL,
  `min_stock_level` decimal(10,2) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_notifications`
--

INSERT INTO `inventory_notifications` (`notification_id`, `item_id`, `notification_type`, `message`, `current_stock`, `min_stock_level`, `is_read`, `created_at`, `read_at`) VALUES
(1, 6, 'Out of Stock', 'Stain Remover is OUT OF STOCK! Please restock immediately.', 0.00, 2.00, 0, '2026-04-19 20:40:29', NULL),
(2, 6, 'Low Stock', 'Stain Remover is running low. Current stock: 0.99 pieces', 0.99, 2.00, 0, '2026-04-19 20:40:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('Stock In','Stock Out','Adjustment','Waste','Usage') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `previous_stock` decimal(10,2) NOT NULL,
  `new_stock` decimal(10,2) NOT NULL,
  `reference_type` enum('Purchase','Booking','Manual','Waste','Expired') DEFAULT 'Manual',
  `reference_id` int(11) DEFAULT NULL COMMENT 'booking_id or purchase_order_id',
  `supplier_id` int(11) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `transaction_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`transaction_id`, `item_id`, `transaction_type`, `quantity`, `previous_stock`, `new_stock`, `reference_type`, `reference_id`, `supplier_id`, `unit_cost`, `notes`, `created_by`, `transaction_date`) VALUES
(18, 4, 'Usage', 1.00, 300.00, 299.00, 'Booking', 451, NULL, 0.00, '🛍️ Per booking (Booking #451)', NULL, '2026-04-19 19:48:58'),
(19, 1, 'Usage', 0.40, 25.00, 24.60, 'Booking', 451, NULL, 0.00, '🧼 Detergent for 8kg (Booking #451)', NULL, '2026-04-19 19:48:58'),
(20, 1, 'Usage', 0.05, 24.60, 24.55, 'Booking', 451, NULL, 0.00, '🧴 Add-on: Liquid Detergent per Cup (+₱10) (Booking #451)', NULL, '2026-04-19 19:48:58'),
(21, 2, 'Usage', 0.03, 15.00, 14.97, 'Booking', 451, NULL, 0.00, '🧴 Add-on: Fabric Conditioner (+₱10) (Booking #451)', NULL, '2026-04-19 19:48:58'),
(22, 6, 'Stock Out', 5.00, 5.00, 0.00, 'Manual', NULL, NULL, 0.00, '', NULL, '2026-04-19 20:40:29'),
(23, 6, 'Stock In', 0.99, 0.00, 0.99, 'Manual', NULL, NULL, 0.00, '', NULL, '2026-04-19 20:40:41'),
(24, 6, 'Stock In', 1.00, 0.99, 1.99, 'Manual', NULL, 1, 9.99, '', NULL, '2026-04-19 20:56:33'),
(25, 4, 'Usage', 1.00, 299.00, 298.00, 'Booking', 453, NULL, 0.00, '🛍️ Per booking (Booking #453)', NULL, '2026-04-19 21:14:22'),
(26, 1, 'Usage', 0.15, 24.55, 24.40, 'Booking', 453, NULL, 0.00, '🧼 Detergent for 3kg (Booking #453)', NULL, '2026-04-19 21:14:22'),
(27, 2, 'Usage', 0.03, 14.97, 14.94, 'Booking', 453, NULL, 0.00, '🧴 Add-on: Fabric Conditioner (+₱10) (Booking #453)', NULL, '2026-04-19 21:14:22');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_usage_rules`
--

CREATE TABLE `inventory_usage_rules` (
  `rule_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL COMMENT 'NULL means applies to all services or add-ons',
  `addon_name` varchar(100) DEFAULT NULL COMMENT 'Name of add-on this rule applies to',
  `usage_per_kg` decimal(10,4) DEFAULT NULL COMMENT 'Amount used per kg of laundry',
  `usage_per_load` decimal(10,4) DEFAULT NULL COMMENT 'Fixed amount per load/use',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications_admin`
--

CREATE TABLE `notifications_admin` (
  `notif_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('sent','pending') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications_admin`
--

INSERT INTO `notifications_admin` (`notif_id`, `booking_id`, `user_id`, `message`, `status`, `created_at`) VALUES
(1, 1, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(2, 2, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(3, 3, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(4, 5, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(5, 7, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(6, 10, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(7, 13, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(8, 16, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(9, 21, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(10, 24, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(11, 29, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(12, 33, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(13, 40, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(14, 46, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(15, 52, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(16, 58, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(17, 65, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(18, 72, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(19, 79, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(20, 89, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(21, 98, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(22, 107, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(23, 115, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(24, 126, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(25, 136, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(26, 147, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(27, 158, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(28, 169, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(29, 180, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(30, 192, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(31, 206, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(32, 219, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(33, 234, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(34, 248, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(35, 264, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(36, 277, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(37, 295, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(38, 309, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(39, 326, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(40, 344, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(41, 361, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(42, 378, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(43, 397, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(44, 414, 1, 'New booking from Maria Santos', '', '2025-11-07 00:32:35'),
(45, 4, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(46, 6, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(47, 8, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(48, 11, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(49, 14, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(50, 18, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(51, 22, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(52, 26, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(53, 30, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(54, 35, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(55, 41, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(56, 47, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(57, 53, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(58, 59, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(59, 66, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(60, 73, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(61, 80, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(62, 90, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(63, 99, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(64, 109, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(65, 116, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(66, 128, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(67, 137, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(68, 149, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(69, 159, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(70, 172, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(71, 181, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(72, 196, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(73, 208, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(74, 223, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(75, 236, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(76, 253, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(77, 267, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(78, 281, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(79, 297, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(80, 313, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(81, 329, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(82, 348, 2, 'New booking from Juan Dela Cruz', '', '2025-11-07 00:32:35'),
(83, 9, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(84, 15, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(85, 23, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(86, 31, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(87, 42, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(88, 54, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(89, 67, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(90, 81, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(91, 100, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(92, 117, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(93, 138, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(94, 160, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(95, 182, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(96, 209, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(97, 237, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(98, 268, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(99, 298, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(100, 330, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(101, 363, 3, 'New booking from Ana Reyes', '', '2025-11-07 00:32:35'),
(102, 12, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(103, 17, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(104, 20, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(105, 25, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(106, 28, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(107, 32, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(108, 39, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(109, 45, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(110, 51, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(111, 57, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(112, 64, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(113, 71, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(114, 78, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(115, 88, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(116, 97, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(117, 106, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(118, 114, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(119, 125, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(120, 135, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(121, 146, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(122, 157, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(123, 168, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(124, 179, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(125, 191, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(126, 205, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(127, 218, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(128, 233, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(129, 247, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(130, 263, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(131, 276, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(132, 294, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(133, 308, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(134, 325, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(135, 343, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(136, 360, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(137, 377, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(138, 396, 4, 'New booking from Pedro Garcia', '', '2025-11-07 00:32:35'),
(139, 19, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(140, 37, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(141, 62, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(142, 94, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(143, 144, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(144, 189, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(145, 245, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(146, 323, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(147, 394, 5, 'New booking from Rosa Bautista', '', '2025-11-07 00:32:35'),
(148, 27, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(149, 36, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(150, 48, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(151, 60, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(152, 74, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(153, 91, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(154, 110, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(155, 129, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(156, 151, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(157, 173, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(158, 198, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(159, 224, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(160, 254, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(161, 283, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(162, 314, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(163, 350, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(164, 384, 6, 'New booking from Jose Gonzales', '', '2025-11-07 00:32:35'),
(165, 34, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(166, 38, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(167, 43, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(168, 49, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(169, 55, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(170, 61, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(171, 69, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(172, 76, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(173, 86, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(174, 93, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(175, 103, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(176, 112, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(177, 123, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(178, 133, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(179, 143, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(180, 154, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(181, 166, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(182, 177, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(183, 188, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(184, 202, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(185, 216, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(186, 230, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(187, 244, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(188, 259, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(189, 273, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(190, 290, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(191, 305, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(192, 321, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(193, 337, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(194, 357, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(195, 373, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(196, 392, 7, 'New booking from Luz Torres', '', '2025-11-07 00:32:35'),
(197, 44, 8, 'New booking from Miguel Ramos', '', '2025-11-07 00:32:35'),
(198, 122, 8, 'New booking from Miguel Ramos', '', '2025-11-07 00:32:35'),
(199, 282, 8, 'New booking from Miguel Ramos', '', '2025-11-07 00:32:35'),
(200, 50, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(201, 83, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(202, 121, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(203, 165, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(204, 226, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(205, 289, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(206, 369, 9, 'New booking from Carmen Fernandez', '', '2025-11-07 00:32:35'),
(207, 56, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(208, 63, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(209, 70, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(210, 77, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(211, 87, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(212, 96, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(213, 105, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(214, 113, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(215, 124, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(216, 134, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(217, 145, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(218, 156, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(219, 167, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(220, 178, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(221, 190, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(222, 204, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(223, 217, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(224, 232, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(225, 246, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(226, 262, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(227, 275, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(228, 293, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(229, 307, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(230, 324, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(231, 342, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(232, 359, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(233, 376, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(234, 395, 10, 'New booking from Ricardo Lopez', '', '2025-11-07 00:32:35'),
(235, 68, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(236, 82, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(237, 101, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(238, 118, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(239, 139, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(240, 161, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(241, 183, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(242, 210, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(243, 238, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(244, 269, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(245, 299, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(246, 331, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(247, 364, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(248, 400, 11, 'New booking from Elena Villanueva', '', '2025-11-07 00:32:35'),
(249, 75, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(250, 84, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(251, 92, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(252, 102, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(253, 111, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(254, 120, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(255, 131, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(256, 141, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(257, 152, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(258, 163, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(259, 175, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(260, 186, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(261, 201, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(262, 212, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(263, 227, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(264, 241, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(265, 256, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(266, 271, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(267, 286, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(268, 302, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(269, 317, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(270, 334, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(271, 352, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(272, 370, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(273, 387, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(274, 404, 12, 'New booking from Antonio Cruz', '', '2025-11-07 00:32:35'),
(275, 85, 13, 'New booking from Sofia Mendoza', '', '2025-11-07 00:32:35'),
(276, 239, 13, 'New booking from Sofia Mendoza', '', '2025-11-07 00:32:35'),
(277, 95, 14, 'New booking from Ramon Santiago', '', '2025-11-07 00:32:35'),
(278, 104, 14, 'New booking from Ramon Santiago', '', '2025-11-07 00:32:35'),
(279, 155, 14, 'New booking from Ramon Santiago', '', '2025-11-07 00:32:35'),
(280, 203, 14, 'New booking from Ramon Santiago', '', '2025-11-07 00:32:35'),
(281, 261, 14, 'New booking from Ramon Santiago', '', '2025-11-07 00:32:35'),
(282, 339, 14, 'New booking from Ramon Santiago', '', '2025-11-07 00:32:35'),
(283, 108, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(284, 127, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(285, 148, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(286, 171, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(287, 194, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(288, 221, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(289, 250, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(290, 279, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(291, 311, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(292, 346, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(293, 380, 15, 'New booking from Teresa Navarro', '', '2025-11-07 00:32:35'),
(294, 119, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(295, 130, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(296, 140, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(297, 150, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(298, 162, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(299, 174, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(300, 185, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(301, 197, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(302, 211, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(303, 225, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(304, 240, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(305, 255, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(306, 270, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(307, 284, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(308, 300, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(309, 315, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(310, 332, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(311, 351, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(312, 368, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(313, 385, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(314, 403, 16, 'New booking from Carlos Morales', '', '2025-11-07 00:32:35'),
(315, 132, 17, 'New booking from Linda Castillo', '', '2025-11-07 00:32:35'),
(316, 215, 17, 'New booking from Linda Castillo', '', '2025-11-07 00:32:35'),
(317, 340, 17, 'New booking from Linda Castillo', '', '2025-11-07 00:32:35'),
(318, 142, 18, 'New booking from Fernando Aguilar', '', '2025-11-07 00:32:35'),
(319, 195, 18, 'New booking from Fernando Aguilar', '', '2025-11-07 00:32:35'),
(320, 252, 18, 'New booking from Fernando Aguilar', '', '2025-11-07 00:32:35'),
(321, 328, 18, 'New booking from Fernando Aguilar', '', '2025-11-07 00:32:35'),
(322, 399, 18, 'New booking from Fernando Aguilar', '', '2025-11-07 00:32:35'),
(323, 153, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(324, 164, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(325, 176, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(326, 187, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(327, 200, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(328, 214, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(329, 228, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(330, 242, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(331, 257, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(332, 272, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(333, 287, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(334, 304, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(335, 318, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(336, 335, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(337, 353, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(338, 371, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(339, 388, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(340, 406, 19, 'New booking from Gloria Herrera', '', '2025-11-07 00:32:35'),
(341, 170, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(342, 193, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(343, 220, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(344, 249, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(345, 278, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(346, 310, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(347, 345, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(348, 379, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(349, 415, 20, 'New booking from Rodrigo Jimenez', '', '2025-11-07 00:32:35'),
(350, 184, 21, 'New booking from Angelica Valdez', '', '2025-11-07 00:32:35'),
(351, 231, 21, 'New booking from Angelica Valdez', '', '2025-11-07 00:32:35'),
(352, 292, 21, 'New booking from Angelica Valdez', '', '2025-11-07 00:32:35'),
(353, 375, 21, 'New booking from Angelica Valdez', '', '2025-11-07 00:32:35'),
(354, 411, 21, 'New booking from Angelica Valdez', '', '2025-11-07 00:32:35'),
(355, 199, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(356, 207, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(357, 222, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(358, 235, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(359, 251, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(360, 265, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(361, 280, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(362, 296, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(363, 312, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(364, 327, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(365, 347, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(366, 362, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(367, 381, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(368, 398, 22, 'New booking from Roberto Medina', '', '2025-11-07 00:32:35'),
(369, 213, 23, 'New booking from Beatriz Romero', '', '2025-11-07 00:32:35'),
(370, 367, 23, 'New booking from Beatriz Romero', '', '2025-11-07 00:32:35'),
(371, 229, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(372, 258, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(373, 288, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(374, 319, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(375, 354, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(376, 389, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(377, 405, 24, 'New booking from Enrique Gutierrez', '', '2025-11-07 00:32:35'),
(378, 243, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(379, 260, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(380, 274, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(381, 291, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(382, 306, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(383, 322, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(384, 338, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(385, 358, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(386, 374, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(387, 393, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(388, 410, 25, 'New booking from Rosario Ortiz', '', '2025-11-07 00:32:35'),
(389, 266, 26, 'New booking from Alfredo Alvarez', '', '2025-11-07 00:32:35'),
(390, 349, 26, 'New booking from Alfredo Alvarez', '', '2025-11-07 00:32:35'),
(391, 413, 26, 'New booking from Alfredo Alvarez', '', '2025-11-07 00:32:35'),
(392, 285, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(393, 301, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(394, 316, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(395, 333, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(396, 336, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(397, 356, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(398, 372, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(399, 391, 27, 'New booking from Cristina Flores', '', '2025-11-07 00:32:35'),
(400, 303, 28, 'New booking from Eduardo Vargas', '', '2025-11-07 00:32:35'),
(401, 409, 28, 'New booking from Eduardo Vargas', '', '2025-11-07 00:32:35'),
(402, 320, 29, 'New booking from Patricia Campos', '', '2025-11-07 00:32:35'),
(403, 355, 29, 'New booking from Patricia Campos', '', '2025-11-07 00:32:35'),
(404, 390, 29, 'New booking from Patricia Campos', '', '2025-11-07 00:32:35'),
(405, 407, 29, 'New booking from Patricia Campos', '', '2025-11-07 00:32:35'),
(406, 341, 30, 'New booking from Manuel Diaz', '', '2025-11-07 00:32:35'),
(407, 365, 30, 'New booking from Manuel Diaz', '', '2025-11-07 00:32:35'),
(408, 383, 30, 'New booking from Manuel Diaz', '', '2025-11-07 00:32:35'),
(409, 402, 30, 'New booking from Manuel Diaz', '', '2025-11-07 00:32:35'),
(410, 416, 30, 'New booking from Manuel Diaz', '', '2025-11-07 00:32:35'),
(411, 366, 31, 'New booking from Veronica Marquez', '', '2025-11-07 00:32:35'),
(412, 382, 31, 'New booking from Veronica Marquez', '', '2025-11-07 00:32:35'),
(413, 417, 31, 'New booking from Veronica Marquez', '', '2025-11-07 00:32:35'),
(414, 386, 32, 'New booking from Ignacio Ramirez', '', '2025-11-07 00:32:35'),
(415, 412, 32, 'New booking from Ignacio Ramirez', '', '2025-11-07 00:32:35'),
(416, 401, 33, 'New booking from Cecilia Perez', '', '2025-11-07 00:32:35'),
(417, 408, 34, 'New booking from Salvador Rivera', '', '2025-11-07 00:32:35'),
(418, 418, 35, 'New booking from Dolores Sanchez', '', '2025-11-07 00:32:35');

-- --------------------------------------------------------

--
-- Table structure for table `notifications_user`
--

CREATE TABLE `notifications_user` (
  `notif_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('sent','pending') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications_user`
--

INSERT INTO `notifications_user` (`notif_id`, `booking_id`, `user_id`, `message`, `status`, `created_at`) VALUES
(1, 266, 26, 'Your booking #266 is confirmed', 'sent', '2025-11-07 00:33:39'),
(2, 349, 26, 'Your booking #349 is confirmed', 'sent', '2025-11-07 00:33:39'),
(3, 413, 26, 'Your booking #413 is confirmed', 'sent', '2025-11-07 00:33:39'),
(4, 9, 3, 'Your booking #9 is confirmed', 'sent', '2025-11-07 00:33:39'),
(5, 15, 3, 'Your booking #15 is confirmed', 'sent', '2025-11-07 00:33:39'),
(6, 23, 3, 'Your booking #23 is confirmed', 'sent', '2025-11-07 00:33:39'),
(7, 31, 3, 'Your booking #31 is confirmed', 'sent', '2025-11-07 00:33:39'),
(8, 42, 3, 'Your booking #42 is confirmed', 'sent', '2025-11-07 00:33:39'),
(9, 54, 3, 'Your booking #54 is confirmed', 'sent', '2025-11-07 00:33:39'),
(10, 67, 3, 'Your booking #67 is confirmed', 'sent', '2025-11-07 00:33:39'),
(11, 81, 3, 'Your booking #81 is confirmed', 'sent', '2025-11-07 00:33:39'),
(12, 100, 3, 'Your booking #100 is confirmed', 'sent', '2025-11-07 00:33:39'),
(13, 117, 3, 'Your booking #117 is confirmed', 'sent', '2025-11-07 00:33:39'),
(14, 138, 3, 'Your booking #138 is confirmed', 'sent', '2025-11-07 00:33:39'),
(15, 160, 3, 'Your booking #160 is confirmed', 'sent', '2025-11-07 00:33:39'),
(16, 182, 3, 'Your booking #182 is confirmed', 'sent', '2025-11-07 00:33:39'),
(17, 209, 3, 'Your booking #209 is confirmed', 'sent', '2025-11-07 00:33:39'),
(18, 237, 3, 'Your booking #237 is confirmed', 'sent', '2025-11-07 00:33:39'),
(19, 268, 3, 'Your booking #268 is confirmed', 'sent', '2025-11-07 00:33:39'),
(20, 298, 3, 'Your booking #298 is confirmed', 'sent', '2025-11-07 00:33:39'),
(21, 330, 3, 'Your booking #330 is confirmed', 'sent', '2025-11-07 00:33:39'),
(22, 363, 3, 'Your booking #363 is confirmed', 'sent', '2025-11-07 00:33:39'),
(23, 184, 21, 'Your booking #184 is confirmed', 'sent', '2025-11-07 00:33:39'),
(24, 231, 21, 'Your booking #231 is confirmed', 'sent', '2025-11-07 00:33:39'),
(25, 292, 21, 'Your booking #292 is confirmed', 'sent', '2025-11-07 00:33:39'),
(26, 375, 21, 'Your booking #375 is confirmed', 'sent', '2025-11-07 00:33:39'),
(27, 411, 21, 'Your booking #411 is confirmed', 'sent', '2025-11-07 00:33:39'),
(28, 75, 12, 'Your booking #75 is confirmed', 'sent', '2025-11-07 00:33:39'),
(29, 84, 12, 'Your booking #84 is confirmed', 'sent', '2025-11-07 00:33:39'),
(30, 92, 12, 'Your booking #92 is confirmed', 'sent', '2025-11-07 00:33:39'),
(31, 102, 12, 'Your booking #102 is confirmed', 'sent', '2025-11-07 00:33:39'),
(32, 111, 12, 'Your booking #111 is confirmed', 'sent', '2025-11-07 00:33:39'),
(33, 120, 12, 'Your booking #120 is confirmed', 'sent', '2025-11-07 00:33:39'),
(34, 131, 12, 'Your booking #131 is confirmed', 'sent', '2025-11-07 00:33:39'),
(35, 141, 12, 'Your booking #141 is confirmed', 'sent', '2025-11-07 00:33:39'),
(36, 152, 12, 'Your booking #152 is confirmed', 'sent', '2025-11-07 00:33:39'),
(37, 163, 12, 'Your booking #163 is confirmed', 'sent', '2025-11-07 00:33:39'),
(38, 175, 12, 'Your booking #175 is confirmed', 'sent', '2025-11-07 00:33:39'),
(39, 186, 12, 'Your booking #186 is confirmed', 'sent', '2025-11-07 00:33:39'),
(40, 201, 12, 'Your booking #201 is confirmed', 'sent', '2025-11-07 00:33:39'),
(41, 212, 12, 'Your booking #212 is confirmed', 'sent', '2025-11-07 00:33:39'),
(42, 227, 12, 'Your booking #227 is confirmed', 'sent', '2025-11-07 00:33:39'),
(43, 241, 12, 'Your booking #241 is confirmed', 'sent', '2025-11-07 00:33:39'),
(44, 256, 12, 'Your booking #256 is confirmed', 'sent', '2025-11-07 00:33:39'),
(45, 271, 12, 'Your booking #271 is confirmed', 'sent', '2025-11-07 00:33:39'),
(46, 286, 12, 'Your booking #286 is confirmed', 'sent', '2025-11-07 00:33:39'),
(47, 302, 12, 'Your booking #302 is confirmed', 'sent', '2025-11-07 00:33:39'),
(48, 317, 12, 'Your booking #317 is confirmed', 'sent', '2025-11-07 00:33:39'),
(49, 334, 12, 'Your booking #334 is confirmed', 'sent', '2025-11-07 00:33:39'),
(50, 352, 12, 'Your booking #352 is confirmed', 'sent', '2025-11-07 00:33:39'),
(51, 370, 12, 'Your booking #370 is confirmed', 'sent', '2025-11-07 00:33:39'),
(52, 387, 12, 'Your booking #387 is confirmed', 'sent', '2025-11-07 00:33:39'),
(53, 404, 12, 'Your booking #404 is confirmed', 'sent', '2025-11-07 00:33:39'),
(54, 213, 23, 'Your booking #213 is confirmed', 'sent', '2025-11-07 00:33:39'),
(55, 367, 23, 'Your booking #367 is confirmed', 'sent', '2025-11-07 00:33:39'),
(56, 119, 16, 'Your booking #119 is confirmed', 'sent', '2025-11-07 00:33:39'),
(57, 130, 16, 'Your booking #130 is confirmed', 'sent', '2025-11-07 00:33:39'),
(58, 140, 16, 'Your booking #140 is confirmed', 'sent', '2025-11-07 00:33:39'),
(59, 150, 16, 'Your booking #150 is confirmed', 'sent', '2025-11-07 00:33:39'),
(60, 162, 16, 'Your booking #162 is confirmed', 'sent', '2025-11-07 00:33:39'),
(61, 174, 16, 'Your booking #174 is confirmed', 'sent', '2025-11-07 00:33:39'),
(62, 185, 16, 'Your booking #185 is confirmed', 'sent', '2025-11-07 00:33:39'),
(63, 197, 16, 'Your booking #197 is confirmed', 'sent', '2025-11-07 00:33:39'),
(64, 211, 16, 'Your booking #211 is confirmed', 'sent', '2025-11-07 00:33:39'),
(65, 225, 16, 'Your booking #225 is confirmed', 'sent', '2025-11-07 00:33:39'),
(66, 240, 16, 'Your booking #240 is confirmed', 'sent', '2025-11-07 00:33:39'),
(67, 255, 16, 'Your booking #255 is confirmed', 'sent', '2025-11-07 00:33:39'),
(68, 270, 16, 'Your booking #270 is confirmed', 'sent', '2025-11-07 00:33:39'),
(69, 284, 16, 'Your booking #284 is confirmed', 'sent', '2025-11-07 00:33:39'),
(70, 300, 16, 'Your booking #300 is confirmed', 'sent', '2025-11-07 00:33:39'),
(71, 315, 16, 'Your booking #315 is confirmed', 'sent', '2025-11-07 00:33:39'),
(72, 332, 16, 'Your booking #332 is confirmed', 'sent', '2025-11-07 00:33:39'),
(73, 351, 16, 'Your booking #351 is confirmed', 'sent', '2025-11-07 00:33:39'),
(74, 368, 16, 'Your booking #368 is confirmed', 'sent', '2025-11-07 00:33:39'),
(75, 385, 16, 'Your booking #385 is confirmed', 'sent', '2025-11-07 00:33:39'),
(76, 403, 16, 'Your booking #403 is confirmed', 'sent', '2025-11-07 00:33:39'),
(77, 50, 9, 'Your booking #50 is confirmed', 'sent', '2025-11-07 00:33:39'),
(78, 83, 9, 'Your booking #83 is confirmed', 'sent', '2025-11-07 00:33:39'),
(79, 121, 9, 'Your booking #121 is confirmed', 'sent', '2025-11-07 00:33:39'),
(80, 165, 9, 'Your booking #165 is confirmed', 'sent', '2025-11-07 00:33:39'),
(81, 226, 9, 'Your booking #226 is confirmed', 'sent', '2025-11-07 00:33:39'),
(82, 289, 9, 'Your booking #289 is confirmed', 'sent', '2025-11-07 00:33:39'),
(83, 369, 9, 'Your booking #369 is confirmed', 'sent', '2025-11-07 00:33:39'),
(84, 401, 33, 'Your booking #401 is confirmed', 'sent', '2025-11-07 00:33:39'),
(85, 285, 27, 'Your booking #285 is confirmed', 'sent', '2025-11-07 00:33:39'),
(86, 301, 27, 'Your booking #301 is confirmed', 'sent', '2025-11-07 00:33:39'),
(87, 316, 27, 'Your booking #316 is confirmed', 'sent', '2025-11-07 00:33:39'),
(88, 333, 27, 'Your booking #333 is confirmed', 'sent', '2025-11-07 00:33:39'),
(89, 336, 27, 'Your booking #336 is confirmed', 'sent', '2025-11-07 00:33:39'),
(90, 356, 27, 'Your booking #356 is confirmed', 'sent', '2025-11-07 00:33:39'),
(91, 372, 27, 'Your booking #372 is confirmed', 'sent', '2025-11-07 00:33:39'),
(92, 391, 27, 'Your booking #391 is confirmed', 'sent', '2025-11-07 00:33:39'),
(93, 418, 35, 'Your booking #418 is confirmed', 'sent', '2025-11-07 00:33:39'),
(94, 303, 28, 'Your booking #303 is confirmed', 'sent', '2025-11-07 00:33:39'),
(95, 409, 28, 'Your booking #409 is confirmed', 'sent', '2025-11-07 00:33:39'),
(96, 68, 11, 'Your booking #68 is confirmed', 'sent', '2025-11-07 00:33:39'),
(97, 82, 11, 'Your booking #82 is confirmed', 'sent', '2025-11-07 00:33:39'),
(98, 101, 11, 'Your booking #101 is confirmed', 'sent', '2025-11-07 00:33:39'),
(99, 118, 11, 'Your booking #118 is confirmed', 'sent', '2025-11-07 00:33:39'),
(100, 139, 11, 'Your booking #139 is confirmed', 'sent', '2025-11-07 00:33:39'),
(101, 161, 11, 'Your booking #161 is confirmed', 'sent', '2025-11-07 00:33:39'),
(102, 183, 11, 'Your booking #183 is confirmed', 'sent', '2025-11-07 00:33:39'),
(103, 210, 11, 'Your booking #210 is confirmed', 'sent', '2025-11-07 00:33:39'),
(104, 238, 11, 'Your booking #238 is confirmed', 'sent', '2025-11-07 00:33:39'),
(105, 269, 11, 'Your booking #269 is confirmed', 'sent', '2025-11-07 00:33:39'),
(106, 299, 11, 'Your booking #299 is confirmed', 'sent', '2025-11-07 00:33:39'),
(107, 331, 11, 'Your booking #331 is confirmed', 'sent', '2025-11-07 00:33:39'),
(108, 364, 11, 'Your booking #364 is confirmed', 'sent', '2025-11-07 00:33:39'),
(109, 400, 11, 'Your booking #400 is confirmed', 'sent', '2025-11-07 00:33:39'),
(110, 229, 24, 'Your booking #229 is confirmed', 'sent', '2025-11-07 00:33:39'),
(111, 258, 24, 'Your booking #258 is confirmed', 'sent', '2025-11-07 00:33:39'),
(112, 288, 24, 'Your booking #288 is confirmed', 'sent', '2025-11-07 00:33:39'),
(113, 319, 24, 'Your booking #319 is confirmed', 'sent', '2025-11-07 00:33:39'),
(114, 354, 24, 'Your booking #354 is confirmed', 'sent', '2025-11-07 00:33:39'),
(115, 389, 24, 'Your booking #389 is confirmed', 'sent', '2025-11-07 00:33:39'),
(116, 405, 24, 'Your booking #405 is confirmed', 'sent', '2025-11-07 00:33:39'),
(117, 142, 18, 'Your booking #142 is confirmed', 'sent', '2025-11-07 00:33:39'),
(118, 195, 18, 'Your booking #195 is confirmed', 'sent', '2025-11-07 00:33:39'),
(119, 252, 18, 'Your booking #252 is confirmed', 'sent', '2025-11-07 00:33:39'),
(120, 328, 18, 'Your booking #328 is confirmed', 'sent', '2025-11-07 00:33:39'),
(121, 399, 18, 'Your booking #399 is confirmed', 'sent', '2025-11-07 00:33:39'),
(122, 153, 19, 'Your booking #153 is confirmed', 'sent', '2025-11-07 00:33:39'),
(123, 164, 19, 'Your booking #164 is confirmed', 'sent', '2025-11-07 00:33:39'),
(124, 176, 19, 'Your booking #176 is confirmed', 'sent', '2025-11-07 00:33:39'),
(125, 187, 19, 'Your booking #187 is confirmed', 'sent', '2025-11-07 00:33:39'),
(126, 200, 19, 'Your booking #200 is confirmed', 'sent', '2025-11-07 00:33:39'),
(127, 214, 19, 'Your booking #214 is confirmed', 'sent', '2025-11-07 00:33:39'),
(128, 228, 19, 'Your booking #228 is confirmed', 'sent', '2025-11-07 00:33:39'),
(129, 242, 19, 'Your booking #242 is confirmed', 'sent', '2025-11-07 00:33:39'),
(130, 257, 19, 'Your booking #257 is confirmed', 'sent', '2025-11-07 00:33:39'),
(131, 272, 19, 'Your booking #272 is confirmed', 'sent', '2025-11-07 00:33:39'),
(132, 287, 19, 'Your booking #287 is confirmed', 'sent', '2025-11-07 00:33:39'),
(133, 304, 19, 'Your booking #304 is confirmed', 'sent', '2025-11-07 00:33:39'),
(134, 318, 19, 'Your booking #318 is confirmed', 'sent', '2025-11-07 00:33:39'),
(135, 335, 19, 'Your booking #335 is confirmed', 'sent', '2025-11-07 00:33:39'),
(136, 353, 19, 'Your booking #353 is confirmed', 'sent', '2025-11-07 00:33:39'),
(137, 371, 19, 'Your booking #371 is confirmed', 'sent', '2025-11-07 00:33:39'),
(138, 388, 19, 'Your booking #388 is confirmed', 'sent', '2025-11-07 00:33:39'),
(139, 406, 19, 'Your booking #406 is confirmed', 'sent', '2025-11-07 00:33:39'),
(140, 386, 32, 'Your booking #386 is confirmed', 'sent', '2025-11-07 00:33:39'),
(141, 412, 32, 'Your booking #412 is confirmed', 'sent', '2025-11-07 00:33:39'),
(142, 27, 6, 'Your booking #27 is confirmed', 'sent', '2025-11-07 00:33:39'),
(143, 36, 6, 'Your booking #36 is confirmed', 'sent', '2025-11-07 00:33:39'),
(144, 48, 6, 'Your booking #48 is confirmed', 'sent', '2025-11-07 00:33:39'),
(145, 60, 6, 'Your booking #60 is confirmed', 'sent', '2025-11-07 00:33:39'),
(146, 74, 6, 'Your booking #74 is confirmed', 'sent', '2025-11-07 00:33:39'),
(147, 91, 6, 'Your booking #91 is confirmed', 'sent', '2025-11-07 00:33:39'),
(148, 110, 6, 'Your booking #110 is confirmed', 'sent', '2025-11-07 00:33:39'),
(149, 129, 6, 'Your booking #129 is confirmed', 'sent', '2025-11-07 00:33:39'),
(150, 151, 6, 'Your booking #151 is confirmed', 'sent', '2025-11-07 00:33:39'),
(151, 173, 6, 'Your booking #173 is confirmed', 'sent', '2025-11-07 00:33:39'),
(152, 198, 6, 'Your booking #198 is confirmed', 'sent', '2025-11-07 00:33:39'),
(153, 224, 6, 'Your booking #224 is confirmed', 'sent', '2025-11-07 00:33:39'),
(154, 254, 6, 'Your booking #254 is confirmed', 'sent', '2025-11-07 00:33:39'),
(155, 283, 6, 'Your booking #283 is confirmed', 'sent', '2025-11-07 00:33:39'),
(156, 314, 6, 'Your booking #314 is confirmed', 'sent', '2025-11-07 00:33:39'),
(157, 350, 6, 'Your booking #350 is confirmed', 'sent', '2025-11-07 00:33:39'),
(158, 384, 6, 'Your booking #384 is confirmed', 'sent', '2025-11-07 00:33:39'),
(159, 4, 2, 'Your booking #4 is confirmed', 'sent', '2025-11-07 00:33:39'),
(160, 6, 2, 'Your booking #6 is confirmed', 'sent', '2025-11-07 00:33:39'),
(161, 8, 2, 'Your booking #8 is confirmed', 'sent', '2025-11-07 00:33:39'),
(162, 11, 2, 'Your booking #11 is confirmed', 'sent', '2025-11-07 00:33:39'),
(163, 14, 2, 'Your booking #14 is confirmed', 'sent', '2025-11-07 00:33:39'),
(164, 18, 2, 'Your booking #18 is confirmed', 'sent', '2025-11-07 00:33:39'),
(165, 22, 2, 'Your booking #22 is confirmed', 'sent', '2025-11-07 00:33:39'),
(166, 26, 2, 'Your booking #26 is confirmed', 'sent', '2025-11-07 00:33:39'),
(167, 30, 2, 'Your booking #30 is confirmed', 'sent', '2025-11-07 00:33:39'),
(168, 35, 2, 'Your booking #35 is confirmed', 'sent', '2025-11-07 00:33:39'),
(169, 41, 2, 'Your booking #41 is confirmed', 'sent', '2025-11-07 00:33:39'),
(170, 47, 2, 'Your booking #47 is confirmed', 'sent', '2025-11-07 00:33:39'),
(171, 53, 2, 'Your booking #53 is confirmed', 'sent', '2025-11-07 00:33:39'),
(172, 59, 2, 'Your booking #59 is confirmed', 'sent', '2025-11-07 00:33:39'),
(173, 66, 2, 'Your booking #66 is confirmed', 'sent', '2025-11-07 00:33:39'),
(174, 73, 2, 'Your booking #73 is confirmed', 'sent', '2025-11-07 00:33:39'),
(175, 80, 2, 'Your booking #80 is confirmed', 'sent', '2025-11-07 00:33:39'),
(176, 90, 2, 'Your booking #90 is confirmed', 'sent', '2025-11-07 00:33:39'),
(177, 99, 2, 'Your booking #99 is confirmed', 'sent', '2025-11-07 00:33:39'),
(178, 109, 2, 'Your booking #109 is confirmed', 'sent', '2025-11-07 00:33:39'),
(179, 116, 2, 'Your booking #116 is confirmed', 'sent', '2025-11-07 00:33:39'),
(180, 128, 2, 'Your booking #128 is confirmed', 'sent', '2025-11-07 00:33:39'),
(181, 137, 2, 'Your booking #137 is confirmed', 'sent', '2025-11-07 00:33:39'),
(182, 149, 2, 'Your booking #149 is confirmed', 'sent', '2025-11-07 00:33:39'),
(183, 159, 2, 'Your booking #159 is confirmed', 'sent', '2025-11-07 00:33:39'),
(184, 172, 2, 'Your booking #172 is confirmed', 'sent', '2025-11-07 00:33:39'),
(185, 181, 2, 'Your booking #181 is confirmed', 'sent', '2025-11-07 00:33:39'),
(186, 196, 2, 'Your booking #196 is confirmed', 'sent', '2025-11-07 00:33:39'),
(187, 208, 2, 'Your booking #208 is confirmed', 'sent', '2025-11-07 00:33:39'),
(188, 223, 2, 'Your booking #223 is confirmed', 'sent', '2025-11-07 00:33:39'),
(189, 236, 2, 'Your booking #236 is confirmed', 'sent', '2025-11-07 00:33:39'),
(190, 253, 2, 'Your booking #253 is confirmed', 'sent', '2025-11-07 00:33:39'),
(191, 267, 2, 'Your booking #267 is confirmed', 'sent', '2025-11-07 00:33:39'),
(192, 281, 2, 'Your booking #281 is confirmed', 'sent', '2025-11-07 00:33:39'),
(193, 297, 2, 'Your booking #297 is confirmed', 'sent', '2025-11-07 00:33:39'),
(194, 313, 2, 'Your booking #313 is confirmed', 'sent', '2025-11-07 00:33:39'),
(195, 329, 2, 'Your booking #329 is confirmed', 'sent', '2025-11-07 00:33:39'),
(196, 348, 2, 'Your booking #348 is confirmed', 'sent', '2025-11-07 00:33:39'),
(197, 132, 17, 'Your booking #132 is confirmed', 'sent', '2025-11-07 00:33:39'),
(198, 215, 17, 'Your booking #215 is confirmed', 'sent', '2025-11-07 00:33:39'),
(199, 340, 17, 'Your booking #340 is confirmed', 'sent', '2025-11-07 00:33:39'),
(200, 34, 7, 'Your booking #34 is confirmed', 'sent', '2025-11-07 00:33:39'),
(201, 38, 7, 'Your booking #38 is confirmed', 'sent', '2025-11-07 00:33:39'),
(202, 43, 7, 'Your booking #43 is confirmed', 'sent', '2025-11-07 00:33:39'),
(203, 49, 7, 'Your booking #49 is confirmed', 'sent', '2025-11-07 00:33:39'),
(204, 55, 7, 'Your booking #55 is confirmed', 'sent', '2025-11-07 00:33:39'),
(205, 61, 7, 'Your booking #61 is confirmed', 'sent', '2025-11-07 00:33:39'),
(206, 69, 7, 'Your booking #69 is confirmed', 'sent', '2025-11-07 00:33:39'),
(207, 76, 7, 'Your booking #76 is confirmed', 'sent', '2025-11-07 00:33:39'),
(208, 86, 7, 'Your booking #86 is confirmed', 'sent', '2025-11-07 00:33:39'),
(209, 93, 7, 'Your booking #93 is confirmed', 'sent', '2025-11-07 00:33:39'),
(210, 103, 7, 'Your booking #103 is confirmed', 'sent', '2025-11-07 00:33:39'),
(211, 112, 7, 'Your booking #112 is confirmed', 'sent', '2025-11-07 00:33:39'),
(212, 123, 7, 'Your booking #123 is confirmed', 'sent', '2025-11-07 00:33:39'),
(213, 133, 7, 'Your booking #133 is confirmed', 'sent', '2025-11-07 00:33:39'),
(214, 143, 7, 'Your booking #143 is confirmed', 'sent', '2025-11-07 00:33:39'),
(215, 154, 7, 'Your booking #154 is confirmed', 'sent', '2025-11-07 00:33:39'),
(216, 166, 7, 'Your booking #166 is confirmed', 'sent', '2025-11-07 00:33:39'),
(217, 177, 7, 'Your booking #177 is confirmed', 'sent', '2025-11-07 00:33:39'),
(218, 188, 7, 'Your booking #188 is confirmed', 'sent', '2025-11-07 00:33:39'),
(219, 202, 7, 'Your booking #202 is confirmed', 'sent', '2025-11-07 00:33:39'),
(220, 216, 7, 'Your booking #216 is confirmed', 'sent', '2025-11-07 00:33:39'),
(221, 230, 7, 'Your booking #230 is confirmed', 'sent', '2025-11-07 00:33:39'),
(222, 244, 7, 'Your booking #244 is confirmed', 'sent', '2025-11-07 00:33:39'),
(223, 259, 7, 'Your booking #259 is confirmed', 'sent', '2025-11-07 00:33:39'),
(224, 273, 7, 'Your booking #273 is confirmed', 'sent', '2025-11-07 00:33:39'),
(225, 290, 7, 'Your booking #290 is confirmed', 'sent', '2025-11-07 00:33:39'),
(226, 305, 7, 'Your booking #305 is confirmed', 'sent', '2025-11-07 00:33:39'),
(227, 321, 7, 'Your booking #321 is confirmed', 'sent', '2025-11-07 00:33:39'),
(228, 337, 7, 'Your booking #337 is confirmed', 'sent', '2025-11-07 00:33:39'),
(229, 357, 7, 'Your booking #357 is confirmed', 'sent', '2025-11-07 00:33:39'),
(230, 373, 7, 'Your booking #373 is confirmed', 'sent', '2025-11-07 00:33:39'),
(231, 392, 7, 'Your booking #392 is confirmed', 'sent', '2025-11-07 00:33:39'),
(232, 341, 30, 'Your booking #341 is confirmed', 'sent', '2025-11-07 00:33:39'),
(233, 365, 30, 'Your booking #365 is confirmed', 'sent', '2025-11-07 00:33:39'),
(234, 383, 30, 'Your booking #383 is confirmed', 'sent', '2025-11-07 00:33:39'),
(235, 402, 30, 'Your booking #402 is confirmed', 'sent', '2025-11-07 00:33:39'),
(236, 416, 30, 'Your booking #416 is confirmed', 'sent', '2025-11-07 00:33:39'),
(237, 1, 1, 'Your booking #1 is confirmed', 'sent', '2025-11-07 00:33:39'),
(238, 2, 1, 'Your booking #2 is confirmed', 'sent', '2025-11-07 00:33:39'),
(239, 3, 1, 'Your booking #3 is confirmed', 'sent', '2025-11-07 00:33:39'),
(240, 5, 1, 'Your booking #5 is confirmed', 'sent', '2025-11-07 00:33:39'),
(241, 7, 1, 'Your booking #7 is confirmed', 'sent', '2025-11-07 00:33:39'),
(242, 10, 1, 'Your booking #10 is confirmed', 'sent', '2025-11-07 00:33:39'),
(243, 13, 1, 'Your booking #13 is confirmed', 'sent', '2025-11-07 00:33:39'),
(244, 16, 1, 'Your booking #16 is confirmed', 'sent', '2025-11-07 00:33:39'),
(245, 21, 1, 'Your booking #21 is confirmed', 'sent', '2025-11-07 00:33:39'),
(246, 24, 1, 'Your booking #24 is confirmed', 'sent', '2025-11-07 00:33:39'),
(247, 29, 1, 'Your booking #29 is confirmed', 'sent', '2025-11-07 00:33:39'),
(248, 33, 1, 'Your booking #33 is confirmed', 'sent', '2025-11-07 00:33:39'),
(249, 40, 1, 'Your booking #40 is confirmed', 'sent', '2025-11-07 00:33:39'),
(250, 46, 1, 'Your booking #46 is confirmed', 'sent', '2025-11-07 00:33:39'),
(251, 52, 1, 'Your booking #52 is confirmed', 'sent', '2025-11-07 00:33:39'),
(252, 58, 1, 'Your booking #58 is confirmed', 'sent', '2025-11-07 00:33:39'),
(253, 65, 1, 'Your booking #65 is confirmed', 'sent', '2025-11-07 00:33:39'),
(254, 72, 1, 'Your booking #72 is confirmed', 'sent', '2025-11-07 00:33:39'),
(255, 79, 1, 'Your booking #79 is confirmed', 'sent', '2025-11-07 00:33:39'),
(256, 89, 1, 'Your booking #89 is confirmed', 'sent', '2025-11-07 00:33:39'),
(257, 98, 1, 'Your booking #98 is confirmed', '', '2025-11-07 00:33:39'),
(258, 107, 1, 'Your booking #107 is confirmed', 'sent', '2025-11-07 00:33:39'),
(259, 115, 1, 'Your booking #115 is confirmed', 'sent', '2025-11-07 00:33:39'),
(260, 126, 1, 'Your booking #126 is confirmed', 'sent', '2025-11-07 00:33:39'),
(261, 136, 1, 'Your booking #136 is confirmed', 'sent', '2025-11-07 00:33:39'),
(262, 147, 1, 'Your booking #147 is confirmed', 'sent', '2025-11-07 00:33:39'),
(263, 158, 1, 'Your booking #158 is confirmed', 'sent', '2025-11-07 00:33:39'),
(264, 169, 1, 'Your booking #169 is confirmed', 'sent', '2025-11-07 00:33:39'),
(265, 180, 1, 'Your booking #180 is confirmed', 'sent', '2025-11-07 00:33:39'),
(266, 192, 1, 'Your booking #192 is confirmed', 'sent', '2025-11-07 00:33:39'),
(267, 206, 1, 'Your booking #206 is confirmed', 'sent', '2025-11-07 00:33:39'),
(268, 219, 1, 'Your booking #219 is confirmed', 'sent', '2025-11-07 00:33:39'),
(269, 234, 1, 'Your booking #234 is confirmed', 'sent', '2025-11-07 00:33:39'),
(270, 248, 1, 'Your booking #248 is confirmed', 'sent', '2025-11-07 00:33:39'),
(271, 264, 1, 'Your booking #264 is confirmed', 'sent', '2025-11-07 00:33:39'),
(272, 277, 1, 'Your booking #277 is confirmed', 'sent', '2025-11-07 00:33:39'),
(273, 295, 1, 'Your booking #295 is confirmed', 'sent', '2025-11-07 00:33:39'),
(274, 309, 1, 'Your booking #309 is confirmed', 'sent', '2025-11-07 00:33:39'),
(275, 326, 1, 'Your booking #326 is confirmed', 'sent', '2025-11-07 00:33:39'),
(276, 344, 1, 'Your booking #344 is confirmed', 'sent', '2025-11-07 00:33:39'),
(277, 361, 1, 'Your booking #361 is confirmed', 'sent', '2025-11-07 00:33:39'),
(278, 378, 1, 'Your booking #378 is confirmed', 'sent', '2025-11-07 00:33:39'),
(279, 397, 1, 'Your booking #397 is confirmed', 'sent', '2025-11-07 00:33:39'),
(280, 414, 1, 'Your booking #414 is confirmed', 'sent', '2025-11-07 00:33:39'),
(281, 44, 8, 'Your booking #44 is confirmed', 'sent', '2025-11-07 00:33:39'),
(282, 122, 8, 'Your booking #122 is confirmed', 'sent', '2025-11-07 00:33:39'),
(283, 282, 8, 'Your booking #282 is confirmed', 'sent', '2025-11-07 00:33:39'),
(284, 320, 29, 'Your booking #320 is confirmed', 'sent', '2025-11-07 00:33:39'),
(285, 355, 29, 'Your booking #355 is confirmed', 'sent', '2025-11-07 00:33:39'),
(286, 390, 29, 'Your booking #390 is confirmed', 'sent', '2025-11-07 00:33:39'),
(287, 407, 29, 'Your booking #407 is confirmed', 'sent', '2025-11-07 00:33:39'),
(288, 12, 4, 'Your booking #12 is confirmed', 'sent', '2025-11-07 00:33:39'),
(289, 17, 4, 'Your booking #17 is confirmed', 'sent', '2025-11-07 00:33:39'),
(290, 20, 4, 'Your booking #20 is confirmed', 'sent', '2025-11-07 00:33:39'),
(291, 25, 4, 'Your booking #25 is confirmed', 'sent', '2025-11-07 00:33:39'),
(292, 28, 4, 'Your booking #28 is confirmed', 'sent', '2025-11-07 00:33:39'),
(293, 32, 4, 'Your booking #32 is confirmed', 'sent', '2025-11-07 00:33:39'),
(294, 39, 4, 'Your booking #39 is confirmed', 'sent', '2025-11-07 00:33:39'),
(295, 45, 4, 'Your booking #45 is confirmed', 'sent', '2025-11-07 00:33:39'),
(296, 51, 4, 'Your booking #51 is confirmed', 'sent', '2025-11-07 00:33:39'),
(297, 57, 4, 'Your booking #57 is confirmed', 'sent', '2025-11-07 00:33:39'),
(298, 64, 4, 'Your booking #64 is confirmed', 'sent', '2025-11-07 00:33:39'),
(299, 71, 4, 'Your booking #71 is confirmed', 'sent', '2025-11-07 00:33:39'),
(300, 78, 4, 'Your booking #78 is confirmed', 'sent', '2025-11-07 00:33:39'),
(301, 88, 4, 'Your booking #88 is confirmed', 'sent', '2025-11-07 00:33:39'),
(302, 97, 4, 'Your booking #97 is confirmed', 'sent', '2025-11-07 00:33:39'),
(303, 106, 4, 'Your booking #106 is confirmed', 'sent', '2025-11-07 00:33:39'),
(304, 114, 4, 'Your booking #114 is confirmed', 'sent', '2025-11-07 00:33:39'),
(305, 125, 4, 'Your booking #125 is confirmed', 'sent', '2025-11-07 00:33:39'),
(306, 135, 4, 'Your booking #135 is confirmed', 'sent', '2025-11-07 00:33:39'),
(307, 146, 4, 'Your booking #146 is confirmed', 'sent', '2025-11-07 00:33:39'),
(308, 157, 4, 'Your booking #157 is confirmed', 'sent', '2025-11-07 00:33:39'),
(309, 168, 4, 'Your booking #168 is confirmed', 'sent', '2025-11-07 00:33:39'),
(310, 179, 4, 'Your booking #179 is confirmed', 'sent', '2025-11-07 00:33:39'),
(311, 191, 4, 'Your booking #191 is confirmed', 'sent', '2025-11-07 00:33:39'),
(312, 205, 4, 'Your booking #205 is confirmed', 'sent', '2025-11-07 00:33:39'),
(313, 218, 4, 'Your booking #218 is confirmed', 'sent', '2025-11-07 00:33:39'),
(314, 233, 4, 'Your booking #233 is confirmed', 'sent', '2025-11-07 00:33:39'),
(315, 247, 4, 'Your booking #247 is confirmed', 'sent', '2025-11-07 00:33:39'),
(316, 263, 4, 'Your booking #263 is confirmed', 'sent', '2025-11-07 00:33:39'),
(317, 276, 4, 'Your booking #276 is confirmed', 'sent', '2025-11-07 00:33:39'),
(318, 294, 4, 'Your booking #294 is confirmed', 'sent', '2025-11-07 00:33:39'),
(319, 308, 4, 'Your booking #308 is confirmed', 'sent', '2025-11-07 00:33:39'),
(320, 325, 4, 'Your booking #325 is confirmed', 'sent', '2025-11-07 00:33:39'),
(321, 343, 4, 'Your booking #343 is confirmed', 'sent', '2025-11-07 00:33:39'),
(322, 360, 4, 'Your booking #360 is confirmed', 'sent', '2025-11-07 00:33:39'),
(323, 377, 4, 'Your booking #377 is confirmed', 'sent', '2025-11-07 00:33:39'),
(324, 396, 4, 'Your booking #396 is confirmed', 'sent', '2025-11-07 00:33:39'),
(325, 95, 14, 'Your booking #95 is confirmed', 'sent', '2025-11-07 00:33:39'),
(326, 104, 14, 'Your booking #104 is confirmed', 'sent', '2025-11-07 00:33:39'),
(327, 155, 14, 'Your booking #155 is confirmed', 'sent', '2025-11-07 00:33:39'),
(328, 203, 14, 'Your booking #203 is confirmed', 'sent', '2025-11-07 00:33:39'),
(329, 261, 14, 'Your booking #261 is confirmed', 'sent', '2025-11-07 00:33:39'),
(330, 339, 14, 'Your booking #339 is confirmed', 'sent', '2025-11-07 00:33:39'),
(331, 56, 10, 'Your booking #56 is confirmed', 'sent', '2025-11-07 00:33:39'),
(332, 63, 10, 'Your booking #63 is confirmed', 'sent', '2025-11-07 00:33:39'),
(333, 70, 10, 'Your booking #70 is confirmed', 'sent', '2025-11-07 00:33:39'),
(334, 77, 10, 'Your booking #77 is confirmed', 'sent', '2025-11-07 00:33:39'),
(335, 87, 10, 'Your booking #87 is confirmed', 'sent', '2025-11-07 00:33:39'),
(336, 96, 10, 'Your booking #96 is confirmed', 'sent', '2025-11-07 00:33:39'),
(337, 105, 10, 'Your booking #105 is confirmed', 'sent', '2025-11-07 00:33:39'),
(338, 113, 10, 'Your booking #113 is confirmed', 'sent', '2025-11-07 00:33:39'),
(339, 124, 10, 'Your booking #124 is confirmed', 'sent', '2025-11-07 00:33:39'),
(340, 134, 10, 'Your booking #134 is confirmed', 'sent', '2025-11-07 00:33:39'),
(341, 145, 10, 'Your booking #145 is confirmed', 'sent', '2025-11-07 00:33:39'),
(342, 156, 10, 'Your booking #156 is confirmed', 'sent', '2025-11-07 00:33:39'),
(343, 167, 10, 'Your booking #167 is confirmed', 'sent', '2025-11-07 00:33:39'),
(344, 178, 10, 'Your booking #178 is confirmed', 'sent', '2025-11-07 00:33:39'),
(345, 190, 10, 'Your booking #190 is confirmed', 'sent', '2025-11-07 00:33:39'),
(346, 204, 10, 'Your booking #204 is confirmed', 'sent', '2025-11-07 00:33:39'),
(347, 217, 10, 'Your booking #217 is confirmed', 'sent', '2025-11-07 00:33:39'),
(348, 232, 10, 'Your booking #232 is confirmed', 'sent', '2025-11-07 00:33:39'),
(349, 246, 10, 'Your booking #246 is confirmed', 'sent', '2025-11-07 00:33:39'),
(350, 262, 10, 'Your booking #262 is confirmed', 'sent', '2025-11-07 00:33:39'),
(351, 275, 10, 'Your booking #275 is confirmed', 'sent', '2025-11-07 00:33:39'),
(352, 293, 10, 'Your booking #293 is confirmed', 'sent', '2025-11-07 00:33:39'),
(353, 307, 10, 'Your booking #307 is confirmed', 'sent', '2025-11-07 00:33:39'),
(354, 324, 10, 'Your booking #324 is confirmed', 'sent', '2025-11-07 00:33:39'),
(355, 342, 10, 'Your booking #342 is confirmed', 'sent', '2025-11-07 00:33:39'),
(356, 359, 10, 'Your booking #359 is confirmed', 'sent', '2025-11-07 00:33:39'),
(357, 376, 10, 'Your booking #376 is confirmed', 'sent', '2025-11-07 00:33:39'),
(358, 395, 10, 'Your booking #395 is confirmed', 'sent', '2025-11-07 00:33:39'),
(359, 199, 22, 'Your booking #199 is confirmed', 'sent', '2025-11-07 00:33:39'),
(360, 207, 22, 'Your booking #207 is confirmed', 'sent', '2025-11-07 00:33:39'),
(361, 222, 22, 'Your booking #222 is confirmed', 'sent', '2025-11-07 00:33:39'),
(362, 235, 22, 'Your booking #235 is confirmed', 'sent', '2025-11-07 00:33:39'),
(363, 251, 22, 'Your booking #251 is confirmed', 'sent', '2025-11-07 00:33:39'),
(364, 265, 22, 'Your booking #265 is confirmed', 'sent', '2025-11-07 00:33:39'),
(365, 280, 22, 'Your booking #280 is confirmed', 'sent', '2025-11-07 00:33:39'),
(366, 296, 22, 'Your booking #296 is confirmed', 'sent', '2025-11-07 00:33:39'),
(367, 312, 22, 'Your booking #312 is confirmed', 'sent', '2025-11-07 00:33:39'),
(368, 327, 22, 'Your booking #327 is confirmed', 'sent', '2025-11-07 00:33:39'),
(369, 347, 22, 'Your booking #347 is confirmed', 'sent', '2025-11-07 00:33:39'),
(370, 362, 22, 'Your booking #362 is confirmed', 'sent', '2025-11-07 00:33:39'),
(371, 381, 22, 'Your booking #381 is confirmed', 'sent', '2025-11-07 00:33:39'),
(372, 398, 22, 'Your booking #398 is confirmed', 'sent', '2025-11-07 00:33:39'),
(373, 170, 20, 'Your booking #170 is confirmed', 'sent', '2025-11-07 00:33:39'),
(374, 193, 20, 'Your booking #193 is confirmed', 'sent', '2025-11-07 00:33:39'),
(375, 220, 20, 'Your booking #220 is confirmed', 'sent', '2025-11-07 00:33:39'),
(376, 249, 20, 'Your booking #249 is confirmed', 'sent', '2025-11-07 00:33:39'),
(377, 278, 20, 'Your booking #278 is confirmed', 'sent', '2025-11-07 00:33:39'),
(378, 310, 20, 'Your booking #310 is confirmed', 'sent', '2025-11-07 00:33:39'),
(379, 345, 20, 'Your booking #345 is confirmed', 'sent', '2025-11-07 00:33:39'),
(380, 379, 20, 'Your booking #379 is confirmed', 'sent', '2025-11-07 00:33:39'),
(381, 415, 20, 'Your booking #415 is confirmed', 'sent', '2025-11-07 00:33:39'),
(382, 19, 5, 'Your booking #19 is confirmed', 'sent', '2025-11-07 00:33:39'),
(383, 37, 5, 'Your booking #37 is confirmed', 'sent', '2025-11-07 00:33:39'),
(384, 62, 5, 'Your booking #62 is confirmed', 'sent', '2025-11-07 00:33:39'),
(385, 94, 5, 'Your booking #94 is confirmed', 'sent', '2025-11-07 00:33:39'),
(386, 144, 5, 'Your booking #144 is confirmed', 'sent', '2025-11-07 00:33:39'),
(387, 189, 5, 'Your booking #189 is confirmed', 'sent', '2025-11-07 00:33:39'),
(388, 245, 5, 'Your booking #245 is confirmed', 'sent', '2025-11-07 00:33:39'),
(389, 323, 5, 'Your booking #323 is confirmed', 'sent', '2025-11-07 00:33:39'),
(390, 394, 5, 'Your booking #394 is confirmed', 'sent', '2025-11-07 00:33:39'),
(391, 243, 25, 'Your booking #243 is confirmed', 'sent', '2025-11-07 00:33:39'),
(392, 260, 25, 'Your booking #260 is confirmed', 'sent', '2025-11-07 00:33:39'),
(393, 274, 25, 'Your booking #274 is confirmed', 'sent', '2025-11-07 00:33:39'),
(394, 291, 25, 'Your booking #291 is confirmed', 'sent', '2025-11-07 00:33:39'),
(395, 306, 25, 'Your booking #306 is confirmed', 'sent', '2025-11-07 00:33:39'),
(396, 322, 25, 'Your booking #322 is confirmed', 'sent', '2025-11-07 00:33:39'),
(397, 338, 25, 'Your booking #338 is confirmed', 'sent', '2025-11-07 00:33:39'),
(398, 358, 25, 'Your booking #358 is confirmed', 'sent', '2025-11-07 00:33:39'),
(399, 374, 25, 'Your booking #374 is confirmed', 'sent', '2025-11-07 00:33:39'),
(400, 393, 25, 'Your booking #393 is confirmed', 'sent', '2025-11-07 00:33:39'),
(401, 410, 25, 'Your booking #410 is confirmed', 'sent', '2025-11-07 00:33:39'),
(402, 408, 34, 'Your booking #408 is confirmed', 'sent', '2025-11-07 00:33:39'),
(403, 85, 13, 'Your booking #85 is confirmed', 'sent', '2025-11-07 00:33:39'),
(404, 239, 13, 'Your booking #239 is confirmed', 'sent', '2025-11-07 00:33:39'),
(405, 108, 15, 'Your booking #108 is confirmed', 'sent', '2025-11-07 00:33:39'),
(406, 127, 15, 'Your booking #127 is confirmed', 'sent', '2025-11-07 00:33:39'),
(407, 148, 15, 'Your booking #148 is confirmed', 'sent', '2025-11-07 00:33:39'),
(408, 171, 15, 'Your booking #171 is confirmed', 'sent', '2025-11-07 00:33:39'),
(409, 194, 15, 'Your booking #194 is confirmed', 'sent', '2025-11-07 00:33:39'),
(410, 221, 15, 'Your booking #221 is confirmed', 'sent', '2025-11-07 00:33:39'),
(411, 250, 15, 'Your booking #250 is confirmed', 'sent', '2025-11-07 00:33:39'),
(412, 279, 15, 'Your booking #279 is confirmed', 'sent', '2025-11-07 00:33:39'),
(413, 311, 15, 'Your booking #311 is confirmed', 'sent', '2025-11-07 00:33:39'),
(414, 346, 15, 'Your booking #346 is confirmed', 'sent', '2025-11-07 00:33:39'),
(415, 380, 15, 'Your booking #380 is confirmed', 'sent', '2025-11-07 00:33:39'),
(416, 366, 31, 'Your booking #366 is confirmed', 'sent', '2025-11-07 00:33:39'),
(417, 382, 31, 'Your booking #382 is confirmed', 'sent', '2025-11-07 00:33:39'),
(418, 417, 31, 'Your booking #417 is confirmed', 'sent', '2025-11-07 00:33:39'),
(419, 2, 1, 'Your booking #2 has been confirmed and is now processing.', 'sent', '2025-11-28 13:21:49'),
(420, 5, 1, 'Payment for booking #5 has been confirmed. Your laundry is now in progress.', '', '2025-11-28 14:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `on_booking_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash','Card') DEFAULT 'Cash',
  `payment_status` enum('Pending','Paid','Refunded') DEFAULT 'Pending',
  `payment_date` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `customer_id`, `booking_id`, `on_booking_id`, `amount`, `payment_method`, `payment_status`, `payment_date`, `remarks`) VALUES
(1, 26, 266, NULL, 604.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(2, 26, 349, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(3, 26, 413, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(4, 3, 9, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(5, 3, 15, NULL, 240.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(6, 3, 23, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(7, 3, 31, NULL, 642.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(8, 3, 42, NULL, 551.90, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(9, 3, 54, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(10, 3, 67, NULL, 348.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(11, 3, 81, NULL, 503.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(12, 3, 100, NULL, 254.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(13, 3, 117, NULL, 599.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(14, 3, 138, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(15, 3, 160, NULL, 368.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(16, 3, 182, NULL, 646.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(17, 3, 209, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(18, 3, 237, NULL, 359.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(19, 3, 268, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(20, 3, 298, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(21, 3, 330, NULL, 202.29, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(22, 3, 363, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(23, 21, 184, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(24, 21, 231, NULL, 378.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(25, 21, 292, NULL, 407.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(26, 21, 375, NULL, 204.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(27, 21, 411, NULL, 566.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(28, 12, 75, NULL, 200.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(29, 12, 84, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(30, 12, 92, NULL, 543.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(31, 12, 102, NULL, 409.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(32, 12, 111, NULL, 451.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(33, 12, 120, NULL, 439.29, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(34, 12, 131, NULL, 608.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(35, 12, 141, NULL, 438.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(36, 12, 152, NULL, 446.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(37, 12, 163, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(38, 12, 175, NULL, 203.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(39, 12, 186, NULL, 553.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(40, 12, 201, NULL, 451.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(41, 12, 212, NULL, 625.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(42, 12, 227, NULL, 537.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(43, 12, 241, NULL, 512.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(44, 12, 256, NULL, 537.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(45, 12, 271, NULL, 434.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(46, 12, 286, NULL, 374.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(47, 12, 302, NULL, 480.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(48, 12, 317, NULL, 632.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(49, 12, 334, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(50, 12, 352, NULL, 380.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(51, 12, 370, NULL, 236.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(52, 12, 387, NULL, 485.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(53, 12, 404, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(54, 23, 213, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(55, 23, 367, NULL, 388.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(56, 16, 119, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(57, 16, 130, NULL, 266.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(58, 16, 140, NULL, 301.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(59, 16, 150, NULL, 389.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(60, 16, 162, NULL, 496.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(61, 16, 174, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(62, 16, 185, NULL, 323.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(63, 16, 197, NULL, 648.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(64, 16, 211, NULL, 586.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(65, 16, 225, NULL, 239.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(66, 16, 240, NULL, 237.29, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(67, 16, 255, NULL, 248.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(68, 16, 270, NULL, 198.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(69, 16, 284, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(70, 16, 300, NULL, 271.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(71, 16, 315, NULL, 267.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(72, 16, 332, NULL, 404.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(73, 16, 351, NULL, 550.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(74, 16, 368, NULL, 515.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(75, 16, 385, NULL, 222.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(76, 16, 403, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(77, 9, 50, NULL, 288.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(78, 9, 83, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(79, 9, 121, NULL, 613.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(80, 9, 165, NULL, 221.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(81, 9, 226, NULL, 510.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(82, 9, 289, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(83, 9, 369, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(84, 33, 401, NULL, 560.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(85, 27, 285, NULL, 223.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(86, 27, 301, NULL, 663.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(87, 27, 316, NULL, 417.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(88, 27, 333, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(89, 27, 336, NULL, 497.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(90, 27, 356, NULL, 687.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(91, 27, 372, NULL, 586.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(92, 27, 391, NULL, 510.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(93, 35, 418, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(94, 28, 303, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(95, 28, 409, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(96, 11, 68, NULL, 515.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(97, 11, 82, NULL, 535.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(98, 11, 101, NULL, 542.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(99, 11, 118, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(100, 11, 139, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(101, 11, 161, NULL, 487.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(102, 11, 183, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(103, 11, 210, NULL, 357.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(104, 11, 238, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(105, 11, 269, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(106, 11, 299, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(107, 11, 331, NULL, 393.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(108, 11, 364, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(109, 11, 400, NULL, 250.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(110, 24, 229, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(111, 24, 258, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(112, 24, 288, NULL, 465.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(113, 24, 319, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(114, 24, 354, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(115, 24, 389, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(116, 24, 405, NULL, 267.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(117, 18, 142, NULL, 482.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(118, 18, 195, NULL, 235.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(119, 18, 252, NULL, 575.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(120, 18, 328, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(121, 18, 399, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(122, 19, 153, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(123, 19, 164, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(124, 19, 176, NULL, 466.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(125, 19, 187, NULL, 338.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(126, 19, 200, NULL, 264.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(127, 19, 214, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(128, 19, 228, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(129, 19, 242, NULL, 435.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(130, 19, 257, NULL, 498.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(131, 19, 272, NULL, 623.90, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(132, 19, 287, NULL, 380.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(133, 19, 304, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(134, 19, 318, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(135, 19, 335, NULL, 253.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(136, 19, 353, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(137, 19, 371, NULL, 637.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(138, 19, 388, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(139, 19, 406, NULL, 682.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(140, 32, 386, NULL, 584.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(141, 32, 412, NULL, 460.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(142, 6, 27, NULL, 391.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(143, 6, 36, NULL, 513.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(144, 6, 48, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(145, 6, 60, NULL, 224.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(146, 6, 74, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(147, 6, 91, NULL, 514.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(148, 6, 110, NULL, 448.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(149, 6, 129, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(150, 6, 151, NULL, 663.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(151, 6, 173, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(152, 6, 198, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(153, 6, 224, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(154, 6, 254, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(155, 6, 283, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(156, 6, 314, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(157, 6, 350, NULL, 488.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(158, 6, 384, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(159, 2, 4, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(160, 2, 6, NULL, 421.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(161, 2, 8, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(162, 2, 11, NULL, 576.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(163, 2, 14, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(164, 2, 18, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(165, 2, 22, NULL, 533.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(166, 2, 26, NULL, 488.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(167, 2, 30, NULL, 276.29, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(168, 2, 35, NULL, 249.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(169, 2, 41, NULL, 672.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(170, 2, 47, NULL, 465.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(171, 2, 53, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(172, 2, 59, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(173, 2, 66, NULL, 646.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(174, 2, 73, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(175, 2, 80, NULL, 490.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(176, 2, 90, NULL, 242.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(177, 2, 99, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(178, 2, 109, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(179, 2, 116, NULL, 739.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(180, 2, 128, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(181, 2, 137, NULL, 382.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(182, 2, 149, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(183, 2, 159, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(184, 2, 172, NULL, 577.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(185, 2, 181, NULL, 672.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(186, 2, 196, NULL, 413.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(187, 2, 208, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(188, 2, 223, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(189, 2, 236, NULL, 578.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(190, 2, 253, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(191, 2, 267, NULL, 503.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(192, 2, 281, NULL, 456.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(193, 2, 297, NULL, 572.90, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(194, 2, 313, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(195, 2, 329, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(196, 2, 348, NULL, 465.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(197, 17, 132, NULL, 449.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(198, 17, 215, NULL, 320.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(199, 17, 340, NULL, 253.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(200, 7, 34, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(201, 7, 38, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(202, 7, 43, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(203, 7, 49, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(204, 7, 55, NULL, 378.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(205, 7, 61, NULL, 382.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(206, 7, 69, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(207, 7, 76, NULL, 564.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(208, 7, 86, NULL, 444.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(209, 7, 93, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(210, 7, 103, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(211, 7, 112, NULL, 516.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(212, 7, 123, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(213, 7, 133, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(214, 7, 143, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(215, 7, 154, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(216, 7, 166, NULL, 617.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(217, 7, 177, NULL, 361.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(218, 7, 188, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(219, 7, 202, NULL, 339.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(220, 7, 216, NULL, 553.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(221, 7, 230, NULL, 311.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(222, 7, 244, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(223, 7, 259, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(224, 7, 273, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(225, 7, 290, NULL, 269.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(226, 7, 305, NULL, 262.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(227, 7, 321, NULL, 604.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(228, 7, 337, NULL, 443.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(229, 7, 357, NULL, 422.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(230, 7, 373, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(231, 7, 392, NULL, 460.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(232, 30, 341, NULL, 583.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(233, 30, 365, NULL, 312.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(234, 30, 383, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(235, 30, 402, NULL, 526.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(236, 30, 416, NULL, 604.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(237, 1, 1, NULL, 638.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(238, 1, 2, NULL, 536.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(239, 1, 3, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(240, 1, 5, NULL, 465.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(241, 1, 7, NULL, 529.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(242, 1, 10, NULL, 212.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(243, 1, 13, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(244, 1, 16, NULL, 557.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(245, 1, 21, NULL, 413.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(246, 1, 24, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(247, 1, 29, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(248, 1, 33, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(249, 1, 40, NULL, 432.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(250, 1, 46, NULL, 684.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(251, 1, 52, NULL, 499.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(252, 1, 58, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(253, 1, 65, NULL, 307.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(254, 1, 72, NULL, 545.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(255, 1, 79, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(256, 1, 89, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(257, 1, 98, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(258, 1, 107, NULL, 436.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(259, 1, 115, NULL, 215.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(260, 1, 126, NULL, 412.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(261, 1, 136, NULL, 402.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(262, 1, 147, NULL, 430.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(263, 1, 158, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(264, 1, 169, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(265, 1, 180, NULL, 258.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(266, 1, 192, NULL, 511.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(267, 1, 206, NULL, 420.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(268, 1, 219, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(269, 1, 234, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(270, 1, 248, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(271, 1, 264, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(272, 1, 277, NULL, 411.90, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(273, 1, 295, NULL, 254.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(274, 1, 309, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(275, 1, 326, NULL, 610.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(276, 1, 344, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(277, 1, 361, NULL, 598.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(278, 1, 378, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(279, 1, 397, NULL, 472.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(280, 1, 414, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(281, 8, 44, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(282, 8, 122, NULL, 513.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(283, 8, 282, NULL, 430.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(284, 29, 320, NULL, 284.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(285, 29, 355, NULL, 210.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(286, 29, 390, NULL, 248.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(287, 29, 407, NULL, 456.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(288, 4, 12, NULL, 552.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(289, 4, 17, NULL, 382.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(290, 4, 20, NULL, 342.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(291, 4, 25, NULL, 274.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(292, 4, 28, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(293, 4, 32, NULL, 487.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(294, 4, 39, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(295, 4, 45, NULL, 267.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(296, 4, 51, NULL, 508.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(297, 4, 57, NULL, 424.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(298, 4, 64, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(299, 4, 71, NULL, 432.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(300, 4, 78, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(301, 4, 88, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(302, 4, 97, NULL, 486.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(303, 4, 106, NULL, 516.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(304, 4, 114, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(305, 4, 125, NULL, 307.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(306, 4, 135, NULL, 257.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(307, 4, 146, NULL, 499.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(308, 4, 157, NULL, 573.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(309, 4, 168, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(310, 4, 179, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(311, 4, 191, NULL, 552.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(312, 4, 205, NULL, 213.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(313, 4, 218, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(314, 4, 233, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(315, 4, 247, NULL, 463.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(316, 4, 263, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(317, 4, 276, NULL, 540.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(318, 4, 294, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(319, 4, 308, NULL, 515.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(320, 4, 325, NULL, 203.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(321, 4, 343, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(322, 4, 360, NULL, 277.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(323, 4, 377, NULL, 551.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(324, 4, 396, NULL, 633.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(325, 14, 95, NULL, 475.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(326, 14, 104, NULL, 315.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(327, 14, 155, NULL, 282.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(328, 14, 203, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(329, 14, 261, NULL, 498.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(330, 14, 339, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(331, 10, 56, NULL, 529.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(332, 10, 63, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(333, 10, 70, NULL, 263.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(334, 10, 77, NULL, 526.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(335, 10, 87, NULL, 458.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(336, 10, 96, NULL, 635.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(337, 10, 105, NULL, 377.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(338, 10, 113, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(339, 10, 124, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(340, 10, 134, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(341, 10, 145, NULL, 369.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(342, 10, 156, NULL, 529.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(343, 10, 167, NULL, 625.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(344, 10, 178, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(345, 10, 190, NULL, 263.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(346, 10, 204, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(347, 10, 217, NULL, 591.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(348, 10, 232, NULL, 545.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(349, 10, 246, NULL, 386.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(350, 10, 262, NULL, 605.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(351, 10, 275, NULL, 275.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(352, 10, 293, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(353, 10, 307, NULL, 358.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(354, 10, 324, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(355, 10, 342, NULL, 591.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(356, 10, 359, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(357, 10, 376, NULL, 515.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(358, 10, 395, NULL, 253.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(359, 22, 199, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(360, 22, 207, NULL, 409.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(361, 22, 222, NULL, 559.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(362, 22, 235, NULL, 253.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(363, 22, 251, NULL, 504.80, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(364, 22, 265, NULL, 383.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(365, 22, 280, NULL, 434.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(366, 22, 296, NULL, 691.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(367, 22, 312, NULL, 548.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(368, 22, 327, NULL, 500.10, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(369, 22, 347, NULL, 478.70, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(370, 22, 362, NULL, 419.90, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(371, 22, 381, NULL, 379.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(372, 22, 398, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(373, 20, 170, NULL, 277.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(374, 20, 193, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(375, 20, 220, NULL, 364.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(376, 20, 249, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(377, 20, 278, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(378, 20, 310, NULL, 228.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(379, 20, 345, NULL, 253.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(380, 20, 379, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(381, 20, 415, NULL, 259.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(382, 5, 19, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(383, 5, 37, NULL, 547.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(384, 5, 62, NULL, 547.30, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(385, 5, 94, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(386, 5, 144, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(387, 5, 189, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(388, 5, 245, NULL, 294.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(389, 5, 323, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(390, 5, 394, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(391, 25, 243, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(392, 25, 260, NULL, 439.57, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(393, 25, 274, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(394, 25, 291, NULL, 508.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(395, 25, 306, NULL, 547.60, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(396, 25, 322, NULL, 537.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(397, 25, 338, NULL, 500.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(398, 25, 358, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(399, 25, 374, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(400, 25, 393, NULL, 450.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(401, 25, 410, NULL, 319.14, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(402, 34, 408, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(403, 13, 85, NULL, 247.71, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(404, 13, 239, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(405, 15, 108, NULL, 465.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(406, 15, 127, NULL, 439.90, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(407, 15, 148, NULL, 265.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(408, 15, 171, NULL, 628.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(409, 15, 194, NULL, 300.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(410, 15, 221, NULL, 495.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(411, 15, 250, NULL, 354.86, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(412, 15, 279, NULL, 250.00, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(413, 15, 311, NULL, 739.20, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(414, 15, 346, NULL, 464.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(415, 15, 380, NULL, 330.43, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(416, 31, 366, NULL, 412.40, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(417, 31, 382, NULL, 501.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(418, 31, 417, NULL, 424.50, 'Cash', 'Paid', '2025-11-07 00:26:35', NULL),
(419, 2, 419, NULL, 0.00, 'Cash', 'Pending', '2025-11-11 17:40:46', NULL),
(420, 2, 420, NULL, 210.00, 'Cash', 'Pending', '2025-11-11 17:40:46', NULL),
(421, 60, 421, NULL, 0.00, 'Cash', 'Pending', '2025-11-12 12:47:56', NULL),
(422, 60, 422, NULL, 435.00, 'Cash', 'Pending', '2025-11-12 12:47:56', NULL),
(423, 61, 423, NULL, 0.00, 'Cash', 'Pending', '2025-11-12 12:49:24', NULL),
(424, 61, 424, NULL, 80.00, 'Cash', 'Pending', '2025-11-12 12:49:24', NULL),
(425, 62, 425, NULL, 0.00, 'Cash', 'Pending', '2025-11-12 12:51:51', NULL),
(426, 62, 426, NULL, 230.00, 'Cash', 'Pending', '2025-11-12 12:51:51', NULL),
(427, 63, 427, NULL, 0.00, 'Cash', 'Pending', '2025-11-25 03:47:41', NULL),
(428, 63, 428, NULL, 200.00, 'Cash', 'Pending', '2025-11-25 03:47:41', NULL),
(429, 65, 429, NULL, 0.00, 'Cash', 'Pending', '2025-11-29 14:01:13', NULL),
(430, 65, 430, NULL, 225.00, 'Cash', 'Pending', '2025-11-29 14:01:13', NULL),
(431, 1, 431, NULL, 0.00, 'Cash', 'Pending', '2025-11-29 14:56:00', NULL),
(432, 1, 432, NULL, 0.00, 'Cash', 'Pending', '2025-11-29 14:56:00', NULL),
(433, 2, 433, NULL, 0.00, 'Cash', 'Pending', '2025-11-29 14:56:00', NULL),
(434, 61, 434, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 18:32:08', NULL),
(435, 61, 435, NULL, 210.00, 'Cash', 'Pending', '2026-04-19 18:32:08', NULL),
(436, 61, 436, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 18:32:28', NULL),
(437, 61, 437, NULL, 210.00, 'Cash', 'Pending', '2026-04-19 18:32:28', NULL),
(438, 61, 438, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 18:37:00', NULL),
(439, 61, 439, NULL, 80.00, 'Cash', 'Pending', '2026-04-19 18:37:00', NULL),
(442, 61, 442, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 18:49:45', NULL),
(443, 61, 443, NULL, 200.00, 'Cash', 'Pending', '2026-04-19 18:49:45', NULL),
(444, 61, 444, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 18:51:31', NULL),
(445, 61, 445, NULL, 200.00, 'Cash', 'Pending', '2026-04-19 18:51:31', NULL),
(446, 61, 446, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 18:53:52', NULL),
(447, 61, 447, NULL, 200.00, 'Cash', 'Pending', '2026-04-19 18:53:52', NULL),
(448, 61, 448, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 19:25:57', NULL),
(449, 61, 449, NULL, 210.00, 'Cash', 'Pending', '2026-04-19 19:25:57', NULL),
(450, 36, 450, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 19:48:58', NULL),
(451, 36, 451, NULL, 220.00, 'Cash', 'Pending', '2026-04-19 19:48:58', NULL),
(452, 38, 452, NULL, 0.00, 'Cash', 'Pending', '2026-04-19 21:14:22', NULL),
(453, 38, 453, NULL, 210.00, 'Cash', 'Pending', '2026-04-19 21:14:22', NULL),
(454, 61, 454, NULL, 0.00, 'Cash', 'Pending', '2026-05-28 05:14:29', NULL),
(455, 61, 455, NULL, 425.00, 'Cash', 'Pending', '2026-05-28 05:14:29', NULL);

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `after_payment_update` AFTER UPDATE ON `payments` FOR EACH ROW BEGIN
  IF NEW.payment_status = 'Paid' THEN
    UPDATE booking
    SET status = 'Confirmed'
    WHERE Booking_ID = NEW.booking_id;

    UPDATE transaction
    SET payment_status = 'Paid'
    WHERE booking_id = NEW.booking_id;

    INSERT INTO financial_records (date, description, category, type, amount)
    VALUES (CURDATE(), CONCAT('Payment received for Booking #', NEW.booking_id), 'Other', 'Revenue', NEW.amount);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sync_booking_status_after_payment` AFTER UPDATE ON `payments` FOR EACH ROW BEGIN
    -- Only run if payment_status actually changed
    IF NEW.payment_status <> OLD.payment_status THEN
        
        -- If Paid → Confirm booking
        IF NEW.payment_status = 'Paid' THEN
            UPDATE booking
            SET status = 'Confirmed'
            WHERE Booking_ID = NEW.booking_id;
        END IF;

        -- If Refunded → Cancel booking
        IF NEW.payment_status = 'Refunded' THEN
            UPDATE booking
            SET status = 'Cancelled'
            WHERE Booking_ID = NEW.booking_id;
        END IF;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payments_online`
--

CREATE TABLE `payments_online` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash') NOT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `reference_number` varchar(50) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_proof` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments_online`
--

INSERT INTO `payments_online` (`payment_id`, `booking_id`, `amount`, `payment_method`, `payment_status`, `reference_number`, `payment_date`, `payment_proof`, `notes`) VALUES
(1, 1, 310.00, 'GCash', 'Pending', NULL, '2025-11-28 05:01:43', NULL, NULL),
(2, 1, 310.00, 'Cash', 'Paid', '3130 845 137565', '2025-11-28 05:02:25', 'payment_69292ce1d6032.pdf', NULL),
(3, 2, 435.00, 'GCash', 'Pending', NULL, '2025-11-28 05:21:49', NULL, NULL),
(4, 2, 435.00, 'Cash', 'Paid', '1270 631 963020', '2025-11-28 05:22:10', 'payment_6929318267072.pdf', NULL),
(5, 3, 235.00, 'GCash', 'Pending', NULL, '2025-11-28 06:01:23', NULL, NULL),
(6, 3, 235.00, 'Cash', 'Paid', '5789 476 495680', '2025-11-28 06:01:33', 'payment_69293abd8215d.pdf', NULL),
(7, 4, 435.00, 'GCash', 'Pending', NULL, '2025-11-28 06:09:08', NULL, NULL),
(8, 4, 435.00, 'Cash', 'Paid', '3739 415 772372', '2025-11-28 06:09:17', 'payment_69293c8def44e.pdf', NULL),
(9, 5, 445.00, 'GCash', 'Pending', NULL, '2025-11-28 06:14:55', NULL, NULL),
(10, 5, 445.00, 'GCash', 'Paid', '2589 774 898016', '2025-11-28 06:15:05', 'gcash_69293de9e3957.pdf', NULL),
(11, 6, 445.00, 'GCash', 'Pending', NULL, '2025-11-28 06:18:42', NULL, NULL),
(12, 7, 245.00, 'GCash', 'Pending', NULL, '2025-11-28 06:21:56', NULL, NULL),
(13, 8, 245.00, 'GCash', 'Pending', NULL, '2025-11-28 06:23:02', NULL, NULL),
(14, 9, 245.00, 'GCash', 'Pending', NULL, '2025-11-28 06:23:07', NULL, NULL),
(15, 10, 235.00, 'GCash', 'Pending', NULL, '2025-11-28 07:05:23', NULL, NULL),
(16, 10, 235.00, 'Cash', 'Paid', '9178 657 966213', '2025-11-28 07:05:42', 'payment_692949c6d15c7.pdf', NULL);

--
-- Triggers `payments_online`
--
DELIMITER $$
CREATE TRIGGER `after_payment_online_paid` AFTER UPDATE ON `payments_online` FOR EACH ROW BEGIN
    IF NEW.payment_status = 'Paid' AND OLD.payment_status <> 'Paid' THEN
        UPDATE booking_online
        SET payment_status = 'Paid'
        WHERE id = NEW.booking_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sync_online_booking_after_payment` AFTER UPDATE ON `payments_online` FOR EACH ROW BEGIN
    IF NEW.payment_status <> OLD.payment_status THEN

        IF NEW.payment_status = 'Paid' THEN
            UPDATE booking_online
            SET payment_status = 'Paid',
                status = 'Processing'
            WHERE id = NEW.booking_id;
        END IF;

    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products_inventory`
--

CREATE TABLE `products_inventory` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'Misc',
  `unit` varchar(20) DEFAULT 'pcs',
  `stock` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_value` decimal(12,2) GENERATED ALWAYS AS (`stock` * `unit_cost`) STORED,
  `date_added` date NOT NULL DEFAULT curdate(),
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products_inventory`
--

INSERT INTO `products_inventory` (`id`, `product_name`, `category`, `unit`, `stock`, `unit_cost`, `date_added`, `remarks`) VALUES
(1, 'Fabcon 1 Gallon', 'Supplies', 'gallon', 4, 1875.00, '2025-11-06', 'Monthly supply (4 gl = ₱7,500)'),
(2, 'Detergent 1 Gallon', 'Supplies', 'gallon', 4, 1875.00, '2025-11-06', 'Monthly supply (4 gl = ₱7,500)'),
(3, 'Gas', 'Utilities', 'kg', 50, 71.40, '2025-11-06', '50kg total ₱3,570'),
(4, 'Water', 'Utilities', 'usage', 1, 2000.00, '2025-11-06', 'Monthly bill range ₱1,800–2,000'),
(5, 'Electricity', 'Utilities', 'usage', 1, 2500.00, '2025-11-06', 'Monthly bill range ₱2,300–2,500');

--
-- Triggers `products_inventory`
--
DELIMITER $$
CREATE TRIGGER `after_inventory_deduction` AFTER UPDATE ON `products_inventory` FOR EACH ROW BEGIN
  IF NEW.stock < OLD.stock THEN
    INSERT INTO financial_records (date, description, category, type, amount)
    VALUES (
      CURDATE(),
      CONCAT('Used ', ABS(NEW.stock - OLD.stock), ' pcs of ', NEW.product_name),
      'Supplies',
      'Expense',
      (ABS(NEW.stock - OLD.stock) * NEW.unit_cost)
    );
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_products_inventory_update` BEFORE UPDATE ON `products_inventory` FOR EACH ROW BEGIN
  DECLARE action_type ENUM('Added','Updated','Deducted');

  IF NEW.stock > OLD.stock THEN
    SET action_type = 'Added';
  ELSEIF NEW.stock < OLD.stock THEN
    SET action_type = 'Deducted';
  ELSE
    SET action_type = 'Updated';
  END IF;

  INSERT INTO inventory_logs (
    inventory_type, item_name, action, quantity, previous_quantity, new_quantity, remarks
  )
  VALUES (
    'product',
    NEW.product_name,
    action_type,
    ABS(NEW.stock - OLD.stock),
    OLD.stock,
    NEW.stock,
    CONCAT('Product stock ', action_type)
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `po_id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('Draft','Ordered','Received','Cancelled') DEFAULT 'Draft',
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`po_id`, `po_number`, `supplier_id`, `order_date`, `expected_delivery_date`, `delivery_date`, `status`, `total_amount`, `notes`, `created_at`, `updated_at`) VALUES
(4, 'PO-2025-0010', 4, '2026-05-27', '2026-05-28', NULL, 'Draft', 625.00, '', '2026-05-28 05:08:51', '2026-05-28 05:08:51');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `po_item_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,
  `received_quantity` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`po_item_id`, `po_id`, `item_id`, `quantity`, `unit_cost`, `received_quantity`, `notes`) VALUES
(3, 4, 1, 5.00, 125.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `Schedule_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `pick_deliver` varchar(50) DEFAULT NULL,
  `drop_off_time` varchar(50) DEFAULT NULL,
  `service` varchar(50) DEFAULT NULL,
  `add_ons` varchar(50) DEFAULT NULL,
  `admin_confirmation` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `payment_proof` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`Schedule_ID`, `Customer_ID`, `date`, `time`, `pick_deliver`, `drop_off_time`, `service`, `add_ons`, `admin_confirmation`, `payment_proof`) VALUES
(1, 1, '2024-12-08', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(2, 1, '2024-12-14', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(3, 1, '2024-12-21', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(4, 2, '2024-12-22', '10:45:00', 'Delivery', '1:00 PM - 3:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(5, 1, '2024-12-28', '07:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(6, 2, '2024-12-29', '09:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(7, 1, '2025-01-04', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(8, 2, '2025-01-05', '10:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(9, 3, '2025-01-05', '07:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(10, 1, '2025-01-11', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(11, 2, '2025-01-12', '10:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(12, 4, '2025-01-18', '08:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(13, 1, '2025-01-18', '07:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(14, 2, '2025-01-19', '09:45:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(15, 3, '2025-01-19', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(16, 1, '2025-01-25', '08:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(17, 4, '2025-01-25', '10:15:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(18, 2, '2025-01-26', '07:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(19, 5, '2025-01-29', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(20, 4, '2025-02-01', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(21, 1, '2025-02-01', '08:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(22, 2, '2025-02-02', '07:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(23, 3, '2025-02-02', '09:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(24, 1, '2025-02-08', '10:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(25, 4, '2025-02-08', '08:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(26, 2, '2025-02-09', '07:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(27, 6, '2025-02-10', '09:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(28, 4, '2025-02-15', '10:30:00', 'Delivery', '5:00 PM - 6:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(29, 1, '2025-02-15', '08:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(30, 2, '2025-02-16', '07:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(31, 3, '2025-02-16', '09:45:00', 'Delivery', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(32, 4, '2025-02-22', '11:00:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(33, 1, '2025-02-22', '08:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(34, 7, '2025-02-23', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(35, 2, '2025-02-23', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(36, 6, '2025-02-24', '09:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(37, 5, '2025-02-28', '10:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(38, 7, '2025-02-28', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(39, 4, '2025-03-01', '07:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(40, 1, '2025-03-01', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(41, 2, '2025-03-02', '10:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(42, 3, '2025-03-02', '08:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(43, 7, '2025-03-07', '07:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(44, 8, '2025-03-07', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(45, 4, '2025-03-08', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(46, 1, '2025-03-08', '08:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(47, 2, '2025-03-09', '07:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(48, 6, '2025-03-10', '09:45:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(49, 7, '2025-03-14', '11:00:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(50, 9, '2025-03-14', '08:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(51, 4, '2025-03-15', '10:15:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(52, 1, '2025-03-15', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(53, 2, '2025-03-16', '09:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(54, 3, '2025-03-16', '10:45:00', 'Pickup', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(55, 7, '2025-03-21', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(56, 10, '2025-03-22', '07:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(57, 4, '2025-03-22', '09:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(58, 1, '2025-03-22', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(59, 2, '2025-03-23', '08:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(60, 6, '2025-03-24', '07:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(61, 7, '2025-03-28', '09:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(62, 5, '2025-03-28', '10:30:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(63, 10, '2025-03-29', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(64, 4, '2025-03-29', '07:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(65, 1, '2025-03-29', '09:45:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(66, 2, '2025-03-30', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(67, 3, '2025-03-30', '08:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(68, 11, '2025-03-30', '10:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(69, 7, '2025-04-04', '07:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(70, 10, '2025-04-05', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(71, 4, '2025-04-05', '10:45:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(72, 1, '2025-04-05', '08:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(73, 2, '2025-04-06', '07:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(74, 6, '2025-04-07', '09:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(75, 12, '2025-04-08', '10:00:00', 'Pickup', '1:00 PM - 3:00 PM', 'Full Service', 'None', '', NULL),
(76, 7, '2025-04-11', '08:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(77, 10, '2025-04-12', '07:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(78, 4, '2025-04-12', '09:15:00', 'Pickup', '3:00 PM - 5:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(79, 1, '2025-04-12', '10:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(80, 2, '2025-04-13', '08:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(81, 3, '2025-04-13', '07:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(82, 11, '2025-04-13', '09:45:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(83, 9, '2025-04-15', '11:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(84, 12, '2025-04-15', '08:30:00', 'Pickup', '5:00 PM - 6:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(85, 13, '2025-04-17', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(86, 7, '2025-04-18', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(87, 10, '2025-04-19', '09:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(88, 4, '2025-04-19', '10:45:00', 'Delivery', '1:00 PM - 3:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(89, 1, '2025-04-19', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(90, 2, '2025-04-20', '07:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(91, 6, '2025-04-21', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(92, 12, '2025-04-22', '10:00:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(93, 7, '2025-04-25', '08:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(94, 5, '2025-04-25', '07:15:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(95, 14, '2025-04-25', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(96, 10, '2025-04-26', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(97, 4, '2025-04-26', '08:00:00', 'Delivery', '5:00 PM - 6:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(98, 1, '2025-04-26', '07:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(99, 2, '2025-04-27', '09:45:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(100, 3, '2025-04-27', '11:00:00', 'Delivery', '1:00 PM - 3:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(101, 11, '2025-04-27', '08:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(102, 12, '2025-04-29', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(103, 7, '2025-05-02', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(104, 14, '2025-05-02', '09:00:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(105, 10, '2025-05-03', '10:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(106, 4, '2025-05-03', '08:15:00', 'Delivery', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(107, 1, '2025-05-03', '07:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(108, 15, '2025-05-03', '09:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(109, 2, '2025-05-04', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(110, 6, '2025-05-05', '08:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(111, 12, '2025-05-06', '07:15:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(112, 7, '2025-05-09', '09:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(113, 10, '2025-05-10', '10:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(114, 4, '2025-05-10', '08:00:00', 'Pickup', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(115, 1, '2025-05-10', '07:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(116, 2, '2025-05-11', '09:45:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(117, 3, '2025-05-11', '11:00:00', 'Pickup', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(118, 11, '2025-05-11', '08:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(119, 16, '2025-05-12', '10:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(120, 12, '2025-05-13', '07:30:00', 'Pickup', '11:00 AM - 1:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(121, 9, '2025-05-14', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(122, 8, '2025-05-15', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(123, 7, '2025-05-16', '08:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(124, 10, '2025-05-17', '07:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(125, 4, '2025-05-17', '09:30:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Full Service', 'None', '', NULL),
(126, 1, '2025-05-17', '10:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(127, 15, '2025-05-17', '08:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(128, 2, '2025-05-18', '07:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(129, 6, '2025-05-19', '09:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(130, 16, '2025-05-19', '10:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(131, 12, '2025-05-20', '08:00:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(132, 17, '2025-05-21', '07:45:00', 'Pickup', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(133, 7, '2025-05-23', '09:45:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(134, 10, '2025-05-24', '11:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(135, 4, '2025-05-24', '08:30:00', 'Pickup', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(136, 1, '2025-05-24', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(137, 2, '2025-05-25', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(138, 3, '2025-05-25', '09:00:00', 'Pickup', '5:00 PM - 6:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(139, 11, '2025-05-25', '10:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(140, 16, '2025-05-26', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(141, 12, '2025-05-27', '07:00:00', 'Pickup', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(142, 18, '2025-05-29', '09:30:00', 'Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(143, 7, '2025-05-30', '10:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(144, 5, '2025-05-30', '08:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(145, 10, '2025-05-31', '07:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(146, 4, '2025-05-31', '09:15:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(147, 1, '2025-05-31', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(148, 15, '2025-05-31', '08:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(149, 2, '2025-06-01', '07:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(150, 16, '2025-06-02', '09:45:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(151, 6, '2025-06-02', '11:00:00', 'Delivery', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(152, 12, '2025-06-03', '08:30:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(153, 19, '2025-06-05', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(154, 7, '2025-06-06', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(155, 14, '2025-06-06', '09:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(156, 10, '2025-06-07', '10:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(157, 4, '2025-06-07', '08:15:00', 'Delivery', '5:00 PM - 6:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(158, 1, '2025-06-07', '07:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(159, 2, '2025-06-08', '09:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(160, 3, '2025-06-08', '10:00:00', 'Delivery', '1:00 PM - 3:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(161, 11, '2025-06-08', '08:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(162, 16, '2025-06-09', '07:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(163, 12, '2025-06-10', '09:15:00', 'Delivery', '3:00 PM - 5:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(164, 19, '2025-06-11', '10:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(165, 9, '2025-06-13', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(166, 7, '2025-06-13', '07:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(167, 10, '2025-06-14', '09:45:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(168, 4, '2025-06-14', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(169, 1, '2025-06-14', '08:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(170, 20, '2025-06-14', '10:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(171, 15, '2025-06-14', '07:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(172, 2, '2025-06-15', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(173, 6, '2025-06-16', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(174, 16, '2025-06-16', '08:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(175, 12, '2025-06-17', '07:00:00', 'Delivery', '5:00 PM - 6:00 PM', 'Full Service', 'None', '', NULL),
(176, 19, '2025-06-18', '09:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(177, 7, '2025-06-20', '10:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(178, 10, '2025-06-21', '08:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(179, 4, '2025-06-21', '07:15:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(180, 1, '2025-06-21', '09:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(181, 2, '2025-06-22', '10:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(182, 3, '2025-06-22', '08:00:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(183, 11, '2025-06-22', '07:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(184, 21, '2025-06-22', '09:45:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(185, 16, '2025-06-23', '11:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(186, 12, '2025-06-24', '08:30:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(187, 19, '2025-06-25', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(188, 7, '2025-06-27', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(189, 5, '2025-06-27', '09:00:00', 'Pickup', '5:00 PM - 6:00 PM', 'Comforter', 'None', '', NULL),
(190, 10, '2025-06-28', '10:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(191, 4, '2025-06-28', '08:15:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(192, 1, '2025-06-28', '07:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(193, 20, '2025-06-28', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(194, 15, '2025-06-28', '10:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(195, 18, '2025-06-28', '08:45:00', 'Pickup', '3:00 PM - 5:00 PM', 'Full Service', 'None', '', NULL),
(196, 2, '2025-06-29', '07:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(197, 16, '2025-06-30', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(198, 6, '2025-06-30', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(199, 22, '2025-06-30', '08:00:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(200, 19, '2025-07-02', '07:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(201, 12, '2025-07-01', '09:45:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(202, 7, '2025-07-04', '11:00:00', 'Delivery', '1:00 PM - 3:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(203, 14, '2025-07-04', '08:30:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(204, 10, '2025-07-05', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(205, 4, '2025-07-05', '07:30:00', 'Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(206, 1, '2025-07-05', '09:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(207, 22, '2025-07-05', '10:45:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(208, 2, '2025-07-06', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(209, 3, '2025-07-06', '07:00:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(210, 11, '2025-07-06', '09:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(211, 16, '2025-07-07', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(212, 12, '2025-07-08', '08:45:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(213, 23, '2025-07-08', '07:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(214, 19, '2025-07-09', '09:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(215, 17, '2025-07-10', '10:30:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(216, 7, '2025-07-11', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(217, 10, '2025-07-12', '07:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(218, 4, '2025-07-12', '09:45:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(219, 1, '2025-07-12', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(220, 20, '2025-07-12', '08:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(221, 15, '2025-07-12', '10:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(222, 22, '2025-07-12', '07:30:00', 'Pickup', '1:00 PM - 3:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(223, 2, '2025-07-13', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(224, 6, '2025-07-14', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(225, 16, '2025-07-14', '08:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(226, 9, '2025-07-15', '07:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(227, 12, '2025-07-15', '09:30:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(228, 19, '2025-07-16', '10:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(229, 24, '2025-07-16', '08:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(230, 7, '2025-07-18', '07:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(231, 21, '2025-07-18', '09:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(232, 10, '2025-07-19', '10:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(233, 4, '2025-07-19', '08:00:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(234, 1, '2025-07-19', '07:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(235, 22, '2025-07-19', '09:45:00', 'Delivery', '1:00 PM - 3:00 PM', 'Full Service', 'None', '', NULL),
(236, 2, '2025-07-20', '11:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(237, 3, '2025-07-20', '08:30:00', 'Pickup', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(238, 11, '2025-07-20', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(239, 13, '2025-07-20', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(240, 16, '2025-07-21', '09:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(241, 12, '2025-07-22', '10:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(242, 19, '2025-07-23', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(243, 25, '2025-07-24', '07:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(244, 7, '2025-07-25', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(245, 5, '2025-07-25', '10:00:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Full Service', 'None', '', NULL),
(246, 10, '2025-07-26', '08:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(247, 4, '2025-07-26', '07:15:00', 'Delivery', '1:00 PM - 3:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(248, 1, '2025-07-26', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(249, 20, '2025-07-26', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(250, 15, '2025-07-26', '08:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(251, 22, '2025-07-26', '07:45:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(252, 18, '2025-07-26', '09:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(253, 2, '2025-07-27', '11:00:00', 'Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(254, 6, '2025-07-28', '08:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(255, 16, '2025-07-28', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(256, 12, '2025-07-29', '07:30:00', 'Delivery', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(257, 19, '2025-07-30', '09:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(258, 24, '2025-07-30', '10:45:00', 'Pickup', '1:00 PM - 3:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(259, 7, '2025-08-01', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(260, 25, '2025-08-01', '07:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(261, 14, '2025-08-01', '09:30:00', 'Pickup', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(262, 10, '2025-08-02', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(263, 4, '2025-08-02', '08:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(264, 1, '2025-08-02', '07:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(265, 22, '2025-08-02', '09:15:00', 'Delivery', '5:00 PM - 6:00 PM', 'Full Service', 'None', '', NULL),
(266, 26, '2025-08-02', '10:30:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(267, 2, '2025-08-03', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(268, 3, '2025-08-03', '07:45:00', 'Delivery', '3:00 PM - 5:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(269, 11, '2025-08-03', '09:45:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(270, 16, '2025-08-04', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(271, 12, '2025-08-05', '08:30:00', 'Delivery', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(272, 19, '2025-08-06', '10:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(273, 7, '2025-08-08', '07:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(274, 25, '2025-08-08', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(275, 10, '2025-08-09', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(276, 4, '2025-08-09', '08:15:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(277, 1, '2025-08-09', '07:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(278, 20, '2025-08-09', '09:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(279, 15, '2025-08-09', '10:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(280, 22, '2025-08-09', '08:45:00', 'Delivery', '1:00 PM - 3:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(281, 2, '2025-08-10', '07:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(282, 8, '2025-08-10', '09:15:00', 'Pickup', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(283, 6, '2025-08-11', '10:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(284, 16, '2025-08-11', '08:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(285, 27, '2025-08-11', '07:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(286, 12, '2025-08-12', '09:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(287, 19, '2025-08-13', '11:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(288, 24, '2025-08-13', '08:30:00', 'Pickup', '5:00 PM - 6:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(289, 9, '2025-08-14', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(290, 7, '2025-08-15', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(291, 25, '2025-08-15', '09:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(292, 21, '2025-08-15', '10:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(293, 10, '2025-08-16', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(294, 4, '2025-08-16', '07:00:00', 'Pickup', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(295, 1, '2025-08-16', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(296, 22, '2025-08-16', '10:00:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(297, 2, '2025-08-17', '08:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(298, 3, '2025-08-17', '07:15:00', 'Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(299, 11, '2025-08-17', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(300, 16, '2025-08-18', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(301, 27, '2025-08-18', '08:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(302, 12, '2025-08-19', '07:45:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(303, 28, '2025-08-19', '09:45:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(304, 19, '2025-08-20', '11:00:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(305, 7, '2025-08-22', '08:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(306, 25, '2025-08-22', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(307, 10, '2025-08-23', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(308, 4, '2025-08-23', '09:00:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(309, 1, '2025-08-23', '10:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(310, 20, '2025-08-23', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(311, 15, '2025-08-23', '07:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(312, 22, '2025-08-23', '09:30:00', 'Pickup', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(313, 2, '2025-08-24', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(314, 6, '2025-08-25', '08:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(315, 16, '2025-08-25', '07:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(316, 27, '2025-08-25', '09:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(317, 12, '2025-08-26', '10:30:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(318, 19, '2025-08-27', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(319, 24, '2025-08-27', '07:45:00', 'Delivery', '5:00 PM - 6:00 PM', 'Comforter', 'None', '', NULL),
(320, 29, '2025-08-27', '09:45:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(321, 7, '2025-08-29', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(322, 25, '2025-08-29', '08:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(323, 5, '2025-08-29', '10:15:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(324, 10, '2025-08-30', '07:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(325, 4, '2025-08-30', '09:00:00', 'Delivery', '11:00 AM - 1:00 PM', 'Full Service', 'None', '', NULL),
(326, 1, '2025-08-30', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(327, 22, '2025-08-30', '08:15:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(328, 18, '2025-08-30', '07:00:00', 'Delivery', '1:00 PM - 3:00 PM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(329, 2, '2025-08-31', '09:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(330, 3, '2025-08-31', '10:00:00', 'Pickup', '3:00 PM - 5:00 PM', 'Full Service', 'None', '', NULL),
(331, 11, '2025-08-31', '08:45:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(332, 16, '2025-09-01', '07:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(333, 27, '2025-09-01', '09:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(334, 12, '2025-09-02', '10:30:00', 'Delivery', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(335, 19, '2025-09-03', '08:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(336, 27, '2025-09-04', '07:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(337, 7, '2025-09-05', '09:45:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(338, 25, '2025-09-05', '11:00:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(339, 14, '2025-09-05', '08:30:00', 'Pickup', '5:00 PM - 6:00 PM', 'Comforter', 'None', '', NULL),
(340, 17, '2025-09-05', '10:15:00', 'Delivery', '1:00 PM - 3:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(341, 30, '2025-09-05', '07:30:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(342, 10, '2025-09-06', '09:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(343, 4, '2025-09-06', '10:45:00', 'Delivery', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(344, 1, '2025-09-06', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(345, 20, '2025-09-06', '07:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(346, 15, '2025-09-06', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(347, 22, '2025-09-06', '10:00:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(348, 2, '2025-09-07', '08:45:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(349, 26, '2025-09-07', '07:15:00', 'Delivery', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(350, 6, '2025-09-08', '09:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(351, 16, '2025-09-08', '10:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(352, 12, '2025-09-09', '08:00:00', 'Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(353, 19, '2025-09-10', '07:45:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(354, 24, '2025-09-10', '09:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Comforter', 'None', '', NULL),
(355, 29, '2025-09-10', '11:00:00', 'Delivery', '5:00 PM - 6:00 PM', 'Full Service', 'None', '', NULL),
(356, 27, '2025-09-11', '08:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(357, 7, '2025-09-12', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(358, 25, '2025-09-12', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(359, 10, '2025-09-13', '09:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(360, 4, '2025-09-13', '10:45:00', 'Pickup', '1:00 PM - 3:00 PM', 'Full Service', 'Extra Dry', '', NULL),
(361, 1, '2025-09-13', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(362, 22, '2025-09-13', '07:00:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(363, 3, '2025-09-14', '09:30:00', 'Pickup', '11:00 AM - 1:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(364, 11, '2025-09-14', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(365, 30, '2025-09-14', '08:45:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Full Service', 'None', '', NULL),
(366, 31, '2025-09-14', '07:15:00', 'Pickup', '1:00 PM - 3:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(367, 23, '2025-09-15', '09:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(368, 16, '2025-09-15', '10:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(369, 9, '2025-09-16', '08:00:00', 'Pickup', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(370, 12, '2025-09-16', '07:45:00', 'Delivery', '3:00 PM - 5:00 PM', 'Full Service', 'None', '', NULL),
(371, 19, '2025-09-17', '09:45:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(372, 27, '2025-09-18', '11:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(373, 7, '2025-09-19', '08:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(374, 25, '2025-09-19', '10:15:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(375, 21, '2025-09-19', '07:30:00', 'Pickup', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(376, 10, '2025-09-20', '09:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(377, 4, '2025-09-20', '10:45:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(378, 1, '2025-09-20', '08:15:00', 'Pickup', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(379, 20, '2025-09-20', '07:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(380, 15, '2025-09-20', '09:30:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(381, 22, '2025-09-20', '10:00:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(382, 31, '2025-09-20', '08:45:00', 'Delivery', '1:00 PM - 3:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(383, 30, '2025-09-21', '07:15:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(384, 6, '2025-09-22', '09:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'Extra Dry', '', NULL),
(385, 16, '2025-09-22', '10:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(386, 32, '2025-09-22', '08:00:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(387, 12, '2025-09-23', '07:45:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(388, 19, '2025-09-24', '09:45:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(389, 24, '2025-09-24', '11:00:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Comforter', 'None', '', NULL),
(390, 29, '2025-09-24', '08:30:00', 'Pickup', '1:00 PM - 3:00 PM', 'Full Service', 'None', '', NULL),
(391, 27, '2025-09-25', '10:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Wash Only', 'None', '', NULL),
(392, 7, '2025-09-26', '07:30:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(393, 25, '2025-09-26', '09:00:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(394, 5, '2025-09-26', '10:45:00', 'Delivery', '3:00 PM - 5:00 PM', 'Comforter', 'None', '', NULL),
(395, 10, '2025-09-27', '08:15:00', 'Pickup and Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'None', '', NULL),
(396, 4, '2025-09-27', '07:00:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(397, 1, '2025-09-27', '09:30:00', 'Delivery', '9:00 AM - 11:00 AM', 'Self Service - Dry Only', 'None', '', NULL),
(398, 22, '2025-09-27', '10:00:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(399, 18, '2025-09-27', '08:45:00', 'Pickup', '1:00 PM - 3:00 PM', 'Comforter', 'None', '', NULL),
(400, 11, '2025-09-28', '07:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Full Service', 'Extra Dry', '', NULL),
(401, 33, '2025-09-28', '09:15:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(402, 30, '2025-09-28', '10:30:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(403, 16, '2025-09-29', '08:00:00', 'Delivery', '7:00 AM - 9:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(404, 12, '2025-09-30', '07:45:00', 'Pickup and Delivery', '5:00 PM - 6:00 PM', 'Comforter', 'Extra Dry', '', NULL),
(405, 24, '2025-10-01', '09:45:00', 'Pickup', '1:00 PM - 3:00 PM', 'Full Service', 'None', '', NULL),
(406, 19, '2025-10-01', '11:00:00', 'Delivery', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(407, 29, '2025-10-01', '08:30:00', 'Pickup and Delivery', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(408, 34, '2025-10-02', '10:15:00', 'Pickup', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'Extra Dry', '', NULL),
(409, 28, '2025-10-02', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Comforter', 'None', '', NULL),
(410, 25, '2025-10-03', '09:00:00', 'Pickup and Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(411, 21, '2025-10-03', '10:45:00', 'Pickup', '5:00 PM - 6:00 PM', 'Self Service - Wash Only', 'None', '', NULL),
(412, 32, '2025-10-03', '08:15:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Dry Only', 'Extra Dry', '', NULL),
(413, 26, '2025-10-04', '07:00:00', 'Pickup and Delivery', '1:00 PM - 3:00 PM', 'Blanket/Bedsheet', 'None', '', NULL),
(414, 1, '2025-10-04', '09:30:00', 'Pickup', '9:00 AM - 11:00 AM', 'Comforter', 'None', '', NULL),
(415, 20, '2025-10-04', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Full Service', 'None', '', NULL),
(416, 30, '2025-10-04', '08:45:00', 'Pickup and Delivery', '3:00 PM - 5:00 PM', 'Self Service - Wash Only', 'Extra Dry', '', NULL),
(417, 31, '2025-10-04', '07:15:00', 'Pickup', '11:00 AM - 1:00 PM', 'Self Service - Dry Only', 'None', '', NULL),
(418, 35, '2025-10-04', '09:15:00', 'Delivery', '9:00 AM - 11:00 AM', 'Blanket/Bedsheet', 'None', '', NULL),
(419, 2, '2025-11-13', '08:00:00', 'Delivery', NULL, 'Full Service', 'Liquid Detergent per Cup (+₱10)', 'Pending', NULL),
(420, 60, '2025-11-22', '03:00:00', 'delivery', NULL, 'Comforter, Blanket/Bedsheet', 'Extra Dry (+₱15)', 'Pending', NULL),
(421, 61, '2025-11-14', '04:00:00', 'walkin', NULL, 'Dry Only', 'Liquid Detergent per Cup (+₱10)', 'Pending', NULL),
(422, 62, '2025-11-15', '01:00:00', 'delivery', NULL, 'Full Service', 'Fabric Conditioner (+₱10)', 'Pending', NULL),
(423, 63, '2025-11-25', '09:00:00', 'walkin', NULL, 'Comforter', '', 'Pending', NULL),
(424, 65, '2025-11-29', '01:00:00', 'walkin', NULL, 'Comforter', 'Extra Dry (+₱15), Liquid Detergent per Cup (+₱10)', 'Pending', NULL),
(425, 1, '2024-12-08', '07:30:00', 'Delivery', '7:00 AM - 9:00 AM', 'Self Service - Wash Only', 'None', '', 'proof1.jpg'),
(426, 1, '2024-12-10', '15:00:00', 'Pickup', '2:00 PM - 4:00 PM', 'Wash & Fold', 'Fabric Softener', 'Pending', 'proof2.png'),
(427, 2, '2024-12-11', '10:00:00', 'Delivery', '9:00 AM - 11:00 AM', 'Premium Wash', 'Bleach, Softener', 'Pending', NULL),
(428, 61, '2026-04-19', '08:00:00', 'walkin', NULL, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10)', 'Pending', NULL),
(429, 61, '2026-04-19', '08:00:00', 'walkin', NULL, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10)', 'Pending', NULL),
(430, 61, '2026-04-19', '09:00:00', 'walkin', NULL, 'Dry Only', 'Liquid Detergent per Cup (+₱10)', 'Pending', NULL),
(432, 61, '2026-04-19', '11:00:00', 'walkin', NULL, 'Blanket/Bedsheet', '', 'Pending', NULL),
(433, 61, '2026-04-19', '11:00:00', 'walkin', NULL, 'Blanket/Bedsheet', '', 'Pending', NULL),
(434, 61, '2026-04-19', '11:00:00', 'walkin', NULL, 'Blanket/Bedsheet', '', 'Pending', NULL),
(436, 61, '2026-04-19', '08:00:00', 'walkin', NULL, 'Blanket/Bedsheet', 'Fabric Conditioner (+₱10)', 'Pending', NULL),
(437, 36, '2026-04-19', '04:00:00', 'walkin', NULL, 'Blanket/Bedsheet', 'Liquid Detergent per Cup (+₱10), Fabric Conditione', 'Pending', NULL),
(438, 38, '2026-04-19', '08:00:00', 'walkin', NULL, 'Full Service', 'Fabric Conditioner (+₱10)', 'Pending', NULL),
(439, 61, '2026-05-28', '01:00:00', 'walkin', NULL, 'Blanket/Bedsheet, Full Service', 'Extra Dry (+₱15), Liquid Detergent per Cup (+₱10)', 'Pending', NULL);

--
-- Triggers `schedule`
--
DELIMITER $$
CREATE TRIGGER `after_schedule_insert` AFTER INSERT ON `schedule` FOR EACH ROW BEGIN
    INSERT INTO booking (
        Customer_ID,
        Schedule_ID,
        service,
        add_ons,
        pick_deliver,
        status,
        total_amount
    ) VALUES (
        NEW.Customer_ID,
        NEW.Schedule_ID,
        NEW.service,
        NEW.add_ons,
        NEW.pick_deliver,
        'Pending',
        0.00
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_kg` decimal(10,2) DEFAULT 0.00,
  `price_fixed` decimal(10,2) DEFAULT 0.00,
  `discount_type` varchar(50) DEFAULT NULL,
  `extra_fee` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`, `price_per_kg`, `price_fixed`, `discount_type`, `extra_fee`, `created_at`) VALUES
(1, 'Full Service', 'Wash, dry, fold with detergent & fabric conditioner (7kg per load)', 0.00, 200.00, NULL, 0.00, '2025-11-06 23:05:51'),
(2, 'Wash Only', 'Self-service washing only', 0.00, 80.00, NULL, 0.00, '2025-11-06 23:05:51'),
(3, 'Dry Only', 'Self-service drying only', 0.00, 70.00, NULL, 0.00, '2025-11-06 23:05:51'),
(4, 'Blanket/Bedsheet', 'Thick items up to 3kg', 0.00, 200.00, NULL, 0.00, '2025-11-06 23:05:51'),
(5, 'Comforter', '1 piece per load', 0.00, 200.00, NULL, 0.00, '2025-11-06 23:05:51');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `contact_person`, `contact_number`, `email`, `address`, `notes`, `status`, `created_at`) VALUES
(1, 'CleanPro Supplies', 'Maria Reyes', '09171234567', 'orders@cleanpro.ph', '123 Commercial Ave, Manila', '', 'Active', '2026-04-19 15:20:47'),
(2, 'Laundry Solutions Inc', 'Juan Santos', '09281234568', 'sales@laundrysolutions.ph', '456 Industrial Rd, Quezon City', '', 'Inactive', '2026-04-19 15:20:47'),
(3, 'Packaging World', 'Ana Cruz', '09351234569', 'info@packagingworld.ph', '789 Market St, Pasig', NULL, 'Active', '2026-04-19 15:20:47'),
(4, 'Supplyy', 'Alessandra Mae Perey', '09765432357', 'alessandramaeperey7@gmail.com', '', '', 'Active', '2026-04-20 18:05:19');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracking`
--

CREATE TABLE `tracking` (
  `Tracking_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Schedule_ID` int(11) NOT NULL,
  `laundry_status` enum('Scheduled','Picked\r\nUp','Processing','Ready','Completed') DEFAULT NULL,
  `Scheduled_time` time DEFAULT NULL,
  `Pickup_time` time DEFAULT NULL,
  `Processing_time` time DEFAULT NULL,
  `Ready_time` time DEFAULT NULL,
  `Completed_time` time DEFAULT NULL,
  `tracking_time` time DEFAULT NULL,
  `tracking_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracking`
--

INSERT INTO `tracking` (`Tracking_ID`, `Customer_ID`, `Schedule_ID`, `laundry_status`, `Scheduled_time`, `Pickup_time`, `Processing_time`, `Ready_time`, `Completed_time`, `tracking_time`, `tracking_date`) VALUES
(1, 1, 1, 'Completed', '07:46:00', '08:08:12', '08:24:12', '10:24:12', '11:09:12', '13:00:00', '2024-12-08'),
(2, 1, 2, 'Completed', '09:32:00', '09:53:02', '10:10:02', '12:20:02', '13:05:02', '14:00:00', '2024-12-14'),
(3, 1, 3, 'Completed', '08:18:00', '08:26:36', '08:44:36', '11:04:36', '11:49:36', '13:00:00', '2024-12-21'),
(4, 2, 4, 'Completed', '11:04:00', '11:28:37', '11:47:37', '14:17:37', '15:02:37', '14:00:00', '2024-12-22'),
(5, 1, 5, 'Completed', '07:15:00', '07:39:53', '07:54:53', '10:34:53', '11:19:53', '14:00:00', '2024-12-28'),
(6, 2, 6, 'Completed', '09:46:00', '10:04:39', '10:20:39', '13:05:39', '13:50:39', '15:00:00', '2024-12-29'),
(7, 1, 7, 'Completed', '08:32:00', '08:45:34', '09:02:34', '11:52:34', '12:37:34', '13:00:00', '2025-01-04'),
(8, 2, 8, 'Completed', '10:18:00', '10:24:56', '10:42:56', '13:37:56', '14:22:56', '15:00:00', '2025-01-05'),
(9, 3, 9, 'Completed', '08:04:00', '08:17:57', '08:36:57', '11:36:57', '12:21:57', '18:00:00', '2025-01-05'),
(10, 1, 10, 'Completed', '09:15:00', '09:34:00', '09:49:00', '11:49:00', '12:34:00', '14:00:00', '2025-01-11'),
(11, 2, 11, 'Completed', '10:46:00', '11:09:06', '11:25:06', '13:25:06', '14:10:06', '14:00:00', '2025-01-12'),
(12, 4, 12, 'Completed', '08:47:00', '09:15:34', '09:32:34', '11:42:34', '12:27:34', '15:00:00', '2025-01-18'),
(13, 1, 13, 'Completed', '07:33:00', '07:51:31', '08:09:31', '10:29:31', '11:14:31', '13:00:00', '2025-01-18'),
(14, 2, 14, 'Completed', '10:04:00', '10:30:56', '10:49:56', '13:19:56', '14:04:56', '15:00:00', '2025-01-19'),
(15, 3, 15, 'Completed', '11:15:00', '11:38:33', '11:53:33', '14:33:33', '15:18:33', '19:00:00', '2025-01-19'),
(16, 1, 16, 'Completed', '08:16:00', '08:40:04', '08:56:04', '11:41:04', '12:26:04', '13:00:00', '2025-01-25'),
(17, 4, 17, 'Completed', '10:32:00', '10:38:13', '10:55:13', '13:45:13', '14:30:13', '16:00:00', '2025-01-25'),
(18, 2, 18, 'Completed', '07:48:00', '07:57:35', '08:15:35', '11:10:35', '11:55:35', '14:00:00', '2025-01-26'),
(19, 5, 19, 'Completed', '09:19:00', '09:39:43', '09:58:43', '12:58:43', '13:43:43', '19:00:00', '2025-01-29'),
(20, 4, 20, 'Completed', '11:00:00', '11:15:45', '11:30:45', '13:30:45', '14:15:45', '15:00:00', '2025-02-01'),
(21, 1, 21, 'Completed', '08:31:00', '08:50:48', '09:06:48', '11:06:48', '11:51:48', '14:00:00', '2025-02-01'),
(22, 2, 22, 'Completed', '07:17:00', '07:23:54', '07:40:54', '09:50:54', '10:35:54', '15:00:00', '2025-02-02'),
(23, 3, 23, 'Completed', '09:48:00', '10:08:05', '10:26:05', '12:46:05', '13:31:05', '18:00:00', '2025-02-02'),
(24, 1, 24, 'Completed', '10:19:00', '10:43:43', '11:02:43', '13:32:43', '14:17:43', '13:00:00', '2025-02-08'),
(25, 4, 25, 'Completed', '09:00:00', '09:08:23', '09:23:23', '12:03:23', '12:48:23', '16:00:00', '2025-02-08'),
(26, 2, 26, 'Completed', '07:31:00', '07:43:44', '07:59:44', '10:44:44', '11:29:44', '14:00:00', '2025-02-09'),
(27, 6, 27, 'Completed', '09:32:00', '09:40:31', '09:57:31', '12:47:31', '13:32:31', '15:30:00', '2025-02-10'),
(28, 4, 28, 'Completed', '10:48:00', '11:11:20', '11:29:20', '14:24:20', '15:09:20', '15:00:00', '2025-02-15'),
(29, 1, 29, 'Completed', '08:19:00', '08:43:25', '09:02:25', '12:02:25', '12:47:25', '14:00:00', '2025-02-15'),
(30, 2, 30, 'Completed', '08:00:00', '08:16:31', '08:31:31', '10:31:31', '11:16:31', '14:00:00', '2025-02-16'),
(31, 3, 31, 'Completed', '10:01:00', '10:23:14', '10:39:14', '12:39:14', '13:24:14', '19:00:00', '2025-02-16'),
(32, 4, 32, 'Completed', '11:17:00', '11:34:33', '11:51:33', '14:01:33', '14:46:33', '16:00:00', '2025-02-22'),
(33, 1, 33, 'Completed', '08:48:00', '09:17:21', '09:35:21', '11:55:21', '12:40:21', '13:00:00', '2025-02-22'),
(34, 7, 34, 'Completed', '10:34:00', '10:51:13', '11:10:13', '13:40:13', '14:25:13', '17:00:00', '2025-02-23'),
(35, 2, 35, 'Completed', '07:45:00', '08:03:01', '08:18:01', '10:58:01', '11:43:01', '15:00:00', '2025-02-23'),
(36, 6, 36, 'Completed', '09:16:00', '09:24:26', '09:40:26', '12:25:26', '13:10:26', '14:30:00', '2025-02-24'),
(37, 5, 37, 'Completed', '11:02:00', '11:35:09', '11:52:09', '14:42:09', '15:27:09', '20:00:00', '2025-02-28'),
(38, 7, 38, 'Completed', '08:33:00', '08:41:09', '08:59:09', '11:54:09', '12:39:09', '18:00:00', '2025-02-28'),
(39, 4, 39, 'Completed', '07:19:00', '07:29:27', '07:48:27', '10:48:27', '11:33:27', '15:00:00', '2025-03-01'),
(40, 1, 40, 'Completed', '09:45:00', '10:07:48', '10:22:48', '12:22:48', '13:07:48', '14:00:00', '2025-03-01'),
(41, 2, 41, 'Completed', '10:16:00', '10:43:39', '10:59:39', '12:59:39', '13:44:39', '15:00:00', '2025-03-02'),
(42, 3, 42, 'Completed', '09:02:00', '09:16:52', '09:33:52', '11:43:52', '12:28:52', '18:00:00', '2025-03-02'),
(43, 7, 43, 'Completed', '07:33:00', '07:44:23', '08:02:23', '10:22:23', '11:07:23', '17:00:00', '2025-03-07'),
(44, 8, 44, 'Completed', '09:34:00', '09:41:18', '10:00:18', '12:30:18', '13:15:18', '20:00:00', '2025-03-07'),
(45, 4, 45, 'Completed', '10:45:00', '11:07:22', '11:22:22', '14:02:22', '14:47:22', '16:00:00', '2025-03-08'),
(46, 1, 46, 'Completed', '08:16:00', '08:25:58', '08:41:58', '11:26:58', '12:11:58', '13:00:00', '2025-03-08'),
(47, 2, 47, 'Completed', '08:02:00', '08:29:44', '08:46:44', '11:36:44', '12:21:44', '14:00:00', '2025-03-09'),
(48, 6, 48, 'Completed', '10:03:00', '10:31:47', '10:49:47', '13:44:47', '14:29:47', '15:30:00', '2025-03-10'),
(49, 7, 49, 'Completed', '11:19:00', '11:42:28', '12:01:28', '15:01:28', '15:46:28', '18:00:00', '2025-03-14'),
(50, 9, 50, 'Completed', '08:45:00', '08:50:44', '09:05:44', '11:05:44', '11:50:44', '14:30:00', '2025-03-14'),
(51, 4, 51, 'Completed', '10:31:00', '10:43:01', '10:59:01', '12:59:01', '13:44:01', '15:00:00', '2025-03-15'),
(52, 1, 52, 'Completed', '07:47:00', '07:59:18', '08:16:18', '10:26:18', '11:11:18', '14:00:00', '2025-03-15'),
(53, 2, 53, 'Completed', '09:18:00', '09:32:21', '09:50:21', '12:10:21', '12:55:21', '15:00:00', '2025-03-16'),
(54, 3, 54, 'Completed', '11:04:00', '11:20:05', '11:39:05', '14:09:05', '14:54:05', '19:00:00', '2025-03-16'),
(55, 7, 55, 'Completed', '08:30:00', '08:59:48', '09:14:48', '11:54:48', '12:39:48', '17:00:00', '2025-03-21'),
(56, 10, 56, 'Completed', '07:16:00', '07:41:59', '07:57:59', '10:42:59', '11:27:59', '12:00:00', '2025-03-22'),
(57, 4, 57, 'Completed', '09:47:00', '09:57:33', '10:14:33', '13:04:33', '13:49:33', '16:00:00', '2025-03-22'),
(58, 1, 58, 'Completed', '10:18:00', '10:37:49', '10:55:49', '13:50:49', '14:35:49', '13:00:00', '2025-03-22'),
(59, 2, 59, 'Completed', '09:04:00', '09:16:24', '09:35:24', '12:35:24', '13:20:24', '14:00:00', '2025-03-23'),
(60, 6, 60, 'Completed', '07:30:00', '07:52:34', '08:07:34', '10:07:34', '10:52:34', '14:30:00', '2025-03-24'),
(61, 7, 61, 'Completed', '09:31:00', '09:51:40', '10:07:40', '12:07:40', '12:52:40', '18:00:00', '2025-03-28'),
(62, 5, 62, 'Completed', '10:47:00', '11:09:44', '11:26:44', '13:36:44', '14:21:44', '19:00:00', '2025-03-28'),
(63, 10, 63, 'Completed', '08:18:00', '08:23:36', '08:41:36', '11:01:36', '11:46:36', '13:00:00', '2025-03-29'),
(64, 4, 64, 'Completed', '08:04:00', '08:15:03', '08:34:03', '11:04:03', '11:49:03', '15:00:00', '2025-03-29'),
(65, 1, 65, 'Completed', '10:00:00', '10:08:25', '10:23:25', '13:03:25', '13:48:25', '14:00:00', '2025-03-29'),
(66, 2, 66, 'Completed', '11:16:00', '11:28:54', '11:44:54', '14:29:54', '15:14:54', '14:00:00', '2025-03-30'),
(67, 3, 67, 'Completed', '08:47:00', '09:15:59', '09:32:59', '12:22:59', '13:07:59', '19:00:00', '2025-03-30'),
(68, 11, 68, 'Completed', '10:33:00', '10:47:39', '11:05:39', '14:00:39', '14:45:39', '15:30:00', '2025-03-30'),
(69, 7, 69, 'Completed', '07:49:00', '07:55:18', '08:14:18', '11:14:18', '11:59:18', '17:00:00', '2025-04-04'),
(70, 10, 70, 'Completed', '09:15:00', '09:22:35', '09:37:35', '11:37:35', '12:22:35', '13:00:00', '2025-04-05'),
(71, 4, 71, 'Completed', '11:01:00', '11:06:07', '11:22:07', '13:22:07', '14:07:07', '15:00:00', '2025-04-05'),
(72, 1, 72, 'Completed', '08:32:00', '08:46:01', '09:03:01', '11:13:01', '11:58:01', '14:00:00', '2025-04-05'),
(73, 2, 73, 'Completed', '07:18:00', '07:35:20', '07:53:20', '10:13:20', '10:58:20', '14:00:00', '2025-04-06'),
(74, 6, 74, 'Completed', '09:49:00', '10:03:39', '10:22:39', '12:52:39', '13:37:39', '15:30:00', '2025-04-07'),
(75, 12, 75, 'Completed', '10:15:00', '10:40:26', '10:55:26', '13:35:26', '14:20:26', '18:30:00', '2025-04-08'),
(76, 7, 76, 'Completed', '09:01:00', '09:17:15', '09:33:15', '12:18:15', '13:03:15', '18:00:00', '2025-04-11'),
(77, 10, 77, 'Completed', '07:32:00', '07:39:19', '07:56:19', '10:46:19', '11:31:19', '12:00:00', '2025-04-12'),
(78, 4, 78, 'Completed', '09:33:00', '09:51:59', '10:09:59', '13:04:59', '13:49:59', '16:00:00', '2025-04-12'),
(79, 1, 79, 'Completed', '10:49:00', '10:56:51', '11:15:51', '14:15:51', '15:00:51', '13:00:00', '2025-04-12'),
(80, 2, 80, 'Completed', '08:15:00', '08:27:18', '08:42:18', '10:42:18', '11:27:18', '15:00:00', '2025-04-13'),
(81, 3, 81, 'Completed', '08:01:00', '08:20:05', '08:36:05', '10:36:05', '11:21:05', '18:00:00', '2025-04-13'),
(82, 11, 82, 'Completed', '10:02:00', '10:09:57', '10:26:57', '12:36:57', '13:21:57', '16:30:00', '2025-04-13'),
(83, 9, 83, 'Completed', '11:18:00', '11:39:45', '11:57:45', '14:17:45', '15:02:45', '13:30:00', '2025-04-15'),
(84, 12, 84, 'Completed', '08:49:00', '09:13:33', '09:32:33', '12:02:33', '12:47:33', '19:30:00', '2025-04-15'),
(85, 13, 85, 'Completed', '10:30:00', '10:52:52', '11:07:52', '13:47:52', '14:32:52', '16:00:00', '2025-04-17'),
(86, 7, 86, 'Completed', '07:46:00', '07:56:30', '08:12:30', '10:57:30', '11:42:30', '17:00:00', '2025-04-18'),
(87, 10, 87, 'Completed', '09:17:00', '09:45:53', '10:02:53', '12:52:53', '13:37:53', '13:00:00', '2025-04-19'),
(88, 4, 88, 'Completed', '11:03:00', '11:16:35', '11:34:35', '14:29:35', '15:14:35', '15:00:00', '2025-04-19'),
(89, 1, 89, 'Completed', '08:34:00', '08:41:55', '09:00:55', '12:00:55', '12:45:55', '14:00:00', '2025-04-19'),
(90, 2, 90, 'Completed', '07:15:00', '07:37:58', '07:52:58', '09:52:58', '10:37:58', '14:00:00', '2025-04-20'),
(91, 6, 91, 'Completed', '09:46:00', '09:57:06', '10:13:06', '12:13:06', '12:58:06', '14:30:00', '2025-04-21'),
(92, 12, 92, 'Completed', '10:17:00', '10:23:01', '10:40:01', '12:50:01', '13:35:01', '19:30:00', '2025-04-22'),
(93, 7, 93, 'Completed', '09:03:00', '09:09:36', '09:27:36', '11:47:36', '12:32:36', '18:00:00', '2025-04-25'),
(94, 5, 94, 'Completed', '07:34:00', '07:50:30', '08:09:30', '10:39:30', '11:24:30', '19:00:00', '2025-04-25'),
(95, 14, 95, 'Completed', '09:30:00', '09:49:44', '10:04:44', '12:44:44', '13:29:44', '20:00:00', '2025-04-25'),
(96, 10, 96, 'Completed', '10:46:00', '11:09:52', '11:25:52', '14:10:52', '14:55:52', '12:00:00', '2025-04-26'),
(97, 4, 97, 'Completed', '08:17:00', '08:39:24', '08:56:24', '11:46:24', '12:31:24', '16:00:00', '2025-04-26'),
(98, 1, 98, 'Completed', '08:03:00', '08:08:10', '08:26:10', '11:21:10', '12:06:10', '13:00:00', '2025-04-26'),
(99, 2, 99, 'Completed', '10:04:00', '10:28:13', '10:47:13', '13:47:13', '14:32:13', '15:00:00', '2025-04-27'),
(100, 3, 100, 'Completed', '11:15:00', '11:34:21', '11:49:21', '13:49:21', '14:34:21', '19:00:00', '2025-04-27'),
(101, 11, 101, 'Completed', '08:46:00', '09:11:37', '09:27:37', '11:27:37', '12:12:37', '15:30:00', '2025-04-27'),
(102, 12, 102, 'Completed', '10:32:00', '10:57:24', '11:14:24', '13:24:24', '14:09:24', '18:30:00', '2025-04-29'),
(103, 7, 103, 'Completed', '07:48:00', '08:08:10', '08:26:10', '10:46:10', '11:31:10', '17:00:00', '2025-05-02'),
(104, 14, 104, 'Completed', '09:19:00', '09:57:41', '10:16:41', '12:46:41', '13:31:41', '21:00:00', '2025-05-02'),
(105, 10, 105, 'Completed', '11:00:00', '11:12:15', '11:27:15', '14:07:15', '14:52:15', '12:00:00', '2025-05-03'),
(106, 4, 106, 'Completed', '08:31:00', '08:37:45', '08:53:45', '11:38:45', '12:23:45', '16:00:00', '2025-05-03'),
(107, 1, 107, 'Completed', '07:17:00', '07:36:38', '07:53:38', '10:43:38', '11:28:38', '13:00:00', '2025-05-03'),
(108, 15, 108, 'Completed', '09:48:00', '09:55:40', '10:13:40', '13:08:40', '13:53:40', '19:30:00', '2025-05-03'),
(109, 2, 109, 'Completed', '10:19:00', '10:43:25', '11:02:25', '14:02:25', '14:47:25', '15:00:00', '2025-05-04'),
(110, 6, 110, 'Completed', '09:00:00', '09:19:09', '09:34:09', '11:34:09', '12:19:09', '14:30:00', '2025-05-05'),
(111, 12, 111, 'Completed', '07:31:00', '07:44:23', '08:00:23', '10:00:23', '10:45:23', '19:30:00', '2025-05-06'),
(112, 7, 112, 'Completed', '09:32:00', '09:49:29', '10:06:29', '12:16:29', '13:01:29', '18:00:00', '2025-05-09'),
(113, 10, 113, 'Completed', '10:48:00', '11:12:57', '11:30:57', '13:50:57', '14:35:57', '13:00:00', '2025-05-10'),
(114, 4, 114, 'Completed', '08:19:00', '08:44:23', '09:03:23', '11:33:23', '12:18:23', '15:00:00', '2025-05-10'),
(115, 1, 115, 'Completed', '08:00:00', '08:17:18', '08:32:18', '11:12:18', '11:57:18', '14:00:00', '2025-05-10'),
(116, 2, 116, 'Completed', '10:01:00', '10:07:42', '10:23:42', '13:08:42', '13:53:42', '14:00:00', '2025-05-11'),
(117, 3, 117, 'Completed', '11:17:00', '11:22:51', '11:39:51', '14:29:51', '15:14:51', '18:00:00', '2025-05-11'),
(118, 11, 118, 'Completed', '08:48:00', '09:14:35', '09:32:35', '12:27:35', '13:12:35', '16:30:00', '2025-05-11'),
(119, 16, 119, 'Completed', '10:34:00', '10:41:52', '11:00:52', '14:00:52', '14:45:52', '14:00:00', '2025-05-12'),
(120, 12, 120, 'Completed', '07:45:00', '08:07:01', '08:22:01', '10:22:01', '11:07:01', '18:30:00', '2025-05-13'),
(121, 9, 121, 'Completed', '09:16:00', '09:45:34', '10:01:34', '12:01:34', '12:46:34', '14:30:00', '2025-05-14'),
(122, 8, 122, 'Completed', '11:02:00', '11:26:50', '11:43:50', '13:53:50', '14:38:50', '20:00:00', '2025-05-15'),
(123, 7, 123, 'Completed', '08:33:00', '08:52:15', '09:10:15', '11:30:15', '12:15:15', '17:00:00', '2025-05-16'),
(124, 10, 124, 'Completed', '07:19:00', '07:46:32', '08:05:32', '10:35:32', '11:20:32', '12:00:00', '2025-05-17'),
(125, 4, 125, 'Completed', '09:45:00', '09:59:40', '10:14:40', '12:54:40', '13:39:40', '16:00:00', '2025-05-17'),
(126, 1, 126, 'Completed', '10:16:00', '10:40:58', '10:56:58', '13:41:58', '14:26:58', '13:00:00', '2025-05-17'),
(127, 15, 127, 'Completed', '09:02:00', '09:14:12', '09:31:12', '12:21:12', '13:06:12', '20:30:00', '2025-05-17'),
(128, 2, 128, 'Completed', '07:33:00', '07:39:07', '07:57:07', '10:52:07', '11:37:07', '15:00:00', '2025-05-18'),
(129, 6, 129, 'Completed', '09:34:00', '09:48:02', '10:07:02', '13:07:02', '13:52:02', '15:30:00', '2025-05-19'),
(130, 16, 130, 'Completed', '10:45:00', '11:06:47', '11:21:47', '13:21:47', '14:06:47', '14:00:00', '2025-05-19'),
(131, 12, 131, 'Completed', '08:16:00', '08:34:06', '08:50:06', '10:50:06', '11:35:06', '19:30:00', '2025-05-20'),
(132, 17, 132, 'Completed', '08:02:00', '08:35:01', '08:52:01', '11:02:01', '11:47:01', '15:00:00', '2025-05-21'),
(133, 7, 133, 'Completed', '10:03:00', '10:14:50', '10:32:50', '12:52:50', '13:37:50', '18:00:00', '2025-05-23'),
(134, 10, 134, 'Completed', '11:19:00', '11:42:10', '12:01:10', '14:31:10', '15:16:10', '13:00:00', '2025-05-24'),
(135, 4, 135, 'Completed', '08:45:00', '08:51:17', '09:06:17', '11:46:17', '12:31:17', '15:00:00', '2025-05-24'),
(136, 1, 136, 'Completed', '10:31:00', '10:44:48', '11:00:48', '13:45:48', '14:30:48', '14:00:00', '2025-05-24'),
(137, 2, 137, 'Completed', '07:47:00', '08:15:32', '08:32:32', '11:22:32', '12:07:32', '14:00:00', '2025-05-25'),
(138, 3, 138, 'Completed', '09:18:00', '09:26:15', '09:44:15', '12:39:15', '13:24:15', '19:00:00', '2025-05-25'),
(139, 11, 139, 'Completed', '11:04:00', '11:20:57', '11:39:57', '14:39:57', '15:24:57', '15:30:00', '2025-05-25'),
(140, 16, 140, 'Completed', '08:30:00', '08:51:17', '09:06:17', '11:06:17', '11:51:17', '15:00:00', '2025-05-26'),
(141, 12, 141, 'Completed', '07:16:00', '07:51:44', '08:07:44', '10:07:44', '10:52:44', '18:30:00', '2025-05-27'),
(142, 18, 142, 'Completed', '09:47:00', '10:26:40', '10:43:40', '12:53:40', '13:38:40', '18:00:00', '2025-05-29'),
(143, 7, 143, 'Completed', '10:18:00', '10:33:47', '10:51:47', '13:11:47', '13:56:47', '17:00:00', '2025-05-30'),
(144, 5, 144, 'Completed', '09:04:00', '09:34:53', '09:53:53', '12:23:53', '13:08:53', '20:00:00', '2025-05-30'),
(145, 10, 145, 'Completed', '07:30:00', '07:40:07', '07:55:07', '10:35:07', '11:20:07', '12:00:00', '2025-05-31'),
(146, 4, 146, 'Completed', '09:31:00', '09:43:14', '09:59:14', '12:44:14', '13:29:14', '16:00:00', '2025-05-31'),
(147, 1, 147, 'Completed', '10:47:00', '11:10:12', '11:27:12', '14:17:12', '15:02:12', '13:00:00', '2025-05-31'),
(148, 15, 148, 'Completed', '08:18:00', '08:23:42', '08:41:42', '11:36:42', '12:21:42', '19:30:00', '2025-05-31'),
(149, 2, 149, 'Completed', '08:04:00', '08:32:55', '08:51:55', '11:51:55', '12:36:55', '15:00:00', '2025-06-01'),
(150, 16, 150, 'Completed', '10:00:00', '10:22:28', '10:37:28', '12:37:28', '13:22:28', '14:00:00', '2025-06-02'),
(151, 6, 151, 'Completed', '11:16:00', '11:32:07', '11:48:07', '13:48:07', '14:33:07', '15:30:00', '2025-06-02'),
(152, 12, 152, 'Completed', '08:47:00', '09:13:35', '09:30:35', '11:40:35', '12:25:35', '19:30:00', '2025-06-03'),
(153, 19, 153, 'Completed', '10:33:00', '10:53:35', '11:11:35', '13:31:35', '14:16:35', '13:00:00', '2025-06-05'),
(154, 7, 154, 'Completed', '07:49:00', '07:54:31', '08:13:31', '10:43:31', '11:28:31', '17:00:00', '2025-06-06'),
(155, 14, 155, 'Completed', '09:15:00', '09:23:34', '09:38:34', '12:18:34', '13:03:34', '21:00:00', '2025-06-06'),
(156, 10, 156, 'Completed', '11:01:00', '11:06:13', '11:22:13', '14:07:13', '14:52:13', '12:00:00', '2025-06-07'),
(157, 4, 157, 'Completed', '08:32:00', '08:43:26', '09:00:26', '11:50:26', '12:35:26', '15:00:00', '2025-06-07'),
(158, 1, 158, 'Completed', '07:18:00', '07:28:49', '07:46:49', '10:41:49', '11:26:49', '14:00:00', '2025-06-07'),
(159, 2, 159, 'Completed', '09:49:00', '09:56:36', '10:15:36', '13:15:36', '14:00:36', '14:00:00', '2025-06-08'),
(160, 3, 160, 'Completed', '10:15:00', '10:24:36', '10:39:36', '12:39:36', '13:24:36', '19:00:00', '2025-06-08'),
(161, 11, 161, 'Completed', '09:01:00', '09:26:32', '09:42:32', '11:42:32', '12:27:32', '15:30:00', '2025-06-08'),
(162, 16, 162, 'Completed', '07:32:00', '07:56:54', '08:13:54', '10:23:54', '11:08:54', '15:00:00', '2025-06-09'),
(163, 12, 163, 'Completed', '09:33:00', '10:06:14', '10:24:14', '12:44:14', '13:29:14', '18:30:00', '2025-06-10'),
(164, 19, 164, 'Completed', '10:49:00', '11:06:52', '11:25:52', '13:55:52', '14:40:52', '14:00:00', '2025-06-11'),
(165, 9, 165, 'Completed', '08:15:00', '08:24:39', '08:39:39', '11:19:39', '12:04:39', '13:30:00', '2025-06-13'),
(166, 7, 166, 'Completed', '08:01:00', '08:15:40', '08:31:40', '11:16:40', '12:01:40', '18:00:00', '2025-06-13'),
(167, 10, 167, 'Completed', '10:02:00', '10:16:25', '10:33:25', '13:23:25', '14:08:25', '13:00:00', '2025-06-14'),
(168, 4, 168, 'Completed', '11:18:00', '11:43:24', '12:01:24', '14:56:24', '15:41:24', '15:00:00', '2025-06-14'),
(169, 1, 169, 'Completed', '08:49:00', '09:12:04', '09:31:04', '12:31:04', '13:16:04', '13:00:00', '2025-06-14'),
(170, 20, 170, 'Completed', '10:30:00', '10:47:07', '11:02:07', '13:02:07', '13:47:07', '17:30:00', '2025-06-14'),
(171, 15, 171, 'Completed', '07:46:00', '07:57:23', '08:13:23', '10:13:23', '10:58:23', '20:30:00', '2025-06-14'),
(172, 2, 172, 'Completed', '09:17:00', '09:42:33', '09:59:33', '12:09:33', '12:54:33', '15:00:00', '2025-06-15'),
(173, 6, 173, 'Completed', '11:03:00', '11:19:22', '11:37:22', '13:57:22', '14:42:22', '14:30:00', '2025-06-16'),
(174, 16, 174, 'Completed', '08:34:00', '08:47:39', '09:06:39', '11:36:39', '12:21:39', '14:00:00', '2025-06-16'),
(175, 12, 175, 'Completed', '07:15:00', '07:30:28', '07:45:28', '10:25:28', '11:10:28', '19:30:00', '2025-06-17'),
(176, 19, 176, 'Completed', '09:46:00', '09:57:35', '10:13:35', '12:58:35', '13:43:35', '13:00:00', '2025-06-18'),
(177, 7, 177, 'Completed', '10:17:00', '10:28:57', '10:45:57', '13:35:57', '14:20:57', '17:00:00', '2025-06-20'),
(178, 10, 178, 'Completed', '09:03:00', '09:23:03', '09:41:03', '12:36:03', '13:21:03', '12:00:00', '2025-06-21'),
(179, 4, 179, 'Completed', '07:34:00', '08:01:21', '08:20:21', '11:20:21', '12:05:21', '16:00:00', '2025-06-21'),
(180, 1, 180, 'Completed', '09:30:00', '09:39:24', '09:54:24', '11:54:24', '12:39:24', '13:00:00', '2025-06-21'),
(181, 2, 181, 'Completed', '10:46:00', '10:52:53', '11:08:53', '13:08:53', '13:53:53', '15:00:00', '2025-06-22'),
(182, 3, 182, 'Completed', '08:17:00', '08:49:05', '09:06:05', '11:16:05', '12:01:05', '18:00:00', '2025-06-22'),
(183, 11, 183, 'Completed', '08:03:00', '08:29:13', '08:47:13', '11:07:13', '11:52:13', '16:30:00', '2025-06-22'),
(184, 21, 184, 'Completed', '10:04:00', '10:09:28', '10:28:28', '12:58:28', '13:43:28', '14:30:00', '2025-06-22'),
(185, 16, 185, 'Completed', '11:15:00', '11:45:36', '12:00:36', '14:40:36', '15:25:36', '15:00:00', '2025-06-23'),
(186, 12, 186, 'Completed', '08:46:00', '08:54:55', '09:10:55', '11:55:55', '12:40:55', '18:30:00', '2025-06-24'),
(187, 19, 187, 'Completed', '10:32:00', '10:50:40', '11:07:40', '13:57:40', '14:42:40', '14:00:00', '2025-06-25'),
(188, 7, 188, 'Completed', '07:48:00', '08:09:55', '08:27:55', '11:22:55', '12:07:55', '18:00:00', '2025-06-27'),
(189, 5, 189, 'Completed', '09:19:00', '09:37:03', '09:56:03', '12:56:03', '13:41:03', '19:00:00', '2025-06-27'),
(190, 10, 190, 'Completed', '11:00:00', '11:07:48', '11:22:48', '13:22:48', '14:07:48', '13:00:00', '2025-06-28'),
(191, 4, 191, 'Completed', '08:31:00', '09:09:25', '09:25:25', '11:25:25', '12:10:25', '15:00:00', '2025-06-28'),
(192, 1, 192, 'Completed', '07:17:00', '07:40:37', '07:57:37', '10:07:37', '10:52:37', '14:00:00', '2025-06-28'),
(193, 20, 193, 'Completed', '09:48:00', '10:10:18', '10:28:18', '12:48:18', '13:33:18', '18:30:00', '2025-06-28'),
(194, 15, 194, 'Completed', '10:19:00', '10:29:43', '10:48:43', '13:18:43', '14:03:43', '19:30:00', '2025-06-28'),
(195, 18, 195, 'Completed', '09:00:00', '09:20:47', '09:35:47', '12:15:47', '13:00:47', '19:00:00', '2025-06-28'),
(196, 2, 196, 'Completed', '07:31:00', '07:37:41', '07:53:41', '10:38:41', '11:23:41', '14:00:00', '2025-06-29'),
(197, 16, 197, 'Completed', '09:32:00', '09:53:15', '10:10:15', '13:00:15', '13:45:15', '14:00:00', '2025-06-30'),
(198, 6, 198, 'Completed', '10:48:00', '10:54:13', '11:12:13', '14:07:13', '14:52:13', '15:30:00', '2025-06-30'),
(199, 22, 199, 'Completed', '08:19:00', '08:26:15', '08:45:15', '11:45:15', '12:30:15', '19:00:00', '2025-06-30'),
(200, 19, 200, 'Completed', '08:00:00', '08:12:23', '08:27:23', '10:27:23', '11:12:23', '13:00:00', '2025-07-02'),
(201, 12, 201, 'Completed', '10:01:00', '10:19:51', '10:35:51', '12:35:51', '13:20:51', '18:30:00', '2025-07-01'),
(202, 7, 202, 'Completed', '11:17:00', '11:35:41', '11:52:41', '14:02:41', '14:47:41', '17:00:00', '2025-07-04'),
(203, 14, 203, 'Completed', '08:48:00', '09:14:07', '09:32:07', '11:52:07', '12:37:07', '21:00:00', '2025-07-04'),
(204, 10, 204, 'Completed', '10:34:00', '10:47:13', '11:06:13', '13:36:13', '14:21:13', '12:00:00', '2025-07-05'),
(205, 4, 205, 'Completed', '07:45:00', '07:52:51', '08:07:51', '10:47:51', '11:32:51', '16:00:00', '2025-07-05'),
(206, 1, 206, 'Completed', '09:16:00', '09:39:59', '09:55:59', '12:40:59', '13:25:59', '13:00:00', '2025-07-05'),
(207, 22, 207, 'Completed', '11:02:00', '11:17:05', '11:34:05', '14:24:05', '15:09:05', '20:00:00', '2025-07-05'),
(208, 2, 208, 'Completed', '08:33:00', '08:58:16', '09:16:16', '12:11:16', '12:56:16', '14:00:00', '2025-07-06'),
(209, 3, 209, 'Completed', '07:19:00', '07:53:31', '08:12:31', '11:12:31', '11:57:31', '19:00:00', '2025-07-06'),
(210, 11, 210, 'Completed', '09:45:00', '10:09:25', '10:24:25', '12:24:25', '13:09:25', '15:30:00', '2025-07-06'),
(211, 16, 211, 'Completed', '10:16:00', '10:32:16', '10:48:16', '12:48:16', '13:33:16', '15:00:00', '2025-07-07'),
(212, 12, 212, 'Completed', '09:02:00', '09:21:16', '09:38:16', '11:48:16', '12:33:16', '18:30:00', '2025-07-08'),
(213, 23, 213, 'Completed', '07:33:00', '08:01:04', '08:19:04', '10:39:04', '11:24:04', '16:30:00', '2025-07-08'),
(214, 19, 214, 'Completed', '09:34:00', '09:45:32', '10:04:32', '12:34:32', '13:19:32', '14:00:00', '2025-07-09'),
(215, 17, 215, 'Completed', '10:45:00', '10:57:32', '11:12:32', '13:52:32', '14:37:32', '16:00:00', '2025-07-10'),
(216, 7, 216, 'Completed', '08:16:00', '08:34:29', '08:50:29', '11:35:29', '12:20:29', '18:00:00', '2025-07-11'),
(217, 10, 217, 'Completed', '08:02:00', '08:29:49', '08:46:49', '11:36:49', '12:21:49', '12:00:00', '2025-07-12'),
(218, 4, 218, 'Completed', '10:03:00', '10:16:54', '10:34:54', '13:29:54', '14:14:54', '15:00:00', '2025-07-12'),
(219, 1, 219, 'Completed', '11:19:00', '11:53:09', '12:12:09', '15:12:09', '15:57:09', '14:00:00', '2025-07-12'),
(220, 20, 220, 'Completed', '08:45:00', '09:13:38', '09:28:38', '11:28:38', '12:13:38', '17:30:00', '2025-07-12'),
(221, 15, 221, 'Completed', '10:31:00', '11:00:46', '11:16:46', '13:16:46', '14:01:46', '20:30:00', '2025-07-12'),
(222, 22, 222, 'Completed', '07:47:00', '08:09:46', '08:26:46', '10:36:46', '11:21:46', '19:00:00', '2025-07-12'),
(223, 2, 223, 'Completed', '09:18:00', '09:25:57', '09:43:57', '12:03:57', '12:48:57', '15:00:00', '2025-07-13'),
(224, 6, 224, 'Completed', '11:04:00', '11:12:07', '11:31:07', '14:01:07', '14:46:07', '15:30:00', '2025-07-14'),
(225, 16, 225, 'Completed', '08:30:00', '08:50:25', '09:05:25', '11:45:25', '12:30:25', '14:00:00', '2025-07-14'),
(226, 9, 226, 'Completed', '07:16:00', '07:39:14', '07:55:14', '10:40:14', '11:25:14', '14:30:00', '2025-07-15'),
(227, 12, 227, 'Completed', '09:47:00', '10:03:03', '10:20:03', '13:10:03', '13:55:03', '19:30:00', '2025-07-15'),
(228, 19, 228, 'Completed', '10:18:00', '10:42:58', '11:00:58', '13:55:58', '14:40:58', '13:00:00', '2025-07-16'),
(229, 24, 229, 'Completed', '09:04:00', '09:27:09', '09:46:09', '12:46:09', '13:31:09', '20:00:00', '2025-07-16'),
(230, 7, 230, 'Completed', '07:30:00', '07:55:09', '08:10:09', '10:10:09', '10:55:09', '17:00:00', '2025-07-18'),
(231, 21, 231, 'Completed', '09:31:00', '09:51:51', '10:07:51', '12:07:51', '12:52:51', '15:30:00', '2025-07-18'),
(232, 10, 232, 'Completed', '10:47:00', '11:10:47', '11:27:47', '13:37:47', '14:22:47', '13:00:00', '2025-07-19'),
(233, 4, 233, 'Completed', '08:18:00', '08:37:15', '08:55:15', '11:15:15', '12:00:15', '15:00:00', '2025-07-19'),
(234, 1, 234, 'Completed', '08:04:00', '08:30:23', '08:49:23', '11:19:23', '12:04:23', '14:00:00', '2025-07-19'),
(235, 22, 235, 'Completed', '10:00:00', '10:06:56', '10:21:56', '13:01:56', '13:46:56', '19:00:00', '2025-07-19'),
(236, 2, 236, 'Completed', '11:16:00', '11:42:24', '11:58:24', '14:43:24', '15:28:24', '14:00:00', '2025-07-20'),
(237, 3, 237, 'Completed', '08:47:00', '09:03:34', '09:20:34', '12:10:34', '12:55:34', '19:00:00', '2025-07-20'),
(238, 11, 238, 'Completed', '10:33:00', '10:38:36', '10:56:36', '13:51:36', '14:36:36', '15:30:00', '2025-07-20'),
(239, 13, 239, 'Completed', '07:49:00', '08:07:50', '08:26:50', '11:26:50', '12:11:50', '17:00:00', '2025-07-20'),
(240, 16, 240, 'Completed', '09:15:00', '09:37:23', '09:52:23', '11:52:23', '12:37:23', '14:00:00', '2025-07-21'),
(241, 12, 241, 'Completed', '11:01:00', '11:28:33', '11:44:33', '13:44:33', '14:29:33', '18:30:00', '2025-07-22'),
(242, 19, 242, 'Completed', '08:32:00', '08:57:25', '09:14:25', '11:24:25', '12:09:25', '14:00:00', '2025-07-23'),
(243, 25, 243, 'Completed', '07:18:00', '07:47:57', '08:05:57', '10:25:57', '11:10:57', '12:30:00', '2025-07-24'),
(244, 7, 244, 'Completed', '09:49:00', '10:07:32', '10:26:32', '12:56:32', '13:41:32', '18:00:00', '2025-07-25'),
(245, 5, 245, 'Completed', '10:15:00', '10:24:32', '10:39:32', '13:19:32', '14:04:32', '20:00:00', '2025-07-25'),
(246, 10, 246, 'Completed', '09:01:00', '09:23:47', '09:39:47', '12:24:47', '13:09:47', '12:00:00', '2025-07-26'),
(247, 4, 247, 'Completed', '07:32:00', '07:54:04', '08:11:04', '11:01:04', '11:46:04', '16:00:00', '2025-07-26'),
(248, 1, 248, 'Completed', '09:33:00', '10:01:22', '10:19:22', '13:14:22', '13:59:22', '13:00:00', '2025-07-26'),
(249, 20, 249, 'Completed', '10:49:00', '11:07:30', '11:26:30', '14:26:30', '15:11:30', '18:30:00', '2025-07-26'),
(250, 15, 250, 'Completed', '08:15:00', '08:42:23', '08:57:23', '10:57:23', '11:42:23', '19:30:00', '2025-07-26'),
(251, 22, 251, 'Completed', '08:01:00', '08:21:35', '08:37:35', '10:37:35', '11:22:35', '20:00:00', '2025-07-26'),
(252, 18, 252, 'Completed', '10:02:00', '10:25:34', '10:42:34', '12:52:34', '13:37:34', '18:00:00', '2025-07-26'),
(253, 2, 253, 'Completed', '11:18:00', '11:48:10', '12:06:10', '14:26:10', '15:11:10', '15:00:00', '2025-07-27'),
(254, 6, 254, 'Completed', '08:49:00', '09:15:27', '09:34:27', '12:04:27', '12:49:27', '14:30:00', '2025-07-28'),
(255, 16, 255, 'Completed', '10:30:00', '10:50:04', '11:05:04', '13:45:04', '14:30:04', '15:00:00', '2025-07-28'),
(256, 12, 256, 'Completed', '07:46:00', '08:10:58', '08:26:58', '11:11:58', '11:56:58', '19:30:00', '2025-07-29'),
(257, 19, 257, 'Completed', '09:17:00', '09:33:00', '09:50:00', '12:40:00', '13:25:00', '13:00:00', '2025-07-30'),
(258, 24, 258, 'Completed', '11:03:00', '11:34:43', '11:52:43', '14:47:43', '15:32:43', '21:00:00', '2025-07-30'),
(259, 7, 259, 'Completed', '08:34:00', '08:48:51', '09:07:51', '12:07:51', '12:52:51', '18:00:00', '2025-08-01'),
(260, 25, 260, 'Completed', '07:15:00', '07:36:12', '07:51:12', '09:51:12', '10:36:12', '13:30:00', '2025-08-01'),
(261, 14, 261, 'Completed', '09:46:00', '10:07:51', '10:23:51', '12:23:51', '13:08:51', '20:00:00', '2025-08-01'),
(262, 10, 262, 'Completed', '10:17:00', '10:23:31', '10:40:31', '12:50:31', '13:35:31', '13:00:00', '2025-08-02'),
(263, 4, 263, 'Completed', '09:03:00', '09:18:11', '09:36:11', '11:56:11', '12:41:11', '15:00:00', '2025-08-02'),
(264, 1, 264, 'Completed', '07:34:00', '07:47:58', '08:06:58', '10:36:58', '11:21:58', '14:00:00', '2025-08-02'),
(265, 22, 265, 'Completed', '09:30:00', '09:41:13', '09:56:13', '12:36:13', '13:21:13', '19:00:00', '2025-08-02'),
(266, 26, 266, 'Completed', '10:46:00', '10:55:09', '11:11:09', '13:56:09', '14:41:09', '19:30:00', '2025-08-02'),
(267, 2, 267, 'Completed', '08:17:00', '08:37:19', '08:54:19', '11:44:19', '12:29:19', '14:00:00', '2025-08-03'),
(268, 3, 268, 'Completed', '08:03:00', '08:13:11', '08:31:11', '11:26:11', '12:11:11', '18:00:00', '2025-08-03'),
(269, 11, 269, 'Completed', '10:04:00', '10:33:41', '10:52:41', '13:52:41', '14:37:41', '16:30:00', '2025-08-03'),
(270, 16, 270, 'Completed', '11:15:00', '11:25:23', '11:40:23', '13:40:23', '14:25:23', '14:00:00', '2025-08-04'),
(271, 12, 271, 'Completed', '08:46:00', '09:17:24', '09:33:24', '11:33:24', '12:18:24', '19:30:00', '2025-08-05'),
(272, 19, 272, 'Completed', '10:32:00', '10:39:26', '10:56:26', '13:06:26', '13:51:26', '13:00:00', '2025-08-06'),
(273, 7, 273, 'Completed', '07:48:00', '08:06:11', '08:24:11', '10:44:11', '11:29:11', '17:00:00', '2025-08-08'),
(274, 25, 274, 'Completed', '09:19:00', '09:32:34', '09:51:34', '12:21:34', '13:06:34', '12:30:00', '2025-08-08'),
(275, 10, 275, 'Completed', '11:00:00', '11:30:50', '11:45:50', '14:25:50', '15:10:50', '12:00:00', '2025-08-09'),
(276, 4, 276, 'Completed', '08:31:00', '08:47:11', '09:03:11', '11:48:11', '12:33:11', '16:00:00', '2025-08-09'),
(277, 1, 277, 'Completed', '07:17:00', '07:25:20', '07:42:20', '10:32:20', '11:17:20', '13:00:00', '2025-08-09'),
(278, 20, 278, 'Completed', '09:48:00', '10:08:56', '10:26:56', '13:21:56', '14:06:56', '18:30:00', '2025-08-09'),
(279, 15, 279, 'Completed', '10:19:00', '10:43:41', '11:02:41', '14:02:41', '14:47:41', '19:30:00', '2025-08-09'),
(280, 22, 280, 'Completed', '09:00:00', '09:18:27', '09:33:27', '11:33:27', '12:18:27', '20:00:00', '2025-08-09'),
(281, 2, 281, 'Completed', '07:31:00', '07:36:36', '07:52:36', '09:52:36', '10:37:36', '15:00:00', '2025-08-10'),
(282, 8, 282, 'Completed', '09:32:00', '09:53:44', '10:10:44', '12:20:44', '13:05:44', '20:00:00', '2025-08-10'),
(283, 6, 283, 'Completed', '10:48:00', '11:11:58', '11:29:58', '13:49:58', '14:34:58', '15:30:00', '2025-08-11'),
(284, 16, 284, 'Completed', '08:19:00', '08:42:05', '09:01:05', '11:31:05', '12:16:05', '14:00:00', '2025-08-11'),
(285, 27, 285, 'Completed', '08:00:00', '08:13:30', '08:28:30', '11:08:30', '11:53:30', '16:00:00', '2025-08-11'),
(286, 12, 286, 'Completed', '10:01:00', '10:25:58', '10:41:58', '13:26:58', '14:11:58', '18:30:00', '2025-08-12'),
(287, 19, 287, 'Completed', '11:17:00', '11:44:23', '12:01:23', '14:51:23', '15:36:23', '14:00:00', '2025-08-13'),
(288, 24, 288, 'Completed', '08:48:00', '09:10:16', '09:28:16', '12:23:16', '13:08:16', '21:00:00', '2025-08-13'),
(289, 9, 289, 'Completed', '10:34:00', '10:52:17', '11:11:17', '14:11:17', '14:56:17', '13:30:00', '2025-08-14'),
(290, 7, 290, 'Completed', '07:45:00', '08:05:55', '08:20:55', '10:20:55', '11:05:55', '18:00:00', '2025-08-15'),
(291, 25, 291, 'Completed', '09:16:00', '09:35:43', '09:51:43', '11:51:43', '12:36:43', '13:30:00', '2025-08-15'),
(292, 21, 292, 'Completed', '11:02:00', '11:29:02', '11:46:02', '13:56:02', '14:41:02', '14:30:00', '2025-08-15'),
(293, 10, 293, 'Completed', '08:33:00', '08:38:52', '08:56:52', '11:16:52', '12:01:52', '12:00:00', '2025-08-16'),
(294, 4, 294, 'Completed', '07:19:00', '07:32:20', '07:51:20', '10:21:20', '11:06:20', '15:00:00', '2025-08-16'),
(295, 1, 295, 'Completed', '09:45:00', '10:00:11', '10:15:11', '12:55:11', '13:40:11', '14:00:00', '2025-08-16'),
(296, 22, 296, 'Completed', '10:16:00', '10:47:26', '11:03:26', '13:48:26', '14:33:26', '19:00:00', '2025-08-16'),
(297, 2, 297, 'Completed', '09:02:00', '09:30:21', '09:47:21', '12:37:21', '13:22:21', '14:00:00', '2025-08-17'),
(298, 3, 298, 'Completed', '07:33:00', '07:51:00', '08:09:00', '11:04:00', '11:49:00', '19:00:00', '2025-08-17'),
(299, 11, 299, 'Completed', '09:34:00', '09:50:13', '10:09:13', '13:09:13', '13:54:13', '15:30:00', '2025-08-17'),
(300, 16, 300, 'Completed', '10:45:00', '11:01:02', '11:16:02', '13:16:02', '14:01:02', '15:00:00', '2025-08-18'),
(301, 27, 301, 'Completed', '08:16:00', '08:42:31', '08:58:31', '10:58:31', '11:43:31', '17:00:00', '2025-08-18'),
(302, 12, 302, 'Completed', '08:02:00', '08:19:48', '08:36:48', '10:46:48', '11:31:48', '19:30:00', '2025-08-19'),
(303, 28, 303, 'Completed', '10:03:00', '10:32:29', '10:50:29', '13:10:29', '13:55:29', '14:30:00', '2025-08-19'),
(304, 19, 304, 'Completed', '11:19:00', '11:52:53', '12:11:53', '14:41:53', '15:26:53', '13:00:00', '2025-08-20'),
(305, 7, 305, 'Completed', '08:45:00', '08:57:52', '09:12:52', '11:52:52', '12:37:52', '17:00:00', '2025-08-22'),
(306, 25, 306, 'Completed', '10:31:00', '10:51:56', '11:07:56', '13:52:56', '14:37:56', '13:30:00', '2025-08-22'),
(307, 10, 307, 'Completed', '07:47:00', '07:58:04', '08:15:04', '11:05:04', '11:50:04', '13:00:00', '2025-08-23'),
(308, 4, 308, 'Completed', '09:18:00', '09:25:13', '09:43:13', '12:38:13', '13:23:13', '15:00:00', '2025-08-23'),
(309, 1, 309, 'Completed', '11:04:00', '11:24:26', '11:43:26', '14:43:26', '15:28:26', '14:00:00', '2025-08-23'),
(310, 20, 310, 'Completed', '08:30:00', '08:42:30', '08:57:30', '10:57:30', '11:42:30', '17:30:00', '2025-08-23'),
(311, 15, 311, 'Completed', '07:16:00', '07:40:22', '07:56:22', '09:56:22', '10:41:22', '20:30:00', '2025-08-23'),
(312, 22, 312, 'Completed', '09:47:00', '09:53:48', '10:10:48', '12:20:48', '13:05:48', '19:00:00', '2025-08-23'),
(313, 2, 313, 'Completed', '10:18:00', '10:47:18', '11:05:18', '13:25:18', '14:10:18', '15:00:00', '2025-08-24'),
(314, 6, 314, 'Completed', '09:04:00', '09:22:24', '09:41:24', '12:11:24', '12:56:24', '14:30:00', '2025-08-25'),
(315, 16, 315, 'Completed', '07:30:00', '07:54:06', '08:09:06', '10:49:06', '11:34:06', '14:00:00', '2025-08-25'),
(316, 27, 316, 'Completed', '09:31:00', '09:41:18', '09:57:18', '12:42:18', '13:27:18', '16:00:00', '2025-08-25'),
(317, 12, 317, 'Completed', '10:47:00', '11:12:30', '11:29:30', '14:19:30', '15:04:30', '19:30:00', '2025-08-26'),
(318, 19, 318, 'Completed', '08:18:00', '08:42:14', '09:00:14', '11:55:14', '12:40:14', '13:00:00', '2025-08-27'),
(319, 24, 319, 'Completed', '08:04:00', '08:21:14', '08:40:14', '11:40:14', '12:25:14', '20:00:00', '2025-08-27'),
(320, 29, 320, 'Completed', '10:00:00', '10:07:20', '10:22:20', '12:22:20', '13:07:20', '20:00:00', '2025-08-27'),
(321, 7, 321, 'Completed', '11:16:00', '11:47:16', '12:03:16', '14:03:16', '14:48:16', '18:00:00', '2025-08-29'),
(322, 25, 322, 'Completed', '08:47:00', '08:57:14', '09:14:14', '11:24:14', '12:09:14', '12:30:00', '2025-08-29'),
(323, 5, 323, 'Completed', '10:33:00', '10:44:17', '11:02:17', '13:22:17', '14:07:17', '19:00:00', '2025-08-29'),
(324, 10, 324, 'Completed', '07:49:00', '08:12:30', '08:31:30', '11:01:30', '11:46:30', '12:00:00', '2025-08-30'),
(325, 4, 325, 'Completed', '09:15:00', '09:29:47', '09:44:47', '12:24:47', '13:09:47', '16:00:00', '2025-08-30'),
(326, 1, 326, 'Completed', '11:01:00', '11:06:09', '11:22:09', '14:07:09', '14:52:09', '13:00:00', '2025-08-30'),
(327, 22, 327, 'Completed', '08:32:00', '08:39:46', '08:56:46', '11:46:46', '12:31:46', '20:00:00', '2025-08-30'),
(328, 18, 328, 'Completed', '07:18:00', '07:47:25', '08:05:25', '11:00:25', '11:45:25', '18:00:00', '2025-08-30'),
(329, 2, 329, 'Completed', '09:49:00', '09:55:49', '10:14:49', '13:14:49', '13:59:49', '14:00:00', '2025-08-31'),
(330, 3, 330, 'Completed', '10:15:00', '10:53:13', '11:08:13', '13:08:13', '13:53:13', '19:00:00', '2025-08-31'),
(331, 11, 331, 'Completed', '09:01:00', '09:09:34', '09:25:34', '11:25:34', '12:10:34', '15:30:00', '2025-08-31'),
(332, 16, 332, 'Completed', '07:32:00', '07:49:26', '08:06:26', '10:16:26', '11:01:26', '15:00:00', '2025-09-01'),
(333, 27, 333, 'Completed', '09:33:00', '09:39:26', '09:57:26', '12:17:26', '13:02:26', '16:00:00', '2025-09-01'),
(334, 12, 334, 'Completed', '10:49:00', '10:55:25', '11:14:25', '13:44:25', '14:29:25', '18:30:00', '2025-09-02'),
(335, 19, 335, 'Completed', '08:15:00', '08:39:55', '08:54:55', '11:34:55', '12:19:55', '14:00:00', '2025-09-03'),
(336, 27, 336, 'Completed', '08:01:00', '08:26:18', '08:42:18', '11:27:18', '12:12:18', '17:00:00', '2025-09-04'),
(337, 7, 337, 'Completed', '10:02:00', '10:23:44', '10:40:44', '13:30:44', '14:15:44', '17:00:00', '2025-09-05'),
(338, 25, 338, 'Completed', '11:18:00', '11:29:39', '11:47:39', '14:42:39', '15:27:39', '13:30:00', '2025-09-05'),
(339, 14, 339, 'Completed', '08:49:00', '09:11:10', '09:30:10', '12:30:10', '13:15:10', '20:00:00', '2025-09-05'),
(340, 17, 340, 'Completed', '10:30:00', '10:57:54', '11:12:54', '13:12:54', '13:57:54', '15:00:00', '2025-09-05'),
(341, 30, 341, 'Completed', '07:46:00', '08:05:49', '08:21:49', '10:21:49', '11:06:49', '21:00:00', '2025-09-05'),
(342, 10, 342, 'Completed', '09:17:00', '09:44:45', '10:01:45', '12:11:45', '12:56:45', '12:00:00', '2025-09-06'),
(343, 4, 343, 'Completed', '11:03:00', '11:37:02', '11:55:02', '14:15:02', '15:00:02', '16:00:00', '2025-09-06'),
(344, 1, 344, 'Completed', '08:34:00', '08:52:33', '09:11:33', '11:41:33', '12:26:33', '13:00:00', '2025-09-06'),
(345, 20, 345, 'Completed', '07:15:00', '07:44:33', '07:59:33', '10:39:33', '11:24:33', '18:30:00', '2025-09-06'),
(346, 15, 346, 'Completed', '09:46:00', '09:58:08', '10:14:08', '12:59:08', '13:44:08', '19:30:00', '2025-09-06'),
(347, 22, 347, 'Completed', '10:17:00', '10:39:29', '10:56:29', '13:46:29', '14:31:29', '20:00:00', '2025-09-06'),
(348, 2, 348, 'Completed', '09:03:00', '09:19:58', '09:37:58', '12:32:58', '13:17:58', '14:00:00', '2025-09-07'),
(349, 26, 349, 'Completed', '07:34:00', '07:44:23', '08:03:23', '11:03:23', '11:48:23', '19:30:00', '2025-09-07'),
(350, 6, 350, 'Completed', '09:30:00', '09:48:28', '10:03:28', '12:03:28', '12:48:28', '15:30:00', '2025-09-08'),
(351, 16, 351, 'Completed', '10:46:00', '10:57:28', '11:13:28', '13:13:28', '13:58:28', '14:00:00', '2025-09-08'),
(352, 12, 352, 'Completed', '08:17:00', '08:39:30', '08:56:30', '11:06:30', '11:51:30', '19:30:00', '2025-09-09'),
(353, 19, 353, 'Completed', '08:03:00', '08:24:54', '08:42:54', '11:02:54', '11:47:54', '13:00:00', '2025-09-10'),
(354, 24, 354, 'Completed', '10:04:00', '10:14:11', '10:33:11', '13:03:11', '13:48:11', '21:00:00', '2025-09-10'),
(355, 29, 355, 'Completed', '11:15:00', '11:35:58', '11:50:58', '14:30:58', '15:15:58', '19:00:00', '2025-09-10'),
(356, 27, 356, 'Completed', '08:46:00', '09:06:09', '09:22:09', '12:07:09', '12:52:09', '16:00:00', '2025-09-11'),
(357, 7, 357, 'Completed', '10:32:00', '10:37:03', '10:54:03', '13:44:03', '14:29:03', '18:00:00', '2025-09-12'),
(358, 25, 358, 'Completed', '07:48:00', '07:57:47', '08:15:47', '11:10:47', '11:55:47', '12:30:00', '2025-09-12'),
(359, 10, 359, 'Completed', '09:19:00', '09:47:48', '10:06:48', '13:06:48', '13:51:48', '13:00:00', '2025-09-13'),
(360, 4, 360, 'Completed', '11:00:00', '11:06:20', '11:21:20', '13:21:20', '14:06:20', '15:00:00', '2025-09-13'),
(361, 1, 361, 'Completed', '08:31:00', '08:40:37', '08:56:37', '10:56:37', '11:41:37', '14:00:00', '2025-09-13'),
(362, 22, 362, 'Completed', '07:17:00', '07:46:11', '08:03:11', '10:13:11', '10:58:11', '19:00:00', '2025-09-13'),
(363, 3, 363, 'Completed', '09:48:00', '10:21:50', '10:39:50', '12:59:50', '13:44:50', '18:00:00', '2025-09-14'),
(364, 11, 364, 'Completed', '10:19:00', '10:25:45', '10:44:45', '13:14:45', '13:59:45', '16:30:00', '2025-09-14'),
(365, 30, 365, 'Completed', '09:00:00', '09:12:25', '09:27:25', '12:07:25', '12:52:25', '20:00:00', '2025-09-14'),
(366, 31, 366, 'Completed', '07:31:00', '07:47:54', '08:03:54', '10:48:54', '11:33:54', '15:00:00', '2025-09-14'),
(367, 23, 367, 'Completed', '09:32:00', '09:56:51', '10:13:51', '13:03:51', '13:48:51', '16:30:00', '2025-09-15'),
(368, 16, 368, 'Completed', '10:48:00', '11:12:02', '11:30:02', '14:25:02', '15:10:02', '14:00:00', '2025-09-15'),
(369, 9, 369, 'Completed', '08:19:00', '08:34:38', '08:53:38', '11:53:38', '12:38:38', '14:30:00', '2025-09-16'),
(370, 12, 370, 'Completed', '08:00:00', '08:26:58', '08:41:58', '10:41:58', '11:26:58', '18:30:00', '2025-09-16'),
(371, 19, 371, 'Completed', '10:01:00', '10:27:06', '10:43:06', '12:43:06', '13:28:06', '14:00:00', '2025-09-17'),
(372, 27, 372, 'Completed', '11:17:00', '11:30:38', '11:47:38', '13:57:38', '14:42:38', '17:00:00', '2025-09-18'),
(373, 7, 373, 'Completed', '08:48:00', '09:16:35', '09:34:35', '11:54:35', '12:39:35', '17:00:00', '2025-09-19'),
(374, 25, 374, 'Completed', '10:34:00', '10:43:38', '11:02:38', '13:32:38', '14:17:38', '13:30:00', '2025-09-19'),
(375, 21, 375, 'Completed', '07:45:00', '07:52:26', '08:07:26', '10:47:26', '11:32:26', '14:30:00', '2025-09-19'),
(376, 10, 376, 'Completed', '09:16:00', '09:44:15', '10:00:15', '12:45:15', '13:30:15', '12:00:00', '2025-09-20'),
(377, 4, 377, 'Completed', '11:02:00', '11:23:42', '11:40:42', '14:30:42', '15:15:42', '16:00:00', '2025-09-20'),
(378, 1, 378, 'Completed', '08:33:00', '08:46:59', '09:04:59', '11:59:59', '12:44:59', '13:00:00', '2025-09-20'),
(379, 20, 379, 'Completed', '07:19:00', '07:24:11', '07:43:11', '10:43:11', '11:28:11', '17:30:00', '2025-09-20'),
(380, 15, 380, 'Completed', '09:45:00', '10:13:59', '10:28:59', '12:28:59', '13:13:59', '20:30:00', '2025-09-20'),
(381, 22, 381, 'Completed', '10:16:00', '10:30:09', '10:46:09', '12:46:09', '13:31:09', '19:00:00', '2025-09-20'),
(382, 31, 382, 'Completed', '09:02:00', '09:11:08', '09:28:08', '11:38:08', '12:23:08', '16:00:00', '2025-09-20'),
(383, 30, 383, 'Completed', '07:33:00', '08:02:45', '08:20:45', '10:40:45', '11:25:45', '21:00:00', '2025-09-21'),
(384, 6, 384, 'Completed', '09:34:00', '09:58:21', '10:17:21', '12:47:21', '13:32:21', '14:30:00', '2025-09-22'),
(385, 16, 385, 'Completed', '10:45:00', '11:14:48', '11:29:48', '14:09:48', '14:54:48', '15:00:00', '2025-09-22'),
(386, 32, 386, 'Completed', '08:16:00', '08:37:00', '08:53:00', '11:38:00', '12:23:00', '13:30:00', '2025-09-22'),
(387, 12, 387, 'Completed', '08:02:00', '08:34:34', '08:51:34', '11:41:34', '12:26:34', '19:30:00', '2025-09-23'),
(388, 19, 388, 'Completed', '10:03:00', '10:13:33', '10:31:33', '13:26:33', '14:11:33', '13:00:00', '2025-09-24'),
(389, 24, 389, 'Completed', '11:19:00', '11:27:33', '11:46:33', '14:46:33', '15:31:33', '20:00:00', '2025-09-24'),
(390, 29, 390, 'Completed', '08:45:00', '08:56:25', '09:11:25', '11:11:25', '11:56:25', '20:00:00', '2025-09-24'),
(391, 27, 391, 'Completed', '10:31:00', '10:40:49', '10:56:49', '12:56:49', '13:41:49', '16:00:00', '2025-09-25'),
(392, 7, 392, 'Completed', '07:47:00', '07:59:23', '08:16:23', '10:26:23', '11:11:23', '18:00:00', '2025-09-26'),
(393, 25, 393, 'Completed', '09:18:00', '09:45:31', '10:03:31', '12:23:31', '13:08:31', '12:30:00', '2025-09-26'),
(394, 5, 394, 'Completed', '11:04:00', '11:36:48', '11:55:48', '14:25:48', '15:10:48', '19:00:00', '2025-09-26'),
(395, 10, 395, 'Completed', '08:30:00', '08:50:25', '09:05:25', '11:45:25', '12:30:25', '12:00:00', '2025-09-27'),
(396, 4, 396, 'Completed', '07:16:00', '07:48:45', '08:04:45', '10:49:45', '11:34:45', '16:00:00', '2025-09-27'),
(397, 1, 397, 'Completed', '09:47:00', '10:01:33', '10:18:33', '13:08:33', '13:53:33', '13:00:00', '2025-09-27'),
(398, 22, 398, 'Completed', '10:18:00', '10:33:18', '10:51:18', '13:46:18', '14:31:18', '20:00:00', '2025-09-27'),
(399, 18, 399, 'Completed', '09:04:00', '09:23:48', '09:42:48', '12:42:48', '13:27:48', '18:00:00', '2025-09-27'),
(400, 11, 400, 'Completed', '07:30:00', '07:36:31', '07:51:31', '09:51:31', '10:36:31', '15:30:00', '2025-09-28'),
(401, 33, 401, 'Completed', '09:31:00', '10:01:37', '10:17:37', '12:17:37', '13:02:37', '19:00:00', '2025-09-28'),
(402, 30, 402, 'Completed', '10:47:00', '11:18:05', '11:35:05', '13:45:05', '14:30:05', '20:00:00', '2025-09-28'),
(403, 16, 403, 'Completed', '08:18:00', '08:26:58', '08:44:58', '11:04:58', '11:49:58', '14:00:00', '2025-09-29'),
(404, 12, 404, 'Completed', '08:04:00', '08:09:53', '08:28:53', '10:58:53', '11:43:53', '18:30:00', '2025-09-30'),
(405, 24, 405, 'Completed', '10:00:00', '10:18:39', '10:33:39', '13:13:39', '13:58:39', '21:00:00', '2025-10-01'),
(406, 19, 406, 'Completed', '11:16:00', '11:47:24', '12:03:24', '14:48:24', '15:33:24', '13:00:00', '2025-10-01'),
(407, 29, 407, 'Completed', '08:47:00', '09:09:11', '09:26:11', '12:16:11', '13:01:11', '19:00:00', '2025-10-01'),
(408, 34, 408, 'Completed', '10:33:00', '10:53:15', '11:11:15', '14:06:15', '14:51:15', '18:30:00', '2025-10-02'),
(409, 28, 409, 'Completed', '07:49:00', '08:08:24', '08:27:24', '11:27:24', '12:12:24', '15:30:00', '2025-10-02'),
(410, 25, 410, 'Completed', '09:15:00', '09:21:13', '09:36:13', '11:36:13', '12:21:13', '12:30:00', '2025-10-03'),
(411, 21, 411, 'Completed', '11:01:00', '11:19:29', '11:35:29', '13:35:29', '14:20:29', '15:30:00', '2025-10-03'),
(412, 32, 412, 'Completed', '08:32:00', '08:49:56', '09:06:56', '11:16:56', '12:01:56', '14:30:00', '2025-10-03'),
(413, 26, 413, 'Completed', '07:18:00', '07:44:02', '08:02:02', '10:22:02', '11:07:02', '19:30:00', '2025-10-04'),
(414, 1, 414, 'Completed', '09:49:00', '10:04:59', '10:23:59', '12:53:59', '13:38:59', '13:00:00', '2025-10-04'),
(415, 20, 415, 'Completed', '10:15:00', '10:36:09', '10:51:09', '13:31:09', '14:16:09', '18:30:00', '2025-10-04'),
(416, 30, 416, 'Completed', '09:01:00', '09:32:02', '09:48:02', '12:33:02', '13:18:02', '20:00:00', '2025-10-04'),
(417, 31, 417, 'Completed', '07:32:00', '07:44:40', '08:01:40', '10:51:40', '11:36:40', '15:00:00', '2025-10-04'),
(418, 35, 418, 'Completed', '09:33:00', '10:00:47', '10:18:47', '13:13:47', '13:58:47', '15:00:00', '2025-10-04'),
(419, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(420, 1, 2, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(421, 1, 3, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(422, 2, 4, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(423, 1, 5, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(424, 2, 6, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(425, 1, 7, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(426, 2, 8, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(427, 3, 9, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(428, 1, 10, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(429, 2, 11, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(430, 4, 12, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(431, 1, 13, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(432, 2, 14, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(433, 3, 15, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(434, 1, 16, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(435, 4, 17, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(436, 2, 18, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(437, 5, 19, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(438, 4, 20, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(439, 1, 21, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(440, 2, 22, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(441, 3, 23, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(442, 1, 24, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(443, 4, 25, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(444, 2, 26, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(445, 6, 27, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(446, 4, 28, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(447, 1, 29, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(448, 2, 30, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(449, 3, 31, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(450, 4, 32, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(451, 1, 33, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(452, 7, 34, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(453, 2, 35, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(454, 6, 36, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(455, 5, 37, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(456, 7, 38, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(457, 4, 39, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07');
INSERT INTO `tracking` (`Tracking_ID`, `Customer_ID`, `Schedule_ID`, `laundry_status`, `Scheduled_time`, `Pickup_time`, `Processing_time`, `Ready_time`, `Completed_time`, `tracking_time`, `tracking_date`) VALUES
(458, 1, 40, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(459, 2, 41, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(460, 3, 42, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(461, 7, 43, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(462, 8, 44, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(463, 4, 45, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(464, 1, 46, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(465, 2, 47, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(466, 6, 48, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(467, 7, 49, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(468, 9, 50, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(469, 4, 51, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(470, 1, 52, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(471, 2, 53, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(472, 3, 54, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(473, 7, 55, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(474, 10, 56, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(475, 4, 57, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(476, 1, 58, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(477, 2, 59, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(478, 6, 60, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(479, 7, 61, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(480, 5, 62, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(481, 10, 63, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(482, 4, 64, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(483, 1, 65, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(484, 2, 66, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(485, 3, 67, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(486, 11, 68, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(487, 7, 69, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(488, 10, 70, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(489, 4, 71, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(490, 1, 72, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(491, 2, 73, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(492, 6, 74, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(493, 12, 75, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(494, 7, 76, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(495, 10, 77, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(496, 4, 78, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(497, 1, 79, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(498, 2, 80, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(499, 3, 81, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(500, 11, 82, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(501, 9, 83, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(502, 12, 84, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(503, 13, 85, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(504, 7, 86, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(505, 10, 87, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(506, 4, 88, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(507, 1, 89, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(508, 2, 90, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(509, 6, 91, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(510, 12, 92, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(511, 7, 93, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(512, 5, 94, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(513, 14, 95, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(514, 10, 96, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(515, 4, 97, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(516, 1, 98, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(517, 2, 99, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(518, 3, 100, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(519, 11, 101, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(520, 12, 102, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(521, 7, 103, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(522, 14, 104, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(523, 10, 105, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(524, 4, 106, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(525, 1, 107, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(526, 15, 108, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(527, 2, 109, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(528, 6, 110, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(529, 12, 111, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(530, 7, 112, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(531, 10, 113, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(532, 4, 114, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(533, 1, 115, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(534, 2, 116, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(535, 3, 117, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(536, 11, 118, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(537, 16, 119, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(538, 12, 120, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(539, 9, 121, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(540, 8, 122, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(541, 7, 123, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(542, 10, 124, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(543, 4, 125, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(544, 1, 126, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(545, 15, 127, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(546, 2, 128, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(547, 6, 129, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(548, 16, 130, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(549, 12, 131, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(550, 17, 132, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(551, 7, 133, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(552, 10, 134, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(553, 4, 135, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(554, 1, 136, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(555, 2, 137, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(556, 3, 138, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(557, 11, 139, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(558, 16, 140, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(559, 12, 141, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(560, 18, 142, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(561, 7, 143, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(562, 5, 144, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(563, 10, 145, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(564, 4, 146, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(565, 1, 147, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(566, 15, 148, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(567, 2, 149, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(568, 16, 150, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(569, 6, 151, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(570, 12, 152, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(571, 19, 153, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(572, 7, 154, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(573, 14, 155, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(574, 10, 156, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(575, 4, 157, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(576, 1, 158, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(577, 2, 159, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(578, 3, 160, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(579, 11, 161, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(580, 16, 162, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(581, 12, 163, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(582, 19, 164, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(583, 9, 165, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(584, 7, 166, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(585, 10, 167, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(586, 4, 168, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(587, 1, 169, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(588, 20, 170, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(589, 15, 171, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(590, 2, 172, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(591, 6, 173, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(592, 16, 174, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(593, 12, 175, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(594, 19, 176, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(595, 7, 177, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(596, 10, 178, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(597, 4, 179, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(598, 1, 180, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(599, 2, 181, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(600, 3, 182, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(601, 11, 183, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(602, 21, 184, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(603, 16, 185, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(604, 12, 186, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(605, 19, 187, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(606, 7, 188, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(607, 5, 189, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(608, 10, 190, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(609, 4, 191, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(610, 1, 192, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(611, 20, 193, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(612, 15, 194, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(613, 18, 195, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(614, 2, 196, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(615, 16, 197, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(616, 6, 198, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(617, 22, 199, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(618, 19, 200, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(619, 12, 201, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(620, 7, 202, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(621, 14, 203, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(622, 10, 204, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(623, 4, 205, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(624, 1, 206, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(625, 22, 207, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(626, 2, 208, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(627, 3, 209, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(628, 11, 210, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(629, 16, 211, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(630, 12, 212, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(631, 23, 213, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(632, 19, 214, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(633, 17, 215, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(634, 7, 216, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(635, 10, 217, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(636, 4, 218, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(637, 1, 219, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(638, 20, 220, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(639, 15, 221, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(640, 22, 222, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(641, 2, 223, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(642, 6, 224, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(643, 16, 225, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(644, 9, 226, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(645, 12, 227, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(646, 19, 228, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(647, 24, 229, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(648, 7, 230, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(649, 21, 231, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(650, 10, 232, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(651, 4, 233, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(652, 1, 234, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(653, 22, 235, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(654, 2, 236, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(655, 3, 237, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(656, 11, 238, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(657, 13, 239, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(658, 16, 240, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(659, 12, 241, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(660, 19, 242, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(661, 25, 243, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(662, 7, 244, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(663, 5, 245, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(664, 10, 246, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(665, 4, 247, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(666, 1, 248, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(667, 20, 249, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(668, 15, 250, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(669, 22, 251, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(670, 18, 252, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(671, 2, 253, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(672, 6, 254, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(673, 16, 255, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(674, 12, 256, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(675, 19, 257, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(676, 24, 258, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(677, 7, 259, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(678, 25, 260, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(679, 14, 261, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(680, 10, 262, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(681, 4, 263, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(682, 1, 264, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(683, 22, 265, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(684, 26, 266, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(685, 2, 267, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(686, 3, 268, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(687, 11, 269, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(688, 16, 270, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(689, 12, 271, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(690, 19, 272, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(691, 7, 273, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(692, 25, 274, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(693, 10, 275, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(694, 4, 276, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(695, 1, 277, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(696, 20, 278, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(697, 15, 279, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(698, 22, 280, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(699, 2, 281, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(700, 8, 282, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(701, 6, 283, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(702, 16, 284, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(703, 27, 285, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(704, 12, 286, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(705, 19, 287, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(706, 24, 288, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(707, 9, 289, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(708, 7, 290, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(709, 25, 291, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(710, 21, 292, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(711, 10, 293, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(712, 4, 294, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(713, 1, 295, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(714, 22, 296, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(715, 2, 297, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(716, 3, 298, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(717, 11, 299, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(718, 16, 300, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(719, 27, 301, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(720, 12, 302, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(721, 28, 303, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(722, 19, 304, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(723, 7, 305, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(724, 25, 306, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(725, 10, 307, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(726, 4, 308, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(727, 1, 309, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(728, 20, 310, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(729, 15, 311, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(730, 22, 312, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(731, 2, 313, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(732, 6, 314, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(733, 16, 315, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(734, 27, 316, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(735, 12, 317, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(736, 19, 318, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(737, 24, 319, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(738, 29, 320, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(739, 7, 321, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(740, 25, 322, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(741, 5, 323, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(742, 10, 324, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(743, 4, 325, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(744, 1, 326, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(745, 22, 327, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(746, 18, 328, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(747, 2, 329, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(748, 3, 330, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(749, 11, 331, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(750, 16, 332, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(751, 27, 333, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(752, 12, 334, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(753, 19, 335, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(754, 27, 336, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(755, 7, 337, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(756, 25, 338, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(757, 14, 339, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(758, 17, 340, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(759, 30, 341, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(760, 10, 342, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(761, 4, 343, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(762, 1, 344, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(763, 20, 345, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(764, 15, 346, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(765, 22, 347, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(766, 2, 348, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(767, 26, 349, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(768, 6, 350, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(769, 16, 351, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(770, 12, 352, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(771, 19, 353, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(772, 24, 354, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(773, 29, 355, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(774, 27, 356, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(775, 7, 357, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(776, 25, 358, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(777, 10, 359, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(778, 4, 360, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(779, 1, 361, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(780, 22, 362, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(781, 3, 363, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(782, 11, 364, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(783, 30, 365, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(784, 31, 366, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(785, 23, 367, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(786, 16, 368, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(787, 9, 369, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(788, 12, 370, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(789, 19, 371, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(790, 27, 372, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(791, 7, 373, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(792, 25, 374, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(793, 21, 375, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(794, 10, 376, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(795, 4, 377, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(796, 1, 378, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(797, 20, 379, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(798, 15, 380, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(799, 22, 381, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(800, 31, 382, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(801, 30, 383, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(802, 6, 384, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(803, 16, 385, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(804, 32, 386, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(805, 12, 387, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(806, 19, 388, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(807, 24, 389, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(808, 29, 390, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(809, 27, 391, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(810, 7, 392, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(811, 25, 393, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(812, 5, 394, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(813, 10, 395, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(814, 4, 396, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(815, 1, 397, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(816, 22, 398, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(817, 18, 399, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(818, 11, 400, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(819, 33, 401, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(820, 30, 402, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(821, 16, 403, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(822, 12, 404, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(823, 24, 405, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(824, 19, 406, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(825, 29, 407, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(826, 34, 408, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(827, 28, 409, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(828, 25, 410, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(829, 21, 411, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(830, 32, 412, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(831, 26, 413, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(832, 1, 414, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(833, 20, 415, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(834, 30, 416, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(835, 31, 417, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(836, 35, 418, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07'),
(837, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(838, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(839, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(840, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(841, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(842, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(843, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(844, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(845, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(846, 1, 1, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28'),
(847, 61, 428, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(848, 61, 429, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(849, 61, 430, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(851, 61, 432, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(852, 61, 433, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(853, 61, 434, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(854, 61, 436, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(855, 36, 437, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19'),
(856, 38, 438, 'Scheduled', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19');

--
-- Triggers `tracking`
--
DELIMITER $$
CREATE TRIGGER `after_tracking_update_booking` AFTER UPDATE ON `tracking` FOR EACH ROW BEGIN
    IF NEW.laundry_status <> OLD.laundry_status THEN
        UPDATE booking_online
        SET status = CASE
            WHEN NEW.laundry_status = 'Scheduled' THEN 'Processing'
            WHEN NEW.laundry_status = 'Picked Up' THEN 'Processing'
            WHEN NEW.laundry_status = 'Processing' THEN 'Ready'
            WHEN NEW.laundry_status = 'Ready' THEN 'Completed'
            ELSE status
        END
        WHERE schedule_id = NEW.Schedule_ID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `Transaction_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Schedule_ID` int(11) NOT NULL,
  `laundry_weight` decimal(10,2) NOT NULL,
  `cost_per_weight` decimal(10,2) NOT NULL,
  `add_ons_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pick_deliver_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment` decimal(10,2) NOT NULL,
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `transaction_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`Transaction_ID`, `Customer_ID`, `Schedule_ID`, `laundry_weight`, `cost_per_weight`, `add_ons_cost`, `pick_deliver_cost`, `payment`, `payment_status`, `transaction_date`) VALUES
(1, 1, 1, 7.36, 588.80, 0.00, 50.00, 638.80, 'Paid', '2024-12-08'),
(2, 1, 2, 6.24, 436.80, 0.00, 100.00, 536.80, 'Paid', '2024-12-14'),
(3, 1, 3, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2024-12-21'),
(4, 2, 4, 6.17, 200.00, 15.00, 50.00, 265.00, 'Paid', '2024-12-22'),
(5, 1, 5, 12.78, 365.14, 0.00, 100.00, 465.14, 'Paid', '2024-12-28'),
(6, 2, 6, 4.64, 371.20, 0.00, 50.00, 421.20, 'Paid', '2024-12-29'),
(7, 1, 7, 6.85, 479.50, 0.00, 50.00, 529.50, 'Paid', '2025-01-04'),
(8, 2, 8, 3.00, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-01-05'),
(9, 3, 9, 4.89, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-01-05'),
(10, 1, 10, 5.70, 162.86, 0.00, 50.00, 212.86, 'Paid', '2025-01-11'),
(11, 2, 11, 5.95, 476.00, 0.00, 100.00, 576.00, 'Paid', '2025-01-12'),
(12, 4, 12, 6.96, 487.20, 15.00, 50.00, 552.20, 'Paid', '2025-01-18'),
(13, 1, 13, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-01-18'),
(14, 2, 14, 6.88, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-01-19'),
(15, 3, 15, 6.68, 190.86, 0.00, 50.00, 240.86, 'Paid', '2025-01-19'),
(16, 1, 16, 6.15, 492.00, 15.00, 50.00, 557.00, 'Paid', '2025-01-25'),
(17, 4, 17, 4.03, 282.10, 0.00, 100.00, 382.10, 'Paid', '2025-01-25'),
(18, 2, 18, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-01-26'),
(19, 5, 19, 4.32, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-01-29'),
(20, 4, 20, 7.95, 227.14, 15.00, 100.00, 342.14, 'Paid', '2025-02-01'),
(21, 1, 21, 4.54, 363.20, 0.00, 50.00, 413.20, 'Paid', '2025-02-01'),
(22, 2, 22, 6.91, 483.70, 0.00, 50.00, 533.70, 'Paid', '2025-02-02'),
(23, 3, 23, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-02-02'),
(24, 1, 24, 6.92, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-02-08'),
(25, 4, 25, 7.84, 224.00, 0.00, 50.00, 274.00, 'Paid', '2025-02-08'),
(26, 2, 26, 4.85, 388.00, 0.00, 100.00, 488.00, 'Paid', '2025-02-09'),
(27, 6, 27, 4.88, 341.60, 0.00, 50.00, 391.60, 'Paid', '2025-02-10'),
(28, 4, 28, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-02-15'),
(29, 1, 29, 6.05, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-02-15'),
(30, 2, 30, 7.92, 226.29, 0.00, 50.00, 276.29, 'Paid', '2025-02-16'),
(31, 3, 31, 7.40, 592.00, 0.00, 50.00, 642.00, 'Paid', '2025-02-16'),
(32, 4, 32, 5.32, 372.40, 15.00, 100.00, 487.40, 'Paid', '2025-02-22'),
(33, 1, 33, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-02-22'),
(34, 7, 34, 5.59, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-02-23'),
(35, 2, 35, 5.24, 149.71, 0.00, 100.00, 249.71, 'Paid', '2025-02-23'),
(36, 6, 36, 5.61, 448.80, 15.00, 50.00, 513.80, 'Paid', '2025-02-24'),
(37, 5, 37, 7.10, 497.00, 0.00, 50.00, 547.00, 'Paid', '2025-02-28'),
(38, 7, 38, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-02-28'),
(39, 4, 39, 6.95, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-03-01'),
(40, 1, 40, 12.87, 367.71, 15.00, 50.00, 432.71, 'Paid', '2025-03-01'),
(41, 2, 41, 7.16, 572.80, 0.00, 100.00, 672.80, 'Paid', '2025-03-02'),
(42, 3, 42, 7.17, 501.90, 0.00, 50.00, 551.90, 'Paid', '2025-03-02'),
(43, 7, 43, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-03-07'),
(44, 8, 44, 5.74, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-03-07'),
(45, 4, 45, 7.61, 217.43, 0.00, 50.00, 267.43, 'Paid', '2025-03-08'),
(46, 1, 46, 7.93, 634.40, 0.00, 50.00, 684.40, 'Paid', '2025-03-08'),
(47, 2, 47, 5.22, 365.40, 0.00, 100.00, 465.40, 'Paid', '2025-03-09'),
(48, 6, 48, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-03-10'),
(49, 7, 49, 6.89, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-03-14'),
(50, 9, 50, 6.61, 188.86, 0.00, 100.00, 288.86, 'Paid', '2025-03-14'),
(51, 4, 51, 5.73, 458.40, 0.00, 50.00, 508.40, 'Paid', '2025-03-15'),
(52, 1, 52, 6.21, 434.70, 15.00, 50.00, 499.70, 'Paid', '2025-03-15'),
(53, 2, 53, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-03-16'),
(54, 3, 54, 5.95, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-03-16'),
(55, 7, 55, 11.51, 328.86, 0.00, 50.00, 378.86, 'Paid', '2025-03-21'),
(56, 10, 56, 5.18, 414.40, 15.00, 100.00, 529.40, 'Paid', '2025-03-22'),
(57, 4, 57, 5.35, 374.50, 0.00, 50.00, 424.50, 'Paid', '2025-03-22'),
(58, 1, 58, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-03-22'),
(59, 2, 59, 4.04, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-03-23'),
(60, 6, 60, 5.59, 159.71, 15.00, 50.00, 224.71, 'Paid', '2025-03-24'),
(61, 7, 61, 4.15, 332.00, 0.00, 50.00, 382.00, 'Paid', '2025-03-28'),
(62, 5, 62, 6.39, 447.30, 0.00, 100.00, 547.30, 'Paid', '2025-03-28'),
(63, 10, 63, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-03-29'),
(64, 4, 64, 5.76, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-03-29'),
(65, 1, 65, 7.26, 207.43, 0.00, 100.00, 307.43, 'Paid', '2025-03-29'),
(66, 2, 66, 7.46, 596.80, 0.00, 50.00, 646.80, 'Paid', '2025-03-30'),
(67, 3, 67, 4.26, 298.20, 0.00, 50.00, 348.20, 'Paid', '2025-03-30'),
(68, 11, 68, 6.00, 400.00, 15.00, 100.00, 515.00, 'Paid', '2025-03-30'),
(69, 7, 69, 5.33, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-04-04'),
(70, 10, 70, 7.48, 213.71, 0.00, 50.00, 263.71, 'Paid', '2025-04-05'),
(71, 4, 71, 4.15, 332.00, 0.00, 100.00, 432.00, 'Paid', '2025-04-05'),
(72, 1, 72, 6.86, 480.20, 15.00, 50.00, 545.20, 'Paid', '2025-04-05'),
(73, 2, 73, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-04-06'),
(74, 6, 74, 4.40, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-04-07'),
(75, 12, 75, 5.27, 150.57, 0.00, 50.00, 200.57, 'Paid', '2025-04-08'),
(76, 7, 76, 6.24, 499.20, 15.00, 50.00, 564.20, 'Paid', '2025-04-11'),
(77, 10, 77, 6.09, 426.30, 0.00, 100.00, 526.30, 'Paid', '2025-04-12'),
(78, 4, 78, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-04-12'),
(79, 1, 79, 4.39, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-04-12'),
(80, 2, 80, 13.14, 375.43, 15.00, 100.00, 490.43, 'Paid', '2025-04-13'),
(81, 3, 81, 5.67, 453.60, 0.00, 50.00, 503.60, 'Paid', '2025-04-13'),
(82, 11, 82, 6.93, 485.10, 0.00, 50.00, 535.10, 'Paid', '2025-04-13'),
(83, 9, 83, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-04-15'),
(84, 12, 84, 6.56, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-04-15'),
(85, 13, 85, 6.92, 197.71, 0.00, 50.00, 247.71, 'Paid', '2025-04-17'),
(86, 7, 86, 4.30, 344.00, 0.00, 100.00, 444.00, 'Paid', '2025-04-18'),
(87, 10, 87, 5.83, 408.10, 0.00, 50.00, 458.10, 'Paid', '2025-04-19'),
(88, 4, 88, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-04-19'),
(89, 1, 89, 6.79, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-04-19'),
(90, 2, 90, 6.72, 192.00, 0.00, 50.00, 242.00, 'Paid', '2025-04-20'),
(91, 6, 91, 5.80, 464.00, 0.00, 50.00, 514.00, 'Paid', '2025-04-21'),
(92, 12, 92, 6.12, 428.40, 15.00, 100.00, 543.40, 'Paid', '2025-04-22'),
(93, 7, 93, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-04-25'),
(94, 5, 94, 6.77, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-04-25'),
(95, 14, 95, 13.13, 375.14, 0.00, 100.00, 475.14, 'Paid', '2025-04-25'),
(96, 10, 96, 7.13, 570.40, 15.00, 50.00, 635.40, 'Paid', '2025-04-26'),
(97, 4, 97, 6.23, 436.10, 0.00, 50.00, 486.10, 'Paid', '2025-04-26'),
(98, 1, 98, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-04-26'),
(99, 2, 99, 5.67, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-04-27'),
(100, 3, 100, 6.62, 189.14, 15.00, 50.00, 254.14, 'Paid', '2025-04-27'),
(101, 11, 101, 5.53, 442.40, 0.00, 100.00, 542.40, 'Paid', '2025-04-27'),
(102, 12, 102, 5.14, 359.80, 0.00, 50.00, 409.80, 'Paid', '2025-04-29'),
(103, 7, 103, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-02'),
(104, 14, 104, 5.57, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-05-02'),
(105, 10, 105, 11.45, 327.14, 0.00, 50.00, 377.14, 'Paid', '2025-05-03'),
(106, 4, 106, 5.83, 466.40, 0.00, 50.00, 516.40, 'Paid', '2025-05-03'),
(107, 1, 107, 4.80, 336.00, 0.00, 100.00, 436.00, 'Paid', '2025-05-03'),
(108, 15, 108, 6.00, 400.00, 15.00, 50.00, 465.00, 'Paid', '2025-05-03'),
(109, 2, 109, 5.64, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-04'),
(110, 6, 110, 12.21, 348.86, 0.00, 100.00, 448.86, 'Paid', '2025-05-05'),
(111, 12, 111, 5.02, 401.60, 0.00, 50.00, 451.60, 'Paid', '2025-05-06'),
(112, 7, 112, 6.45, 451.50, 15.00, 50.00, 516.50, 'Paid', '2025-05-09'),
(113, 10, 113, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-05-10'),
(114, 4, 114, 6.02, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-10'),
(115, 1, 115, 5.79, 165.43, 0.00, 50.00, 215.43, 'Paid', '2025-05-10'),
(116, 2, 116, 7.80, 624.00, 15.00, 100.00, 739.00, 'Paid', '2025-05-11'),
(117, 3, 117, 7.85, 549.50, 0.00, 50.00, 599.50, 'Paid', '2025-05-11'),
(118, 11, 118, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-05-11'),
(119, 16, 119, 6.75, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-05-12'),
(120, 12, 120, 13.10, 374.29, 15.00, 50.00, 439.29, 'Paid', '2025-05-13'),
(121, 9, 121, 7.04, 563.20, 0.00, 50.00, 613.20, 'Paid', '2025-05-14'),
(122, 8, 122, 5.91, 413.70, 0.00, 100.00, 513.70, 'Paid', '2025-05-15'),
(123, 7, 123, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-16'),
(124, 10, 124, 4.33, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-05-17'),
(125, 4, 125, 7.27, 207.71, 0.00, 100.00, 307.71, 'Paid', '2025-05-17'),
(126, 1, 126, 4.53, 362.40, 0.00, 50.00, 412.40, 'Paid', '2025-05-17'),
(127, 15, 127, 5.57, 389.90, 0.00, 50.00, 439.90, 'Paid', '2025-05-17'),
(128, 2, 128, 3.00, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-05-18'),
(129, 6, 129, 5.96, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-19'),
(130, 16, 130, 7.56, 216.00, 0.00, 50.00, 266.00, 'Paid', '2025-05-19'),
(131, 12, 131, 6.35, 508.00, 0.00, 100.00, 608.00, 'Paid', '2025-05-20'),
(132, 17, 132, 5.49, 384.30, 15.00, 50.00, 449.30, 'Paid', '2025-05-21'),
(133, 7, 133, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-23'),
(134, 10, 134, 5.12, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-05-24'),
(135, 4, 135, 7.27, 207.71, 0.00, 50.00, 257.71, 'Paid', '2025-05-24'),
(136, 1, 136, 4.22, 337.60, 15.00, 50.00, 402.60, 'Paid', '2025-05-24'),
(137, 2, 137, 4.03, 282.10, 0.00, 100.00, 382.10, 'Paid', '2025-05-25'),
(138, 3, 138, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-05-25'),
(139, 11, 139, 5.04, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-05-25'),
(140, 16, 140, 6.53, 186.57, 15.00, 100.00, 301.57, 'Paid', '2025-05-26'),
(141, 12, 141, 4.86, 388.80, 0.00, 50.00, 438.80, 'Paid', '2025-05-27'),
(142, 18, 142, 6.18, 432.60, 0.00, 50.00, 482.60, 'Paid', '2025-05-29'),
(143, 7, 143, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-05-30'),
(144, 5, 144, 6.40, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-05-30'),
(145, 10, 145, 11.17, 319.14, 0.00, 50.00, 369.14, 'Paid', '2025-05-31'),
(146, 4, 146, 4.99, 399.20, 0.00, 100.00, 499.20, 'Paid', '2025-05-31'),
(147, 1, 147, 5.44, 380.80, 0.00, 50.00, 430.80, 'Paid', '2025-05-31'),
(148, 15, 148, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-05-31'),
(149, 2, 149, 4.59, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-06-01'),
(150, 16, 150, 11.88, 339.43, 0.00, 50.00, 389.43, 'Paid', '2025-06-02'),
(151, 6, 151, 7.67, 613.60, 0.00, 50.00, 663.60, 'Paid', '2025-06-02'),
(152, 12, 152, 4.73, 331.10, 15.00, 100.00, 446.10, 'Paid', '2025-06-03'),
(153, 19, 153, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-05'),
(154, 7, 154, 4.65, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-06'),
(155, 14, 155, 6.39, 182.57, 0.00, 100.00, 282.57, 'Paid', '2025-06-06'),
(156, 10, 156, 5.81, 464.80, 15.00, 50.00, 529.80, 'Paid', '2025-06-07'),
(157, 4, 157, 7.48, 523.60, 0.00, 50.00, 573.60, 'Paid', '2025-06-07'),
(158, 1, 158, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-06-07'),
(159, 2, 159, 5.10, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-08'),
(160, 3, 160, 10.61, 303.14, 15.00, 50.00, 368.14, 'Paid', '2025-06-08'),
(161, 11, 161, 4.84, 387.20, 0.00, 100.00, 487.20, 'Paid', '2025-06-08'),
(162, 16, 162, 6.38, 446.60, 0.00, 50.00, 496.60, 'Paid', '2025-06-09'),
(163, 12, 163, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-10'),
(164, 19, 164, 6.79, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-06-11'),
(165, 9, 165, 5.99, 171.14, 0.00, 50.00, 221.14, 'Paid', '2025-06-13'),
(166, 7, 166, 7.09, 567.20, 0.00, 50.00, 617.20, 'Paid', '2025-06-13'),
(167, 10, 167, 7.50, 525.00, 0.00, 100.00, 625.00, 'Paid', '2025-06-14'),
(168, 4, 168, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-06-14'),
(169, 1, 169, 5.94, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-14'),
(170, 20, 170, 6.21, 177.43, 0.00, 100.00, 277.43, 'Paid', '2025-06-14'),
(171, 15, 171, 7.23, 578.40, 0.00, 50.00, 628.40, 'Paid', '2025-06-14'),
(172, 2, 172, 7.32, 512.40, 15.00, 50.00, 577.40, 'Paid', '2025-06-15'),
(173, 6, 173, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-06-16'),
(174, 16, 174, 4.41, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-16'),
(175, 12, 175, 5.37, 153.43, 0.00, 50.00, 203.43, 'Paid', '2025-06-17'),
(176, 19, 176, 4.39, 351.20, 15.00, 100.00, 466.20, 'Paid', '2025-06-18'),
(177, 7, 177, 4.45, 311.50, 0.00, 50.00, 361.50, 'Paid', '2025-06-20'),
(178, 10, 178, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-21'),
(179, 4, 179, 4.10, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-06-21'),
(180, 1, 180, 6.77, 193.43, 15.00, 50.00, 258.43, 'Paid', '2025-06-21'),
(181, 2, 181, 7.78, 622.40, 0.00, 50.00, 672.40, 'Paid', '2025-06-22'),
(182, 3, 182, 7.81, 546.70, 0.00, 100.00, 646.70, 'Paid', '2025-06-22'),
(183, 11, 183, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-06-22'),
(184, 21, 184, 6.29, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-06-22'),
(185, 16, 185, 7.83, 223.71, 0.00, 100.00, 323.71, 'Paid', '2025-06-23'),
(186, 12, 186, 6.29, 503.20, 0.00, 50.00, 553.20, 'Paid', '2025-06-24'),
(187, 19, 187, 4.12, 288.40, 0.00, 50.00, 338.40, 'Paid', '2025-06-25'),
(188, 7, 188, 3.00, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-06-27'),
(189, 5, 189, 4.33, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-27'),
(190, 10, 190, 7.46, 213.14, 0.00, 50.00, 263.14, 'Paid', '2025-06-28'),
(191, 4, 191, 5.65, 452.00, 0.00, 100.00, 552.00, 'Paid', '2025-06-28'),
(192, 1, 192, 6.38, 446.60, 15.00, 50.00, 511.60, 'Paid', '2025-06-28'),
(193, 20, 193, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-06-28'),
(194, 15, 194, 6.82, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-06-28'),
(195, 18, 195, 6.49, 185.43, 0.00, 50.00, 235.43, 'Paid', '2025-06-28'),
(196, 2, 196, 4.36, 348.80, 15.00, 50.00, 413.80, 'Paid', '2025-06-29'),
(197, 16, 197, 7.83, 548.10, 0.00, 100.00, 648.10, 'Paid', '2025-06-30'),
(198, 6, 198, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-30'),
(199, 22, 199, 6.16, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-06-30'),
(200, 19, 200, 5.24, 149.71, 15.00, 100.00, 264.71, 'Paid', '2025-07-02'),
(201, 12, 201, 5.02, 401.60, 0.00, 50.00, 451.60, 'Paid', '2025-07-01'),
(202, 7, 202, 4.14, 289.80, 0.00, 50.00, 339.80, 'Paid', '2025-07-04'),
(203, 14, 203, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-07-04'),
(204, 10, 204, 6.77, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-07-05'),
(205, 4, 205, 5.73, 163.71, 0.00, 50.00, 213.71, 'Paid', '2025-07-05'),
(206, 1, 206, 4.01, 320.80, 0.00, 100.00, 420.80, 'Paid', '2025-07-05'),
(207, 22, 207, 5.13, 359.10, 0.00, 50.00, 409.10, 'Paid', '2025-07-05'),
(208, 2, 208, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-07-06'),
(209, 3, 209, 4.59, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-07-06'),
(210, 11, 210, 10.76, 307.43, 0.00, 50.00, 357.43, 'Paid', '2025-07-06'),
(211, 16, 211, 6.71, 536.80, 0.00, 50.00, 586.80, 'Paid', '2025-07-07'),
(212, 12, 212, 7.29, 510.30, 15.00, 100.00, 625.30, 'Paid', '2025-07-08'),
(213, 23, 213, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-08'),
(214, 19, 214, 6.73, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-09'),
(215, 17, 215, 7.70, 220.00, 0.00, 100.00, 320.00, 'Paid', '2025-07-10'),
(216, 7, 216, 6.11, 488.80, 15.00, 50.00, 553.80, 'Paid', '2025-07-11'),
(217, 10, 217, 7.73, 541.10, 0.00, 50.00, 591.10, 'Paid', '2025-07-12'),
(218, 4, 218, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-07-12'),
(219, 1, 219, 5.82, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-12'),
(220, 20, 220, 10.48, 299.43, 15.00, 50.00, 364.43, 'Paid', '2025-07-12'),
(221, 15, 221, 4.94, 395.20, 0.00, 100.00, 495.20, 'Paid', '2025-07-12'),
(222, 22, 222, 7.28, 509.60, 0.00, 50.00, 559.60, 'Paid', '2025-07-12'),
(223, 2, 223, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-13'),
(224, 6, 224, 5.57, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-07-14'),
(225, 16, 225, 6.62, 189.14, 0.00, 50.00, 239.14, 'Paid', '2025-07-14'),
(226, 9, 226, 5.75, 460.00, 0.00, 50.00, 510.00, 'Paid', '2025-07-15'),
(227, 12, 227, 6.25, 437.50, 0.00, 100.00, 537.50, 'Paid', '2025-07-15'),
(228, 19, 228, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-07-16'),
(229, 24, 229, 6.51, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-16'),
(230, 7, 230, 7.41, 211.71, 0.00, 100.00, 311.71, 'Paid', '2025-07-18'),
(231, 21, 231, 4.10, 328.00, 0.00, 50.00, 378.00, 'Paid', '2025-07-18'),
(232, 10, 232, 6.86, 480.20, 15.00, 50.00, 545.20, 'Paid', '2025-07-19'),
(233, 4, 233, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-07-19'),
(234, 1, 234, 5.13, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-19'),
(235, 22, 235, 7.13, 203.71, 0.00, 50.00, 253.71, 'Paid', '2025-07-19'),
(236, 2, 236, 5.79, 463.20, 15.00, 100.00, 578.20, 'Paid', '2025-07-20'),
(237, 3, 237, 4.42, 309.40, 0.00, 50.00, 359.40, 'Paid', '2025-07-20'),
(238, 11, 238, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-20'),
(239, 13, 239, 5.78, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-07-20'),
(240, 16, 240, 6.03, 172.29, 15.00, 50.00, 237.29, 'Paid', '2025-07-21'),
(241, 12, 241, 5.78, 462.40, 0.00, 50.00, 512.40, 'Paid', '2025-07-22'),
(242, 19, 242, 4.79, 335.30, 0.00, 100.00, 435.30, 'Paid', '2025-07-23'),
(243, 25, 243, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-07-24'),
(244, 7, 244, 5.95, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-07-25'),
(245, 5, 245, 6.79, 194.00, 0.00, 100.00, 294.00, 'Paid', '2025-07-25'),
(246, 10, 246, 4.21, 336.80, 0.00, 50.00, 386.80, 'Paid', '2025-07-26'),
(247, 4, 247, 5.91, 413.70, 0.00, 50.00, 463.70, 'Paid', '2025-07-26'),
(248, 1, 248, 3.00, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-07-26'),
(249, 20, 249, 6.13, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-26'),
(250, 15, 250, 10.67, 304.86, 0.00, 50.00, 354.86, 'Paid', '2025-07-26'),
(251, 22, 251, 5.06, 404.80, 0.00, 100.00, 504.80, 'Paid', '2025-07-26'),
(252, 18, 252, 7.29, 510.30, 15.00, 50.00, 575.30, 'Paid', '2025-07-26'),
(253, 2, 253, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-07-27'),
(254, 6, 254, 4.32, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-07-28'),
(255, 16, 255, 6.96, 198.86, 0.00, 50.00, 248.86, 'Paid', '2025-07-28'),
(256, 12, 256, 5.90, 472.00, 15.00, 50.00, 537.00, 'Paid', '2025-07-29'),
(257, 19, 257, 5.69, 398.30, 0.00, 100.00, 498.30, 'Paid', '2025-07-30'),
(258, 24, 258, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-07-30'),
(259, 7, 259, 4.50, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-01'),
(260, 25, 260, 11.36, 324.57, 15.00, 100.00, 439.57, 'Paid', '2025-08-01'),
(261, 14, 261, 5.60, 448.00, 0.00, 50.00, 498.00, 'Paid', '2025-08-01'),
(262, 10, 262, 7.93, 555.10, 0.00, 50.00, 605.10, 'Paid', '2025-08-02'),
(263, 4, 263, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-08-02'),
(264, 1, 264, 5.78, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-08-02'),
(265, 22, 265, 11.67, 333.43, 0.00, 50.00, 383.43, 'Paid', '2025-08-02'),
(266, 26, 266, 6.30, 504.00, 0.00, 100.00, 604.00, 'Paid', '2025-08-02'),
(267, 2, 267, 6.48, 453.60, 0.00, 50.00, 503.60, 'Paid', '2025-08-03'),
(268, 3, 268, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-08-03'),
(269, 11, 269, 4.10, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-08-03'),
(270, 16, 270, 5.18, 148.00, 0.00, 50.00, 198.00, 'Paid', '2025-08-04'),
(271, 12, 271, 4.80, 384.00, 0.00, 50.00, 434.00, 'Paid', '2025-08-05'),
(272, 19, 272, 7.27, 508.90, 15.00, 100.00, 623.90, 'Paid', '2025-08-06'),
(273, 7, 273, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-08'),
(274, 25, 274, 6.96, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-08'),
(275, 10, 275, 6.13, 175.14, 0.00, 100.00, 275.14, 'Paid', '2025-08-09'),
(276, 4, 276, 5.94, 475.20, 15.00, 50.00, 540.20, 'Paid', '2025-08-09'),
(277, 1, 277, 5.17, 361.90, 0.00, 50.00, 411.90, 'Paid', '2025-08-09'),
(278, 20, 278, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-08-09'),
(279, 15, 279, 4.53, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-09'),
(280, 22, 280, 12.94, 369.71, 15.00, 50.00, 434.71, 'Paid', '2025-08-09'),
(281, 2, 281, 4.45, 356.00, 0.00, 100.00, 456.00, 'Paid', '2025-08-10'),
(282, 8, 282, 5.43, 380.10, 0.00, 50.00, 430.10, 'Paid', '2025-08-10'),
(283, 6, 283, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-11'),
(284, 16, 284, 4.56, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-08-11'),
(285, 27, 285, 6.06, 173.14, 0.00, 50.00, 223.14, 'Paid', '2025-08-11'),
(286, 12, 286, 4.05, 324.00, 0.00, 50.00, 374.00, 'Paid', '2025-08-12'),
(287, 19, 287, 4.01, 280.70, 0.00, 100.00, 380.70, 'Paid', '2025-08-13'),
(288, 24, 288, 6.00, 400.00, 15.00, 50.00, 465.00, 'Paid', '2025-08-13'),
(289, 9, 289, 6.58, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-14'),
(290, 7, 290, 5.94, 169.71, 0.00, 100.00, 269.71, 'Paid', '2025-08-15'),
(291, 25, 291, 5.73, 458.40, 0.00, 50.00, 508.40, 'Paid', '2025-08-15'),
(292, 21, 292, 4.89, 342.30, 15.00, 50.00, 407.30, 'Paid', '2025-08-15'),
(293, 10, 293, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-08-16'),
(294, 4, 294, 5.22, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-16'),
(295, 1, 295, 7.14, 204.00, 0.00, 50.00, 254.00, 'Paid', '2025-08-16'),
(296, 22, 296, 7.20, 576.00, 15.00, 100.00, 691.00, 'Paid', '2025-08-16'),
(297, 2, 297, 7.47, 522.90, 0.00, 50.00, 572.90, 'Paid', '2025-08-17'),
(298, 3, 298, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-08-17'),
(299, 11, 299, 4.24, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-08-17'),
(300, 16, 300, 7.23, 206.57, 15.00, 50.00, 271.57, 'Paid', '2025-08-18'),
(301, 27, 301, 7.67, 613.60, 0.00, 50.00, 663.60, 'Paid', '2025-08-18'),
(302, 12, 302, 5.43, 380.10, 0.00, 100.00, 480.10, 'Paid', '2025-08-19'),
(303, 28, 303, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-19'),
(304, 19, 304, 4.39, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-08-20'),
(305, 7, 305, 5.67, 162.00, 0.00, 100.00, 262.00, 'Paid', '2025-08-22'),
(306, 25, 306, 6.22, 497.60, 0.00, 50.00, 547.60, 'Paid', '2025-08-22'),
(307, 10, 307, 4.41, 308.70, 0.00, 50.00, 358.70, 'Paid', '2025-08-23'),
(308, 4, 308, 6.00, 400.00, 15.00, 100.00, 515.00, 'Paid', '2025-08-23'),
(309, 1, 309, 6.74, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-23'),
(310, 20, 310, 6.25, 178.57, 0.00, 50.00, 228.57, 'Paid', '2025-08-23'),
(311, 15, 311, 7.99, 639.20, 0.00, 100.00, 739.20, 'Paid', '2025-08-23'),
(312, 22, 312, 6.91, 483.70, 15.00, 50.00, 548.70, 'Paid', '2025-08-23'),
(313, 2, 313, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-08-24'),
(314, 6, 314, 4.23, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-08-25'),
(315, 16, 315, 7.62, 217.71, 0.00, 50.00, 267.71, 'Paid', '2025-08-25'),
(316, 27, 316, 4.41, 352.80, 15.00, 50.00, 417.80, 'Paid', '2025-08-25'),
(317, 12, 317, 7.60, 532.00, 0.00, 100.00, 632.00, 'Paid', '2025-08-26'),
(318, 19, 318, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-27'),
(319, 24, 319, 4.69, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-08-27'),
(320, 29, 320, 5.92, 169.14, 15.00, 100.00, 284.14, 'Paid', '2025-08-27'),
(321, 7, 321, 6.93, 554.40, 0.00, 50.00, 604.40, 'Paid', '2025-08-29'),
(322, 25, 322, 6.96, 487.20, 0.00, 50.00, 537.20, 'Paid', '2025-08-29'),
(323, 5, 323, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-08-29'),
(324, 10, 324, 4.91, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-08-30'),
(325, 4, 325, 5.37, 153.43, 0.00, 50.00, 203.43, 'Paid', '2025-08-30'),
(326, 1, 326, 6.38, 510.40, 0.00, 100.00, 610.40, 'Paid', '2025-08-30'),
(327, 22, 327, 6.43, 450.10, 0.00, 50.00, 500.10, 'Paid', '2025-08-30'),
(328, 18, 328, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-08-30'),
(329, 2, 329, 5.36, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-08-31'),
(330, 3, 330, 5.33, 152.29, 0.00, 50.00, 202.29, 'Paid', '2025-08-31'),
(331, 11, 331, 4.29, 343.20, 0.00, 50.00, 393.20, 'Paid', '2025-08-31'),
(332, 16, 332, 4.14, 289.80, 15.00, 100.00, 404.80, 'Paid', '2025-09-01'),
(333, 27, 333, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-09-01'),
(334, 12, 334, 5.96, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-02'),
(335, 19, 335, 5.36, 153.14, 0.00, 100.00, 253.14, 'Paid', '2025-09-03'),
(336, 27, 336, 5.41, 432.80, 15.00, 50.00, 497.80, 'Paid', '2025-09-04'),
(337, 7, 337, 5.62, 393.40, 0.00, 50.00, 443.40, 'Paid', '2025-09-05'),
(338, 25, 338, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-09-05'),
(339, 14, 339, 5.76, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-05'),
(340, 17, 340, 6.61, 188.86, 15.00, 50.00, 253.86, 'Paid', '2025-09-05'),
(341, 30, 341, 6.04, 483.20, 0.00, 100.00, 583.20, 'Paid', '2025-09-05'),
(342, 10, 342, 7.74, 541.80, 0.00, 50.00, 591.80, 'Paid', '2025-09-06'),
(343, 4, 343, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-06'),
(344, 1, 344, 6.70, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-09-06'),
(345, 20, 345, 7.13, 203.71, 0.00, 50.00, 253.71, 'Paid', '2025-09-06'),
(346, 15, 346, 5.18, 414.40, 0.00, 50.00, 464.40, 'Paid', '2025-09-06'),
(347, 22, 347, 5.41, 378.70, 0.00, 100.00, 478.70, 'Paid', '2025-09-06'),
(348, 2, 348, 6.00, 400.00, 15.00, 50.00, 465.00, 'Paid', '2025-09-07'),
(349, 26, 349, 4.96, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-07'),
(350, 6, 350, 13.58, 388.00, 0.00, 100.00, 488.00, 'Paid', '2025-09-08'),
(351, 16, 351, 6.25, 500.00, 0.00, 50.00, 550.00, 'Paid', '2025-09-08'),
(352, 12, 352, 4.51, 315.70, 15.00, 50.00, 380.70, 'Paid', '2025-09-09'),
(353, 19, 353, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-09-10'),
(354, 24, 354, 5.16, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-10'),
(355, 29, 355, 5.60, 160.00, 0.00, 50.00, 210.00, 'Paid', '2025-09-10'),
(356, 27, 356, 7.15, 572.00, 15.00, 100.00, 687.00, 'Paid', '2025-09-11'),
(357, 7, 357, 5.32, 372.40, 0.00, 50.00, 422.40, 'Paid', '2025-09-12'),
(358, 25, 358, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-12'),
(359, 10, 359, 5.39, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-09-13'),
(360, 4, 360, 7.42, 212.00, 15.00, 50.00, 277.00, 'Paid', '2025-09-13'),
(361, 1, 361, 6.85, 548.00, 0.00, 50.00, 598.00, 'Paid', '2025-09-13'),
(362, 22, 362, 4.57, 319.90, 0.00, 100.00, 419.90, 'Paid', '2025-09-13'),
(363, 3, 363, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-14'),
(364, 11, 364, 5.44, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-09-14'),
(365, 30, 365, 7.45, 212.86, 0.00, 100.00, 312.86, 'Paid', '2025-09-14'),
(366, 31, 366, 4.53, 362.40, 0.00, 50.00, 412.40, 'Paid', '2025-09-14'),
(367, 23, 367, 4.84, 338.80, 0.00, 50.00, 388.80, 'Paid', '2025-09-15'),
(368, 16, 368, 6.00, 400.00, 15.00, 100.00, 515.00, 'Paid', '2025-09-15'),
(369, 9, 369, 5.96, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-16'),
(370, 12, 370, 6.54, 186.86, 0.00, 50.00, 236.86, 'Paid', '2025-09-16'),
(371, 19, 371, 6.72, 537.60, 0.00, 100.00, 637.60, 'Paid', '2025-09-17'),
(372, 27, 372, 7.45, 521.50, 15.00, 50.00, 586.50, 'Paid', '2025-09-18'),
(373, 7, 373, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-19'),
(374, 25, 374, 6.35, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-09-19'),
(375, 21, 375, 5.42, 154.86, 0.00, 50.00, 204.86, 'Paid', '2025-09-19'),
(376, 10, 376, 5.63, 450.40, 15.00, 50.00, 515.40, 'Paid', '2025-09-20'),
(377, 4, 377, 6.45, 451.50, 0.00, 100.00, 551.50, 'Paid', '2025-09-20'),
(378, 1, 378, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-09-20'),
(379, 20, 379, 5.16, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-20'),
(380, 15, 380, 7.54, 215.43, 15.00, 100.00, 330.43, 'Paid', '2025-09-20'),
(381, 22, 381, 4.12, 329.60, 0.00, 50.00, 379.60, 'Paid', '2025-09-20'),
(382, 31, 382, 6.45, 451.50, 0.00, 50.00, 501.50, 'Paid', '2025-09-20'),
(383, 30, 383, 6.00, 400.00, 0.00, 100.00, 500.00, 'Paid', '2025-09-21'),
(384, 6, 384, 6.98, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-09-22'),
(385, 16, 385, 6.02, 172.00, 0.00, 50.00, 222.00, 'Paid', '2025-09-22'),
(386, 32, 386, 6.06, 484.80, 0.00, 100.00, 584.80, 'Paid', '2025-09-22'),
(387, 12, 387, 6.22, 435.40, 0.00, 50.00, 485.40, 'Paid', '2025-09-23'),
(388, 19, 388, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-09-24'),
(389, 24, 389, 5.36, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-09-24'),
(390, 29, 390, 6.95, 198.57, 0.00, 50.00, 248.57, 'Paid', '2025-09-24'),
(391, 27, 391, 5.75, 460.00, 0.00, 50.00, 510.00, 'Paid', '2025-09-25'),
(392, 7, 392, 4.94, 345.80, 15.00, 100.00, 460.80, 'Paid', '2025-09-26'),
(393, 25, 393, 6.00, 400.00, 0.00, 50.00, 450.00, 'Paid', '2025-09-26'),
(394, 5, 394, 5.93, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-26'),
(395, 10, 395, 5.36, 153.14, 0.00, 100.00, 253.14, 'Paid', '2025-09-27'),
(396, 4, 396, 7.10, 568.00, 15.00, 50.00, 633.00, 'Paid', '2025-09-27'),
(397, 1, 397, 6.03, 422.10, 0.00, 50.00, 472.10, 'Paid', '2025-09-27'),
(398, 22, 398, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-09-27'),
(399, 18, 399, 5.59, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-27'),
(400, 11, 400, 6.49, 185.43, 15.00, 50.00, 250.43, 'Paid', '2025-09-28'),
(401, 33, 401, 5.75, 460.00, 0.00, 100.00, 560.00, 'Paid', '2025-09-28'),
(402, 30, 402, 6.80, 476.00, 0.00, 50.00, 526.00, 'Paid', '2025-09-28'),
(403, 16, 403, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-09-29'),
(404, 12, 404, 6.39, 200.00, 15.00, 100.00, 315.00, 'Paid', '2025-09-30'),
(405, 24, 405, 7.60, 217.14, 0.00, 50.00, 267.14, 'Paid', '2025-10-01'),
(406, 19, 406, 7.90, 632.00, 0.00, 50.00, 682.00, 'Paid', '2025-10-01'),
(407, 29, 407, 5.09, 356.30, 0.00, 100.00, 456.30, 'Paid', '2025-10-01'),
(408, 34, 408, 3.00, 200.00, 15.00, 50.00, 265.00, 'Paid', '2025-10-02'),
(409, 28, 409, 5.16, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-10-02'),
(410, 25, 410, 7.67, 219.14, 0.00, 100.00, 319.14, 'Paid', '2025-10-03'),
(411, 21, 411, 6.46, 516.80, 0.00, 50.00, 566.80, 'Paid', '2025-10-03'),
(412, 32, 412, 5.65, 395.50, 15.00, 50.00, 460.50, 'Paid', '2025-10-03'),
(413, 26, 413, 3.00, 200.00, 0.00, 100.00, 300.00, 'Paid', '2025-10-04'),
(414, 1, 414, 6.44, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-10-04'),
(415, 20, 415, 7.32, 209.14, 0.00, 50.00, 259.14, 'Paid', '2025-10-04'),
(416, 30, 416, 6.12, 489.60, 15.00, 100.00, 604.60, 'Paid', '2025-10-04'),
(417, 31, 417, 5.35, 374.50, 0.00, 50.00, 424.50, 'Paid', '2025-10-04'),
(418, 35, 418, 3.00, 200.00, 0.00, 50.00, 250.00, 'Paid', '2025-10-04'),
(513, 61, 428, 0.00, 0.00, 0.00, 0.00, 210.00, 'Unpaid', '2026-04-19'),
(514, 61, 429, 0.00, 0.00, 0.00, 0.00, 210.00, 'Unpaid', '2026-04-19'),
(515, 61, 430, 3.00, 0.00, 0.00, 0.00, 80.00, 'Unpaid', '2026-04-19'),
(517, 61, 432, 4.90, 0.00, 0.00, 0.00, 200.00, 'Unpaid', '2026-04-19'),
(518, 61, 433, 4.90, 0.00, 0.00, 0.00, 200.00, 'Unpaid', '2026-04-19'),
(519, 61, 434, 4.90, 0.00, 0.00, 0.00, 200.00, 'Unpaid', '2026-04-19'),
(520, 61, 436, 3.00, 0.00, 0.00, 0.00, 210.00, 'Unpaid', '2026-04-19'),
(521, 36, 437, 8.00, 0.00, 0.00, 0.00, 220.00, 'Unpaid', '2026-04-19'),
(522, 38, 438, 3.00, 0.00, 0.00, 0.00, 210.00, 'Unpaid', '2026-04-19');

--
-- Triggers `transaction`
--
DELIMITER $$
CREATE TRIGGER `add_financial_record_after_payment` AFTER UPDATE ON `transaction` FOR EACH ROW BEGIN
  IF NEW.payment_status = 'Paid' THEN
    INSERT INTO financial_records (date, description, category, type, amount)
    VALUES (CURDATE(), CONCAT('Booking Payment - Schedule ', NEW.Schedule_ID), 'Other', 'Revenue', NEW.payment);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_transaction_paid` AFTER INSERT ON `transaction` FOR EACH ROW BEGIN
  IF NEW.payment_status = 'Paid' THEN
    INSERT INTO financial_records (`date`, `description`, `category`, `type`, `amount`)
    VALUES (
      CURDATE(),
      CONCAT('Payment from Customer #', NEW.Customer_ID),
      'Other',
      'Revenue',
      NEW.payment
    );
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_booking_total` AFTER INSERT ON `transaction` FOR EACH ROW BEGIN
    UPDATE booking
    SET total_amount = NEW.payment
    WHERE Schedule_ID = NEW.Schedule_ID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Online'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`id`, `customer_id`, `login_time`, `logout_time`, `status`) VALUES
(1, 1, '2025-11-18 17:17:28', NULL, 'Online'),
(2, 1, '2025-11-18 17:19:30', NULL, 'Online'),
(3, 1, '2025-11-27 21:39:44', NULL, 'Online'),
(4, 1, '2025-11-27 23:23:38', NULL, 'Online'),
(5, 1, '2025-11-28 10:03:27', NULL, 'Online'),
(6, 1, '2025-11-28 12:54:36', NULL, 'Online'),
(7, 1, '2025-11-28 12:55:20', NULL, 'Online'),
(8, 1, '2025-11-28 13:36:54', NULL, 'Online'),
(9, 1, '2025-11-28 13:38:07', NULL, 'Online'),
(10, 1, '2025-11-28 13:44:43', NULL, 'Online'),
(11, 1, '2025-11-28 14:00:54', NULL, 'Online'),
(12, 1, '2025-11-28 14:34:54', NULL, 'Online'),
(13, 1, '2025-11-28 15:04:11', NULL, 'Online'),
(14, 1, '2025-11-29 13:28:58', NULL, 'Online'),
(15, 1, '2025-11-29 14:07:44', NULL, 'Online'),
(16, 1, '2025-11-29 14:15:39', NULL, 'Online'),
(17, 1, '2025-12-08 12:31:21', NULL, 'Online'),
(18, 74, '2026-08-10 15:49:49', NULL, 'Online');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_ons`
--
ALTER TABLE `add_ons`
  ADD PRIMARY KEY (`addon_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`Admin_ID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`Booking_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `Admin_ID` (`Admin_ID`),
  ADD KEY `Schedule_ID` (`Schedule_ID`);

--
-- Indexes for table `booking_online`
--
ALTER TABLE `booking_online`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD KEY `fk_complaints_customer` (`customer_id`);

--
-- Indexes for table `customer_info`
--
ALTER TABLE `customer_info`
  ADD PRIMARY KEY (`Customer_ID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `online_booking_id` (`online_booking_id`);

--
-- Indexes for table `employee_salaries`
--
ALTER TABLE `employee_salaries`
  ADD PRIMARY KEY (`salary_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expense_inventory`
--
ALTER TABLE `expense_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_financial_records` (`financial_record_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `inventory_usage_rules`
--
ALTER TABLE `inventory_usage_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `notifications_admin`
--
ALTER TABLE `notifications_admin`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications_user`
--
ALTER TABLE `notifications_user`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `on_booking_id` (`on_booking_id`);

--
-- Indexes for table `payments_online`
--
ALTER TABLE `payments_online`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payments_booking` (`booking_id`);

--
-- Indexes for table `products_inventory`
--
ALTER TABLE `products_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`po_id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`po_item_id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`Schedule_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `tracking`
--
ALTER TABLE `tracking`
  ADD PRIMARY KEY (`Tracking_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `Schedule_ID` (`Schedule_ID`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`Transaction_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `Schedule_ID` (`Schedule_ID`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_activity_customer` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_ons`
--
ALTER TABLE `add_ons`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `Booking_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=456;

--
-- AUTO_INCREMENT for table `booking_online`
--
ALTER TABLE `booking_online`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `customer_info`
--
ALTER TABLE `customer_info`
  MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=419;

--
-- AUTO_INCREMENT for table `employee_salaries`
--
ALTER TABLE `employee_salaries`
  MODIFY `salary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `expense_inventory`
--
ALTER TABLE `expense_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=419;

--
-- AUTO_INCREMENT for table `financial_records`
--
ALTER TABLE `financial_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=930;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `inventory_usage_rules`
--
ALTER TABLE `inventory_usage_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications_admin`
--
ALTER TABLE `notifications_admin`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=419;

--
-- AUTO_INCREMENT for table `notifications_user`
--
ALTER TABLE `notifications_user`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=421;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=456;

--
-- AUTO_INCREMENT for table `payments_online`
--
ALTER TABLE `payments_online`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `products_inventory`
--
ALTER TABLE `products_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `po_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `Schedule_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=440;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tracking`
--
ALTER TABLE `tracking`
  MODIFY `Tracking_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=857;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `Transaction_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=523;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_ons`
--
ALTER TABLE `add_ons`
  ADD CONSTRAINT `add_ons_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`Admin_ID`) REFERENCES `admin` (`Admin_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_ibfk_3` FOREIGN KEY (`Schedule_ID`) REFERENCES `schedule` (`Schedule_ID`) ON DELETE SET NULL;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaints_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `fk_delivery_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`Booking_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_delivery_online` FOREIGN KEY (`online_booking_id`) REFERENCES `booking_online` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expense_inventory`
--
ALTER TABLE `expense_inventory`
  ADD CONSTRAINT `fk_financial_records` FOREIGN KEY (`financial_record_id`) REFERENCES `financial_records` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_notifications`
--
ALTER TABLE `inventory_notifications`
  ADD CONSTRAINT `inventory_notifications_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_usage_rules`
--
ALTER TABLE `inventory_usage_rules`
  ADD CONSTRAINT `inventory_usage_rules_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_usage_rules_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications_admin`
--
ALTER TABLE `notifications_admin`
  ADD CONSTRAINT `notifications_admin_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_admin_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE;

--
-- Constraints for table `notifications_user`
--
ALTER TABLE `notifications_user`
  ADD CONSTRAINT `notifications_user_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`Booking_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`Booking_ID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_online` FOREIGN KEY (`on_booking_id`) REFERENCES `booking_online` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments_online`
--
ALTER TABLE `payments_online`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking_online` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`po_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer_info` (`Customer_ID`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`Admin_ID`) ON DELETE CASCADE;

--
-- Constraints for table `tracking`
--
ALTER TABLE `tracking`
  ADD CONSTRAINT `tracking_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer_info` (`Customer_ID`),
  ADD CONSTRAINT `tracking_ibfk_2` FOREIGN KEY (`Schedule_ID`) REFERENCES `schedule` (`Schedule_ID`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customer_info` (`Customer_ID`),
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`Schedule_ID`) REFERENCES `schedule` (`Schedule_ID`);

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `fk_user_activity_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer_info` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
