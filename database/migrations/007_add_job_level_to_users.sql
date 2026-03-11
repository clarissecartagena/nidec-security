-- Migration 007: Add job_level column to users table
-- Stores the employee's job level from the company Employee API.
-- Used in PDF reports to show "GA <job_level>" instead of static role text.

ALTER TABLE users
    ADD COLUMN job_level VARCHAR(100) NULL AFTER position;
