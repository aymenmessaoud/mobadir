-- ==============================================================================
-- Mobadir (مُبادِر) — Complete Production Database Schema
-- Compatible with MySQL 5.7+, 8.0+, MariaDB 10.3+ (InfinityFree / cPanel / Localhost)
-- ==============================================================================

DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `campaign_images`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `follows`;
DROP TABLE IF EXISTS `volunteering_applications`;
DROP TABLE IF EXISTS `campaigns`;
DROP TABLE IF EXISTS `clubs_profile`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table (Volunteers, Clubs, Admins)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `role` ENUM('volunteer', 'club', 'admin') NOT NULL DEFAULT 'volunteer',
  `wilaya` VARCHAR(80) NOT NULL DEFAULT '08 - بشار',
  `municipality` VARCHAR(80) NOT NULL DEFAULT 'بشار',
  `address_details` VARCHAR(255) NULL,
  `avatar` VARCHAR(255) NULL,
  `bio` TEXT NULL,
  `facebook_url` VARCHAR(255) NULL,
  `instagram_url` VARCHAR(255) NULL,
  `linkedin_url` VARCHAR(255) NULL,
  `is_suspended` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`role`),
  INDEX(`wilaya`),
  INDEX(`is_suspended`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Clubs Profile Table (Youth Institutions, Associations, Clubs)
CREATE TABLE `clubs_profile` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `organization_name` VARCHAR(160) NOT NULL,
  `institution_type` VARCHAR(80) NOT NULL DEFAULT 'دار الشباب',
  `bio` TEXT NULL,
  `logo` VARCHAR(255) NULL,
  `cover_image` VARCHAR(255) NULL,
  `wilaya` VARCHAR(80) NOT NULL DEFAULT '08 - بشار',
  `municipality` VARCHAR(80) NOT NULL DEFAULT 'بشار',
  `address` VARCHAR(255) NULL,
  `address_details` VARCHAR(255) NULL,
  `facebook_url` VARCHAR(255) NULL,
  `instagram_url` VARCHAR(255) NULL,
  `website_url` VARCHAR(255) NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`institution_type`),
  INDEX(`wilaya`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Campaigns Table (Volunteering Opportunities)
CREATE TABLE `campaigns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `club_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'بيئة وتراث',
  `wilaya` VARCHAR(80) NOT NULL DEFAULT '08 - بشار',
  `municipality` VARCHAR(80) NOT NULL DEFAULT 'بشار',
  `location_name` VARCHAR(200) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `daily_start_time` TIME NOT NULL DEFAULT '08:00',
  `daily_end_time` TIME NOT NULL DEFAULT '12:00',
  `hours_count` INT NOT NULL DEFAULT 4,
  `min_volunteers` INT NOT NULL DEFAULT 15,
  `status` ENUM('published', 'completed', 'cancelled') NOT NULL DEFAULT 'published',
  `image` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`category`),
  INDEX(`wilaya`),
  INDEX(`status`),
  INDEX(`start_date`),
  FOREIGN KEY (`club_id`) REFERENCES `clubs_profile`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Campaign Gallery Images Table
CREATE TABLE `campaign_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`campaign_id`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Volunteering Applications Table (RSVP & Hours Accreditation)
CREATE TABLE `volunteering_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT NOT NULL,
  `volunteer_id` INT NOT NULL,
  `status` ENUM('applied', 'confirmed', 'attended', 'cancelled') NOT NULL DEFAULT 'confirmed',
  `hours_awarded` INT NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `attended_at` DATETIME NULL,
  UNIQUE KEY `unique_app` (`campaign_id`, `volunteer_id`),
  INDEX(`status`),
  FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`volunteer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Follows Table (Volunteer follows Club)
CREATE TABLE `follows` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `volunteer_id` INT NOT NULL,
  `club_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_follow` (`volunteer_id`, `club_id`),
  FOREIGN KEY (`volunteer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`club_id`) REFERENCES `clubs_profile`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`user_id`),
  INDEX(`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Password Resets Table
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX(`email`),
  INDEX(`token`),
  INDEX(`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
