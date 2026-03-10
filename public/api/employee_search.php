<?php

/**
 * Employee Search API Endpoint
 *
 * Method  : GET
 * Auth    : Session — roles ga_president or ga_staff only
 *
 * Query params (mutually exclusive; employee_id takes precedence):
 *   employee_id  Exact employee ID lookup → returns 0 or 1 record.
 *   q            Free-text search query (min 2 chars) → returns 0..N records.
 *
 * Response (JSON):
 * {
 *   "success"    : true | false,
 *   "employees"  : [ { employee_id, fullname, department, position, email }, … ],
 *   "error"      : null | "human readable message",
 *   "using_mock" : true | false
 * }
 *
 * This is a READ-ONLY endpoint — it never modifies any database.
 * The company Employee API database is never written to by this system.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../app/services/EmployeeService.php';

// ── Always emit JSON ────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
// Prevent downstream caching of sensitive employee lookup results.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// ── Authentication guard ────────────────────────────────────────────────────
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'employees' => [], 'error' => 'Unauthorized.', 'using_mock' => false]);
    exit;
}

$currentUser  = getUser();
$allowedRoles = ['ga_president', 'ga_staff'];

if (!in_array($currentUser['role'] ?? '', $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'employees' => [], 'error' => 'Forbidden.', 'using_mock' => false]);
    exit;
}

// ── Only GET is accepted ────────────────────────────────────────────────────
if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'employees' => [], 'error' => 'Method not allowed.', 'using_mock' => false]);
    exit;
}

// ── Input ───────────────────────────────────────────────────────────────────
$employeeId = trim((string)($_GET['employee_id'] ?? ''));
$query      = trim((string)($_GET['q'] ?? ''));

// ── Dispatch ────────────────────────────────────────────────────────────────
try {
    $service = new EmployeeService();

    if ($employeeId !== '') {
        // Exact lookup by employee_id.
        $result = $service->getEmployee($employeeId);
        echo json_encode([
            'success'    => $result['success'],
            'employees'  => $result['success'] && $result['employee'] !== null
                ? [$result['employee']]
                : [],
            'error'      => $result['error'],
            'using_mock' => false,
        ]);
    } elseif ($query !== '') {
        // Free-text search.
        $result = $service->search($query);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode([
            'success'    => false,
            'employees'  => [],
            'error'      => 'Provide either a search query (q=...) or an employee_id.',
            'using_mock' => false,
        ]);
    }
} catch (Throwable $e) {
    // Never leak internal details in response body.
    http_response_code(500);
    echo json_encode([
        'success'    => false,
        'employees'  => [],
        'error'      => 'An unexpected server error occurred. Please try again.',
        'using_mock' => false,
    ]);
}
