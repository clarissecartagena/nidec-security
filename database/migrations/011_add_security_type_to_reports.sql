-- Migration 011: Add security_type column to reports table
-- This decouples the report type (internal/external) from the submitting user's account settings.
-- Security staff can now select the type when submitting a report.
ALTER TABLE reports
    ADD COLUMN security_type ENUM('internal','external') NOT NULL DEFAULT 'internal'
    AFTER building;

-- Back-fill existing reports from the submitting user's security_type
UPDATE reports r
    JOIN users u ON u.employee_no = r.submitted_by
SET r.security_type = u.security_type
WHERE u.security_type IN ('internal','external');

-- Ensure any remaining rows (users with no security_type) default to 'internal'
UPDATE reports SET security_type = 'internal' WHERE security_type NOT IN ('internal','external');
