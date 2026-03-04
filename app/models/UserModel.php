<?php

require_once __DIR__ . '/../../includes/db.php';

class UserModel {
    public function findByUsername(string $username): ?array {
        $user = db_fetch_one(
            'SELECT u.id, u.name, u.username, u.password_hash, u.role, u.account_status, u.department_id, u.security_type, u.building, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.username = ? LIMIT 1',
            's',
            [$username]
        );

        return $user ?: null;
    }
}
