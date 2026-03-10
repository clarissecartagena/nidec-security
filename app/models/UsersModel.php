<?php

class UsersModel
{
    public function getUserRoleById(int $id): ?string
    {
        $row = db_fetch_one('SELECT role FROM users WHERE id=? LIMIT 1', 'i', [$id]);
        if (!$row) return null;
        return (string)($row['role'] ?? '');
    }

    /**
     * Insert a new user whose identity has been verified via the Employee API.
     *
     * $employeeId, $name, $email, $position come from the API response — they
     * are never sourced from raw POST data to prevent spoofing.
     */
    public function insertUser(
        string $employeeId,
        string $name,
        string $email,
        string $position,
        string $username,
        string $passwordHash,
        string $role,
        string $securityType,
        string $building,
        int    $departmentId,
        string $accountStatus
    ): void {
        db_execute(
            'INSERT INTO users
                 (employee_id, name, email, position, username, password_hash,
                  role, security_type, building, department_id, account_status)
             VALUES
                 (NULLIF(?,\'\'), ?, NULLIF(?,\'\'), NULLIF(?,\'\'),
                  ?, ?, ?,
                  NULLIF(?,\'\'), NULLIF(?,\'\'), NULLIF(?,0), ?)',
            '',
            [
                $employeeId, $name, $email, $position,
                $username, $passwordHash, $role,
                $securityType, $building, $departmentId, $accountStatus,
            ]
        );
    }

    public function updateUserWithPassword(
        int $id,
        string $name,
        string $username,
        string $passwordHash,
        string $role,
        string $securityType,
        string $building,
        int $departmentId,
        string $accountStatus
    ): void {
        db_execute(
            'UPDATE users SET name=?, username=?, password_hash=?, role=?, security_type=NULLIF(?,\'\'), building=NULLIF(?,\'\'), department_id=NULLIF(?,0), account_status=? WHERE id=?',
            'ssssssisi',
            [$name, $username, $passwordHash, $role, $securityType, $building, $departmentId, $accountStatus, $id]
        );
    }

    public function updateUserNoPassword(
        int $id,
        string $name,
        string $username,
        string $role,
        string $securityType,
        string $building,
        int $departmentId,
        string $accountStatus
    ): void {
        db_execute(
            'UPDATE users SET name=?, username=?, role=?, security_type=NULLIF(?,\'\'), building=NULLIF(?,\'\'), department_id=NULLIF(?,0), account_status=? WHERE id=?',
            'sssssisi',
            [$name, $username, $role, $securityType, $building, $departmentId, $accountStatus, $id]
        );
    }

    public function deleteUserById(int $id): void
    {
        db_execute('DELETE FROM users WHERE id=?', 'i', [$id]);
    }

    /**
     * Look up a just-provisioned user by employee_id or username.
     * Returns the full record (including department_name join) needed for
     * session creation, or null when no matching row is found.
     *
     * @return array<string,mixed>|null
     */
    public function findProvisionedUser(string $employeeId, string $username): ?array
    {
        // Try by employee_id first (most reliable).
        $user = db_fetch_one(
            'SELECT u.id, u.employee_id, u.name, u.email, u.position,
                    u.username, u.password_hash, u.role, u.account_status,
                    u.department_id, u.security_type, u.building,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.employee_id = ? LIMIT 1',
            's',
            [$employeeId]
        );

        if ($user) {
            return $user;
        }

        // Fall back to username lookup.
        return db_fetch_one(
            'SELECT u.id, u.employee_id, u.name, u.email, u.position,
                    u.username, u.password_hash, u.role, u.account_status,
                    u.department_id, u.security_type, u.building,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.username = ? LIMIT 1',
            's',
            [$username]
        ) ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllUsers(): array
    {
        return db_fetch_all(
            'SELECT u.id, u.employee_id, u.name, u.email, u.position,
                    u.username, u.role, u.security_type, u.building,
                    u.department_id, u.account_status,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             ORDER BY u.created_at DESC'
        );
    }
}
