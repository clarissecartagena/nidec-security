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
 * HOW TO ADD AN EMPLOYEE
 * ──────────────────────
 * Add a new entry to the array below:
 *
 *   [
 *       'employee_id'   => '123456',        // Employee ID from the corporate system
 *       'role'          => 'ga_staff',      // One of: ga_president, ga_staff, security, department
 *       'security_type' => null,            // 'internal' or 'external' (security role only)
 *       'building'      => null,            // 'NCFL' or 'NPFL' (security role only)
 *       'department_id' => null,            // Department ID (department role only)
 *   ]
 *
 * ROLES
 * ─────
 *   ga_president  – GA President (highest authority)
 *   ga_staff      – GA Staff
 *   security      – Security personnel (requires security_type and building)
 *   department    – Department representative (requires department_id)
 *
 * NOTE: The employee's name, email, and position are always fetched from the
 * Employee API and are never stored here.
 */

return [
    // ── GA President ──────────────────────────────────────────────────────
    [
        'employee_id'   => '300553',
        'role'          => 'ga_president',
        'security_type' => null,
        'building'      => null,
        'department_id' => null,
    ],

    // ── GA Staff ──────────────────────────────────────────────────────────
    [
        'employee_id'   => '401157',
        'role'          => 'ga_staff',
        'security_type' => null,
        'building'      => null,
        'department_id' => null,
    ],
    [
        'employee_id'   => '1200385',
        'role'          => 'ga_staff',
        'security_type' => null,
        'building'      => null,
        'department_id' => null,
    ],

    // ── Security – NCFL ───────────────────────────────────────────────────
    [
        'employee_id'   => '8810183',
        'role'          => 'security',
        'security_type' => 'external',
        'building'      => 'NCFL',
        'department_id' => null,
    ],
    [
        'employee_id'   => '8810305',
        'role'          => 'security',
        'security_type' => 'internal',
        'building'      => 'NCFL',
        'department_id' => null,
    ],

    // ── Security – NPFL ───────────────────────────────────────────────────
    [
        'employee_id'   => '8810279',
        'role'          => 'security',
        'security_type' => 'internal',
        'building'      => 'NPFL',
        'department_id' => null,
    ],
    [
        'employee_id'   => '8810222',
        'role'          => 'security',
        'security_type' => 'external',
        'building'      => 'NPFL',
        'department_id' => null,
    ],

    // ── Add more employees below ──────────────────────────────────────────
];
