-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 04, 2025 at 01:00 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `r&m-apple_gadgets`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `password`, `password_hash`, `created_at`) VALUES
(1, 'admin', 'Admin@123', '$2y$10$zVTNtGThiu74kZU8wP.EU.1xT/HwAYCdpWjhMBlaF/6JBnCxoX2Ce', '2025-11-04 08:02:23');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `category` enum('iphone','ipad','macbook','accessories') NOT NULL,
  `condition_type` enum('new','refurbished','pre-owned') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `sku` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `storage` varchar(20) DEFAULT NULL,
  `model_year` int DEFAULT NULL,
  `description` text,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `idx_category` (`category`),
  KEY `idx_condition` (`condition_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `condition_type`, `price`, `original_price`, `stock_quantity`, `sku`, `color`, `storage`, `model_year`, `description`, `image_url`, `status`, `created_at`, `updated_at`) VALUES
(1, 'iPhone 17 Pro Max', 'iphone', 'new', 86990.00, NULL, 10, 'IP17PM-256', 'Deep Blue,Cosmic Orange,Silver', '256GB,512GB,1TB', 2025, 'The most advanced iPhone ever with titanium design and A18 Pro chip.', 'images/ip17pro.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:06:00'),
(2, 'iPhone 17 Pro', 'iphone', 'new', 79990.00, NULL, 15, 'IP17P-256', 'Deep Blue,Cosmic Orange,Silver', '256GB,512GB,1TB', 2025, 'Professional performance in a perfect size.', 'images/iphone17max.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:05:37'),
(3, 'iPhone Air', 'iphone', 'new', 72990.00, NULL, 12, 'IPAIR-256', 'White,Black,Blue,Pink', '256GB,512GB', 2025, 'Ultra-thin design meets powerful performance.', 'images/ipAir.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(4, 'iPhone 17', 'iphone', 'new', 57990.00, NULL, 20, 'IP17-128', 'Green,Pink,White,Purple,Black', '128GB,256GB,512GB', 2025, 'The perfect iPhone for everyone.', 'images/IP17.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(5, 'iPhone 16 Pro Max', 'iphone', 'new', 69990.00, NULL, 8, 'IP16PM-256', 'Blue Titanium,White Titanium,Black Titanium,Natura', '256GB,512GB,1TB', 2024, 'Last year\'s Pro Max with incredible camera system.', 'images/IP16PROMAX.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(6, 'iPhone 16 Plus', 'iphone', 'new', 49990.00, NULL, 15, 'IP16P-128', 'Teal,Pink,White,Black,Ultramarine', '128GB,256GB,512GB', 2024, 'Big screen, big performance, great value.', 'images/IP16Plus.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(7, 'iPhone 16e', 'iphone', 'new', 44990.00, NULL, 18, 'IP16E-128', 'White,Black', '128GB,256GB', 2024, 'Affordable iPhone with essential features.', 'images/iphone16e.png', 'active', '2025-11-04 10:02:02', '2025-11-04 12:41:50'),
(8, 'iPhone 16', 'iphone', 'new', 39990.00, NULL, 25, 'IP16-128', 'Teal,Pink,White,Black,Ultramarine', '128GB,256GB,512GB', 2024, 'The standard iPhone with exceptional value.', 'images/IP17.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(9, 'iPhone 15 Pro', 'iphone', 'new', 59990.00, NULL, 10, 'IP15P-256', 'Natural Titanium,Blue Titanium,White Titanium,Blac', '256GB,512GB', 2023, 'Pro features at a great price.', 'images/15pro.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(10, 'iPhone 15', 'iphone', 'new', 49990.00, NULL, 12, 'IP15-128', 'Pink,Yellow,Green,Blue,Black', '128GB,256GB,512GB', 2023, 'Modern design with Dynamic Island.', 'images/iphone15Plus.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(11, 'iPhone 14', 'iphone', 'new', 44990.00, NULL, 15, 'IP14-128', 'Blue,Purple,Starlight,Midnight,Red', '128GB,256GB,512GB', 2022, 'Reliable performance for everyday use.', 'images/ip14.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(12, 'iPhone 13', 'iphone', 'new', 39990.00, NULL, 20, 'IP13-128', 'Pink,Blue,Midnight,Starlight,Red', '128GB,256GB,512GB', 2021, 'Great value iPhone with dual cameras.', 'images/ip13.png', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(13, 'iPhone 16 Pro Max', 'iphone', 'refurbished', 56990.00, 68990.00, 5, 'RF16PM-256', 'Blue Titanium,White Titanium', '256GB,512GB', 2024, 'Excellent condition refurbished Pro Max.', 'images/RF16PM.jpg', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(14, 'iPhone 16 Pro', 'iphone', 'refurbished', 50990.00, 63990.00, 6, 'RF16P-256', 'Blue Titanium,White Titanium,Black Titanium', '256GB,512GB', 2024, 'Pre-owned in excellent condition.', 'images/RF16P.jpg', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02'),
(15, 'iPhone 15 Pro Max', 'iphone', 'refurbished', 47990.00, 57990.00, 4, 'RF15PM-256', 'Blue Titanium,White Titanium,Black Titanium', '256GB,512GB', 2023, 'Good condition with warranty.', 'images/RF15PM.jpg', 'active', '2025-11-04 10:02:02', '2025-11-04 10:02:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'Rhudsel James Uy', 'RjUy', 'rhudseluy69@gmail.com', '09198239694', '$2y$10$jEPPKJQHUVqstbbhst9Q6uy7tojZpGNkDHRXi3l.crsGQyal1.uYi', '2025-11-03 12:19:34');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
