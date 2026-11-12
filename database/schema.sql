CREATE DATABASE IF NOT EXISTS `artgorithm_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `artgorithm_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `bio` TEXT NULL,
    `avatar_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Repositories Table
CREATE TABLE IF NOT EXISTS `repositories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `is_private` TINYINT(1) NOT NULL DEFAULT 0,
    `primary_language` VARCHAR(50) DEFAULT 'Plain Text',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_repo` (`user_id`, `slug`)
) ENGINE=InnoDB;

-- 3. Repository Files Table
CREATE TABLE IF NOT EXISTS `repository_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repository_id` INT UNSIGNED NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(100) NOT NULL,
    `content` LONGTEXT NULL,
    `language` VARCHAR(50) DEFAULT 'plaintext',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`repository_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_repo_filepath` (`repository_id`, `file_path`)
) ENGINE=InnoDB;

-- 4. Repository Stars Table
CREATE TABLE IF NOT EXISTS `repository_stars` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `repository_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`repository_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_star` (`user_id`, `repository_id`)
) ENGINE=InnoDB;

-- 5. Comments Table
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repository_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`repository_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Followers Table
CREATE TABLE IF NOT EXISTS `followers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `follower_id` INT UNSIGNED NOT NULL,
    `following_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_follow` (`follower_id`, `following_id`)
) ENGINE=InnoDB;

-- 7. Collaborations Table
CREATE TABLE IF NOT EXISTS `collaborations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repository_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    `role` ENUM('contributor', 'admin') DEFAULT 'contributor',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`repository_id`) REFERENCES `repositories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_collab` (`repository_id`, `user_id`)
) ENGINE=InnoDB;

-- 8. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `sender_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `target_id` INT UNSIGNED NULL,
    `message` VARCHAR(255) NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;