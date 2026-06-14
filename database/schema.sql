-- =============================================================================
-- YMO Booking — schema
-- Target: MySQL 5.7+ / MariaDB 10.3+
-- Charset: utf8mb4 (full unicode + emoji safe)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Users (booking customers)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(120) NOT NULL,
    `mobile`              VARCHAR(15)  NOT NULL,
    `email`               VARCHAR(180) NOT NULL,
    `area`                VARCHAR(120) NOT NULL,
    `city`                VARCHAR(80)  NOT NULL,
    `password_hash`       VARCHAR(255) NOT NULL,
    `mobile_verified_at`  DATETIME NULL,
    `email_verified_at`   DATETIME NULL,
    `is_active`           TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at`       DATETIME NULL,
    `failed_login_count`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`        DATETIME NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_users_mobile` (`mobile`),
    UNIQUE KEY `uniq_users_email`  (`email`),
    KEY `idx_users_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- OTP codes (signup, login, password reset)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `channel`       ENUM('sms','email') NOT NULL,
    `destination`   VARCHAR(180) NOT NULL,        -- mobile or email
    `purpose`       ENUM('signup','login','reset','contact_verify') NOT NULL,
    `code_hash`     VARCHAR(255) NOT NULL,
    `expires_at`    DATETIME NOT NULL,
    `attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `used_at`       DATETIME NULL,
    `ip_address`    VARCHAR(45) NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_otp_lookup` (`channel`,`destination`,`purpose`,`used_at`),
    KEY `idx_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Vehicle makes (Maruti, Honda, ...). Seeded.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_makes` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80) NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_makes_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Vehicles (per user). Soft-deleted to preserve booking history.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicles` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `make_id`        INT UNSIGNED NOT NULL,
    `variant`        VARCHAR(120) NOT NULL,
    `image_path`     VARCHAR(255) NULL,
    `vehicle_number` VARCHAR(20)  NOT NULL,
    `deleted_at`     DATETIME NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_vehicles_user` (`user_id`),
    KEY `fk_vehicles_make` (`make_id`),
    KEY `idx_vehicles_number` (`vehicle_number`),
    CONSTRAINT `fk_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vehicles_make` FOREIGN KEY (`make_id`) REFERENCES `vehicle_makes`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Service packages (3-4 fixed packages, admin-editable)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_packages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `slug`       VARCHAR(140) NOT NULL,
    `summary`    VARCHAR(500) NULL,
    `price`      DECIMAL(10,2) NOT NULL DEFAULT 0,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_packages_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_package_features` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `package_id`   INT UNSIGNED NOT NULL,
    `feature_text` VARCHAR(255) NOT NULL,
    `sort_order`   SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    PRIMARY KEY (`id`),
    KEY `fk_features_package` (`package_id`),
    CONSTRAINT `fk_features_package` FOREIGN KEY (`package_id`) REFERENCES `service_packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Bookings
