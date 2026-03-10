-- Migration 004: Remove users.id, make employee_no the PRIMARY KEY,
-- and update all child-table FK columns to VARCHAR(50) referencing users.employee_no.
--
-- Run this on an existing database that was created from an earlier schema version.
-- Safe to run once; subsequent runs will fail on the duplicate-key/column checks
-- but will not corrupt data — wrap in a transaction where your client supports it.

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ============================================================
-- 1. notifications  (user_id INT → VARCHAR(50))
-- ============================================================
ALTER TABLE notifications
  DROP FOREIGN KEY fk_notifications_user,
  MODIFY COLUMN user_id VARCHAR(50) NOT NULL,
  ADD CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- ============================================================
-- 2. report_attachments  (uploaded_by INT → VARCHAR(50))
-- ============================================================
ALTER TABLE report_attachments
  DROP FOREIGN KEY fk_attach_user,
  MODIFY COLUMN uploaded_by VARCHAR(50) NULL,
  ADD CONSTRAINT fk_attach_user
    FOREIGN KEY (uploaded_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 3. report_status_history  (changed_by INT → VARCHAR(50))
-- ============================================================
ALTER TABLE report_status_history
  DROP FOREIGN KEY fk_rsh_user,
  MODIFY COLUMN changed_by VARCHAR(50) NULL,
  ADD CONSTRAINT fk_rsh_user
    FOREIGN KEY (changed_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 4. ga_staff_reviews  (reviewed_by INT → VARCHAR(50))
-- ============================================================
ALTER TABLE ga_staff_reviews
  DROP FOREIGN KEY fk_gasr_user,
  MODIFY COLUMN reviewed_by VARCHAR(50) NULL,
  ADD CONSTRAINT fk_gasr_user
    FOREIGN KEY (reviewed_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 5. ga_president_approvals  (decided_by INT → VARCHAR(50))
-- ============================================================
ALTER TABLE ga_president_approvals
  DROP FOREIGN KEY fk_gapa_user,
  MODIFY COLUMN decided_by VARCHAR(50) NULL,
  ADD CONSTRAINT fk_gapa_user
    FOREIGN KEY (decided_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 6. department_actions  (acted_by INT → VARCHAR(50))
-- ============================================================
ALTER TABLE department_actions
  DROP FOREIGN KEY fk_depta_user,
  MODIFY COLUMN acted_by VARCHAR(50) NULL,
  ADD CONSTRAINT fk_depta_user
    FOREIGN KEY (acted_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 7. security_final_checks  (checked_by INT → VARCHAR(50))
-- ============================================================
ALTER TABLE security_final_checks
  DROP FOREIGN KEY fk_sfc_user,
  MODIFY COLUMN checked_by VARCHAR(50) NULL,
  ADD CONSTRAINT fk_sfc_user
    FOREIGN KEY (checked_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 8. reports  (submitted_by, resolved_by_security, returned_by_security INT → VARCHAR(50))
-- ============================================================
ALTER TABLE reports
  DROP FOREIGN KEY fk_reports_submitted_by,
  DROP FOREIGN KEY fk_reports_resolved_by,
  DROP FOREIGN KEY fk_reports_returned_by,
  MODIFY COLUMN submitted_by        VARCHAR(50) NULL,
  MODIFY COLUMN resolved_by_security VARCHAR(50) NULL,
  MODIFY COLUMN returned_by_security VARCHAR(50) NULL,
  ADD CONSTRAINT fk_reports_submitted_by
    FOREIGN KEY (submitted_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL,
  ADD CONSTRAINT fk_reports_resolved_by
    FOREIGN KEY (resolved_by_security) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL,
  ADD CONSTRAINT fk_reports_returned_by
    FOREIGN KEY (returned_by_security) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 9. users self-referencing FK: created_by_user_id → created_by_employee_no
-- ============================================================
ALTER TABLE users
  DROP FOREIGN KEY fk_users_created_by,
  DROP KEY idx_users_created_by;

-- Add the new column only if it does not already exist.
-- (MySQL 8.0+ supports IF NOT EXISTS; for older versions remove the guard.)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS created_by_employee_no VARCHAR(50) NULL
    AFTER created_by_role;

-- Copy existing data: map integer id → employee_no via self-join.
UPDATE users u
  JOIN users creator ON creator.id = u.created_by_user_id
  SET u.created_by_employee_no = creator.employee_no
  WHERE u.created_by_user_id IS NOT NULL;

ALTER TABLE users
  ADD CONSTRAINT fk_users_created_by
    FOREIGN KEY (created_by_employee_no) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ============================================================
-- 10. Drop users.id column and promote employee_no to PRIMARY KEY
-- ============================================================
-- Step 10a: ensure employee_no is NOT NULL (it must be unique & not null to be a PK).
UPDATE users SET employee_no = CONCAT('emp_', id) WHERE employee_no IS NULL OR employee_no = '';

ALTER TABLE users
  MODIFY COLUMN employee_no VARCHAR(50) NOT NULL;

-- Step 10b: drop unique index on employee_no (will be replaced by the PK).
ALTER TABLE users DROP INDEX uq_users_employee_no;

-- Step 10c: drop the old AUTO_INCREMENT primary key.
ALTER TABLE users DROP PRIMARY KEY, DROP COLUMN id;

-- Step 10d: promote employee_no to PRIMARY KEY.
ALTER TABLE users ADD PRIMARY KEY (employee_no);

-- Step 10e: drop the old created_by_user_id column (now replaced by created_by_employee_no).
ALTER TABLE users DROP COLUMN IF EXISTS created_by_user_id;

SET foreign_key_checks = 1;
