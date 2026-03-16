-- Migration 009: Keep active departments aligned to config/constants.php canonical list.
--
-- Notes:
-- - Deactivates active departments that are not in the canonical list.
-- - Inserts or re-activates canonical departments.
-- - Does not delete rows, so historical foreign-key references remain valid.

UPDATE departments
SET is_active = 0
WHERE is_active = 1
  AND name NOT IN (
    'Quality Assurance',
    'Engineering Component',
    'Production Component',
    'Engineering Module',
    'L Office-Maint',
    'Administration',
    'Others',
    'NCFL',
    'Purchasing',
    'L Office-QC',
    'PSD Office-HR',
    'Accounting and Finance',
    'Technical Support',
    'Production',
    'QA Commin',
    'Process Control'
  );

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
