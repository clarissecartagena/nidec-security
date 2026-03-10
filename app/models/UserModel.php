<?php

require_once __DIR__ . '/../../includes/db.php';

class UserModel {
    public function findByUsername(string $username) {
    // Ensure 'password_hash' is in the SELECT list
    $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function findByEmployeeId(string $employeeId): ?array {
        $user = db_fetch_one(
            'SELECT u.id, u.name, u.username, u.password_hash, u.role, u.account_status, u.department_id, u.security_type, u.building, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.employee_id = ? LIMIT 1',
            's',
            [$employeeId]
        );

        return $user ?: null;
    }
}
