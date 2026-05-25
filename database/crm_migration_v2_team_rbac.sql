-- =============================================================================
-- YMO CRM v2 — Team management & booking-side RBAC
--   mysql -u ... ymo_booking < database/crm_migration_v2_team_rbac.sql
-- Run AFTER crm_migration_v1.sql (or on fresh schema.sql that includes v1).
-- Idempotent: INSERT IGNORE
-- =============================================================================

SET NAMES utf8mb4;

INSERT IGNORE INTO `crm_permissions` (`id`, `perm_key`, `label`) VALUES
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

-- Administrator: all new permissions
INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `crm_permissions` WHERE id >= 16;

-- Default Mechanic role (bookings-only staff)
INSERT IGNORE INTO `crm_roles` (`id`, `slug`, `label`, `sort_order`) VALUES
    (5, 'mechanic', 'Mechanic', 50);

INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (5, 16), (5, 17), (5, 18), (5, 19);

-- CRM staff roles: allow dashboard (booking modules remain gated separately)
INSERT IGNORE INTO `crm_role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 16), (3, 16), (4, 16);
