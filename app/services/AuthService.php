<?php

require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../api_clients/LoginApiClient.php';
require_once __DIR__ . '/AllowedUsersService.php';

class AuthService {
    /** @var UserModel */
    private $users;

    /** @var LoginApiClient */
    private $loginApi;

    /** @var AllowedUsersService */
    private $allowedUsers;

    /**
     * Employee ID prefix → role mapping.
     * Extend this map as new employee categories are introduced.
     *
     * Prefix matching is case-insensitive, longest match wins.
     */
    private const ROLE_PREFIX_MAP = [
        'B40' => 'ga_president',   // GA Manager level
        'B30' => 'ga_staff',       // GA Staff level
        'SC3' => 'security',       // Security personnel
    ];

    public function __construct(?UserModel $users = null, ?LoginApiClient $loginApi = null, ?AllowedUsersService $allowedUsers = null) {
        $this->users        = $users        ?: new UserModel();
        $this->loginApi     = $loginApi     ?: new LoginApiClient();
        $this->allowedUsers = $allowedUsers ?: new AllowedUsersService();
    }

    public function user(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    /**
     * Authenticate a user via the corporate login API.
     *
     * Flow:
     *   1. Call the corporate login API (encrypted payload).
     *   2. API returns success + employee_id on valid credentials.
     *   3. Look up the employee_id in the local users table.
     *   4. Verify the local account is active.
     *   5. Create a session using the locally-stored role and profile.
     *
     * The role is NEVER taken from POST data — it comes exclusively from
     * the local users table (set at registration time).  detectRoleFromId()
     * is a utility for registration flows and fallback logging only.
     *
     * @param  string $username  Employee ID or username sent to the login API.
     * @param  string $password  Plain-text password (encrypted before sending).
     * @return bool              True on successful authentication and session creation.
     */
    public function login(string $username, string $password): bool {
        // --- STEP 1: CHECK LOCAL DATABASE FIRST (For seeded users like k.enriquez) ---
        $user = $this->users->findByUsername($username);

        // If user exists locally and has a password hash (seeded users do)
        if ($user && !empty($user['password_hash'])) {
            // Verify the plain text 'Password123!' against the hash in seed.sql
            if (password_verify($password, $user['password_hash'])) {
                if (trim(strtolower((string)($user['account_status'] ?? ''))) === 'active') {
                    return $this->establishSession($user);
                }
            }
        }

        // --- STEP 2: CALL CORPORATE API (For everyone else) ---
        $apiResult = $this->loginApi->authenticate($username, $password);

        if (!empty($apiResult['success'])) {
            $employeeId = (string)($apiResult['employee_id'] ?? '');
            
            // Re-fetch or sync the local user profile after API success
            $user = ($employeeId !== '') ? $this->users->findByEmployeeId($employeeId) : null;
            if (!$user) {
                $user = $this->users->findByUsername($username);
            }

            // Auto-provision: if the employee is on the allowed list but does
            // not yet have a local account, create one now using their Employee
            // API profile data.
            if (!$user && $employeeId !== '' && $this->allowedUsers->isAllowed($employeeId)) {
                $user = $this->allowedUsers->provision($employeeId, $username);
            }

            if ($user && trim(strtolower((string)($user['account_status'] ?? ''))) === 'active') {
                return $this->establishSession($user);
            }
        }

        return false;
    }

    /**
     * Helper to centralize session creation logic
     */
    private function establishSession(array $user): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'              => (int)$user['id'],
            'username'        => $user['username'],
            'role'            => $user['role'],
            'displayName'     => $user['name'],
            'department'      => $user['department_name'] ?? null,
            'department_id'   => isset($user['department_id']) ? (int)$user['department_id'] : null,
            'security_type'   => $user['security_type'] ?? null,
            'building'        => $user['building'] ?? null,
        ];
        return true;
    }

    /**
     * Derive a system role from an employee ID prefix.
     *
     * Intended for use during user registration (auto-fill the role field)
     * and for diagnostic purposes.  The login flow does NOT call this —
     * it reads the role from the local users table instead.
     *
     * @param  string $employeeId  e.g. "B30-00123", "SC3-00456"
     * @return string              One of the system role values, or 'department'
     *                             when no prefix matches.
     */
    public function detectRoleFromId(string $employeeId): string {
        $upper = strtoupper(ltrim($employeeId));

        foreach (self::ROLE_PREFIX_MAP as $prefix => $role) {
            if (strncasecmp($upper, $prefix, strlen($prefix)) === 0) {
                return $role;
            }
        }

        return 'department';
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
