-- Anime PC Warranty System Database Backup
-- Generated: 2025-11-26 10:54:13
-- Database: anime_pc_warranty

SET FOREIGN_KEY_CHECKS=0;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `created_at`) VALUES ('1', 'admin', '$2y$10$Gu6hRHgHD.NV1mXxxKmDgeuKs5/Gos6SE70kCZWch.ghy3MFoFrta', 'admin@animepc.com', 'System Administrator', '2025-11-23 13:11:06');

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_name`, `email`, `phone`, `address`, `company`, `created_at`, `updated_at`) VALUES ('1', 'rian Terrado', 'aldrianterradao0@gmail.com', '09550445956d', '200 pinagbayanan lingunan valezuela city\r\n200 pinagbayanan lingunan valezuela city', 'N/A', '2025-11-23 13:24:36', '2025-11-23 15:02:11');
INSERT INTO `clients` (`id`, `client_name`, `email`, `phone`, `address`, `company`, `created_at`, `updated_at`) VALUES ('3', 'DIWATA UNICE', 'DWIATA@GMAIL.COM', '+639051610559', 'Blk21 Lot18 pagsibol village phase2 brgy catmon sta. maria bulacan', 'N/A', '2025-11-26 12:27:57', '2025-11-26 12:27:57');

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `warranty_period` int(11) NOT NULL,
  `warranty_end_date` date NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('active','expired','void') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `serial_number`, `category`, `purchase_date`, `warranty_period`, `warranty_end_date`, `client_id`, `image_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('2', 'IPHONE 17 PRO MAX', '1456FG11', 'Other', '2025-11-23', '3', '2026-02-23', '1', 'uploads/1763880575_mines poster.jpg', 'active', 'FDGFDS', '2025-11-23 14:49:35', '2025-11-23 14:49:35');
INSERT INTO `products` (`id`, `product_name`, `serial_number`, `category`, `purchase_date`, `warranty_period`, `warranty_end_date`, `client_id`, `image_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('3', 'HP LAPTOP i5 1TB', 'SAMPLE123456', 'Laptop', '2025-11-23', '1', '2025-12-23', '1', 'uploads/1763881452_Commission_on_Higher_Education_CHEd.svg_.png', 'active', 'GFG', '2025-11-23 15:04:12', '2025-11-23 15:04:12');
INSERT INTO `products` (`id`, `product_name`, `serial_number`, `category`, `purchase_date`, `warranty_period`, `warranty_end_date`, `client_id`, `image_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('4', 'LAPTOP RGD', 'SN,13287690', 'Laptop', '2025-11-26', '9', '2026-08-26', '3', 'uploads/1764131316_2f401a05f63724c926590a06b7243f4e.jpg', 'active', '', '2025-11-26 12:28:36', '2025-11-26 12:28:36');
INSERT INTO `products` (`id`, `product_name`, `serial_number`, `category`, `purchase_date`, `warranty_period`, `warranty_end_date`, `client_id`, `image_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('5', 'KEYBOARD', 'SN,13287690131', 'Accessory', '2025-11-26', '8', '2026-07-26', '1', 'uploads/1764145001_arjay.jpg', 'active', 'BRAND NEW', '2025-11-26 16:16:41', '2025-11-26 16:16:41');

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

-- No data found for table `settings`

SET FOREIGN_KEY_CHECKS=1;
