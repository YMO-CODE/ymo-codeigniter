-- CRM v10: Google Ads click → lead attribution + offline conversion feed.
-- The app also creates these on first use (Crm_ad_click_model::ensure_schema);
-- running this file at deploy just does it up front. Safe to run more than once
-- with mysql --force (errors 1060/1061 "duplicate column/key" on re-runs are harmless).

CREATE TABLE IF NOT EXISTS `crm_ad_wa_clicks` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ref`         CHAR(8) NOT NULL,
    `click_id`    VARCHAR(255) NOT NULL,
    `click_type`  VARCHAR(10) NOT NULL DEFAULT 'gclid',
    `attribution` TEXT NULL,
    `page`        VARCHAR(200) NOT NULL DEFAULT '',
    `ip`          VARCHAR(45) NOT NULL DEFAULT '',
    `lead_id`     BIGINT UNSIGNED NULL,
    `matched_by`  VARCHAR(10) NULL,
    `claimed_at`  DATETIME NULL,
    `created_at`  DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_ad_wa_ref` (`ref`),
    KEY `idx_ad_wa_unclaimed` (`lead_id`, `created_at`),
    KEY `idx_ad_wa_ip` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `crm_leads`
    ADD COLUMN `ad_click_id`   VARCHAR(255) NULL,
    ADD COLUMN `ad_click_type` VARCHAR(10) NULL,
    ADD COLUMN `ad_match`      VARCHAR(10) NULL,
    ADD COLUMN `qualified_at`  DATETIME NULL,
    ADD COLUMN `booked_at`     DATETIME NULL,
    ADD KEY `idx_crm_leads_ad_click` (`ad_click_type`, `created_at`);

-- Backfill: website leads already captured with a Google click id.
UPDATE `crm_leads`
   SET `ad_click_id`   = JSON_UNQUOTE(JSON_EXTRACT(`payload_json`, '$.utm.gclid')),
       `ad_click_type` = 'gclid',
       `ad_match`      = 'form',
       `updated_at`    = `updated_at`
 WHERE `ad_click_id` IS NULL
   AND JSON_EXTRACT(`payload_json`, '$.utm.gclid') IS NOT NULL;

-- Backfill: leads already booked.
UPDATE `crm_leads` SET `booked_at` = `updated_at`, `qualified_at` = COALESCE(`qualified_at`, `updated_at`), `updated_at` = `updated_at`
 WHERE `stage` = 'booked' AND `booked_at` IS NULL;
