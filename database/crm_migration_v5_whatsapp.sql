-- CRM v5: WhatsApp Cloud API — approved templates, WhatsApp campaigns,
-- delivery/read receipts, opt-out, and an outbound/inbound message log
-- (booking notifications, photo updates, campaign sends).
-- Run on existing installs after crm_migration_v4_lead_address_car.sql
-- mysql -u ... ymo < database/crm_migration_v5_whatsapp.sql
--
-- Safe to deploy before WhatsApp is configured: nothing is sent until
-- CRM_META_ACCESS_TOKEN + CRM_WHATSAPP_PHONE_NUMBER_ID are set, and booking
-- notifications keep using SMS until CRM_WHATSAPP_NOTIFY=1.

CREATE TABLE IF NOT EXISTS `crm_wa_templates` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(512) NOT NULL,
    `language`         VARCHAR(15)  NOT NULL DEFAULT 'en',
    `category`         VARCHAR(30)  NULL,
    `status`           VARCHAR(30)  NOT NULL DEFAULT 'APPROVED',
    `header_type`      VARCHAR(20)  NULL,
    `body_text`        TEXT NULL,
    `param_count`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `components_json`  JSON NULL,
    `meta_template_id` VARCHAR(64)  NULL,
    `synced_at`        DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_wa_tpl_name_lang` (`name`(191), `language`),
    KEY `idx_wa_tpl_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_wa_messages` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wa_message_id`  VARCHAR(128) NULL,
    `phone`          VARCHAR(20)  NOT NULL,
    `direction`      ENUM('in','out') NOT NULL DEFAULT 'out',
    `kind`           VARCHAR(20)  NOT NULL DEFAULT 'text',
    `template_name`  VARCHAR(512) NULL,
    `body`           TEXT NULL,
    `media_path`     VARCHAR(255) NULL,
    `context_type`   VARCHAR(20)  NULL,
    `context_id`     BIGINT UNSIGNED NULL,
    `status`         ENUM('queued','sent','delivered','read','failed','received') NOT NULL DEFAULT 'queued',
    `error`          VARCHAR(500) NULL,
    `admin_id`       INT UNSIGNED NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wa_msg_wamid` (`wa_message_id`),
    KEY `idx_wa_msg_phone` (`phone`, `created_at`),
    KEY `idx_wa_msg_context` (`context_type`, `context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `crm_campaigns`
    MODIFY `channel` ENUM('email','sms','both','whatsapp') NOT NULL DEFAULT 'email',
    ADD COLUMN `wa_template_id` INT UNSIGNED NULL AFTER `segment_json`,
    ADD COLUMN `wa_params_json` JSON NULL AFTER `wa_template_id`;

ALTER TABLE `crm_campaign_recipients`
    MODIFY `channel` ENUM('email','sms','whatsapp') NOT NULL,
    ADD COLUMN `wa_message_id` VARCHAR(128) NULL AFTER `error_message`,
    ADD COLUMN `delivery_status` ENUM('sent','delivered','read','failed') NULL AFTER `wa_message_id`,
    ADD COLUMN `delivered_at` DATETIME NULL AFTER `sent_at`,
    ADD COLUMN `read_at` DATETIME NULL AFTER `delivered_at`,
    ADD KEY `idx_ccr_wamid` (`wa_message_id`);

ALTER TABLE `crm_contacts`
    ADD COLUMN `whatsapp_opt_out` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sms_opt_out`;
