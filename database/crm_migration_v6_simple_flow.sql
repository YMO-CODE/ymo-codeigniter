-- CRM v6: simpler lead flow.
--   * Stages: new → contacted → quote_sent → booked / lost (+ lost_reason)
--   * Lead ↔ booking link (booking_id) so revenue can be traced to a source
--   * One follow-up system: every open lead with a follow-up date gets a
--     pending task; the lead's next_follow_up_at mirrors that task
--   * One customer record per person: contacts are linked to website
--     accounts by mobile, and every account gets a contact
-- Run on existing installs after crm_migration_v5_whatsapp.sql:
--   mysql -u ... ymo < database/crm_migration_v6_simple_flow.sql
-- Safe to re-run.

-- 1. Allow old + new stage values while we map them.
ALTER TABLE `crm_leads`
    MODIFY `stage` ENUM('hot_lead','warm_lead','followup_next_week','followup_next_month','later',
                        'quote_sent','lost','new','contacted','booked') NOT NULL DEFAULT 'new';

SET @has_lost_reason := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_leads' AND COLUMN_NAME = 'lost_reason');
SET @sql := IF(@has_lost_reason = 0,
    'ALTER TABLE `crm_leads` ADD COLUMN `lost_reason` VARCHAR(40) NULL AFTER `status`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_booking_id := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_leads' AND COLUMN_NAME = 'booking_id');
SET @sql := IF(@has_booking_id = 0,
    'ALTER TABLE `crm_leads` ADD COLUMN `booking_id` BIGINT UNSIGNED NULL AFTER `converted_contact_id`, ADD KEY `idx_crm_leads_booking` (`booking_id`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2. Map old stages/status to the new pipeline.
UPDATE `crm_leads` SET `stage` = 'lost', `lost_reason` = COALESCE(`lost_reason`, 'spam')
    WHERE `status` = 'junk';
UPDATE `crm_leads` SET `stage` = 'booked'
    WHERE `status` = 'converted' AND `stage` NOT IN ('lost','booked');
UPDATE `crm_leads` SET `lost_reason` = COALESCE(`lost_reason`, 'other')
    WHERE `stage` = 'lost';
UPDATE `crm_leads` l SET l.`stage` = 'new'
    WHERE l.`stage` = 'warm_lead'
      AND NOT EXISTS (SELECT 1 FROM `crm_lead_activities` a
                      WHERE a.`lead_id` = l.`id` AND a.`admin_id` IS NOT NULL);
UPDATE `crm_leads` SET `stage` = 'contacted'
    WHERE `stage` IN ('hot_lead','warm_lead','followup_next_week','followup_next_month','later');

-- 3. Keep the legacy status column in sync (campaign segments, reports use it).
UPDATE `crm_leads` SET `status` = CASE
        WHEN `stage` = 'booked' THEN 'converted'
        WHEN `stage` = 'lost'   THEN 'junk'
        ELSE 'open' END,
    `stage_locked` = 0;

ALTER TABLE `crm_leads`
    MODIFY `stage` ENUM('new','contacted','quote_sent','booked','lost') NOT NULL DEFAULT 'new';

-- 4. One follow-up system: a pending task for every open lead with a date.
INSERT INTO `crm_tasks` (`title`, `due_at`, `status`, `priority`, `lead_id`, `assignee_admin_id`, `created_at`)
SELECT CONCAT('Follow up: ', l.`name`), l.`next_follow_up_at`, 'pending', l.`priority`, l.`id`,
       COALESCE(l.`assigned_to`, (SELECT MIN(`id`) FROM `admin_users` WHERE `is_active` = 1)),
       NOW()
FROM `crm_leads` l
WHERE l.`deleted_at` IS NULL
  AND l.`stage` IN ('new','contacted','quote_sent')
  AND l.`next_follow_up_at` IS NOT NULL
  AND (l.`assigned_to` IS NOT NULL OR EXISTS (SELECT 1 FROM `admin_users` WHERE `is_active` = 1))
  AND NOT EXISTS (SELECT 1 FROM `crm_tasks` t WHERE t.`lead_id` = l.`id` AND t.`status` = 'pending');

-- Closed leads should not keep reminding anyone.
UPDATE `crm_tasks` t JOIN `crm_leads` l ON l.`id` = t.`lead_id`
    SET t.`status` = 'skipped'
    WHERE t.`status` = 'pending' AND l.`stage` IN ('booked','lost');
UPDATE `crm_leads` SET `next_follow_up_at` = NULL WHERE `stage` IN ('booked','lost');

-- 5. One customer per person: link contacts to accounts by mobile …
UPDATE `crm_contacts` c
    JOIN `users` u ON u.`mobile` = RIGHT(c.`mobile`, 10)
    SET c.`user_id` = u.`id`
    WHERE c.`user_id` IS NULL AND c.`deleted_at` IS NULL AND CHAR_LENGTH(c.`mobile`) >= 10;

-- … and make sure every website account has a customer record.
INSERT INTO `crm_contacts` (`user_id`, `name`, `mobile`, `email`, `created_at`, `updated_at`)
SELECT u.`id`, u.`name`, u.`mobile`,
       CASE WHEN u.`email` LIKE '%.invalid' THEN '' ELSE u.`email` END,
       u.`created_at`, NOW()
FROM `users` u
WHERE NOT EXISTS (SELECT 1 FROM `crm_contacts` c WHERE c.`user_id` = u.`id` AND c.`deleted_at` IS NULL);
