-- ==========================================================================
-- Migration 002 — Rename users.building → users.entity
--                  and users.employee_id → users.employee_no
-- ==========================================================================
-- Run this ONCE on existing databases created from schema.sql before this
-- migration was applied.  For fresh installations use database/schema.sql
-- which already contains the updated column names.
--
-- Rename users.building → users.entity
-- The column stores the entity (NCFL / NPFL) that a security employee is
-- assigned to.  "entity" better reflects the business concept (the column
-- is unrelated to the reports.building column which stays unchanged).
--
-- Rename users.employee_id → users.employee_no
-- The column stores the company employee number.  "employee_no" matches
-- the terminology used in the Employee API and HR system.
-- ==========================================================================

-- 1. Drop the old index on building before renaming (MySQL requires this)
ALTER TABLE users
    DROP INDEX IF EXISTS idx_users_building;

-- 2. Rename building → entity
-- MySQL 8.0+: RENAME COLUMN
-- MySQL 5.7 equivalent: CHANGE COLUMN building entity ENUM('NCFL','NPFL') NULL
ALTER TABLE users
    RENAME COLUMN building TO entity;

-- 3. Re-create the index under the new column name
ALTER TABLE users
    ADD INDEX idx_users_entity (entity);

-- 4. Drop the old unique index on employee_id before renaming
ALTER TABLE users
    DROP INDEX IF EXISTS uq_users_employee_id;

-- 5. Rename employee_id → employee_no
ALTER TABLE users
    RENAME COLUMN employee_id TO employee_no;

-- 6. Re-create the unique constraint under the new column name
ALTER TABLE users
    ADD UNIQUE KEY uq_users_employee_no (employee_no);

-- ==========================================================================
-- Verification (run after to confirm):
-- ==========================================================================
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME   = 'users'
--   AND COLUMN_NAME  IN ('employee_no', 'entity')
-- ORDER BY ORDINAL_POSITION;
-- ==========================================================================
