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
 *   2. Derive their username from their fullname (first-initial.lastname).
 *   3. Auto-detect role, entity, and department from the API data.
 *   4. Create a local user account.
 *
 * Alternatively, run the provisioning tool to create accounts in advance
 * (useful for development/testing without needing the corporate API login):
 *
 *   php tools/provision_allowed_users.php
 *
 * ──────────────────────────────────────────────────────────────────────────
 * HOW TO ADD AN EMPLOYEE
 * ──────────────────────
 * Add a new entry to the array below with only the employee number and
 * an initial password.  All other details (name, role, department, entity)
 * are fetched automatically from the Employee API.
 *
 *   [
 *       'employee_no' => '123456',        // Employee ID from the corporate system
 *       'password'    => 'Password123!',  // Initial/test password (plain text here, hashed on save)
 *   ]
 *
 * ROLES (auto-detected from Employee API)
 * ────────────────────────────────────────
 *   ga_president  – GA President (employee_no === '300553')
 *   ga_staff      – section === 'HUMAN RESOURCE, GA AND COMPLIANCE'
 *   security      – job_level === 'Security' (NCFL) or 'SEGURITY GUARD' (NPFL)
 *   department    – job_level === 'SUPPORT/PIC'
 *
 * USERNAME (auto-generated from Employee API fullname)
 * ─────────────────────────────────────────────────────
 * Username is derived from the employee's fullname returned by the API
 * using the pattern "firstinitial.lastname" (e.g. "ENRIQUEZ, KATHY" → "k.enriquez").
 * Falls back to the employee number if the name cannot be parsed.
 *
 * CREDENTIALS
 * ───────────
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
 * ──────────────────────────────────────────────────────────────────────────
 * TEST CREDENTIALS SUMMARY
 * ─────────────────────────────────────────────────────────────────────────
 * All accounts below use the password: Password123!
 *
 *  Employee No   Auto-detected Role   Auto-generated Username
 *  ──────────── ─────────────────    ──────────────────────────
 *  300553        ga_president         k.enriquez
 *  401157        ga_staff             l.acosta
 *  1200385       ga_staff             c.buenconsejo
 *  8810183       security (NCFL)      b.esteban
 *  8810305       security (NCFL)      e.corrales
 *  8810279       security (NPFL)      c.provido
 *  8810222       security (NPFL)      j.ruazol
 * ──────────────────────────────────────────────────────────────────────────
 */

return [
    // ── GA President ──────────────────────────────────────────────────────
    [
        'employee_no' => '0300553',
        'password'    => 'Password123!',
    ],

    // ── GA Staff ──────────────────────────────────────────────────────────
    [
        'employee_no' => '0401157',
        'password'    => 'Password123!',
    ],
    [
        'employee_no' => '1200385',
        'password'    => 'Password123!',
    ],

    // ── Security – NCFL ───────────────────────────────────────────────────
    [
        'employee_no' => '8810183',
        'password'    => 'Password123!',
    ],
    [
        'employee_no' => '8810305',
        'password'    => 'Password123!',
    ],

    // ── Security – NPFL ───────────────────────────────────────────────────
    [
        'employee_no' => '8810279',
        'password'    => 'Password123!',
    ],
    [
        'employee_no' => '8810222',
        'password'    => 'Password123!',
    ],

    // ── Add more employees below ──────────────────────────────────────────
];

