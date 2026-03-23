-- Anime PC Warranty System Database Backup
-- Generated: 2025-12-12 05:57:13
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

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `created_at`) VALUES ('1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@animepc.com', 'System Administrator', '2025-11-23 13:11:06');

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_name`, `email`, `phone`, `address`, `company`, `created_at`, `updated_at`) VALUES ('4', 'DIWATA UNICE', 'unice@gmail.com', '+639051610559', 'Blk21 Lot18 pagsibol village phase2 brgy catmon sta. maria bulacan', 'N/A', '2025-12-11 16:23:40', '2025-12-11 16:23:40');
INSERT INTO `clients` (`id`, `client_name`, `email`, `phone`, `address`, `company`, `created_at`, `updated_at`) VALUES ('5', 'test', 'test@gmail.com', '+639051610559', 'Blk21 Lot18 pagsibol village phase2 brgy catmon sta. maria bulacan', 'ComputerServices', '2025-12-11 16:27:48', '2025-12-11 16:27:48');

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `serial_number`, `category`, `purchase_date`, `warranty_period`, `warranty_end_date`, `client_id`, `image_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('5', 'KEYBOARD', 'SN,132876903131', 'Component', '2025-12-11', '5', '2026-05-11', '4', 'uploads/1765441491_bg.jpg', 'active', '', '2025-12-11 16:24:51', '2025-12-11 16:24:51');
INSERT INTO `products` (`id`, `product_name`, `serial_number`, `category`, `purchase_date`, `warranty_period`, `warranty_end_date`, `client_id`, `image_path`, `status`, `notes`, `created_at`, `updated_at`) VALUES ('7', 'cellphone', 'SN,13287690654', 'Other', '2025-12-12', '2', '2026-02-12', '5', 'uploads/1765513951_workshopbg.jpg', 'active', '', '2025-12-12 12:32:31', '2025-12-12 12:32:31');

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
