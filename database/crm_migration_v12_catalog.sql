-- CRM v12: Catalogue (services + spare parts). The app also creates this table on first use.
-- Fill it with: php index.php cli/crm import_catalog
CREATE TABLE IF NOT EXISTS `crm_catalog_items` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`          ENUM('service','part') NOT NULL,
  `name`          VARCHAR(255) NOT NULL,
  `description`   TEXT NULL,
  `code`          VARCHAR(64) NOT NULL DEFAULT '',
  `category`      VARCHAR(80) NOT NULL DEFAULT '',
  `unit`          VARCHAR(16) NOT NULL DEFAULT 'UNT',
  `rate`          DECIMAL(10,2) NULL,
  `purchase_rate` DECIMAL(10,2) NULL,
  `tax_pct`       DECIMAL(5,2) NULL,
  `tax_inclusive` TINYINT(1) NOT NULL DEFAULT 1,
  `stock`         DECIMAL(10,2) NOT NULL DEFAULT 0,
  `low_stock`     DECIMAL(10,2) NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `okm_id`        INT UNSIGNED NULL,
  `okm_stock`     DECIMAL(10,2) NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    DATETIME NOT NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_catalog_okm` (`okm_id`),
  KEY `idx_catalog_type_name` (`type`, `is_active`, `name`(80)),
  KEY `idx_catalog_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
