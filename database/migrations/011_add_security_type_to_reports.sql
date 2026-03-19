-- Migration 011: Add security_type column to reports table
-- The report type (internal/external) is now chosen by the submitter on the form,
-- instead of being derived from the submitting user's security_type.

ALTER TABLE reports
    ADD COLUMN security_type ENUM('internal','external') NULL
    AFTER building;

-- Populate security_type for existing reports from the submitter's user record
UPDATE reports r
    JOIN users u ON u.employee_no = r.submitted_by
   SET r.security_type = u.security_type
 WHERE r.security_type IS NULL
   AND u.security_type IS NOT NULL;

-- Default any remaining NULL values (e.g. submitter has no security_type) to 'external'
UPDATE reports SET security_type = 'external' WHERE security_type IS NULL;

-- Make the column NOT NULL with default 'external'
ALTER TABLE reports
    MODIFY COLUMN security_type ENUM('internal','external') NOT NULL DEFAULT 'external';
