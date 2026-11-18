-- Reunion 2027 Database Schema
-- বেরইল শামসুদ্দিন দাখিল মাদ্রাসা - সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী ২০২৭

CREATE DATABASE IF NOT EXISTS `reunion` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reunion`;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET character_set_connection=utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `receipts`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `payment_approvals`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `user_permissions`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `event_settings`;
DROP TABLE IF EXISTS `tenants`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tenants Table
CREATE TABLE `tenants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Roles Table
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Permissions Table
CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL UNIQUE,
    `display_name` VARCHAR(120) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Role Permissions Mapping Table
CREATE TABLE `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Users Table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `role_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(120) NOT NULL,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `email` VARCHAR(150) NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. User Specific Permissions Table (Individual Override / Granular for Moderators)
CREATE TABLE `user_permissions` (
    `user_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `granted_by` INT UNSIGNED NULL,
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `permission_id`),
    CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Registrations Table
CREATE TABLE `registrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `registration_code` VARCHAR(32) NOT NULL UNIQUE,
    `student_category` ENUM('former', 'current') NOT NULL,
    `student_name` VARCHAR(150) NOT NULL,
    `father_name` VARCHAR(150) NOT NULL,
    `mother_name` VARCHAR(150) NOT NULL,
    `mobile_number` VARCHAR(20) NOT NULL,
    `current_address` TEXT NOT NULL,
    `occupation` VARCHAR(100) NULL,
    `prev_class` VARCHAR(100) NULL,
    `passing_year` VARCHAR(10) NULL,
    `current_class` VARCHAR(100) NULL,
    `has_guest` TINYINT(1) NOT NULL DEFAULT 0,
    `guest_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `tshirt_size` ENUM('XS', 'S', 'M', 'L', 'XL', 'XXL') NOT NULL DEFAULT 'L',
    `student_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `guest_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `registration_status` ENUM('pending', 'confirmed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `qr_token` VARCHAR(64) NOT NULL UNIQUE,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_reg_code` (`registration_code`),
    INDEX `idx_reg_mobile` (`mobile_number`),
    INDEX `idx_reg_status` (`registration_status`),
    INDEX `idx_reg_payment_status` (`payment_status`),
    CONSTRAINT `fk_registrations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Payments Table
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `registration_id` INT UNSIGNED NOT NULL,
    `payment_method` ENUM('bkash', 'nagad', 'rocket', 'cash') NOT NULL DEFAULT 'bkash',
    `sender_number` VARCHAR(20) NOT NULL,
    `transaction_id` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `proof_image` VARCHAR(255) NULL,
    `payment_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `notes` TEXT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payment_trx` (`transaction_id`),
    INDEX `idx_payment_status` (`payment_status`),
    CONSTRAINT `fk_payments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payments_reg` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Payment Approvals Table (Audit Trail of Every Decision)
CREATE TABLE `payment_approvals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `payment_id` INT UNSIGNED NOT NULL,
    `registration_id` INT UNSIGNED NOT NULL,
    `action` ENUM('approved', 'rejected') NOT NULL,
    `reviewed_by` INT UNSIGNED NOT NULL,
    `reviewer_name` VARCHAR(120) NOT NULL,
    `remarks` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_approval_reviewer` (`reviewed_by`),
    CONSTRAINT `fk_pa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pa_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pa_reg` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pa_user` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Receipts Table
CREATE TABLE `receipts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `registration_id` INT UNSIGNED NOT NULL UNIQUE,
    `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
    `receipt_file_path` VARCHAR(255) NOT NULL,
    `generated_by` INT UNSIGNED NULL,
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_receipts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_receipts_reg` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_receipts_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Audit Logs Table
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(80) NOT NULL,
    `entity_id` VARCHAR(50) NULL,
    `old_values` LONGTEXT NULL,
    `new_values` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    CONSTRAINT `fk_audit_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Event Settings Table
CREATE TABLE `event_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `setting_key` VARCHAR(80) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_settings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED DATA

-- Default Tenant
INSERT INTO `tenants` (`id`, `name`, `slug`) VALUES
(1, 'বেরইল শামসুদ্দিন দাখিল মাদ্রাসা', 'beroil-shamsuddin-madrasa');

-- Default Roles
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'admin', 'প্রধান অ্যাডমিন (Main Admin)', 'সম্পূর্ণ সিস্টেমের নিয়ন্ত্রণ ও পরিচালনাকারী'),
(2, 'moderator', 'মডারেটর (Moderator)', 'নিবন্ধন ও পেমেন্ট যাচাইকারী দল');

-- Default Permissions
INSERT INTO `permissions` (`id`, `name`, `display_name`, `module`, `description`) VALUES
(1, 'registration.view', 'নিবন্ধন তালিকা দেখা', 'registration', 'সকল নিবন্ধন দেখতে পারবেন'),
(2, 'registration.edit', 'নিবন্ধন তথ্য সম্পাদনা', 'registration', 'শিক্ষার্থীর নিবন্ধন তথ্য পরিবর্তন করতে পারবেন'),
(3, 'registration.delete', 'নিবন্ধন বাতিল করা', 'registration', 'অনাকাঙ্ক্ষিত নিবন্ধন মুছে বা বাতিল করতে পারবেন'),
(4, 'payment.view', 'পেমেন্ট তালিকা দেখা', 'payment', 'সকল পেমেন্ট ও ট্রানজেকশন দেখতে পারবেন'),
(5, 'payment.approve', 'পেমেন্ট অনুমোদন (Approve)', 'payment', 'পেমেন্ট যাচাই করে অনুমোদন দিতে পারবেন'),
(6, 'payment.reject', 'পেমেন্ট বাতিল (Reject)', 'payment', 'ভুল বা অসত্য পেমেন্ট বাতিল করতে পারবেন'),
(7, 'moderator.manage', 'মডারেটর পরিচালনা', 'moderator', 'নতুন মডারেটর তৈরি ও পারমিশন নির্ধারণ'),
(8, 'reports.view', 'আর্থিক ও সার্বিক রিপোর্ট দেখা', 'reports', 'মোট কালেকশন ও আর্থিক পরিসংখ্যান দেখা'),
(9, 'audit_logs.view', 'অডিট লগ দেখা', 'audit', 'সিস্টেমের সকল কার্যক্রমের হিস্ট্রি দেখা'),
(10, 'settings.manage', 'ইভেন্ট ও ফি সেটিংস পরিবর্তন', 'settings', 'নিবন্ধন ফি, বিকাশ/নগদ নম্বর ও ইভেন্ট সেটিংস পরিচালনা');

-- Give admin role all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Give moderator role default base permissions (view & approve/reject)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), -- registration.view
(2, 4), -- payment.view
(2, 5), -- payment.approve
(2, 6); -- payment.reject

-- Default Event Settings
INSERT INTO `event_settings` (`tenant_id`, `setting_key`, `setting_value`, `setting_group`, `description`) VALUES
(1, 'event_title', 'সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭', 'general', 'অনুষ্ঠানের নাম'),
(1, 'madrasa_name', 'বেরইল শামসুদ্দিন দাখিল মাদ্রাসা', 'general', 'মাদ্রাসার নাম'),
(1, 'event_date_text', '১১-১২ মার্চ ২০২৭', 'general', 'অনুষ্ঠানের তারিখ টেক্সট'),
(1, 'event_date_start', '2027-03-11 09:00:00', 'general', 'কাউন্টডাউন টার্গেট সময়'),
(1, 'event_venue', 'মাদ্রাসা প্রাঙ্গণ, বেরইল, মাগুরা', 'general', 'অনুষ্ঠানের স্থান'),
(1, 'former_student_fee', '1000', 'finance', 'সাবেক শিক্ষার্থী নিবন্ধন ফি (টাকা)'),
(1, 'current_student_fee', '500', 'finance', 'বর্তমান শিক্ষার্থী নিবন্ধন ফি (টাকা)'),
(1, 'guest_fee', '500', 'finance', 'অতিথি ফি জনপ্রতি (টাকা)'),
(1, 'bkash_number', '01712-345678', 'payment', 'বিকাশ নম্বর (Personal / Send Money)'),
(1, 'nagad_number', '01912-345678', 'payment', 'নগদ নম্বর (Personal / Send Money)'),
(1, 'contact_phone', '01712-345678', 'contact', 'হেল্পলাইন মোবাইল নম্বর'),
(1, 'contact_email', 'reunion2027@gmail.com', 'contact', 'অফিসিয়াল ইমেইল'),
(1, 'contact_facebook', 'https://facebook.com/reunion2027', 'contact', 'ফেসবুক পেজ / গ্রুপ'),
(1, 'registration_enabled', '1', 'general', 'অনলাইন নিবন্ধন চালু (1) বা বন্ধ (0)');

