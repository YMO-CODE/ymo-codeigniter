-- =============================================================================
-- Customer referral programme
--   mysql -u ... ymo_booking < database/referral_migration_v1.sql
-- Idempotent where supported.
-- =============================================================================

SET NAMES utf8mb4;

-- Unique share code per customer (generated on first visit to account).
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'referral_code'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE `users` ADD COLUMN `referral_code` VARCHAR(12) NULL AFTER `city`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uniq_users_referral_code'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_referral_code` (`referral_code`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `referrals` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `referrer_user_id`      BIGINT UNSIGNED NOT NULL,
    `referred_user_id`      BIGINT UNSIGNED NOT NULL,
    `referral_code`         VARCHAR(12) NOT NULL,
    `referred_phone`        VARCHAR(15) NOT NULL,
    `booking_id`            BIGINT UNSIGNED NOT NULL,
    `status`                ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    `referrer_credit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `referred_credit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `completed_at`          DATETIME NULL,
    `referrer_notified_at`  DATETIME NULL,
    `referred_notified_at`  DATETIME NULL,
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_referrals_booking` (`booking_id`),
    KEY `idx_referrals_referrer` (`referrer_user_id`, `status`),
    KEY `idx_referrals_referred` (`referred_user_id`, `status`),
    KEY `idx_referrals_code` (`referral_code`),
    CONSTRAINT `fk_referrals_referrer` FOREIGN KEY (`referrer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referrals_referred` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referrals_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
