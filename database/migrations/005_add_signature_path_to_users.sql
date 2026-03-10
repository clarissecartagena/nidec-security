-- Migration 005: Add signature_path column to users table
-- This enables users to upload a signature image used on PDF reports.

ALTER TABLE users
    ADD COLUMN signature_path VARCHAR(255) NULL DEFAULT NULL AFTER email;
