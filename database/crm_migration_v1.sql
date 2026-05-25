-- =============================================================================
-- YMO CRM v1 — run on existing databases AFTER base schema is applied
--   mysql -u ... ymo_booking < database/crm_migration_v1.sql
-- Idempotent-friendly: uses IF NOT EXISTS / checks where possible
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- RBAC -----------------------------------------------------------------------
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
    KEY `fk_crp_perm` (`permission_id`),
    CONSTRAINT `fk_crp_role` FOREIGN KEY (`role_id`) REFERENCES `crm_roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crp_perm` FOREIGN KEY (`permission_id`) REFERENCES `crm_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link admin users to CRM role (skip these three statements if column already exists)
ALTER TABLE `admin_users`
    ADD COLUMN `crm_role_id` INT UNSIGNED NULL AFTER `role`;

ALTER TABLE `admin_users`
    ADD KEY `fk_admin_crm_role` (`crm_role_id`);

ALTER TABLE `admin_users`
    ADD CONSTRAINT `fk_admin_crm_role_ref`
    FOREIGN KEY (`crm_role_id`) REFERENCES `crm_roles` (`id`) ON DELETE SET NULL;

-- Lead sources ---------------------------------------------------------------
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
    (8, 'referral', 'Referral');

-- Leads ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `crm_leads` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_id`           SMALLINT UNSIGNED NOT NULL,
    `name`                VARCHAR(120) NOT NULL,
    `mobile`              VARCHAR(20)  NOT NULL DEFAULT '',
    `email`               VARCHAR(180) NOT NULL DEFAULT '',
    `company`             VARCHAR(120) NULL,
    `message`             TEXT NULL,
    `stage`               ENUM('new','contacted','qualified','proposal','won','lost') NOT NULL DEFAULT 'new',
    `status`              ENUM('open','converted','junk') NOT NULL DEFAULT 'open',
    `priority`            TINYINT NOT NULL DEFAULT 0,
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
    KEY `idx_crm_leads_ext` (`external_provider`,`external_lead_id`),
    CONSTRAINT `fk_crm_leads_source` FOREIGN KEY (`source_id`) REFERENCES `crm_lead_sources` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_crm_leads_assign` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
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
    CONSTRAINT `fk_crm_act_lead` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crm_act_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags -----------------------------------------------------------------------
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
    KEY `fk_clt_tag` (`tag_id`),
    CONSTRAINT `fk_clt_lead` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_clt_tag` FOREIGN KEY (`tag_id`) REFERENCES `crm_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contacts -------------------------------------------------------------------
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
    CONSTRAINT `fk_crm_contacts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_crm_contacts_lead` FOREIGN KEY (`converted_from_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contact_tags` (
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`     INT UNSIGNED NOT NULL,
    PRIMARY KEY (`contact_id`, `tag_id`),
    KEY `fk_cct_tag` (`tag_id`),
    CONSTRAINT `fk_cct_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cct_tag` FOREIGN KEY (`tag_id`) REFERENCES `crm_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks / follow-ups ---------------------------------------------------------
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
    CONSTRAINT `fk_crm_tasks_lead` FOREIGN KEY (`lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_crm_tasks_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_crm_tasks_assignee` FOREIGN KEY (`assignee_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_crm_tasks_creator` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaigns ------------------------------------------------------------------
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
    CONSTRAINT `fk_crm_camp_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
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
    CONSTRAINT `fk_ccr_camp` FOREIGN KEY (`campaign_id`) REFERENCES `crm_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recruitment ----------------------------------------------------------------
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
    CONSTRAINT `fk_crm_cand_assign` FOREIGN KEY (`assigned_to`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_candidate_documents` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `file_path`    VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(200) NOT NULL,
    `mime_type`    VARCHAR(120) NOT NULL,
    `uploaded_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_ccd_cand` (`candidate_id`),
    CONSTRAINT `fk_ccd_cand` FOREIGN KEY (`candidate_id`) REFERENCES `crm_candidates` (`id`) ON DELETE CASCADE
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
    KEY `fk_cint_cand` (`candidate_id`),
    CONSTRAINT `fk_cint_cand` FOREIGN KEY (`candidate_id`) REFERENCES `crm_candidates` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cint_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integration / webhook logs -------------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;

-- Seed roles & permissions (INSERT IGNORE) -----------------------------------
INSERT IGNORE INTO `crm_roles` (`id`, `slug`, `label`, `sort_order`) VALUES
    (1, 'admin', 'Administrator', 10),
    (2, 'sales_executive', 'Sales executive', 20),
    (3, 'marketing', 'Marketing', 30),
    (4, 'hr', 'HR / Recruitment', 40);

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
    (15, 'integrations.manage', 'Manage integrations & webhooks');

-- Admin: all perms
INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `crm_permissions`;

-- Sales: leads, contacts, tasks, reports
INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 1), (2, 2), (2, 3), (2, 5), (2, 6), (2, 7), (2, 8), (2, 14);

-- Marketing: leads view, campaigns, reports
INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 1), (3, 9), (3, 10), (3, 11), (3, 14);

-- HR: recruitment + limited reports
INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (4, 12), (4, 13), (4, 14);

-- Backfill admin users: role admin -> crm_role_id 1, staff -> 2
UPDATE `admin_users` SET `crm_role_id` = 1 WHERE `role` = 'admin' AND (`crm_role_id` IS NULL OR `crm_role_id` = 0);
UPDATE `admin_users` SET `crm_role_id` = 2 WHERE `role` = 'staff' AND (`crm_role_id` IS NULL OR `crm_role_id` = 0);
