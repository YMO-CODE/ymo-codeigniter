-- CRM v4: service address and car type on leads (manual entry + quick-book mapping).
-- Run on existing installs after crm_migration_v3_flow.sql
-- mysql -u ... ymo < database/crm_migration_v4_lead_address_car.sql

ALTER TABLE `crm_leads`
    ADD COLUMN `address` VARCHAR(255) NULL AFTER `company`,
    ADD COLUMN `car_type` VARCHAR(120) NULL AFTER `address`;
