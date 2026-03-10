-- ==========================================================================
-- Migration 001 — Add Employee API integration fields to users table
-- ==========================================================================
-- Run this ONCE on existing databases that already have the users table.
-- For fresh installations, use database/schema.sql which already includes
-- these columns.
--
-- Safe to run multiple times — each ALTER uses IF NOT EXISTS / IGNORE so
-- it will no-op on columns that already exist.
-- ==========================================================================

-- 1. Add employee_id (nullable, unique — allows pre-existing NULL rows)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS employee_id VARCHAR(50) NULL AFTER id;

-- Apply unique constraint only if it does not already exist.
-- (MySQL 8.0+: IF NOT EXISTS for indexes)
-- If on MySQL 5.7, run the next two lines manually if the key is missing:
--   ALTER TABLE users ADD UNIQUE KEY uq_users_employee_id (employee_id);
ALTER TABLE users
    ADD UNIQUE KEY IF NOT EXISTS uq_users_employee_id (employee_id);

-- 2. Add email (nullable — not all employees may have a work email on record)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER name;

-- 3. Add position / job title (nullable — informational, sourced from API)
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS position VARCHAR(100) NULL AFTER email;

-- 4. Fix the role ENUM if it still contains the old incorrect values.
--    The application uses: ga_president, ga_staff, security, department.
--    The original schema.sql incorrectly declared: ga_manager, pic.
--
--    NOTE: MySQL silently keeps existing row values that are valid in the
--    new ENUM.  Any rows that contain 'ga_manager' or 'pic' must be
--    manually updated before running this ALTER or they will become ''.
--
ALTER TABLE users
    MODIFY COLUMN role ENUM('ga_president','ga_staff','security','department') NOT NULL;

-- ==========================================================================
-- Verification query (run after migration to confirm):
-- ==========================================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME   = 'users'
--   AND COLUMN_NAME  IN ('employee_id', 'email', 'position', 'role')
-- ORDER BY ORDINAL_POSITION;
-- ==========================================================================
