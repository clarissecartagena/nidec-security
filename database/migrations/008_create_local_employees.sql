-- Migration 008: Create local_employees table
-- Stores employee records synchronised from the company Employee API.
-- Used by the Sync on Request feature (employee_sync.php).

CREATE TABLE IF NOT EXISTS local_employees (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    employee_no VARCHAR(50)     NOT NULL,
    name        VARCHAR(120)    NOT NULL,
    entity      VARCHAR(20)     NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_local_employees_employee_no (employee_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
