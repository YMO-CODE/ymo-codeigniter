-- Site-wide promotional offers (popup on WordPress + booking subdomain)
-- Run once on existing installs: mysql ... ymo_booking < database/site_offers_migration_v1.sql

CREATE TABLE IF NOT EXISTS `site_offers` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(160) NOT NULL,
    `body`       TEXT NOT NULL,
    `cta_label`  VARCHAR(80) NULL,
    `cta_url`    VARCHAR(500) NULL,
    `image_path` VARCHAR(255) NULL,
    `starts_at`  DATETIME NULL,
    `ends_at`    DATETIME NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_site_offers_active_window` (`is_active`, `sort_order`, `starts_at`, `ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `crm_permissions` (`id`, `perm_key`, `label`) VALUES
    (27, 'offers.view', 'View site offers'),
    (28, 'offers.edit', 'Edit site offers');

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `crm_permissions` WHERE `perm_key` IN ('offers.view', 'offers.edit');
