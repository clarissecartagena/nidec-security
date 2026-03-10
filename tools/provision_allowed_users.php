<?php
/**
 * Provision Allowed Users
 * ──────────────────────────────────────────────────────────────────────────
 * CLI script that reads config/allowed_users.php and creates a local account
 * in the database for every entry that does not yet have one.
 *
 * Run once after a fresh database setup (instead of running a seeder):
 *
 *   php tools/provision_allowed_users.php
 *
 * What it does for each configured employee:
 *   1. Checks if the employee_id or username already exists in the users table.
 *   2. If not, calls the Employee API to fetch their name/email/position.
 *   3. Inserts a new active account with the configured role and credentials.
 *
 * If the Employee API is unreachable the entry is skipped with a warning.
 * Re-running the script is safe — existing accounts are left untouched.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * TEST CREDENTIALS (all accounts below use the same password)
 *
 *  Username         Password       Role          Building / Dept
 *  ───────────────  ─────────────  ────────────  ──────────────────────
 *  k.enriquez       Password123!   ga_president  —
 *  l.acosta         Password123!   ga_staff      —
 *  c.buenconsejo    Password123!   ga_staff      —
 *  b.esteban        Password123!   security      NCFL / external
 *  e.corrales       Password123!   security      NCFL / internal
 *  c.provido        Password123!   security      NPFL / internal
 *  j.ruazol         Password123!   security      NPFL / external
 * ──────────────────────────────────────────────────────────────────────────
 */

// ── Bootstrap ──────────────────────────────────────────────────────────────
define('SCRIPT_ROOT', dirname(__DIR__));

require_once SCRIPT_ROOT . '/config/database.php';
require_once SCRIPT_ROOT . '/config/api.php';
require_once SCRIPT_ROOT . '/app/models/UsersModel.php';
require_once SCRIPT_ROOT . '/app/api_clients/EmployeeApiClient.php';
require_once SCRIPT_ROOT . '/app/services/EmployeeService.php';

$allowed = require SCRIPT_ROOT . '/config/allowed_users.php';

$model           = new UsersModel();
$employeeService = new EmployeeService();

// ── Helpers ────────────────────────────────────────────────────────────────

function already_exists(string $employeeId, string $username): bool
{
    $byEmpId = db_fetch_one(
        'SELECT id FROM users WHERE employee_id = ? LIMIT 1',
        's',
        [$employeeId]
    );
    if ($byEmpId) {
        return true;
    }
    $byUsername = db_fetch_one(
        'SELECT id FROM users WHERE username = ? LIMIT 1',
        's',
        [$username]
    );
    return (bool)$byUsername;
}

// ── Main loop ──────────────────────────────────────────────────────────────

$provisioned = 0;
$skipped     = 0;
$failed      = 0;

echo "Provisioning allowed users from config/allowed_users.php...\n\n";

foreach ($allowed as $entry) {
    $employeeId = trim((string)($entry['employee_id'] ?? ''));
    $username   = trim((string)($entry['username']   ?? ''));
    $password   = (string)($entry['password']   ?? '');
    $role       = (string)($entry['role']        ?? 'department');

    if ($employeeId === '') {
        echo "  [SKIP]  Entry has no employee_id — skipped.\n";
        $skipped++;
        continue;
    }

    if ($username === '') {
        echo "  [SKIP]  {$employeeId}: no username configured — skipped.\n";
        $skipped++;
        continue;
    }

    // Check if an account already exists.
    if (already_exists($employeeId, $username)) {
        echo "  [OK]    {$username} ({$employeeId}) already exists — skipped.\n";
        $skipped++;
        continue;
    }

    // Fetch employee data from the API.
    $empResult = $employeeService->getEmployee($employeeId);
    if (!$empResult['success'] || empty($empResult['employee'])) {
        $error = $empResult['error'] ?? 'Employee API unreachable or employee not found.';
        echo "  [FAIL]  {$username} ({$employeeId}): {$error}\n";
        $failed++;
        continue;
    }
    $emp = $empResult['employee'];

    // Hash the configured password (or leave blank if none set).
    $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '';

    $securityType = (string)($entry['security_type'] ?? '');
    $building     = (string)($entry['building']      ?? '');
    $departmentId = (int)($entry['department_id']    ?? 0);

    try {
        $model->insertUser(
            (string)($emp['employee_id'] ?? $employeeId),
            (string)($emp['fullname']    ?? $username),
            (string)($emp['email']       ?? ''),
            (string)($emp['position']    ?? ''),
            $username,
            $passwordHash,
            $role,
            $securityType,
            $building,
            $departmentId,
            'active'
        );

        $tag = $password !== '' ? '(password set)' : '(no local password — API login only)';
        echo "  [DONE]  {$username} ({$employeeId}) provisioned as {$role} {$tag}\n";
        $provisioned++;
    } catch (Throwable $e) {
        echo "  [FAIL]  {$username} ({$employeeId}): " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";
echo "Done. Provisioned: {$provisioned}  Skipped: {$skipped}  Failed: {$failed}\n";

if ($failed > 0) {
    echo "\nNote: Failed entries are usually caused by the Employee API being unreachable.\n";
    echo "Ensure the Employee API (or mock) is running and re-run this script.\n";
    exit(1);
}

exit(0);
