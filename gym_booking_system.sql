-- gym_booking_system SQL dump (tables + demo schedules & products)
-- Import this in phpMyAdmin or run: mysql -u root < gym_booking_system.sql

CREATE DATABASE IF NOT EXISTS `gym_booking_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gym_booking_system`;

-- users table (no seeded users here; run api/migrate.php or api/fix_schema.php to seed admin/member)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('member','admin') NOT NULL DEFAULT 'member',
  `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `avatar_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `schedules` (
  `schedule_id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `instructor` VARCHAR(100) NOT NULL,
  `day_of_week` VARCHAR(20) NOT NULL,
  `start_time` TIME NOT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 60,
  `capacity` INT NOT NULL DEFAULT 20,
  `level` VARCHAR(30) NOT NULL DEFAULT 'All Levels',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `schedule_id` INT NOT NULL,
  `booking_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('confirmed','cancelled','pending') NOT NULL DEFAULT 'confirmed',
  UNIQUE KEY `unique_booking` (`user_id`, `schedule_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`schedule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `product_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `category` ENUM('supplements','food','gear','apparel','accessories') NOT NULL DEFAULT 'supplements',
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('available','sold_out','discontinued') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','confirmed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `item_id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed schedules
INSERT INTO `schedules` (`class_name`, `category`, `instructor`, `day_of_week`, `start_time`, `duration_minutes`, `capacity`, `level`) VALUES
('Power HIIT', 'Cardio', 'Sarah Johnson', 'Monday', '07:00:00', 45, 25, 'Intermediate'),
('Vinyasa Flow', 'Yoga', 'Maya Patel', 'Monday', '09:00:00', 60, 20, 'All Levels'),
('Beast Mode Lifting', 'Strength', 'Marcus Chen', 'Tuesday', '06:30:00', 50, 15, 'Advanced'),
('Core & Stretch', 'Flexibility', 'Emma Wilson', 'Tuesday', '10:00:00', 40, 30, 'Beginner'),
('Kickboxing Fusion', 'Combat', 'Jake Rivera', 'Wednesday', '18:00:00', 55, 20, 'Intermediate'),
('Zen Meditation', 'Yoga', 'Maya Patel', 'Thursday', '08:00:00', 45, 25, 'Beginner'),
('CrossFit WOD', 'Strength', 'Marcus Chen', 'Thursday', '17:00:00', 60, 18, 'Advanced'),
('Spin Cycle', 'Cardio', 'Sarah Johnson', 'Friday', '07:00:00', 40, 22, 'All Levels'),
('Muay Thai Basics', 'Combat', 'Jake Rivera', 'Friday', '19:00:00', 60, 16, 'Beginner');

-- Seed products
INSERT INTO `products` (`name`, `description`, `category`, `price`, `stock`, `image_url`) VALUES
('Whey Protein Isolate', 'Premium 100% whey protein, 30g per scoop. Chocolate flavour.', 'supplements', 49.99, 50, NULL),
('BCAA Energy Drink', 'Branch chain amino acids with natural caffeine boost.', 'supplements', 29.99, 80, NULL),
('Creatine Monohydrate', 'Pure micronized creatine for strength and power.', 'supplements', 24.99, 60, NULL),
('Protein Bar Box (12pk)', 'High protein, low sugar snack bars. Mixed flavours.', 'food', 34.99, 40, NULL),
('Organic Energy Balls', 'Date, almond & cocoa energy bites. 10 pack.', 'food', 18.99, 100, NULL),
('Meal Prep Containers', 'BPA-free 3-compartment containers. Set of 10.', 'gear', 22.99, 35, NULL),
('Resistance Bands Set', '5-band set with varying resistance levels.', 'gear', 19.99, 45, NULL),
('Lifting Gloves Pro', 'Padded leather gloves with wrist wraps.', 'gear', 32.99, 25, NULL),
('GymPro Tank Top', 'Moisture-wicking performance tank. Multiple sizes.', 'apparel', 27.99, 60, NULL),
('Compression Leggings', 'High-waist athletic leggings with pocket.', 'apparel', 39.99, 40, NULL),
('Shaker Bottle Pro', '800ml leak-proof shaker with mixing ball.', 'accessories', 14.99, 90, NULL),
('Gym Bag Duffle', 'Large capacity waterproof gym duffle bag.', 'accessories', 44.99, 30, NULL);

-- Note: users (admin/demo) are not inserted here because password hashes are generated by PHP's password_hash().
-- After importing this SQL, run the migration script to seed users:
-- http://localhost/gymprojects/api/migrate.php
-- or run: php api/migrate.php
