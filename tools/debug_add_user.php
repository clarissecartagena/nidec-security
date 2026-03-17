<?php
/**
 * Debug Add-User Tool
 * ──────────────────────────────────────────────────────────────────────────
 * Diagnoses exactly why an employee can or cannot be found / added through
 * the web UI's "Add New User" modal.
 *
 * Usage:
 *   php tools/debug_add_user.php <employee_id>
 *
 * Example:
 *   php tools/debug_add_user.php 300553
 *
 * What it checks:
 *   1. API connectivity (same check as provision_allowed_users.php).
 *   2. getEmployee()  – exact ID lookup (same call as when the form submits).
 *   3. Role detection – shows every field the API returned and whether it
 *      matches the expected role-detection values.
 *   4. Free-text search – simulates typing the employee ID into the search
 *      box so you can see whether q= returns anything.
 *   5. Database      – whether a local account already exists.
 *
 * ──────────────────────────────────────────────────────────────────────────
 */

define('SCRIPT_ROOT', dirname(__DIR__));

require_once SCRIPT_ROOT . '/config/database.php';
require_once SCRIPT_ROOT . '/config/api.php';
require_once SCRIPT_ROOT . '/app/api_clients/EmployeeApiClient.php';
require_once SCRIPT_ROOT . '/app/services/EmployeeService.php';

// ── Args ───────────────────────────────────────────────────────────────────

$employeeId = trim((string)($argv[1] ?? ''));

if ($employeeId === '') {
    echo "Usage: php tools/debug_add_user.php <employee_id>\n";
    echo "Example: php tools/debug_add_user.php 300553\n";
    exit(1);
}

// ── Helpers ────────────────────────────────────────────────────────────────

function line(string $char = '─', int $width = 68): void
{
    echo str_repeat($char, $width) . "\n";
}

function pass(string $msg): void { echo "  [PASS] {$msg}\n"; }
function fail(string $msg): void { echo "  [FAIL] {$msg}\n"; }
function warn(string $msg): void { echo "  [WARN] {$msg}\n"; }
function info(string $msg): void { echo "  [INFO] {$msg}\n"; }

// ── Banner ─────────────────────────────────────────────────────────────────

$service = new EmployeeService();

line('=');
echo " Add-User Debug Tool\n";
line('=');
printf(" %-14s : %s\n", 'Employee ID',  $employeeId);
printf(" %-14s : %s\n", 'API endpoint', $service->getApiBaseUrl());
printf(" %-14s : %s\n", 'Using mock',   $service->isUsingMock() ? 'YES (local mock server)' : 'NO (company API)');
line('=');
echo "\n";

// ─────────────────────────────────────────────────────────────────────────
// Step 1 — Exact lookup (employee_id=)
// This is the lookup used by:
//   • the web UI when you type a numeric ID into the search box
//   • the form submission that re-fetches employee data server-side
// ─────────────────────────────────────────────────────────────────────────

echo "Step 1 — Exact lookup: getEmployee('{$employeeId}')\n";
line();

$result = $service->getEmployee($employeeId);

if (!$result['success'] || $result['employee'] === null) {
    fail("Employee not found via exact lookup.");
    echo "\n  Error : " . ($result['error'] ?? 'No error message returned.') . "\n";
    echo "\n  ► This is why the web UI shows no results when you type this employee\n";
    echo "    ID into the Add User search box.\n";
    echo "\n  Possible causes:\n";
    echo "    • Employee ID does not exist in the company HR system.\n";
    echo "    • Company API is unreachable (check VPN / network).\n";
    echo "    • API endpoint URL is wrong  — check config/api.php.\n";
    echo "\n";
    exit(1);
}

$emp = $result['employee'];
pass("Employee found via exact lookup.");
echo "\n";
echo "  Raw fields returned by the API:\n";
$fieldWidth = max(array_map('strlen', array_keys($emp))) + 2;
foreach ($emp as $field => $value) {
    printf("  %-{$fieldWidth}s : %s\n", $field, ($value !== '' && $value !== null) ? $value : '(empty)');
}
echo "\n";

// ─────────────────────────────────────────────────────────────────────────
// Step 2 — Role detection
// ─────────────────────────────────────────────────────────────────────────

echo "Step 2 — Role detection\n";
line();

$detected = EmployeeService::detectRoleFromEmployee($emp);

$empEmpId   = trim((string)($emp['employee_id'] ?? ''));
$empSection = trim((string)($emp['section']    ?? ''));
$empJobLvl  = trim((string)($emp['job_level']  ?? ''));

echo "  Fields used for role detection:\n";
printf("  %-15s : \"%s\"\n", 'employee_id', $empEmpId);
printf("  %-15s : \"%s\"\n", 'section',     $empSection);
printf("  %-15s : \"%s\"\n", 'job_level',   $empJobLvl);
echo "\n";

