-- =============================================================================
-- YMO Booking service invoices (line items + GST + PDF)
--   mysql -u ... ymo_booking < database/booking_invoice_migration_v1.sql
-- Idempotent: CREATE TABLE IF NOT EXISTS
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `booking_invoices` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_id`          BIGINT UNSIGNED NOT NULL,
    `invoice_number`      VARCHAR(24) NOT NULL,
    `created_by_admin_id` INT UNSIGNED NOT NULL,
    `created_by_name`     VARCHAR(120) NOT NULL,
    `subtotal`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `gst_type`            ENUM('intra','inter') NOT NULL DEFAULT 'intra',
    `gst_rate`            DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `cgst_amount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `sgst_amount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `igst_amount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `grand_total`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes`               TEXT NULL,
    `pdf_path`            VARCHAR(255) NULL,
    `sms_sent_at`         DATETIME NULL,
    `email_sent_at`       DATETIME NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_invoice_number` (`invoice_number`),
    KEY `fk_invoices_booking` (`booking_id`),
    KEY `fk_invoices_admin` (`created_by_admin_id`),
    CONSTRAINT `fk_invoices_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_invoices_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `booking_invoice_lines` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id`  BIGINT UNSIGNED NOT NULL,
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `description` VARCHAR(500) NOT NULL,
    `amount`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `fk_invoice_lines_invoice` (`invoice_id`),
    CONSTRAINT `fk_invoice_lines_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `booking_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
