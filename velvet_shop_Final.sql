-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2026 at 04:30 PM
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
-- Database: `velvet_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `city` varchar(100) NOT NULL,
  `street` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `full_name`, `phone`, `city`, `street`, `is_default`) VALUES
(1, 2, 'Noor Elsaid', '+970 59-234-5678', 'Nablus', 'Al-Makhfiyah St, Building 7, Apt 3', 1),
(2, 2, 'Noor Elsaid', '+970 59-234-5678', 'Ramallah', 'Al-Irsal St, Near Al-Manara Square, Bldg 2', 0),
(3, 3, 'Lara Haddad', '+970 56-345-6789', 'Ramallah', 'Rukab St, Al-Balad, Building 14, 1st Floor', 1),
(4, 4, 'Sana Khalil', '+970 59-456-7890', 'Jenin', 'Al-Quds St, Near the Municipality, Apt 8', 1),
(5, 5, 'Maya Barakat', '+970 56-567-8901', 'Nablus', 'Rafidya, Al-Najah St, Building 3', 1),
(6, 5, 'Maya Barakat', '+970 56-567-8901', 'Tulkarm', 'Al-Salam Neighborhood, Block 5, House 12', 0),
(7, 6, 'Rami Nassar', '+970 59-678-9012', 'Hebron', 'Bab Al-Zawiya, Al-Mansoura St, Building 4', 1),
(8, 7, 'Dina Arafat', '+970 56-789-0123', 'Ramallah', 'Tireh, Jaffa St, Villa 6', 1),
(9, 8, 'Tariq Mansour', '+970 59-890-1234', 'Nablus', 'Rafidya Neighborhood, Building 21, Apt 5', 1),
(10, 9, 'Hana Yousef', '+970 56-901-2345', 'Bethlehem', 'Paul VI St, Near the Church, Building 9', 1),
(11, 10, 'Omar Sabbah', '+970 59-012-3456', 'Nablus', 'Al-Qasabah, Old City, House 33', 1),
(21, 14, 'Nada Feras', '+972 59-151-1010', 'Nablus', 'Rafidia Street', 0),
(22, 12, 'Zeina', '+972 44-100-5555', 'Hebron', 'main Street', 0);

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `parent_id`) VALUES
(1, 'Men', 'men', NULL),
(2, 'Women', 'women', NULL),
(3, 'Top', 'men-top', 1),
(4, 'Bottom', 'men-bottom', 1),
(5, 'Top', 'women-top', 2),
(6, 'Bottom', 'women-bottom', 2),
(7, 'One-Piece', 'women-one-piece', 2);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `is_read`, `sent_at`) VALUES
(2, 'Khalid Mansour', 'khalid.m@hotmail.com', 'I received the wrong size in my order. Can I exchange it?', 1, '2025-02-05 12:30:00'),
(3, 'Rima Nassar', 'rima.n@gmail.com', 'The Red Wrap Dress is beautiful! Do you have it in green?', 0, '2025-02-20 07:15:00'),
(4, 'Yousef Barakat', 'yousef.b@yahoo.com', 'Are there any student discounts available?', 0, '2025-03-10 14:45:00'),
(5, 'Hala Odeh', 'hala.odeh@gmail.com', 'I placed an order 5 days ago and it has not shipped yet.', 1, '2025-04-02 07:00:00'),
(6, 'Firas Salem', 'firas.salem@gmail.com', 'What is your return and exchange policy?', 0, '2025-04-06 10:30:00'),
(7, 'Leen Khoury', 'leen.khoury@outlook.com', 'When is the next restock for the Cream Knit Top in XL?', 1, '2025-04-07 05:45:00'),
(8, 'zeina', 'zeinaSuwwan@2026.gmail', 'Hello Velvet Team ! Thank you for the amazing summer collection .', 1, '2026-05-13 10:17:09'),
(9, 'Zeina', 'zeina.mohannad@gmail.com', 'Amazing Collection !', 0, '2026-05-14 08:56:02');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `uses_limit` int(11) DEFAULT NULL,
  `uses_count` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `uses_limit`, `uses_count`, `is_active`, `expires_at`, `created_at`) VALUES
(1, 'VELVET26', 'percentage', 10.00, 0.00, NULL, 23, 1, '2026-12-31', '2026-05-01 06:00:00'),
(2, 'SUMMER26', 'percentage', 25.00, 200.00, 100, 43, 1, '2026-08-31', '2025-05-01 06:00:00'),
(3, 'NEWMEMBER', 'fixed', 30.00, 0.00, NULL, 8, 1, NULL, '2025-01-01 07:00:00'),
(4, 'FLAT50', 'fixed', 50.00, 350.00, 50, 17, 1, '2025-12-31', '2025-02-01 07:00:00'),
(5, 'WINTER15', 'percentage', 15.00, 150.00, 80, 80, 0, '2025-03-01', '2024-12-01 07:00:00'),
(6, 'EID2026', 'percentage', 20.00, 100.00, 200, 65, 0, '2026-06-01', '2025-03-25 06:00:00'),
(7, 'FLASH30', 'fixed', 30.00, 250.00, 30, 30, 0, '2025-02-14', '2025-02-10 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `coupon_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('COD','VISA') NOT NULL DEFAULT 'COD',
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `address_id`, `coupon_id`, `status`, `payment_method`, `payment_status`, `subtotal`, `discount_amount`, `shipping_fee`, `total_amount`, `notes`, `ordered_at`, `updated_at`) VALUES
(24, 14, 21, 2, 'confirmed', 'COD', 'pending', 560.00, 140.00, 0.00, 420.00, '', '2026-05-13 20:25:37', '2026-05-13 20:26:52'),
(25, 2, 1, NULL, 'delivered', 'COD', 'pending', 480.00, 0.00, 0.00, 480.00, '', '2025-12-03 08:15:00', '2025-12-08 12:00:00'),
(26, 3, 3, NULL, 'delivered', 'VISA', 'paid', 320.00, 0.00, 20.00, 340.00, '', '2025-12-07 07:30:00', '2025-12-12 09:00:00'),
(27, 4, 4, 1, 'delivered', 'COD', 'pending', 300.00, 30.00, 0.00, 270.00, '', '2025-12-14 14:00:00', '2025-12-19 08:00:00'),
(28, 5, 5, NULL, 'cancelled', 'VISA', 'failed', 260.00, 0.00, 20.00, 280.00, '', '2025-12-20 06:45:00', '2025-12-21 07:00:00'),
(29, 6, 7, NULL, 'delivered', 'COD', 'pending', 480.00, 0.00, 0.00, 480.00, '', '2025-12-28 11:20:00', '2026-01-03 08:00:00'),
(30, 7, 8, NULL, 'delivered', 'VISA', 'paid', 400.00, 0.00, 0.00, 400.00, '', '2026-01-05 09:00:00', '2026-01-10 07:00:00'),
(31, 8, 9, 1, 'delivered', 'COD', 'pending', 580.00, 58.00, 0.00, 522.00, '', '2026-01-11 12:30:00', '2026-01-16 08:30:00'),
(32, 9, 10, NULL, 'delivered', 'VISA', 'paid', 320.00, 0.00, 20.00, 340.00, '', '2026-01-17 08:00:00', '2026-01-22 13:00:00'),
(33, 10, 11, NULL, 'shipped', 'COD', 'pending', 300.00, 0.00, 20.00, 320.00, '', '2026-01-22 07:15:00', '2026-01-25 06:00:00'),
(34, 2, 1, NULL, 'delivered', 'VISA', 'paid', 440.00, 0.00, 0.00, 440.00, '', '2026-01-26 14:45:00', '2026-01-31 12:00:00'),
(35, 3, 3, NULL, 'cancelled', 'COD', 'pending', 200.00, 0.00, 20.00, 220.00, '', '2026-01-30 06:00:00', '2026-01-31 07:00:00'),
(36, 4, 4, NULL, 'delivered', 'VISA', 'paid', 560.00, 0.00, 0.00, 560.00, '', '2026-02-02 08:30:00', '2026-02-07 09:00:00'),
(37, 5, 5, 2, 'delivered', 'COD', 'pending', 700.00, 175.00, 0.00, 525.00, '', '2026-02-06 07:00:00', '2026-02-11 08:00:00'),
(38, 6, 7, NULL, 'delivered', 'VISA', 'paid', 320.00, 0.00, 20.00, 340.00, '', '2026-02-10 13:00:00', '2026-02-15 11:00:00'),
(39, 7, 8, NULL, 'processing', 'COD', 'pending', 480.00, 0.00, 0.00, 480.00, '', '2026-02-14 09:30:00', '2026-02-15 07:00:00'),
(40, 8, 9, NULL, 'delivered', 'VISA', 'paid', 400.00, 0.00, 0.00, 400.00, '', '2026-02-18 06:45:00', '2026-02-23 08:00:00'),
(41, 9, 10, 1, 'delivered', 'COD', 'pending', 600.00, 60.00, 0.00, 540.00, '', '2026-02-22 12:00:00', '2026-02-27 09:00:00'),
(42, 10, 11, NULL, 'shipped', 'VISA', 'paid', 340.00, 0.00, 0.00, 340.00, '', '2026-02-27 08:00:00', '2026-03-01 07:00:00'),
(43, 2, 1, NULL, 'delivered', 'VISA', 'paid', 450.00, 0.00, 0.00, 450.00, '', '2026-03-03 07:00:00', '2026-03-08 08:00:00'),
(44, 3, 3, 3, 'delivered', 'COD', 'pending', 800.00, 30.00, 0.00, 770.00, '', '2026-03-07 09:00:00', '2026-03-12 12:00:00'),
(45, 4, 4, NULL, 'delivered', 'VISA', 'paid', 320.00, 0.00, 20.00, 340.00, '', '2026-03-11 11:30:00', '2026-03-16 09:00:00'),
(46, 5, 5, NULL, 'delivered', 'COD', 'pending', 580.00, 0.00, 0.00, 580.00, '', '2026-03-15 08:15:00', '2026-03-20 07:00:00'),
(47, 6, 7, 2, 'delivered', 'VISA', 'paid', 600.00, 150.00, 0.00, 450.00, '', '2026-03-18 07:30:00', '2026-03-23 08:00:00'),
(48, 7, 8, NULL, 'delivered', 'COD', 'pending', 280.00, 0.00, 20.00, 300.00, '', '2026-03-22 13:00:00', '2026-03-27 09:00:00'),
(49, 8, 9, NULL, 'shipped', 'VISA', 'paid', 380.00, 0.00, 0.00, 380.00, '', '2026-03-26 08:00:00', '2026-03-28 06:00:00'),
(50, 9, 10, NULL, 'cancelled', 'COD', 'pending', 200.00, 0.00, 20.00, 220.00, '', '2026-03-29 05:30:00', '2026-03-30 07:00:00'),
(51, 10, 11, NULL, 'delivered', 'VISA', 'paid', 560.00, 0.00, 0.00, 560.00, '', '2026-04-02 06:00:00', '2026-04-07 07:00:00'),
(52, 2, 1, 1, 'delivered', 'COD', 'pending', 700.00, 70.00, 0.00, 630.00, '', '2026-04-05 07:30:00', '2026-04-10 08:00:00'),
(53, 3, 3, NULL, 'delivered', 'VISA', 'paid', 320.00, 0.00, 20.00, 340.00, '', '2026-04-08 11:00:00', '2026-04-13 06:00:00'),
(54, 4, 4, NULL, 'delivered', 'COD', 'pending', 450.00, 0.00, 0.00, 450.00, '', '2026-04-11 08:00:00', '2026-04-16 11:00:00'),
(55, 5, 5, 4, 'delivered', 'VISA', 'paid', 800.00, 50.00, 0.00, 750.00, '', '2026-04-14 06:15:00', '2026-04-19 07:00:00'),
(56, 6, 7, NULL, 'processing', 'COD', 'pending', 300.00, 0.00, 20.00, 320.00, '', '2026-04-17 12:30:00', '2026-04-18 06:00:00'),
(57, 7, 8, NULL, 'shipped', 'VISA', 'paid', 480.00, 0.00, 0.00, 480.00, '', '2026-04-20 07:00:00', '2026-04-22 08:00:00'),
(58, 8, 9, NULL, 'confirmed', 'COD', 'pending', 360.00, 0.00, 0.00, 360.00, '', '2026-04-24 10:00:00', '2026-04-24 12:00:00'),
(59, 9, 10, NULL, 'cancelled', 'VISA', 'failed', 280.00, 0.00, 20.00, 300.00, '', '2026-04-28 06:30:00', '2026-04-29 07:00:00'),
(60, 10, 11, NULL, 'delivered', 'VISA', 'paid', 560.00, 0.00, 0.00, 560.00, '', '2026-05-01 06:00:00', '2026-05-06 08:00:00'),
(61, 2, 1, 1, 'delivered', 'COD', 'pending', 650.00, 65.00, 0.00, 585.00, '', '2026-05-03 07:15:00', '2026-05-08 07:00:00'),
(62, 3, 3, NULL, 'delivered', 'VISA', 'paid', 480.00, 0.00, 0.00, 480.00, '', '2026-05-05 11:30:00', '2026-05-10 06:00:00'),
(63, 4, 4, NULL, 'shipped', 'COD', 'pending', 400.00, 0.00, 0.00, 400.00, '', '2026-05-07 08:00:00', '2026-05-09 07:00:00'),
(64, 5, 5, NULL, 'processing', 'VISA', 'paid', 340.00, 0.00, 20.00, 360.00, '', '2026-05-09 06:30:00', '2026-05-10 05:00:00'),
(65, 6, 7, NULL, 'confirmed', 'COD', 'pending', 280.00, 0.00, 20.00, 300.00, '', '2026-05-10 12:00:00', '2026-05-10 13:00:00'),
(66, 12, 22, NULL, 'shipped', 'COD', 'pending', 450.00, 0.00, 0.00, 450.00, '', '2026-05-12 07:00:00', '2026-05-14 08:50:46'),
(67, 14, 21, NULL, 'confirmed', 'VISA', 'paid', 320.00, 0.00, 20.00, 340.00, '', '2026-05-13 11:00:00', '2026-05-13 12:00:00'),
(69, 12, 22, NULL, 'pending', 'VISA', 'paid', 60.00, 0.00, 20.00, 80.00, '', '2026-05-14 10:29:37', '2026-05-14 10:29:37');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `size`, `color`, `quantity`, `unit_price`) VALUES
(30, 24, 31, NULL, 'Blush Floral Midi Dress', 'S', NULL, 1, 450.00),
(31, 24, 6, 108, 'Women\'s Casual Loose Long Sleeve Shirt', 'S', 'white', 1, 110.00),
(32, 25, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(33, 25, 3, NULL, 'Premium Matte Satin Dress', 'M', NULL, 1, 400.00),
(34, 26, 1, NULL, 'Classic Black Dress', 'S', NULL, 1, 320.00),
(35, 27, 4, NULL, 'Elegant Long Dress', 'L', NULL, 1, 300.00),
(36, 28, 2, 172, 'Summer Dress', 'S', 'Pink', 1, 165.00),
(37, 28, 22, 163, 'Essential White Straight-Leg Pants', 'XS', 'White', 1, 120.00),
(38, 29, 21, NULL, 'Vintage-Style Cream Floral Maxi Dress', 'M', NULL, 1, 200.00),
(39, 29, 25, NULL, 'Classic Women\'s Trench Coat', 'M', NULL, 1, 200.00),
(40, 30, 3, NULL, 'Premium Matte Satin Dress', 'S', NULL, 1, 400.00),
(41, 31, 31, NULL, 'Blush Floral Midi Dress', 'M', NULL, 1, 450.00),
(42, 31, 6, 108, 'Women\'s Casual Loose Long Sleeve Shirt', 'S', 'white', 1, 110.00),
(43, 32, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(44, 33, 21, NULL, 'Vintage-Style Cream Floral Maxi Dress', 'L', NULL, 1, 200.00),
(45, 33, 8, NULL, 'Women\'s Floral Printed Skirt', 'M', NULL, 1, 180.00),
(46, 34, 3, NULL, 'Premium Matte Satin Dress', 'M', NULL, 1, 400.00),
(47, 34, 5, NULL, 'Floral Blouse', 'S', NULL, 1, 160.00),
(48, 35, 25, NULL, 'Classic Women\'s Trench Coat', 'S', NULL, 1, 200.00),
(49, 36, 31, NULL, 'Blush Floral Midi Dress', 'S', NULL, 1, 450.00),
(50, 36, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(51, 37, 3, NULL, 'Premium Matte Satin Dress', 'M', NULL, 1, 400.00),
(52, 37, 21, NULL, 'Vintage-Style Cream Floral Maxi Dress', 'S', NULL, 1, 200.00),
(53, 37, 27, NULL, 'White & Blue Floral Maxi Dress', 'M', NULL, 1, 250.00),
(54, 38, 4, NULL, 'Elegant Long Dress', 'M', NULL, 1, 300.00),
(55, 39, 31, NULL, 'Blush Floral Midi Dress', 'L', NULL, 1, 450.00),
(56, 39, 11, NULL, 'Essential Navy Deep-Tone Shirt', 'M', NULL, 1, 150.00),
(57, 40, 3, NULL, 'Premium Matte Satin Dress', 'S', NULL, 1, 400.00),
(58, 41, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(59, 41, 32, NULL, 'Ruffled Tiered Blouse', 'XS', 'Beige', 2, 60.00),
(60, 41, 22, 163, 'Essential White Straight-Leg Pants', 'XS', 'White', 1, 120.00),
(61, 42, 6, 108, 'Women\'s Casual Loose Long Sleeve Shirt', 'S', 'white', 2, 110.00),
(62, 42, 33, 164, 'Elastic-waist Floral Chiffon Skirt', 'XS', 'pink', 1, 60.00),
(63, 43, 31, NULL, 'Blush Floral Midi Dress', 'M', NULL, 1, 450.00),
(64, 44, 3, NULL, 'Premium Matte Satin Dress', 'M', NULL, 1, 400.00),
(65, 44, 4, NULL, 'Elegant Long Dress', 'L', NULL, 1, 300.00),
(66, 44, 21, NULL, 'Vintage-Style Cream Floral Maxi Dress', 'M', NULL, 1, 200.00),
(67, 45, 1, NULL, 'Classic Black Dress', 'S', NULL, 1, 320.00),
(68, 46, 31, NULL, 'Blush Floral Midi Dress', 'S', NULL, 1, 450.00),
(69, 46, 5, NULL, 'Floral Blouse', 'M', NULL, 1, 160.00),
(70, 47, 3, NULL, 'Premium Matte Satin Dress', 'L', NULL, 1, 400.00),
(71, 47, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(72, 48, 32, NULL, 'Ruffled Tiered Blouse', 'XS', 'Beige', 1, 60.00),
(73, 48, 37, 167, 'Smart-Casual Top for Men', 'L', 'Deep Burgundy', 1, 80.00),
(74, 48, 14, NULL, 'Premium Charcoal Knit Polo', 'M', NULL, 1, 120.00),
(75, 49, 6, 109, 'Women\'s Casual Loose Long Sleeve Shirt', 'M', 'Black', 2, 110.00),
(76, 49, 33, 165, 'Elastic-waist Floral Chiffon Skirt', 'XS', 'lime green', 1, 60.00),
(77, 50, 4, NULL, 'Elegant Long Dress', 'M', NULL, 1, 200.00),
(78, 51, 31, NULL, 'Blush Floral Midi Dress', 'M', NULL, 1, 450.00),
(79, 51, 4, NULL, 'Elegant Long Dress', 'L', NULL, 1, 300.00),
(80, 52, 3, NULL, 'Premium Matte Satin Dress', 'S', NULL, 1, 400.00),
(81, 52, 21, NULL, 'Vintage-Style Cream Floral Maxi Dress', 'M', NULL, 1, 200.00),
(82, 52, 5, NULL, 'Floral Blouse', 'S', NULL, 1, 160.00),
(83, 53, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(84, 54, 31, NULL, 'Blush Floral Midi Dress', 'S', NULL, 1, 450.00),
(85, 55, 3, NULL, 'Premium Matte Satin Dress', 'M', NULL, 1, 400.00),
(86, 55, 31, NULL, 'Blush Floral Midi Dress', 'L', NULL, 1, 450.00),
(87, 56, 6, 108, 'Women\'s Casual Loose Long Sleeve Shirt', 'S', 'white', 1, 110.00),
(88, 56, 38, 170, 'Black Wide Pants', 'M', 'Black', 1, 100.00),
(89, 57, 1, NULL, 'Classic Black Dress', 'M', NULL, 1, 320.00),
(90, 57, 11, NULL, 'Essential Navy Deep-Tone Shirt', 'L', NULL, 1, 150.00),
(91, 58, 32, NULL, 'Ruffled Tiered Blouse', 'XS', 'Beige', 2, 60.00),
(92, 58, 33, 164, 'Elastic-waist Floral Chiffon Skirt', 'XS', 'pink', 2, 60.00),
(93, 59, 4, NULL, 'Elegant Long Dress', 'M', NULL, 1, 300.00),
(94, 60, 31, NULL, 'Blush Floral Midi Dress', 'M', NULL, 1, 450.00),
(95, 60, 1, NULL, 'Classic Black Dress', 'S', NULL, 1, 320.00),
(96, 61, 3, NULL, 'Premium Matte Satin Dress', 'M', NULL, 1, 400.00),
(97, 61, 5, NULL, 'Floral Blouse', 'M', NULL, 1, 160.00),
(98, 62, 31, NULL, 'Blush Floral Midi Dress', 'L', NULL, 1, 450.00),
(99, 62, 21, NULL, 'Vintage-Style Cream Floral Maxi Dress', 'M', NULL, 1, 200.00),
(100, 63, 4, NULL, 'Elegant Long Dress', 'M', NULL, 1, 300.00),
(101, 63, 38, 171, 'Black Wide Pants', 'XL', 'Black', 1, 100.00),
(102, 64, 6, 109, 'Women\'s Casual Loose Long Sleeve Shirt', 'M', 'Black', 1, 110.00),
(103, 64, 33, 165, 'Elastic-waist Floral Chiffon Skirt', 'XS', 'lime green', 2, 60.00),
(104, 65, 32, NULL, 'Ruffled Tiered Blouse', 'XS', 'Beige', 2, 60.00),
(105, 65, 37, 169, 'Smart-Casual Top for Men', 'XL', 'Black', 1, 60.00),
(106, 66, 31, NULL, 'Blush Floral Midi Dress', 'M', NULL, 1, 450.00),
(107, 67, 1, NULL, 'Classic Black Dress', 'S', NULL, 1, 320.00),
(110, 69, 32, NULL, 'Ruffled Tiered Blouse', 'XS', 'Beige', 1, 60.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `event_label` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `happened_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`id`, `order_id`, `event_label`, `description`, `happened_at`) VALUES
(48, 24, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2026-05-13 20:25:37'),
(49, 24, 'Confirmed', 'Status updated by admin.', '2026-05-13 20:26:52'),
(50, 25, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2025-12-03 08:15:00'),
(51, 25, 'Confirmed', 'Status updated by admin.', '2025-12-04 07:00:00'),
(52, 25, 'Shipped', 'Your order is on its way.', '2025-12-05 09:00:00'),
(53, 25, 'Delivered', 'Order delivered successfully.', '2025-12-08 12:00:00'),
(54, 26, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2025-12-07 07:30:00'),
(55, 26, 'Confirmed', 'Status updated by admin.', '2025-12-08 08:00:00'),
(56, 26, 'Delivered', 'Order delivered successfully.', '2025-12-12 09:00:00'),
(57, 27, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2025-12-14 14:00:00'),
(58, 27, 'Delivered', 'Order delivered successfully.', '2025-12-19 08:00:00'),
(59, 28, 'Order Placed', 'Your order has been placed.', '2025-12-20 06:45:00'),
(60, 28, 'Cancelled', 'Customer cancelled the order.', '2025-12-21 07:00:00'),
(61, 29, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2025-12-28 11:20:00'),
(62, 29, 'Delivered', 'Order delivered successfully.', '2026-01-03 08:00:00'),
(63, 30, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2026-01-05 09:00:00'),
(64, 30, 'Delivered', 'Order delivered successfully.', '2026-01-10 07:00:00'),
(65, 31, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2026-01-11 12:30:00'),
(66, 31, 'Confirmed', 'Status updated by admin.', '2026-01-12 07:00:00'),
(67, 31, 'Delivered', 'Order delivered successfully.', '2026-01-16 08:30:00'),
(68, 32, 'Order Placed', 'Your order has been placed.', '2026-01-17 08:00:00'),
(69, 32, 'Delivered', 'Order delivered successfully.', '2026-01-22 13:00:00'),
(70, 33, 'Order Placed', 'Your order has been placed.', '2026-01-22 07:15:00'),
(71, 33, 'Shipped', 'Your order is on its way.', '2026-01-25 06:00:00'),
(72, 34, 'Order Placed', 'Your order has been placed.', '2026-01-26 14:45:00'),
(73, 34, 'Delivered', 'Order delivered successfully.', '2026-01-31 12:00:00'),
(74, 35, 'Order Placed', 'Your order has been placed.', '2026-01-30 06:00:00'),
(75, 35, 'Cancelled', 'Customer cancelled the order.', '2026-01-31 07:00:00'),
(76, 36, 'Order Placed', 'Your order has been placed.', '2026-02-02 08:30:00'),
(77, 36, 'Delivered', 'Order delivered successfully.', '2026-02-07 09:00:00'),
(78, 37, 'Order Placed', 'Your order has been placed.', '2026-02-06 07:00:00'),
(79, 37, 'Delivered', 'Order delivered successfully.', '2026-02-11 08:00:00'),
(80, 38, 'Order Placed', 'Your order has been placed.', '2026-02-10 13:00:00'),
(81, 38, 'Delivered', 'Order delivered successfully.', '2026-02-15 11:00:00'),
(82, 39, 'Order Placed', 'Your order has been placed.', '2026-02-14 09:30:00'),
(83, 39, 'Confirmed', 'Status updated by admin.', '2026-02-15 07:00:00'),
(84, 40, 'Order Placed', 'Your order has been placed.', '2026-02-18 06:45:00'),
(85, 40, 'Delivered', 'Order delivered successfully.', '2026-02-23 08:00:00'),
(86, 41, 'Order Placed', 'Your order has been placed.', '2026-02-22 12:00:00'),
(87, 41, 'Delivered', 'Order delivered successfully.', '2026-02-27 09:00:00'),
(88, 42, 'Order Placed', 'Your order has been placed.', '2026-02-27 08:00:00'),
(89, 42, 'Shipped', 'Your order is on its way.', '2026-03-01 07:00:00'),
(90, 43, 'Order Placed', 'Your order has been placed.', '2026-03-03 07:00:00'),
(91, 43, 'Delivered', 'Order delivered successfully.', '2026-03-08 08:00:00'),
(92, 44, 'Order Placed', 'Your order has been placed.', '2026-03-07 09:00:00'),
(93, 44, 'Confirmed', 'Status updated by admin.', '2026-03-08 07:00:00'),
(94, 44, 'Delivered', 'Order delivered successfully.', '2026-03-12 12:00:00'),
(95, 45, 'Order Placed', 'Your order has been placed.', '2026-03-11 11:30:00'),
(96, 45, 'Delivered', 'Order delivered successfully.', '2026-03-16 09:00:00'),
(97, 46, 'Order Placed', 'Your order has been placed.', '2026-03-15 08:15:00'),
(98, 46, 'Delivered', 'Order delivered successfully.', '2026-03-20 07:00:00'),
(99, 47, 'Order Placed', 'Your order has been placed.', '2026-03-18 07:30:00'),
(100, 47, 'Delivered', 'Order delivered successfully.', '2026-03-23 08:00:00'),
(101, 48, 'Order Placed', 'Your order has been placed.', '2026-03-22 13:00:00'),
(102, 48, 'Delivered', 'Order delivered successfully.', '2026-03-27 09:00:00'),
(103, 49, 'Order Placed', 'Your order has been placed.', '2026-03-26 08:00:00'),
(104, 49, 'Shipped', 'Your order is on its way.', '2026-03-28 06:00:00'),
(105, 50, 'Order Placed', 'Your order has been placed.', '2026-03-29 05:30:00'),
(106, 50, 'Cancelled', 'Customer cancelled the order.', '2026-03-30 07:00:00'),
(107, 51, 'Order Placed', 'Your order has been placed.', '2026-04-02 06:00:00'),
(108, 51, 'Delivered', 'Order delivered successfully.', '2026-04-07 07:00:00'),
(109, 52, 'Order Placed', 'Your order has been placed.', '2026-04-05 07:30:00'),
(110, 52, 'Delivered', 'Order delivered successfully.', '2026-04-10 08:00:00'),
(111, 53, 'Order Placed', 'Your order has been placed.', '2026-04-08 11:00:00'),
(112, 53, 'Delivered', 'Order delivered successfully.', '2026-04-13 06:00:00'),
(113, 54, 'Order Placed', 'Your order has been placed.', '2026-04-11 08:00:00'),
(114, 54, 'Delivered', 'Order delivered successfully.', '2026-04-16 11:00:00'),
(115, 55, 'Order Placed', 'Your order has been placed.', '2026-04-14 06:15:00'),
(116, 55, 'Delivered', 'Order delivered successfully.', '2026-04-19 07:00:00'),
(117, 56, 'Order Placed', 'Your order has been placed.', '2026-04-17 12:30:00'),
(118, 56, 'Confirmed', 'Status updated by admin.', '2026-04-18 06:00:00'),
(119, 57, 'Order Placed', 'Your order has been placed.', '2026-04-20 07:00:00'),
(120, 57, 'Shipped', 'Your order is on its way.', '2026-04-22 08:00:00'),
(121, 58, 'Order Placed', 'Your order has been placed.', '2026-04-24 10:00:00'),
(122, 58, 'Confirmed', 'Status updated by admin.', '2026-04-24 12:00:00'),
(123, 59, 'Order Placed', 'Your order has been placed.', '2026-04-28 06:30:00'),
(124, 59, 'Cancelled', 'Customer cancelled the order.', '2026-04-29 07:00:00'),
(125, 60, 'Order Placed', 'Your order has been placed.', '2026-05-01 06:00:00'),
(126, 60, 'Delivered', 'Order delivered successfully.', '2026-05-06 08:00:00'),
(127, 61, 'Order Placed', 'Your order has been placed.', '2026-05-03 07:15:00'),
(128, 61, 'Confirmed', 'Status updated by admin.', '2026-05-04 06:00:00'),
(129, 61, 'Delivered', 'Order delivered successfully.', '2026-05-08 07:00:00'),
(130, 62, 'Order Placed', 'Your order has been placed.', '2026-05-05 11:30:00'),
(131, 62, 'Delivered', 'Order delivered successfully.', '2026-05-10 06:00:00'),
(132, 63, 'Order Placed', 'Your order has been placed.', '2026-05-07 08:00:00'),
(133, 63, 'Shipped', 'Your order is on its way.', '2026-05-09 07:00:00'),
(134, 64, 'Order Placed', 'Your order has been placed.', '2026-05-09 06:30:00'),
(135, 64, 'Confirmed', 'Status updated by admin.', '2026-05-10 05:00:00'),
(136, 65, 'Order Placed', 'Your order has been placed.', '2026-05-10 12:00:00'),
(137, 65, 'Confirmed', 'Status updated by admin.', '2026-05-10 13:00:00'),
(138, 66, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2026-05-12 07:00:00'),
(139, 67, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2026-05-13 11:00:00'),
(140, 67, 'Confirmed', 'Status updated by admin.', '2026-05-13 12:00:00'),
(142, 66, 'Shipped', 'Status updated by admin.', '2026-05-14 08:49:05'),
(143, 69, 'Order Placed', 'Your order has been placed and is awaiting confirmation.', '2026-05-14 10:29:37');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` char(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token`, `expires_at`, `used`) VALUES
(1, 3, '847291', '2025-03-10 14:32:00', 1),
(2, 7, '563018', '2025-04-02 09:15:00', 1),
(3, 10, '291847', '2025-04-20 18:00:00', 0),
(9, 2, '657349', '2026-05-14 12:54:26', 0);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `is_sale` tinyint(1) NOT NULL DEFAULT 0,
  `is_new` tinyint(1) NOT NULL DEFAULT 0,
  `is_bestseller` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `base_price`, `sale_price`, `is_sale`, `is_new`, `is_bestseller`, `is_active`, `created_at`) VALUES
(1, 7, 'Classic Black Dress', 'classic-black-dress', 'An Elegant mid-length dress in smooth fabric.', 320.00, NULL, 0, 1, 0, 1, '2026-05-07 07:00:00'),
(2, 7, 'Summer Dress', 'summer-dress', 'Light and long dress in soft cotton.', 165.00, 140.00, 1, 1, 1, 1, '2024-10-12 08:00:00'),
(3, 7, 'premium matte satin Dress', 'premium-matte-satin-dress', 'An elegant floor-length dress in a rich deep burgundy shade. Perfect for formal evenings, weddings, engagement celebrations, and special occasions where elegance takes center stage.', 400.00, NULL, 0, 0, 1, 1, '2024-10-20 06:00:00'),
(4, 7, 'Elegant Long Dress', 'elegant-long-dress', 'Crafted from premium soft organza layered over a smooth inner lining, creating a lightweight, ethereal silhouette with graceful volume and elegant movement.', 300.00, NULL, 0, 0, 0, 1, '2026-05-07 11:46:19'),
(5, 5, 'floral blouse', 'floral-blouse', 'Beautiful vintage-inspired floral blouse featuring soft ruffle detailing and long sleeves. This elegant button-down shirt is perfect for spring and summer outfits, casual wear, office style, or feminine chic fashion looks.', 160.00, 140.00, 1, 0, 1, 1, '2026-05-07 12:00:00'),
(6, 5, 'Women\'s Casual Loose Long Sleeve Shirt', 'women-s-casual-loose-long-sleeve-shirt', 'Women\'s Solid Color Casual Loose Long Sleeve Shirt, Suitable For Daily Casual, Commuting, And Office Wear Blouse For Women Elegant Classy Women\'s Shirts Women\'s Work Tops White Blouse Working Blouse', 140.00, 110.00, 1, 0, 1, 1, '2024-11-22 08:00:00'),
(7, 6, 'Women\'s High-waist Pants', 'women-s-high-waist-pants', 'High-waist wide-leg trousers in lightweight fabric.', 100.00, 85.00, 1, 0, 0, 1, '2024-12-01 07:00:00'),
(8, 6, 'women\'s floral printed Skirt', 'floral-long-skirt', 'Elastic Waist Flower Pattern Printed Flared Skirt', 180.00, NULL, 0, 1, 0, 1, '2024-12-10 07:00:00'),
(9, 5, 'Pearl-Embellished Ruffle Chiffon Blouse', 'pearl-ruffle-chiffon-blouse', 'Elevate your wardrobe with this sophisticated and feminine chiffon blouse. This piece perfectly blends classic Victorian charm with modern elegance. ', 150.00, NULL, 0, 0, 0, 1, '2026-05-07 12:17:48'),
(10, 6, 'Elegant Pleated Button-Front Maxi Skirt', 'elegant-button-front-skirt', 'Elevate your wardrobe with this effortlessly feminine Skirt. Combining timeless elegance with modern details, this piece is designed to flow beautifully with every step.', 180.00, NULL, 0, 0, 0, 1, '2026-05-07 13:22:01'),
(11, 3, 'Essential Navy Deep-Tone Shirt', 'men-navy-blue-shirt', 'A sleek, minimalist powerhouse for the modern man. Smooth, high-density matte finish fabric that holds its shape while staying breathable.', 150.00, NULL, 0, 0, 0, 1, '2026-05-07 13:31:22'),
(12, 3, 'Classic Azure Pinstripe Shirt', 'classic-azure-pinstripe-shirt', 'Fine vertical pinstripes in blue and white, featuring a functional chest pocket and a comfortable drop-shoulder cut.', 120.00, NULL, 0, 0, 0, 1, '2026-05-07 13:33:11'),
(14, 3, 'Premium Charcoal Knit Polo', 'premium-charcoal-knit-polo', 'Crafted from a premium fine-gauge knit with a subtle heathered charcoal finish, providing a soft touch and natural stretch.', 120.00, NULL, 0, 0, 1, 1, '2026-05-07 13:45:05'),
(15, 5, 'Pink Pinstripe Blouse.', 'pink-pinstripe-blouse', 'A timeless fine vertical pinstripe in soft candy-pink and white designed with flared ruffle cuffs and delicate self-tie ribbons.', 120.00, 100.00, 1, 0, 0, 1, '2026-05-07 15:10:27'),
(16, 6, 'Women\'s Jeans', 'women-s-jeans', 'Loose High Waist Slimming Straight Leg Jeans for Ladies.', 100.00, 80.00, 1, 0, 0, 1, '2026-05-07 15:14:08'),
(17, 5, 'Casual Women\'s Shirt', 'casual-women-shirt', 'Women\'s Fashion Casual Elegant Round Neck Loose Flattering Long Sleeve Shirt.\r\n', 100.00, NULL, 0, 1, 0, 1, '2026-05-07 15:18:30'),
(18, 5, 'Navy Ruffle Wrap Blouse', 'navy-ruffle-wrap-blouse', 'A beautiful, flowy top that adds a feminine touch to any outfit. Designed with a flattering cinched waist and puffy sleeves with ruffled cuffs.', 120.00, NULL, 0, 1, 0, 1, '2026-05-07 16:49:42'),
(19, 6, 'Pleated Denim Maxi Skirts', 'denim-skirt', 'The perfect alternative to jeans—all the style of denim with the comfort of a skirt.', 120.00, 100.00, 0, 0, 0, 1, '2026-05-07 16:50:40'),
(20, 6, 'Women\'s High Waist A-Line Long Casual Elegant Skirt', 'women-s-high-waist-a-line-long-casual-elegant-skirt', 'Women\'s High Waist A-Line Long Casual Elegant Skirt Black Elegant Polyester Plain Flared Non-Stretch.', 150.00, NULL, 0, 0, 0, 1, '2026-05-07 16:56:56'),
(21, 7, 'Vintage-Style Cream Floral Maxi Dress', 'vintage-style-cream-floral-maxi-dress', 'Made from a sheer, lightweight cream material that feels airy and looks elegant. It includes a soft lining for full coverage.', 200.00, NULL, 0, 0, 1, 1, '2026-05-07 17:04:52'),
(22, 4, 'Essential White Straight-Leg Pants', 'essential-white-straight-leg-pants', 'Crafted from a mid-weight, breathable cotton blend with a clean flat-front design and functional side pockets.', 120.00, NULL, 0, 0, 0, 1, '2026-05-07 17:10:04'),
(25, 5, 'Classic Women\'s Trench Coat', 'women-trench-coat', 'This Midi-Length Trench Coat blends functional utility with a high-fashion silhouette, making it the perfect outer layer for shifting seasons.', 200.00, NULL, 0, 0, 0, 1, '2026-05-07 21:48:22'),
(27, 7, 'White & Blue Floral Maxi Dress.', 'white-blue-floral-maxi-dress', 'Floral and long-sleeved maxi dress featuring a blue botanical print, a waist-defining belt, and elegant flowing sleeves.', 250.00, NULL, 0, 1, 0, 1, '2026-05-08 07:51:31'),
(31, 7, 'Blush Floral Midi Dress', 'blush-floral-midi-dress', 'A dreamlike, light pink dress featuring a textured floral-embossed fabric, a flattering square neckline, and romantic sheer chiffon puff sleeves.', 450.00, NULL, 0, 0, 1, 1, '2026-05-09 20:05:12'),
(32, 5, 'Ruffled Tiered Blouse', 'ruffled-tiered-blouse', 'Available in earthy beige, deep burgundy, and dusty blue, these charming tops are made from a soft, breathable linen-style fabric with delicate scalloped embroidery and ruffled tiers.', 60.00, NULL, 0, 1, 0, 1, '2026-05-09 20:06:11'),
(33, 6, 'Elastic-waist floral chiffon skirt', 'elastic-waist-floral-chiffon-skirt', 'sweet long skirt', 60.00, NULL, 0, 0, 0, 1, '2026-05-09 20:09:39'),
(37, 3, 'Smart-casual top for men', 'smart-casual-top-for-men', 'A top with a V-neck style without buttons for a clean, minimalist look.', 80.00, 60.00, 1, 1, 0, 1, '2026-05-13 10:13:34'),
(38, 4, 'Black Wide Pants', 'black-wide-pants', '', 100.00, 85.00, 1, 0, 0, 1, '2026-05-13 20:45:37'),
(39, 4, 'Smart-Casual Brown Pants for men', 'smart-casual-brown-pants-for-men', 'Crafted from a premium heavyweight twill blend that holds its shape beautifully while offering a soft, matte finish.', 80.00, 69.50, 1, 0, 1, 1, '2026-05-14 13:49:49');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `is_primary`, `sort_order`) VALUES
(33, 1, 'images/women/Classic Black Midi Dress.jpg', 1, 1),
(36, 2, 'images/women/white summer dress.jpg', 0, 2),
(37, 2, 'images/women/pink summer dress.jpg', 1, 1),
(39, 3, 'images/women/red elegant dress.jpg', 1, 1),
(45, 5, 'images/women/floral women top.jpg', 1, 1),
(48, 6, 'images/women/Women\'s Solid Color Casual Loose Long Sleeve Shirt.jpg', 1, 1),
(49, 6, 'images/women/Women\'s Solid Color Casual Loose Long Sleeve Shirt_black.png', 0, 2),
(53, 8, 'images/women/flower pattern skirt.jpg', 1, 1),
(55, 4, 'images/women/green long dress.jpg', 1, 1),
(56, 4, 'images/women/black long dress.jpg', 0, 2),
(57, 4, 'images/women/red long dress.jpg', 0, 3),
(65, 9, 'images/women/chiffon blouse.jpg', 1, 1),
(67, 10, 'images/women/blue skirt.jpg', 1, 1),
(68, 10, 'images/women/pink skirt.jpg', 0, 2),
(69, 11, 'images/men/navy blue men shirt.jpg', 1, 1),
(70, 12, 'images/men/blue men shirt.jpg', 1, 1),
(71, 14, 'images/men/grey men t-shirt.jpg', 1, 1),
(72, 15, 'images/women/pink women shirt.jpg', 1, 1),
(73, 16, 'images/women/women jeans.jpg', 1, 1),
(74, 17, 'images/women/round neck women shirt.jpg', 1, 1),
(75, 18, 'images/women/navy blue women top.jpg', 1, 1),
(76, 19, 'images/women/denim skirt.jpg', 1, 1),
(77, 20, 'images/women/black elegant skirt.jpg', 1, 1),
(78, 21, 'images/women/white floral women dress.jpg', 1, 1),
(79, 22, 'images/men/men white pants.jpg', 1, 1),
(81, 3, 'images/women/baby blue elegant dress.jpg', 0, 2),
(82, 25, 'images/women/trench coat 1.jpg', 0, 2),
(83, 25, 'images/women/trench coat 2.jpg', 1, 1),
(84, 25, 'images/women/trench coat 3.jpg', 0, 3),
(86, 27, 'images/women/prod_27_0_1778226691.jpg', 1, 0),
(89, 6, 'images/women/prod_6_1778265674_0.png', 0, 3),
(90, 7, 'images/women/prod_7_1778320491_0.jpg', 1, 0),
(91, 7, 'images/women/prod_7_1778320590_0.png', 0, 1),
(92, 7, 'images/women/prod_7_1778320611_0.png', 0, 2),
(94, 31, 'images/women/prod_31_1778357112_0.jpg', 1, 0),
(95, 32, 'images/women/prod_32_1778357171_0.jpg', 1, 0),
(96, 33, 'images/women/prod_33_1778357379_0.jpg', 1, 0),
(100, 37, 'images/men/prod_37_1778667214_0.jpg', 1, 0),
(101, 38, 'images/men/prod_38_1778705137_0.jpg', 1, 0),
(102, 2, 'images/women/prod_2_1778706346_0.jpg', 0, 3),
(103, 39, 'images/men/prod_39_1778766589_0.jpg', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` enum('XS','S','M','L','XL','XXL') NOT NULL,
  `color` varchar(50) NOT NULL,
  `color_hex` char(7) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `color`, `color_hex`, `price`, `stock_qty`, `sku`) VALUES
(108, 6, 'S', 'white', '#FFFFFF', 140.00, 8, 'PROD6-S-0'),
(109, 6, 'M', 'Black', '#000000', 140.00, 15, 'PROD6-M-1'),
(163, 22, 'XS', 'White', '#ffffff', 120.00, 50, 'PROD22-XS-0'),
(164, 33, 'XS', 'pink', '#f3b4ca', 60.00, 57, 'PROD33-XS-0'),
(165, 33, 'XS', 'lime green', '#aaeeb2', 60.00, 60, 'PROD33-XS-1'),
(166, 33, 'XS', 'Baby Blue', '#b8f3ff', 60.00, 0, 'PROD33-XS-2'),
(167, 37, 'L', 'Deep Burgundy', '#780707', 80.00, 30, 'PROD37-L-0'),
(168, 37, 'XL', 'Olive Green', '#436b49', 80.00, 0, 'PROD37-XL-1'),
(169, 37, 'XL', 'Black', '#000000', 80.00, 19, 'PROD37-XL-2'),
(170, 38, 'M', 'Black', '#000000', 100.00, 49, 'PROD38-M-0'),
(171, 38, 'XL', 'Black', '#000000', 100.00, 60, 'PROD38-XL-1'),
(172, 2, 'S', 'Pink', '#f3a5d2', 165.00, 40, 'PROD2-S-0'),
(173, 2, 'M', 'Yellow', '#fffa75', 165.00, 59, 'PROD2-M-1'),
(174, 2, 'L', 'White', '#ffffff', 165.00, 50, 'PROD2-L-2'),
(176, 21, 'XS', 'beige', '#f9e9d2', 200.00, 0, 'PROD21-XS-0'),
(177, 20, 'S', '#000000', '#000000', 150.00, 50, 'PROD20-S-0'),
(178, 1, 'S', 'Black', '#000000', 320.00, 30, 'PROD1-S-0'),
(179, 1, 'L', 'Black', '#000000', 320.00, 20, 'PROD1-L-1'),
(181, 16, 'S', 'Jeans', '#000080', 100.00, 50, 'PROD16-S-0'),
(182, 16, 'L', 'Jeans', '#000080', 100.00, 30, 'PROD16-L-1'),
(183, 14, 'XL', 'Gray', '#808080', 120.00, 40, 'PROD14-XL-0'),
(184, 14, 'L', 'Gray', '#808080', 120.00, 20, 'PROD14-L-1'),
(185, 39, 'L', 'Brown', '#4d2600', 80.00, 30, 'PROD39-L-0'),
(186, 39, 'XXL', 'Brown', '#4d2600', 80.00, 20, 'PROD39-XXL-1'),
(187, 39, 'M', 'Brown', '#4d2600', 80.00, 15, 'PROD39-M-2'),
(188, 5, 'S', 'Pink & Orange', '#fe8534', 160.00, 30, 'PROD5-S-0'),
(189, 32, 'XS', 'Beige', '#eed5c4', 60.00, 28, 'PROD32-XS-0'),
(190, 32, 'M', 'Dusty Blue', '#4b3fe9', 60.00, 20, 'PROD32-M-1'),
(191, 32, 'S', 'deep burgundy', '#d21919', 60.00, 20, 'PROD32-S-2'),
(192, 15, 'M', 'Soft Pink', '#ffb3da', 120.00, 25, 'PROD15-M-0'),
(193, 15, 'L', 'Soft Pink', '#ffb3da', 120.00, 30, 'PROD15-L-1');

--
-- Triggers `product_variants`
--
DELIMITER $$
CREATE TRIGGER `set_variant_price` BEFORE INSERT ON `product_variants` FOR EACH ROW BEGIN
    -- If price is left empty (NULL), fetch it from the main product
    IF NEW.price IS NULL THEN
        SET NEW.price = (SELECT base_price FROM products WHERE id = NEW.product_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `store_settings`
--

CREATE TABLE `store_settings` (
  `id` int(11) NOT NULL,
  `store_name` varchar(100) NOT NULL DEFAULT 'VELVET',
  `support_phone` varchar(20) DEFAULT NULL,
  `support_email` varchar(150) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT 20.00,
  `free_shipping_above` decimal(10,2) NOT NULL DEFAULT 300.00,
  `cod_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `accent_color` char(7) NOT NULL DEFAULT '#3498db',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_settings`
--

INSERT INTO `store_settings` (`id`, `store_name`, `support_phone`, `support_email`, `instagram_url`, `facebook_url`, `low_stock_threshold`, `shipping_fee`, `free_shipping_above`, `cod_enabled`, `maintenance_mode`, `accent_color`, `updated_at`) VALUES
(1, 'VELVET', '+970 59-176-9960', 'support@velvet.ps', 'instagram.com/velvet_shop', 'facebook.com/velvet.official', 5, 20.00, 300.00, 1, 0, '#3498db', '2026-05-13 23:53:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `avatar_letter` char(1) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `phone`, `role`, `avatar_letter`, `avatar_url`, `is_active`, `created_at`) VALUES
(1, 'Qais Sharabi', 'Velvet.admin@gmail.com', '$2y$10$W5oH3CBEPwV9jpRVf8YGVOfZqn8nfeN/Vxcte9mAH.0gVDaTBn4Sa', '+970 59-123-4567', 'admin', 'Q', NULL, 1, '2024-10-01 06:00:00'),
(2, 'Noor Elsaid', 'noorelsaid56@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 59-234-5678', 'customer', 'N', NULL, 1, '2024-10-15 11:22:00'),
(3, 'Lara Haddad', 'lara.haddad@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 56-345-6789', 'customer', 'L', NULL, 1, '2024-11-02 08:05:00'),
(4, 'Sana Khalil', 'sana.khalil@outlook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 59-456-7890', 'customer', 'S', NULL, 1, '2024-11-18 14:40:00'),
(5, 'Maya Barakat', 'maya.b@yahoo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 56-567-8901', 'customer', 'M', NULL, 1, '2024-12-03 09:15:00'),
(6, 'Rami Nassar', 'rami.nassar@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 59-678-9012', 'customer', 'R', NULL, 1, '2024-12-20 07:30:00'),
(7, 'Dina Arafat', 'dina.arafat@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 56-789-0123', 'customer', 'D', NULL, 1, '2025-01-05 11:00:00'),
(8, 'Tariq Mansour', 'tariq.m@hotmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 59-890-1234', 'customer', 'T', NULL, 1, '2025-01-22 15:45:00'),
(9, 'Hana Yousef', 'hana.yousef@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 56-901-2345', 'customer', 'H', NULL, 1, '2025-02-10 06:20:00'),
(10, 'Omar Sabbah', 'omar.sabbah@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 59-012-3456', 'customer', 'O', NULL, 1, '2025-02-28 10:00:00'),
(11, 'Rana Jadallah', 'rana.jadallah@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+970 56-111-2233', 'customer', 'R', NULL, 0, '2025-03-14 08:10:00'),
(12, 'Zeina', 'zeina.mohannad@gmail.com', '$2y$10$W/w7Ti3k0NBJvUInKihZpObcI1ju6fEK.QVZ97sCdS/pFjuPUvzwC', '+972 593 444 55', 'customer', 'Z', NULL, 1, '2026-05-12 18:51:36'),
(14, 'Nada Feras', 'nada.elsaid@gmail.com', '$2y$10$vhCe48zxT.7AwVm6fKD9EOZsQG7XTNB4Lw6LKxsiCZD86pO2xli6q', '+972 59-151-1010', 'customer', 'N', NULL, 1, '2026-05-13 19:21:05');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `user_id` int(11) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `email_order_updates` tinyint(1) NOT NULL DEFAULT 1,
  `email_newsletter` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`user_id`, `display_name`, `email_order_updates`, `email_newsletter`, `updated_at`) VALUES
(1, 'Qais — Admin', 1, 0, '2026-05-07 08:49:16'),
(2, 'Noor', 1, 1, '2026-05-07 08:49:16'),
(3, 'Lara', 1, 1, '2026-05-07 08:49:16'),
(4, 'Sana', 1, 0, '2026-05-07 08:49:16'),
(5, 'Maya', 1, 1, '2026-05-07 08:49:16'),
(6, 'Rami', 0, 0, '2026-05-07 08:49:16'),
(7, 'Dina', 1, 1, '2026-05-07 08:49:16'),
(8, 'Tariq', 1, 1, '2026-05-07 08:49:16'),
(9, 'Hana', 1, 1, '2026-05-07 08:49:16'),
(10, 'Omar', 0, 1, '2026-05-07 08:49:16'),
(11, 'Rana', 1, 0, '2026-05-07 08:49:16'),
(14, 'Nada Feras', 1, 1, '2026-05-13 19:21:05');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `saved_at`) VALUES
(30, 14, 9, '2026-05-13 19:58:20'),
(31, 14, 15, '2026-05-13 19:58:22'),
(35, 12, 15, '2026-05-14 08:53:49'),
(36, 1, 37, '2026-05-14 08:59:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_addr_user` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_item` (`user_id`,`variant_id`),
  ADD KEY `fk_cart_product` (`product_id`),
  ADD KEY `fk_cart_variant` (`variant_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_slug` (`slug`),
  ADD KEY `fk_cat_parent` (`parent_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coupon_code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_user` (`user_id`),
  ADD KEY `fk_order_address` (`address_id`),
  ADD KEY `fk_order_coupon` (`coupon_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_oi_order` (`order_id`),
  ADD KEY `fk_oi_product` (`product_id`),
  ADD KEY `fk_oi_variant` (`variant_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_track_order` (`order_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prt_user` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_slug` (`slug`),
  ADD KEY `fk_prod_category` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_img_product` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_combo` (`product_id`,`size`,`color`),
  ADD UNIQUE KEY `uq_variant_sku` (`sku`);

--
-- Indexes for table `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlist_item` (`user_id`,`product_id`),
  ADD KEY `fk_wish_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT for table `store_settings`
--
ALTER TABLE `store_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_address` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `fk_track_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_img_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_var_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `fk_uset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wish_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wish_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
