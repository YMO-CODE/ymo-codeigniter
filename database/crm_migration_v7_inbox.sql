-- CRM v7: WhatsApp Inbox read tracking. Safe to run more than once.
--
--   $COMPOSE exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < database/crm_migration_v7_inbox.sql

SET @have := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_wa_messages' AND COLUMN_NAME = 'read_at');
SET @sql := IF(@have = 0,
    'ALTER TABLE `crm_wa_messages` ADD COLUMN `read_at` DATETIME NULL AFTER `admin_id`, ADD COLUMN `read_by` INT UNSIGNED NULL AFTER `read_at`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @have := (SELECT COUNT(*) FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_wa_messages' AND INDEX_NAME = 'idx_wa_msg_unread');
SET @sql := IF(@have = 0,
    'ALTER TABLE `crm_wa_messages` ADD KEY `idx_wa_msg_unread` (`direction`, `read_at`)',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Start with a clean inbox: anything older than 24 hours counts as already read.
UPDATE `crm_wa_messages`
   SET `read_at` = `created_at`
 WHERE `direction` = 'in' AND `read_at` IS NULL AND `created_at` < NOW() - INTERVAL 24 HOUR;
