-- CRM v9: YMOChat. Live website visitors (IP, location, pages, ad source) and
-- two-way website chat that the team answers from the CRM.
-- The app also creates these tables on first use; running this file is optional.
-- Safe to run more than once.
CREATE TABLE IF NOT EXISTS `ymochat_visitors` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vid`           CHAR(32)      NOT NULL,
    `ip`            VARCHAR(45)   NOT NULL DEFAULT '',
    `city`          VARCHAR(80)   NOT NULL DEFAULT '',
    `region`        VARCHAR(80)   NOT NULL DEFAULT '',
    `country`       VARCHAR(4)    NOT NULL DEFAULT '',
    `lat`           DECIMAL(9,6)  NULL,
    `lon`           DECIMAL(9,6)  NULL,
    `device`        VARCHAR(16)   NOT NULL DEFAULT '',
    `browser`       VARCHAR(40)   NOT NULL DEFAULT '',
    `user_agent`    VARCHAR(255)  NOT NULL DEFAULT '',
    `source`        VARCHAR(24)   NOT NULL DEFAULT '',
    `source_detail` VARCHAR(160)  NOT NULL DEFAULT '',
    `referrer`      VARCHAR(255)  NOT NULL DEFAULT '',
    `landing`       VARCHAR(255)  NOT NULL DEFAULT '',
    `attribution`   TEXT          NULL,
    `current_url`   VARCHAR(255)  NOT NULL DEFAULT '',
    `current_title` VARCHAR(200)  NOT NULL DEFAULT '',
    `visits`        INT UNSIGNED  NOT NULL DEFAULT 1,
    `pages`         INT UNSIGNED  NOT NULL DEFAULT 0,
    `name`          VARCHAR(120)  NOT NULL DEFAULT '',
    `mobile`        VARCHAR(15)   NOT NULL DEFAULT '',
    `lead_id`       BIGINT UNSIGNED NULL,
    `unread_agent`  INT UNSIGNED  NOT NULL DEFAULT 0,
    `last_msg_at`   DATETIME      NULL,
    `visit_started_at` DATETIME   NOT NULL,
    `first_seen`    DATETIME      NOT NULL,
    `last_seen`     DATETIME      NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_vid` (`vid`),
    KEY `idx_last_seen` (`last_seen`),
    KEY `idx_last_msg` (`last_msg_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ymochat_pageviews` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_id`  BIGINT UNSIGNED NOT NULL,
    `url`         VARCHAR(255) NOT NULL DEFAULT '',
    `title`       VARCHAR(200) NOT NULL DEFAULT '',
    `created_at`  DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_visitor` (`visitor_id`, `id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ymochat_messages` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `visitor_id`  BIGINT UNSIGNED NOT NULL,
    `sender`      VARCHAR(8)   NOT NULL,
    `admin_id`    INT UNSIGNED NULL,
    `body`        TEXT         NOT NULL,
    `created_at`  DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_visitor` (`visitor_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Phones and computers that get YMOChat push alerts (one row per browser).
CREATE TABLE IF NOT EXISTS `ymochat_push_subs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id`      INT UNSIGNED  NOT NULL,
    `endpoint_hash` CHAR(40)      NOT NULL,
    `endpoint`      TEXT          NOT NULL,
    `p256dh`        VARCHAR(120)  NOT NULL,
    `auth`          VARCHAR(48)   NOT NULL,
    `mode`          VARCHAR(8)    NOT NULL DEFAULT 'all',
    `quiet_nights`  TINYINT(1)    NOT NULL DEFAULT 0,
    `device`        VARCHAR(80)   NOT NULL DEFAULT '',
    `created_at`    DATETIME      NOT NULL,
    `last_ok_at`    DATETIME      NULL,
    `fail_count`    INT UNSIGNED  NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_endpoint` (`endpoint_hash`),
    KEY `idx_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
