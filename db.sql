-- Full Initial DB Creation Script
-- Run as root user

-- 1. Create Database
CREATE DATABASE IF NOT EXISTS `anonymous_crime_db`
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `anonymous_crime_db`;

-- 2. Drop existing tables
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `media`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `crime_types`;

-- 3. Create Tables in Dependency Order

-- Crime_Types: Categories (admin-managed)
CREATE TABLE `crime_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users: Officers/Admins only
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,  -- bcrypt hash
    `role` ENUM('admin', 'user') NOT NULL,
    `mfa_secret` VARCHAR(32),  -- For TOTP
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports: Core entity
CREATE TABLE `reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `crime_type_id` INT NOT NULL,
    `report_date` DATETIME NOT NULL,
    `location` VARCHAR(255),
    `description` TEXT,
    `status` ENUM('pending', 'in_review', 'closed') DEFAULT 'pending',
    `passphrase_hash` VARCHAR(255) NULL,  -- bcrypt
    `encrypted_data` BLOB,  -- AES-256 for extra sensitive fields
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`crime_type_id`) REFERENCES `crime_types`(`id`) ON DELETE RESTRICT,
    INDEX `idx_crime_type` (`crime_type_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_report_date` (`report_date`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Media: Linked files (metadata stripped in PHP)
CREATE TABLE `media` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` ENUM('image', 'video', 'document') NOT NULL,
    `file_size` INT,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    INDEX `idx_report_id` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages: Secure comms (anonymous from reporter)
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT NOT NULL,
    `user_id` INT NULL,  -- NULL = anonymous reporter
    `message_text` TEXT NOT NULL,
    `is_from_reporter` BOOLEAN NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_report_id` (`report_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_from_reporter` (`is_from_reporter`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Success message
SELECT 'Database and tables created successfully!' AS Status;