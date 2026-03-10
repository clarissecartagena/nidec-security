<?php

/**
 * Allowed Users Configuration
 * ──────────────────────────────────────────────────────────────────────────
 * This file defines which employee IDs are permitted to log in to the system.
 *
 * When an employee in this list authenticates successfully through the
 * corporate login API for the first time (and does not yet have a local
 * account), the system will automatically:
 *   1. Look up their profile in the Employee API.
 *   2. Create a local user account using their API data.
 *   3. Assign the role and settings defined here.
 *
 * Alternatively, run the provisioning tool to create accounts in advance
 * (useful for development/testing without needing the corporate API login):
 *
 *   php tools/provision_allowed_users.php
 *
 * ──────────────────────────────────────────────────────────────────────────
 * HOW TO ADD AN EMPLOYEE
 * ──────────────────────
 * Add a new entry to the array below:
 *
 *   [
 *       'employee_id'   => '123456',        // Employee ID from the corporate system
 *       'username'      => 'j.doe',         // Login username
 *       'password'      => 'Password123!',  // Test/initial password (plain text here, hashed on save)
 *       'role'          => 'ga_staff',      // One of: ga_president, ga_staff, security, department
 *       'security_type' => null,            // 'internal' or 'external' (security role only)
 *       'entity'       => null,            // 'NCFL' or 'NPFL' — fallback if API detection fails (security role only)
 *       'department_id' => null,            // Department ID (department role only)
 *   ]
 *
 * ROLES
 * ─────
 *   ga_president  – GA President (highest authority)
 *   ga_staff      – GA Staff
 *   security      – Security personnel (requires security_type and entity)
 *   department    – Department representative (requires department_id)
 *
 * CREDENTIALS
 * ───────────
 * The 'username' is what the employee types on the login page.
 * The 'password' is stored as a bcrypt hash — the plain text here is only
 * used during account creation/provisioning and is never stored as-is.
 *
 * ⚠️  SECURITY NOTE
 * -----------------
 * This file contains plain-text test passwords and is intended for
 * DEVELOPMENT USE ONLY.  Before deploying to production:
 *   • Remove or blank out the 'password' field for all entries, OR
 *   • Ensure this file is NOT committed to a public repository and is
 *     listed in .gitignore for your production environment.
 * In production, employees authenticate exclusively via the corporate
 * login API — no local password is required.
 *
 * NOTE: The employee's name, email, and position are always fetched from the
 * Employee API and are never stored here.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * TEST CREDENTIALS SUMMARY
 * ─────────────────────────────────────────────────────────────────────────
 * All accounts below use the password: Password123!
 *
 *  Username         Role          Building / Dept
 *  ─────────────── ────────────  ────────────────────────
 *  k.enriquez       ga_president  —
 *  l.acosta         ga_staff      —
 *  c.buenconsejo    ga_staff      —
 *  b.esteban        security      NCFL / external
 *  e.corrales       security      NCFL / internal
 *  c.provido        security      NPFL / internal
 *  j.ruazol         security      NPFL / external
 * ──────────────────────────────────────────────────────────────────────────
 */

return [
    // ── GA President ──────────────────────────────────────────────────────
    [
        'employee_id'   => '300553',
        'username'      => 'k.enriquez',
        'password'      => 'Password123!',
        'role'          => 'ga_president',
        'security_type' => null,
        'entity'       => null,
        'department_id' => null,
    ],

    // ── GA Staff ──────────────────────────────────────────────────────────
    [
        'employee_id'   => '401157',
        'username'      => 'l.acosta',
        'password'      => 'Password123!',
        'role'          => 'ga_staff',
        'security_type' => null,
        'entity'       => null,
        'department_id' => null,
    ],
    [
        'employee_id'   => '1200385',
        'username'      => 'c.buenconsejo',
        'password'      => 'Password123!',
        'role'          => 'ga_staff',
        'security_type' => null,
        'entity'       => null,
        'department_id' => null,
    ],

    // ── Security – NCFL ───────────────────────────────────────────────────
    [
        'employee_id'   => '8810183',
        'username'      => 'b.esteban',
        'password'      => 'Password123!',
        'role'          => 'security',
        'security_type' => 'external',
        'entity'       => 'NCFL',
        'department_id' => null,
    ],
    [
        'employee_id'   => '8810305',
        'username'      => 'e.corrales',
        'password'      => 'Password123!',
        'role'          => 'security',
        'security_type' => 'internal',
        'entity'       => 'NCFL',
        'department_id' => null,
    ],

    // ── Security – NPFL ───────────────────────────────────────────────────
    [
        'employee_id'   => '8810279',
        'username'      => 'c.provido',
        'password'      => 'Password123!',
        'role'          => 'security',
        'security_type' => 'internal',
        'entity'       => 'NPFL',
        'department_id' => null,
    ],
    [
        'employee_id'   => '8810222',
        'username'      => 'j.ruazol',
        'password'      => 'Password123!',
        'role'          => 'security',
        'security_type' => 'external',
        'entity'       => 'NPFL',
        'department_id' => null,
    ],

    // ── Add more employees below ──────────────────────────────────────────
];
