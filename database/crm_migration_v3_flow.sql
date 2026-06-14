-- CRM v3: YMO final flow — new lead stages, follow-up dates, offline marketing source.
-- Run on existing installs after crm_migration_v1.sql / v2.
-- mysql -u ... ymo < database/crm_migration_v3_flow.sql

INSERT IGNORE INTO `crm_lead_sources` (`id`, `slug`, `label`) VALUES
    (9, 'offline_marketing', 'Offline Marketing');

-- Add new columns before stage migration
ALTER TABLE `crm_leads`
    ADD COLUMN `next_follow_up_at` DATETIME NULL AFTER `priority`,
    ADD COLUMN `stage_locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `next_follow_up_at`,
    ADD KEY `idx_crm_leads_followup` (`next_follow_up_at`, `status`);

-- Expand stage ENUM to include both old and new values for safe migration
ALTER TABLE `crm_leads`
    MODIFY COLUMN `stage` ENUM(
        'new','contacted','qualified','proposal','won','lost',
        'hot_lead','warm_lead','followup_next_week','followup_next_month','later','quote_sent'
    ) NOT NULL DEFAULT 'warm_lead';

-- Map old stages to new flow
UPDATE `crm_leads` SET `stage` = 'warm_lead', `stage_locked` = 0
    WHERE `stage` IN ('new', 'contacted') AND `status` = 'open';

UPDATE `crm_leads` SET `stage` = 'hot_lead', `stage_locked` = 0
    WHERE `stage` = 'qualified' AND `status` = 'open';

UPDATE `crm_leads` SET `stage` = 'quote_sent', `stage_locked` = 1
    WHERE `stage` = 'proposal';

UPDATE `crm_leads` SET `stage` = 'quote_sent', `stage_locked` = 1
    WHERE `stage` = 'won' OR `status` = 'converted';

UPDATE `crm_leads` SET `stage` = 'lost', `stage_locked` = 1
    WHERE `stage` = 'lost';

-- Final ENUM: new stages only
ALTER TABLE `crm_leads`
    MODIFY COLUMN `stage` ENUM(
        'hot_lead','warm_lead','followup_next_week','followup_next_month','later','quote_sent','lost'
    ) NOT NULL DEFAULT 'warm_lead';

-- Default open leads without follow-up date to later
UPDATE `crm_leads`
    SET `stage` = 'later'
    WHERE `status` = 'open'
      AND `stage_locked` = 0
      AND `next_follow_up_at` IS NULL
      AND `stage` IN ('warm_lead', 'hot_lead');
