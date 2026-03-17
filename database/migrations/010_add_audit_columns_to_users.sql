-- Migration 010: Add audit columns created_by_role and created_by_employee_no to users table
-- These columns were always present in schema.sql but lacked an explicit migration,
-- so existing installations that upgraded from an older schema may be missing them.
-- This migration is safe to run on any database — both columns use IF NOT EXISTS.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS created_by_role ENUM('ga_president','ga_staff','system') NULL DEFAULT NULL
        AFTER account_status;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS created_by_employee_no VARCHAR(50) NULL
        AFTER created_by_role;