-- `package_snapshot` stores the package state at booking time (JSON of name,
-- price, features) so admin edits to packages don't rewrite history.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`         VARCHAR(20) NOT NULL,        -- public booking code, e.g. YMO-2026-000123
    `user_id`           BIGINT UNSIGNED NOT NULL,
    `vehicle_id`        BIGINT UNSIGNED NOT NULL,
    `package_id`        INT UNSIGNED NOT NULL,
    `package_snapshot`  JSON NULL,
    `remarks`           TEXT NULL,
    `preferred_date`    DATE NULL,
    `status`            ENUM('pending','confirmed','in_progress','completed','cancelled')
                            NOT NULL DEFAULT 'pending',
    `completed_at`      DATETIME NULL,
    `cancelled_reason`  VARCHAR(255) NULL,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bookings_reference` (`reference`),
    KEY `fk_bookings_user` (`user_id`),
    KEY `fk_bookings_vehicle` (`vehicle_id`),
    KEY `fk_bookings_package` (`package_id`),
    KEY `idx_bookings_status_created` (`status`,`created_at`),
    CONSTRAINT `fk_bookings_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)            ON DELETE CASCADE,
    CONSTRAINT `fk_bookings_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)         ON DELETE RESTRICT,
    CONSTRAINT `fk_bookings_package` FOREIGN KEY (`package_id`) REFERENCES `service_packages`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Booking service invoices (work done + GST + PDF)
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- Booking reminders (next-service cron + manual review prompts)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_reminders` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_id`    BIGINT UNSIGNED NOT NULL,
    `type`          ENUM('next_service','review') NOT NULL,
    `channel`       ENUM('sms','email','both') NOT NULL DEFAULT 'both',
    `scheduled_at`  DATETIME NOT NULL,
    `status`        ENUM('pending','sent','skipped','failed') NOT NULL DEFAULT 'pending',
    `attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `sent_at`       DATETIME NULL,
    `last_error`    VARCHAR(500) NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_reminders_booking` (`booking_id`),
    KEY `idx_reminders_run`    (`status`,`type`,`scheduled_at`),
    CONSTRAINT `fk_reminders_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- CRM RBAC (must exist before admin_users.crm_role_id FK)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_roles` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(64)  NOT NULL,
    `label`       VARCHAR(120) NOT NULL,
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_crm_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_permissions` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `perm_key`  VARCHAR(80) NOT NULL,
    `label`     VARCHAR(180) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_crm_perm_key` (`perm_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_role_permissions` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_crp_role_s` FOREIGN KEY (`role_id`) REFERENCES `crm_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crp_perm_s` FOREIGN KEY (`permission_id`) REFERENCES `crm_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `crm_roles` (`id`, `slug`, `label`, `sort_order`) VALUES
    (1, 'admin', 'Administrator', 10),
    (2, 'sales_executive', 'Sales executive', 20),
    (3, 'marketing', 'Marketing', 30),
    (4, 'hr', 'HR / Recruitment', 40),
    (5, 'mechanic', 'Mechanic', 50);

INSERT IGNORE INTO `crm_permissions` (`id`, `perm_key`, `label`) VALUES
    (1, 'leads.view', 'View leads'),
    (2, 'leads.edit', 'Create & edit leads'),
    (3, 'leads.assign', 'Assign leads'),
    (4, 'leads.delete', 'Delete / archive leads'),
    (5, 'contacts.view', 'View contacts'),
    (6, 'contacts.edit', 'Edit contacts'),
    (7, 'tasks.view', 'View tasks'),
    (8, 'tasks.edit', 'Manage tasks'),
    (9, 'campaigns.view', 'View campaigns'),
    (10, 'campaigns.edit', 'Create & edit campaigns'),
    (11, 'campaigns.send', 'Send campaigns'),
    (12, 'recruitment.view', 'View recruitment'),
    (13, 'recruitment.edit', 'Manage recruitment'),
    (14, 'reports.view', 'View CRM reports'),
    (15, 'integrations.manage', 'Manage integrations & webhooks'),
    (16, 'dashboard.view', 'View dashboard'),
    (17, 'bookings.view', 'View bookings'),
    (18, 'bookings.edit', 'Update booking status / actions'),
    (19, 'customers.view', 'View customers'),
    (20, 'packages.view', 'View service packages'),
    (21, 'packages.edit', 'Edit service packages'),
    (22, 'settings.manage', 'Manage app settings'),
    (23, 'team.view', 'View team members'),
    (24, 'team.manage', 'Create & edit team members'),
    (25, 'roles.view', 'View roles'),
    (26, 'roles.manage', 'Create & edit roles & permissions');

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `crm_permissions`;

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 1), (2, 2), (2, 3), (2, 5), (2, 6), (2, 7), (2, 8), (2, 14);

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 1), (3, 9), (3, 10), (3, 11), (3, 14);

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (4, 12), (4, 13), (4, 14);

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (5, 16), (5, 17), (5, 18), (5, 19);

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 16), (3, 16), (4, 16);

-- ---------------------------------------------------------------------------
-- Admin users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120) NOT NULL,
    `email`         VARCHAR(180) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('admin','staff') NOT NULL DEFAULT 'admin',
    `crm_role_id`   INT UNSIGNED NULL,
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `failed_login_count`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`  DATETIME NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_admin_email` (`email`),
    KEY `fk_admin_crm_role` (`crm_role_id`),
    CONSTRAINT `fk_admin_crm_role_schema` FOREIGN KEY (`crm_role_id`) REFERENCES `crm_roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- CRM (embedded): operational tables
