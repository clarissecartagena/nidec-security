<?php

require_once __DIR__ . '/../../includes/db.php';

class UserModel {
    public function findByUsername(string $username): ?array {
        $user = db_fetch_one(
            'SELECT u.id, u.employee_no, u.name, u.email, u.position,
                    u.username, u.password_hash, u.role, u.account_status,
                    u.department_id, u.security_type, u.entity,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.username = ? LIMIT 1',
            's',
            [$username]
        );
        return $user ?: null;
    }

    public function findByEmployeeNo(string $employeeNo): ?array {
        $user = db_fetch_one(
            'SELECT u.id, u.employee_no, u.name, u.email, u.position,
                    u.username, u.password_hash, u.role, u.account_status,
                    u.department_id, u.security_type, u.entity,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.employee_no = ? LIMIT 1',
            's',
            [$employeeNo]
        );
        return $user ?: null;
    }
}

