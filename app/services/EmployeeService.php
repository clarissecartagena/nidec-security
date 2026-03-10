<?php

/**
 * EmployeeService
 *
 * Business-logic layer between controllers / AJAX endpoints and the raw
 * EmployeeApiClient.  Handles input validation, delegates to the client,
 * and returns consistent result envelopes to callers.
 *
 * Methods
 * ───────
 * search($query)        – Free-text search returning a list of employees.
 * getEmployee($id)      – Exact lookup by employee_id; used to re-verify
 *                         employee data at form-submission time so that a
 *                         malicious actor cannot spoof name/department by
 *                         crafting a POST body.
 *
 * @see EmployeeApiClient
 */

require_once __DIR__ . '/../api_clients/EmployeeApiClient.php';

class EmployeeService
{
    private EmployeeApiClient $client;

    public function __construct(?EmployeeApiClient $client = null)
    {
        $this->client = $client ?? new EmployeeApiClient();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Search employees by free-text query (name fragment or employee ID).
     *
     * Returns up to however many records the company API returns. The caller
     * is responsible for presenting / paginating the list.
     *
     * @return array{
     *   success:    bool,
     *   employees:  list<array<string,string>>,
     *   error:      ?string,
     *   using_mock: bool
     * }
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [
                'success'    => false,
                'employees'  => [],
                'error'      => 'Search query must be at least 2 characters.',
                'using_mock' => $this->client->isUsingMock(),
            ];
        }

        $result = $this->client->searchEmployees($query);

        return [
            'success'    => $result['success'],
            'employees'  => $result['data'],
            'error'      => $result['error'],
            'using_mock' => $this->client->isUsingMock(),
        ];
    }

    /**
     * Verify and retrieve a single employee by exact employee_id.
     *
     * Always called at form-submission time so that name / department / email
     * / position data is sourced from the authoritative API rather than from a
     * POST body that could have been tampered with.
     *
     * @return array{
     *   success:  bool,
     *   employee: ?array<string,string>,
     *   error:    ?string
     * }
     */
    public function getEmployee(string $employeeId): array
    {
        $employeeId = trim($employeeId);

        if ($employeeId === '') {
            return ['success' => false, 'employee' => null, 'error' => 'Employee ID is required.'];
        }

        $result = $this->client->getEmployeeById($employeeId);

        return [
            'success'  => $result['success'],
            'employee' => $result['data'] ?? null,
            'error'    => $result['error'],
        ];
    }
}
