-- Migration: 004_jobs_queue.sql
-- Created: 2026-03-21
-- Purpose: Database-backed job queue + error storage

CREATE TABLE IF NOT EXISTS `jobs` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `type`          VARCHAR(50) NOT NULL,
    `payload`       JSON NOT NULL,
    `status`        ENUM('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
    `attempts`      TINYINT UNSIGNED DEFAULT 0,
    `max_attempts`  TINYINT UNSIGNED DEFAULT 3,
    `error_log`     TEXT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `started_at`    TIMESTAMP NULL,
    `finished_at`   TIMESTAMP NULL,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_status_type`     (`status`, `type`),
    INDEX `idx_created`         (`created_at`),
    INDEX `idx_status_attempts` (`status`, `attempts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: marker row to confirm migration ran
INSERT IGNORE INTO `jobs` (`type`, `payload`, `status`, `error_log`)
VALUES (
    'system',
    '{"event": "migration_004_applied", "note": "jobs queue initialized"}',
    'completed',
    NULL
);