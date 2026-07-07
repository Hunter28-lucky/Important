-- SQLite Database Schema for TemplateLink Builder
PRAGMA foreign_keys = ON;

-- 1. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL UNIQUE,
  `password_hash` TEXT NOT NULL,
  `email` TEXT NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL UNIQUE,
  `slug` TEXT NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Templates Table
CREATE TABLE IF NOT EXISTS `templates` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `thumbnail_url` TEXT,
  `category_id` INTEGER,
  `tags` TEXT,
  `slug` TEXT NOT NULL UNIQUE,
  `content` TEXT,
  `status` TEXT DEFAULT 'draft',
  `meta_title` TEXT,
  `meta_description` TEXT,
  `og_image` TEXT,
  `og_title` TEXT,
  `og_description` TEXT,
  `schema_markup` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
);

-- 4. Media Table
CREATE TABLE IF NOT EXISTS `media` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `file_name` TEXT NOT NULL,
  `file_path` TEXT NOT NULL,
  `file_type` TEXT NOT NULL,
  `file_size` INTEGER NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `setting_key` TEXT NOT NULL UNIQUE,
  `setting_value` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. Analytics Views Table
CREATE TABLE IF NOT EXISTS `analytics_views` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `template_id` INTEGER NOT NULL,
  `visitor_ip` TEXT NOT NULL,
  `visitor_ua` TEXT,
  `referrer` TEXT,
  `session_id` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
);

-- 7. Analytics Clicks Table
CREATE TABLE IF NOT EXISTS `analytics_clicks` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `template_id` INTEGER NOT NULL,
  `link_url` TEXT NOT NULL,
  `visitor_ip` TEXT,
  `session_id` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
);

-- 8. Visitor Photos Table
CREATE TABLE IF NOT EXISTS `visitor_photos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `template_id` INTEGER NOT NULL,
  `photo_path` TEXT NOT NULL,
  `visitor_ip` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
);
