-- Migration 003: Add department column to users table
-- Run this on existing databases to store the raw department name from the Employee API.

ALTER TABLE users
    ADD COLUMN department VARCHAR(120) NULL AFTER position;
