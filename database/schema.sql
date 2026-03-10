SET NAMES utf8mb4;

-- ==================================================================
-- Drop tables in child → parent order so FK constraints don't block
-- Safe to run on a fresh or existing database
-- ==================================================================
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS report_status_history;
DROP TABLE IF EXISTS report_attachments;
DROP TABLE IF EXISTS security_final_checks;
DROP TABLE IF EXISTS department_actions;
DROP TABLE IF EXISTS ga_president_approvals;
DROP TABLE IF EXISTS ga_staff_reviews;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;

-- ==================================================================
-- departments
-- ==================================================================
CREATE TABLE departments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_departments_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- users
-- ==================================================================
CREATE TABLE users (
  -- ── Employee API fields (populated from company Employee API) ──────
  employee_no     VARCHAR(50)  NOT NULL,          -- company employee number (PRIMARY KEY)
  name            VARCHAR(120) NOT NULL,           -- fullname from API
  email           VARCHAR(150) NULL,               -- work email from API
  position        VARCHAR(100) NULL,               -- job title from API
  department      VARCHAR(120) NULL,               -- department name from API
  -- ──────────────────────────────────────────────────────────────────

  username        VARCHAR(60)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('ga_president','ga_staff','security','department') NOT NULL,
  department_id   INT UNSIGNED NULL,
  security_type   ENUM('internal','external') NULL,
  entity          ENUM('NCFL','NPFL') NULL,        -- assigned entity (security users)
  account_status  ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_by_role ENUM('ga_president','ga_staff','system') NULL DEFAULT NULL,
  created_by_employee_no VARCHAR(50) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (employee_no),
  UNIQUE KEY uq_users_username    (username),
  KEY idx_users_role              (role),
  KEY idx_users_department        (department_id),
  KEY idx_users_entity            (entity),
  CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_users_created_by FOREIGN KEY (created_by_employee_no) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- reports
-- ==================================================================
CREATE TABLE reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_no VARCHAR(30) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  category VARCHAR(80) NOT NULL,
  location VARCHAR(150) NOT NULL,
  severity ENUM('low','medium','high','critical') NOT NULL,
  building ENUM('NCFL','NPFL') NOT NULL,
  responsible_department_id INT UNSIGNED NOT NULL,
  details TEXT NOT NULL,
  actions_taken TEXT NULL,
  remarks TEXT NULL,
  assessment TEXT NULL,
  recommendations TEXT NULL,
  evidence_image_path VARCHAR(255) NULL,

  submitted_by VARCHAR(50) NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  current_reviewer ENUM('ga_staff','ga_president','department','security') NULL,
  fix_due_date DATETIME NULL,
  resolved_by_security VARCHAR(50) NULL,
  resolved_at DATETIME NULL,
  returned_by_security VARCHAR(50) NULL,
  returned_at DATETIME NULL,
  security_remarks TEXT NULL,

  status ENUM(
    'submitted_to_ga_staff',
    'ga_staff_reviewed',
    'submitted_to_ga_president',
    'approved_by_ga_president',
    'sent_to_department',
    'under_department_fix',
    'for_security_final_check',
    'returned_to_department',
    'resolved',
    'rejected'
  ) NOT NULL DEFAULT 'submitted_to_ga_staff',

  reopen_count TINYINT UNSIGNED NOT NULL DEFAULT 0,

  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_reports_report_no (report_no),
  KEY idx_reports_building (building),
  KEY idx_reports_status (status),
  KEY idx_reports_submitted_at (submitted_at),
  KEY idx_reports_dept (responsible_department_id),
  KEY idx_reports_reviewer (current_reviewer),
  KEY idx_reports_fix_due_date (fix_due_date),
  KEY idx_reports_building_status (building, status),
  KEY idx_reports_building_dept_status (building, responsible_department_id, status),
  KEY idx_reports_status_updated (status, updated_at),
  CONSTRAINT fk_reports_department FOREIGN KEY (responsible_department_id) REFERENCES departments(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reports_submitted_by FOREIGN KEY (submitted_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_reports_resolved_by FOREIGN KEY (resolved_by_security) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_reports_returned_by FOREIGN KEY (returned_by_security) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- notifications
-- ==================================================================
CREATE TABLE notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(50) NOT NULL,
  report_id INT UNSIGNED NULL,
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notifications_user_read (user_id, is_read, created_at),
  KEY idx_notifications_report (report_id),
  KEY idx_notif_dedup (user_id, report_id, message(80), created_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_notifications_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- report_attachments
-- ==================================================================
CREATE TABLE report_attachments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  file_name VARCHAR(180) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  file_size_bytes INT UNSIGNED NULL,
  uploaded_by VARCHAR(50) NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attach_report (report_id),
  CONSTRAINT fk_attach_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_attach_user FOREIGN KEY (uploaded_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- report_status_history
-- ==================================================================
CREATE TABLE report_status_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  status ENUM(
    'submitted_to_ga_staff',
    'ga_staff_reviewed',
    'submitted_to_ga_president',
    'approved_by_ga_president',
    'sent_to_department',
    'under_department_fix',
    'for_security_final_check',
    'returned_to_department',
    'resolved',
    'rejected'
  ) NOT NULL,
  changed_by VARCHAR(50) NULL,
  notes VARCHAR(255) NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rsh_report (report_id),
  KEY idx_rsh_changed_at (changed_at),
  CONSTRAINT fk_rsh_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_rsh_user FOREIGN KEY (changed_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- ga_staff_reviews
-- ==================================================================
CREATE TABLE ga_staff_reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  reviewed_by VARCHAR(50) NULL,
  decision ENUM('forwarded','returned') NOT NULL DEFAULT 'forwarded',
  notes TEXT NULL,
  reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ga_staff_reviews_report (report_id),
  CONSTRAINT fk_gasr_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_gasr_user FOREIGN KEY (reviewed_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- ga_president_approvals
-- ==================================================================
CREATE TABLE ga_president_approvals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  decided_by VARCHAR(50) NULL,
  decision ENUM('approved','rejected','returned') NOT NULL,
  notes TEXT NULL,
  decided_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gapa_report (report_id),
  CONSTRAINT fk_gapa_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_gapa_user FOREIGN KEY (decided_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- department_actions
-- ==================================================================
CREATE TABLE department_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  action_type ENUM('done','timeline') NOT NULL,
  timeline_days INT UNSIGNED NULL,
  timeline_start DATETIME NULL,
  timeline_due DATETIME NULL,
  remarks TEXT NULL,
  evidence_image_path VARCHAR(255) NULL,
  acted_by VARCHAR(50) NULL,
  acted_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dept_actions_report (report_id),
  KEY idx_dept_actions_due (timeline_due),
  CONSTRAINT fk_depta_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_depta_user FOREIGN KEY (acted_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- security_final_checks
-- ==================================================================
CREATE TABLE security_final_checks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  decision ENUM('confirmed','rejected','returned') NOT NULL,
  remarks TEXT NULL,
  checked_by VARCHAR(50) NULL,
  checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sfc_report (report_id),
  CONSTRAINT fk_sfc_report FOREIGN KEY (report_id) REFERENCES reports(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_sfc_user FOREIGN KEY (checked_by) REFERENCES users(employee_no)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;