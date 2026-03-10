-- Migration 005: Add signature_path column to users table
-- This enables users to upload a signature image used on PDF reports.
--
-- Safe to run on existing databases — IF NOT EXISTS is a no-op when the
-- column is already present (e.g. on installs created from a current
-- schema.sql that already includes this column).

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS signature_path VARCHAR(255) NULL DEFAULT NULL AFTER email;
