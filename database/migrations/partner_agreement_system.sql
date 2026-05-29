-- ============================================================
-- WealthMetre Partner Agreement System - Migration
-- Version: 1.0 | Date: 2026-05-23
-- Backup before running: mysqldump -u [user] -p wealthmetre > backup_before_agreement_migration.sql
-- ============================================================

-- ── TABLE 1: agreement_versions ─────────────────────────────
CREATE TABLE IF NOT EXISTS agreement_versions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    agreement_type  VARCHAR(100) NOT NULL DEFAULT 'partner_onboarding',
    version         VARCHAR(50)  NOT NULL,
    title           VARCHAR(255) NOT NULL,
    summary         TEXT         NOT NULL,
    full_text       LONGTEXT     NOT NULL,
    checkbox_text   TEXT         NOT NULL,
    text_hash       VARCHAR(128) NOT NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 0,
    effective_from  DATE         NOT NULL,
    created_by      VARCHAR(100) NOT NULL DEFAULT 'admin',
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_version (version),
    INDEX idx_type_active (agreement_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE 2: partner_agreements ──────────────────────────────
CREATE TABLE IF NOT EXISTS partner_agreements (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    partner_id           INT          NOT NULL,
    agreement_type       VARCHAR(100) NOT NULL DEFAULT 'partner_onboarding',
    agreement_version    VARCHAR(50)  NOT NULL,
    agreement_title      VARCHAR(255) NOT NULL,
    agreement_text_hash  VARCHAR(128) NOT NULL,
    accepted_at          DATETIME     NOT NULL,
    ip_address           VARCHAR(100) NULL,
    user_agent           TEXT         NULL,
    otp_verified_mobile  VARCHAR(20)  NULL,
    acceptance_method    VARCHAR(50)  NOT NULL DEFAULT 'online_checkbox',
    checkbox_text        TEXT         NULL,
    scroll_verified      TINYINT(1)   NOT NULL DEFAULT 0,
    status               ENUM('accepted','revoked','superseded') NOT NULL DEFAULT 'accepted',
    superseded_by_version VARCHAR(50) NULL,
    superseded_at        DATETIME     NULL,
    geo_city             VARCHAR(100) NULL,
    geo_state            VARCHAR(100) NULL,
    geo_country          VARCHAR(100) NOT NULL DEFAULT 'IN',
    created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_partner_id       (partner_id),
    INDEX idx_agreement_version (agreement_version),
    INDEX idx_status           (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── TABLE 3: partner_agreement_audit ─────────────────────────
CREATE TABLE IF NOT EXISTS partner_agreement_audit (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    partner_id        INT          NOT NULL,
    action            ENUM(
        'viewed','scrolled_start','scrolled_complete',
        'checkbox_checked','checkbox_unchecked',
        'submitted','accepted','failed','session_expired'
    ) NOT NULL,
    agreement_version VARCHAR(50)  NULL,
    ip_address        VARCHAR(100) NULL,
    user_agent        TEXT         NULL,
    metadata          JSON         NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_partner_action (partner_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ALTER partners table ──────────────────────────────────────
-- Extend status enum
ALTER TABLE partners
    MODIFY COLUMN status ENUM(
        'active','inactive','suspended',
        'draft','otp_verified','profile_submitted',
        'kyc_pending','under_review','approved',
        'rejected'
    ) NULL DEFAULT 'active';

-- Add agreement tracking columns (safe - checks existence via procedure)
SET @dbname = DATABASE();

-- agreement_version_accepted
SET @col = 'agreement_version_accepted';
SET @tbl = 'partners';
SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tbl AND COLUMN_NAME=@col),
    'SELECT "Column agreement_version_accepted already exists"',
    'ALTER TABLE partners ADD COLUMN agreement_version_accepted VARCHAR(50) NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- agreement_accepted_at
SET @col = 'agreement_accepted_at';
SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tbl AND COLUMN_NAME=@col),
    'SELECT "Column agreement_accepted_at already exists"',
    'ALTER TABLE partners ADD COLUMN agreement_accepted_at DATETIME NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- agreement_acceptance_id
SET @col = 'agreement_acceptance_id';
SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tbl AND COLUMN_NAME=@col),
    'SELECT "Column agreement_acceptance_id already exists"',
    'ALTER TABLE partners ADD COLUMN agreement_acceptance_id INT NULL'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- onboarding_step
SET @col = 'onboarding_step';
SET @sql = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tbl AND COLUMN_NAME=@col),
    'SELECT "Column onboarding_step already exists"',
    'ALTER TABLE partners ADD COLUMN onboarding_step VARCHAR(50) NOT NULL DEFAULT "registration"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Migration completed successfully' AS result;