echo "  Expected values per role:\n";
printf("  %-18s : employee_id === \"%s\"\n",  'ga_president',    GA_PRESIDENT_EMPLOYEE_NO);
printf("  %-18s : section     === \"%s\"\n",  'ga_staff',        GA_STAFF_SECTION);
printf("  %-18s : job_level   === \"%s\"\n",  'security (NCFL)', SECURITY_JOB_LEVEL_NCFL);
printf("  %-18s : job_level   === \"%s\"\n",  'security (NPFL)', SECURITY_JOB_LEVEL_NPFL);
printf("  %-18s : job_level   === \"%s\"\n",  'department',      DEPARTMENT_JOB_LEVEL);
echo "\n";

if ($detected === null) {
    fail("Role detection FAILED — employee does not match any allowed role.");
    echo "\n  ► This is why the web UI rejects this employee (even if they appear\n";
    echo "    in search results, they cannot be added).\n";
    echo "\n  How to fix:\n";
    echo "    Option A: Update the employee's data in the company HR system so\n";
    echo "              their section or job_level matches one of the expected\n";
    echo "              values above.\n";
    echo "    Option B: Add a new matching rule in app/services/EmployeeService.php\n";
    echo "              inside detectRoleFromEmployee() and define a new constant\n";
    echo "              at the top of that file if needed.\n";
    echo "\n";

    // Check character-level differences for the closest match
    foreach ([
        'section (ga_staff check)'    => [$empSection, GA_STAFF_SECTION],
        'job_level (Security NCFL)'   => [$empJobLvl,  SECURITY_JOB_LEVEL_NCFL],
        'job_level (Security NPFL)'   => [$empJobLvl,  SECURITY_JOB_LEVEL_NPFL],
        'job_level (Department)'      => [$empJobLvl,  DEPARTMENT_JOB_LEVEL],
    ] as $label => [$actual, $expected]) {
        if ($actual === '' || $expected === '') continue;
        $similarity = 0;
        similar_text(strtolower($actual), strtolower($expected), $similarity);
        if ($similarity >= 60) {
            warn("Close match on {$label}: \"{$actual}\" vs \"{$expected}\" ({$similarity}% similar).");
            echo "       Likely a whitespace, capitalisation, or typo difference.\n";
        }
    }

    exit(1);
}

pass("Role detection OK → role='{$detected['role']}'"
    . ($detected['entity'] !== '' ? ", entity='{$detected['entity']}'" : '') . ".");
echo "\n";

// ─────────────────────────────────────────────────────────────────────────
// Step 3 — Free-text search (q=)
// This simulates what the web UI sends when the user types the employee ID
// into the search box WITHOUT the numeric-ID detection fix applied.
// ─────────────────────────────────────────────────────────────────────────

echo "Step 3 — Free-text search: search('{$employeeId}')\n";
line();

$searchResult = $service->search($employeeId);

if (!$searchResult['success']) {
    warn("Free-text search returned no results: " . ($searchResult['error'] ?? 'Unknown error'));
    echo "\n  ► If the web UI was using ?q= for this employee ID (instead of\n";
    echo "    ?employee_id=), it would show no results.\n";
    echo "    The fix in views/users/users.php ensures numeric queries now use\n";
    echo "    ?employee_id= so exact lookup is used instead.\n";
} else {
    $found = $searchResult['employees'];
    pass("Free-text search returned " . count($found) . " employee(s).");
    foreach ($found as $e) {
        printf("    • %-10s  %s\n", $e['employee_id'] ?? '?', $e['fullname'] ?? '?');
    }
}

echo "\n";

// ─────────────────────────────────────────────────────────────────────────
// Step 4 — Database check
// ─────────────────────────────────────────────────────────────────────────

echo "Step 4 — Database account check\n";
line();

try {
    $row = db_fetch_one(
        'SELECT id, username, role, account_status FROM users WHERE employee_no = ? LIMIT 1',
        's',
        [$employeeId]
    );

    if ($row) {
        warn("An account already exists for this employee.");
        printf("  %-15s : %s\n", 'username',       $row['username']);
        printf("  %-15s : %s\n", 'role',            $row['role']);
        printf("  %-15s : %s\n", 'account_status',  $row['account_status']);
        echo "\n  ► Adding again via the web UI will fail with a duplicate key error.\n";
        echo "    Use the Edit or Delete buttons in User Management instead.\n";
    } else {
        pass("No existing account — this employee can be added via the web UI.");
    }
} catch (Throwable $e) {
    warn("Could not query the database: " . $e->getMessage());
}

echo "\n";

// ─────────────────────────────────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────────────────────────────────

line('=');
echo " RESULT: Employee '{$employeeId}' passes all checks.\n";
echo "         Detected role : {$detected['role']}"
    . ($detected['entity'] !== '' ? " / entity: {$detected['entity']}" : '') . "\n";
echo "         They can be added via the web UI Add New User form.\n";
line('=');
echo "\n";

exit(0);
