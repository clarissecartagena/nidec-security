-- Migration 006: Replace department list with updated values
-- Safe to run on existing databases:
--   • Old departments are deactivated (not deleted) to preserve FK references
--     in existing reports.
--   • New departments are inserted; if a name already exists the row is
--     reactivated instead of duplicated (UNIQUE KEY uq_departments_name).

-- Step 1: Deactivate all currently active departments
UPDATE departments SET is_active = 0 WHERE is_active = 1;

-- Step 2: Insert / reactivate the updated department list
INSERT INTO departments (name, is_active, created_at) VALUES
  ('Quality Assurance',      1, NOW()),
  ('Engineering Component',  1, NOW()),
  ('Production Component',   1, NOW()),
  ('Engineering Module',     1, NOW()),
  ('L Office-Maint',         1, NOW()),
  ('Administration',         1, NOW()),
  ('Others',                 1, NOW()),
  ('NCFL',                   1, NOW()),
  ('Purchasing',             1, NOW()),
  ('L Office-QC',            1, NOW()),
  ('PSD Office-HR',          1, NOW()),
  ('Accounting and Finance', 1, NOW()),
  ('Technical Support',      1, NOW()),
  ('Production',             1, NOW()),
  ('QA Commin',              1, NOW()),
  ('Process Control',        1, NOW())
ON DUPLICATE KEY UPDATE is_active = 1;
