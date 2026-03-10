<?php

require_once __DIR__ . '/EmployeeService.php';
require_once __DIR__ . '/../models/UsersModel.php';

/**
 * AllowedUsersService
 * ──────────────────────────────────────────────────────────────────────────
 * Manages the list of employee IDs that are permitted to log in.
 *
 * When an employee in the allowed list authenticates via the corporate login
 * API but does not yet have a local account, this service:
 *   1. Looks up their profile data in the Employee API.
 *   2. Inserts a new active account in the local `users` table.
 *   3. Returns the newly created user record.
 *
 * The allowed list is configured in config/allowed_users.php.
 *
 * @see config/allowed_users.php
 * @see EmployeeService
 * @see UsersModel
 */
class AllowedUsersService
{
    /** @var array<int, array<string,mixed>> Loaded from config/allowed_users.php */
    private array $allowed;

    private EmployeeService $employeeService;
    private UsersModel      $usersModel;

    public function __construct(
        ?EmployeeService $employeeService = null,
        ?UsersModel      $usersModel      = null
    ) {
        $this->employeeService = $employeeService ?? new EmployeeService();
        $this->usersModel      = $usersModel      ?? new UsersModel();

        $configPath = __DIR__ . '/../../config/allowed_users.php';
        $this->allowed = is_file($configPath) ? (array)(require $configPath) : [];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Returns true when the given employee ID is in the allowed list.
     */
    public function isAllowed(string $employeeId): bool
    {
        return $this->getConfig($employeeId) !== null;
    }

    /**
     * Returns the role configuration for an allowed employee, or null when
     * the employee ID is not in the list.
     *
     * @return array{employee_id:string, role:string, security_type:?string, building:?string, department_id:?int}|null
     */
    public function getConfig(string $employeeId): ?array
    {
        $employeeId = trim($employeeId);
        foreach ($this->allowed as $entry) {
            if (isset($entry['employee_id']) && (string)$entry['employee_id'] === $employeeId) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Provision a new local user account for an allowed employee.
     *
     * Steps:
     *   1. Verify the employee_id is in the allowed list.
     *   2. Fetch the employee's profile from the Employee API.
     *   3. Insert a new active account into the local `users` table.
     *   4. Return the newly created user record (ready for session creation).
     *
     * The username used is:
     *   - The 'username' value from config/allowed_users.php if present, OR
     *   - The $username parameter passed in (from the corporate API login attempt).
     *
     * The password stored is:
     *   - A bcrypt hash of the 'password' value from the config if present, OR
     *   - An empty hash (only corporate API login will work).
     *
     * Returns null when:
     *   • The employee_id is not in the allowed list.
     *   • The Employee API cannot be reached or the employee is not found.
     *   • Inserting the record fails (e.g., duplicate username).
     *
     * @param  string $employeeId  The employee ID returned by the login API.
     * @param  string $username    The username the employee used to log in (fallback).
     * @return array<string,mixed>|null  User record on success, null on failure.
     */
    public function provision(string $employeeId, string $username): ?array
    {
        $config = $this->getConfig($employeeId);
        if ($config === null) {
            return null;
        }

        // Prefer the username from the config; fall back to the login-time username.
        $resolvedUsername = trim((string)($config['username'] ?? ''));
        if ($resolvedUsername === '') {
            $resolvedUsername = $username;
        }

        // Hash the configured test password, or leave empty (API-only auth).
        $plainPassword = (string)($config['password'] ?? '');
        $passwordHash  = $plainPassword !== '' ? password_hash($plainPassword, PASSWORD_DEFAULT) : '';

        // Fetch profile from the Employee API.
        $empResult = $this->employeeService->getEmployee($employeeId);
        if (!$empResult['success'] || empty($empResult['employee'])) {
            return null;
        }
        $emp = $empResult['employee'];

        $role         = (string)($config['role']          ?? 'department');
        $securityType = $config['security_type'] ?? null;
        $building     = $config['building']      ?? null;
        $departmentId = $config['department_id'] ?? null;

        try {
            $this->usersModel->insertUser(
                (string)($emp['employee_id'] ?? $employeeId),
                (string)($emp['fullname']    ?? ''),
                (string)($emp['email']       ?? ''),
                (string)($emp['position']    ?? ''),
                $resolvedUsername,
                $passwordHash,
                $role,
                (string)($securityType ?? ''),
                (string)($building     ?? ''),
                (int)($departmentId    ?? 0),
                'active'
            );
        } catch (Throwable $e) {
            // Insertion failed (e.g., duplicate username or employee_id).
            return null;
        }

        // Return the freshly inserted record so the caller can open a session.
        return $this->usersModel->findProvisionedUser($employeeId, $resolvedUsername);
    }
}