-- Full migration for existing installs: database/crm_migration_v1.sql
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `crm_lead_sources` (
    `id`     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`   VARCHAR(40) NOT NULL,
    `label`  VARCHAR(80) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_crm_lead_sources_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `crm_lead_sources` (`id`, `slug`, `label`) VALUES
    (1, 'meta', 'Meta Ads (Facebook)'),
    (2, 'instagram', 'Instagram Ads'),
    (3, 'website', 'Website enquiry'),
    (4, 'landing', 'Landing page'),
    (5, 'whatsapp', 'WhatsApp'),
    (6, 'manual', 'Manual entry'),
    (7, 'cold_call', 'Cold calling'),
    (8, 'referral', 'Referral'),
    (9, 'offline_marketing', 'Offline Marketing');

CREATE TABLE IF NOT EXISTS `crm_leads` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_id`           SMALLINT UNSIGNED NOT NULL,
    `name`                VARCHAR(120) NOT NULL,
    `mobile`              VARCHAR(20)  NOT NULL DEFAULT '',
    `email`               VARCHAR(180) NOT NULL DEFAULT '',
    `company`             VARCHAR(120) NULL,
    `message`             TEXT NULL,
    `stage`               ENUM('hot_lead','warm_lead','followup_next_week','followup_next_month','later','quote_sent','lost') NOT NULL DEFAULT 'warm_lead',
    `status`              ENUM('open','converted','junk') NOT NULL DEFAULT 'open',
    `priority`            TINYINT NOT NULL DEFAULT 0,
    `next_follow_up_at`   DATETIME NULL,
    `stage_locked`        TINYINT(1) NOT NULL DEFAULT 0,
    `assigned_to`         INT UNSIGNED NULL,
    `converted_user_id`   BIGINT UNSIGNED NULL,
    `converted_contact_id` BIGINT UNSIGNED NULL,
    `external_lead_id`    VARCHAR(160) NULL,
    `external_provider`   VARCHAR(40) NULL,
    `payload_json`        JSON NULL,
    `deleted_at`          DATETIME NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_crm_leads_source` (`source_id`),
    KEY `idx_crm_leads_stage` (`stage`,`status`),
    KEY `idx_crm_leads_assigned` (`assigned_to`),
    KEY `idx_crm_leads_created` (`created_at`),
    KEY `idx_crm_leads_followup` (`next_follow_up_at`, `status`),
    KEY `idx_crm_leads_ext` (`external_provider`,`external_lead_id`),
    CONSTRAINT `fk_crm_leads_source_s` FOREIGN KEY (`source_id`) REFERENCES `crm_lead_sources` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_crm_leads_assign_s` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_lead_activities` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lead_id`     BIGINT UNSIGNED NOT NULL,
    `admin_id`    INT UNSIGNED NULL,
    `type`        ENUM('note','call','email','sms','whatsapp','status_change','system','webhook') NOT NULL DEFAULT 'note',
    `body`        TEXT NOT NULL,
    `meta_json`   JSON NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_crm_act_lead` (`lead_id`,`created_at`),
    CONSTRAINT `fk_crm_act_lead_s` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crm_act_admin_s` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_tags` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80) NOT NULL,
    `slug`       VARCHAR(80) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_crm_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_lead_tags` (
    `lead_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`lead_id`, `tag_id`),
    CONSTRAINT `fk_clt_lead_s` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_clt_tag_s` FOREIGN KEY (`tag_id`) REFERENCES `crm_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contacts` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NULL,
    `name`           VARCHAR(120) NOT NULL,
    `mobile`         VARCHAR(20)  NOT NULL DEFAULT '',
    `email`          VARCHAR(180) NOT NULL DEFAULT '',
    `company`        VARCHAR(120) NULL,
    `notes`          TEXT NULL,
    `email_opt_out`  TINYINT(1) NOT NULL DEFAULT 0,
    `sms_opt_out`    TINYINT(1) NOT NULL DEFAULT 0,
    `converted_from_lead_id` BIGINT UNSIGNED NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`     DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_crm_contacts_user` (`user_id`),
    KEY `idx_crm_contacts_email` (`email`(64)),
    KEY `idx_crm_contacts_mobile` (`mobile`),
    CONSTRAINT `fk_crm_contacts_user_s` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_crm_contacts_lead_s` FOREIGN KEY (`converted_from_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_tags` (
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`     INT UNSIGNED NOT NULL,
    PRIMARY KEY (`contact_id`, `tag_id`),
    CONSTRAINT `fk_cct_contact_s` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cct_tag_s` FOREIGN KEY (`tag_id`) REFERENCES `crm_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_tasks` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`              VARCHAR(200) NOT NULL,
    `due_at`             DATETIME NOT NULL,
    `status`             ENUM('pending','done','skipped') NOT NULL DEFAULT 'pending',
    `priority`           TINYINT NOT NULL DEFAULT 0,
    `lead_id`            BIGINT UNSIGNED NULL,
    `contact_id`         BIGINT UNSIGNED NULL,
    `assignee_admin_id`  INT UNSIGNED NOT NULL,
    `created_by_admin_id` INT UNSIGNED NULL,
    `completed_at`       DATETIME NULL,
    `notes`              TEXT NULL,
    `reminder_sent_at`   DATETIME NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_crm_tasks_due` (`assignee_admin_id`,`status`,`due_at`),
    KEY `idx_crm_tasks_lead` (`lead_id`),
    CONSTRAINT `fk_crm_tasks_lead_s` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_crm_tasks_contact_s` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_crm_tasks_assignee_s` FOREIGN KEY (`assignee_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crm_tasks_creator_s` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_campaigns` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(160) NOT NULL,
    `channel`        ENUM('email','sms','both') NOT NULL DEFAULT 'email',
    `subject`        VARCHAR(200) NULL,
    `body`           TEXT NOT NULL,
    `segment_json`   JSON NULL,
    `status`         ENUM('draft','scheduled','sending','completed','cancelled','failed') NOT NULL DEFAULT 'draft',
    `scheduled_at`   DATETIME NULL,
    `created_by`     INT UNSIGNED NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_crm_camp_status` (`status`,`scheduled_at`),
    CONSTRAINT `fk_crm_camp_admin_s` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_campaign_recipients` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`  BIGINT UNSIGNED NOT NULL,
    `channel`      ENUM('email','sms') NOT NULL,
    `email`        VARCHAR(180) NOT NULL DEFAULT '',
    `mobile`       VARCHAR(20)  NOT NULL DEFAULT '',
    `name`         VARCHAR(120) NOT NULL DEFAULT '',
    `status`       ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    `error_message` VARCHAR(500) NULL,
    `sent_at`      DATETIME NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ccr_camp` (`campaign_id`,`status`),
    CONSTRAINT `fk_ccr_camp_s` FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_candidates` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(120) NOT NULL,
    `email`        VARCHAR(180) NOT NULL DEFAULT '',
    `mobile`       VARCHAR(20)  NOT NULL DEFAULT '',
    `position`     VARCHAR(120) NULL,
    `stage`        ENUM('applied','screening','interview','offer','hired','rejected') NOT NULL DEFAULT 'applied',
    `notes`        TEXT NULL,
    `assigned_to`  INT UNSIGNED NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_crm_cand_stage` (`stage`),
    CONSTRAINT `fk_crm_cand_assign_s` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_candidate_documents` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `file_path`    VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(200) NOT NULL,
    `mime_type`    VARCHAR(120) NOT NULL,
    `uploaded_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_ccd_cand_s` (`candidate_id`),
    CONSTRAINT `fk_ccd_cand_s` FOREIGN KEY (`candidate_id`) REFERENCES `crm_candidates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_interviews` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `location`     VARCHAR(200) NULL,
    `notes`        TEXT NULL,
    `status`       ENUM('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
    `created_by`   INT UNSIGNED NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_cint_cand_s` (`candidate_id`),
    CONSTRAINT `fk_cint_cand_s` FOREIGN KEY (`candidate_id`) REFERENCES `crm_candidates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cint_admin_s` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_integration_logs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider`     VARCHAR(40) NOT NULL,
    `direction`    ENUM('inbound','outbound') NOT NULL DEFAULT 'inbound',
    `event_type`   VARCHAR(80) NOT NULL,
    `http_status`  SMALLINT NULL,
    `payload_json` JSON NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cil_provider` (`provider`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Generic key-value settings (gateway keys override, reminder months, etc.)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key`   VARCHAR(80) NOT NULL,
    `setting_value` TEXT NULL,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Audit log
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_type` ENUM('user','admin','system') NOT NULL,
    `actor_id`   BIGINT UNSIGNED NULL,
    `action`     VARCHAR(80) NOT NULL,
    `entity`     VARCHAR(60) NULL,
    `entity_id`  BIGINT UNSIGNED NULL,
    `meta_json`  JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_actor`  (`actor_type`,`actor_id`),
    KEY `idx_audit_entity` (`entity`,`entity_id`),
    KEY `idx_audit_action_time` (`action`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- CI database-driven sessions (used because sess_driver=database)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ci_sessions` (
    `id`         VARCHAR(128) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `timestamp`  INT UNSIGNED NOT NULL DEFAULT 0,
    `data`       BLOB NOT NULL,
    PRIMARY KEY (`id`,`ip_address`),
    KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
