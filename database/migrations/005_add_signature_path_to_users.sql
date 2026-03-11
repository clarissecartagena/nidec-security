-- Migration 005: Add signature_path column to users table
-- This enables users to upload a signature image used on PDF reports.
-- Safe to run on databases that already include this column (IF NOT EXISTS).

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS signature_path VARCHAR(255) NULL DEFAULT NULL AFTER email;
