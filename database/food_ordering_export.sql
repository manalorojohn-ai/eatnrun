-- EatNRun Database Export
-- Generated: 2026-04-13 12:57:02

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- Table: admin_notifications
DROP TABLE IF EXISTS `admin_notifications`;
CREATE TABLE `admin_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: admins
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: cart
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Rice Meals', 'Traditional Filipino rice meals', 'active', '2026-03-21 20:26:23', '2026-03-21 20:26:23');
INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Burgers', 'Delicious burger selections', 'active', '2026-03-21 20:26:23', '2026-03-21 20:26:23');
INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Desserts', 'Sweet treats and desserts', 'active', '2026-03-21 20:26:23', '2026-03-21 20:26:23');
INSERT INTO `categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Beverages', 'Refreshing drinks', 'active', '2026-03-21 20:26:23', '2026-03-21 20:26:23');


-- Table: login_history
DROP TABLE IF EXISTS `login_history`;
CREATE TABLE `login_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: menu_items
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `category_id` int DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('available','unavailable') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Coke', 'Refreshing cola drink', '45.00', '2', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Sprite', 'Lemon-lime carbonated drink', '45.00', '2', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Royal', 'Orange-flavored carbonated drink', '45.00', '2', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Mango Juice', 'Fresh Philippine mango juice', '55.00', '2', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Calamansi Juice', 'Fresh Filipino citrus juice', '45.00', '2', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('6', 'Sisig with Rice', 'Sizzling chopped pork with egg and rice', '120.00', '3', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('7', 'Adobo with Rice', 'Classic Filipino pork adobo with rice', '130.00', '3', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('8', 'Fried Chicken with Rice', 'Crispy fried chicken with rice', '125.00', '3', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('9', 'Burger Steak with Rice', 'Beef patty with mushroom gravy and rice', '115.00', '3', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('10', 'Bicol Express with Rice', 'Spicy coconut milk-based pork dish with rice', '135.00', '3', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('11', 'Leche Flan', 'Classic Filipino caramel custard', '60.00', '4', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('12', 'Halo-Halo', 'Mixed Filipino dessert with shaved ice, sweet beans, fruits, and ice cream', '85.00', '4', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('13', 'Plain Burger', 'Classic beef patty with fresh vegetables', '85.00', '1', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');
INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category_id`, `image_path`, `status`, `created_at`, `updated_at`) VALUES ('14', 'Cheese Burger', 'Beef patty with melted cheese and fresh vegetables', '95.00', '1', NULL, 'available', '2026-04-12 19:47:14', '2026-04-12 19:47:14');


-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: order_details
DROP TABLE IF EXISTS `order_details`;
CREATE TABLE `order_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: order_logs
DROP TABLE IF EXISTS `order_logs`;
CREATE TABLE `order_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: orders
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'cod',
  `payment_proof` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `delivery_notes` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT '50.00',
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','out_for_delivery','delivered','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `is_rated` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `received_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: ratings
DROP TABLE IF EXISTS `ratings`;
CREATE TABLE `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`,`menu_item_id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `is_verified` tinyint(1) DEFAULT '0',
  `reset_otp` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_otp_expiry` datetime DEFAULT NULL,
  `dietary_preferences` text COLLATE utf8mb4_unicode_ci,
  `favorite_cuisines` text COLLATE utf8mb4_unicode_ci,
  `ai_personalization` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
