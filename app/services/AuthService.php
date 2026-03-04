<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthService {
    /** @var UserModel */
    private $users;

    public function __construct(?UserModel $users = null) {
        $this->users = $users ?: new UserModel();
    }

    public function user(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public function login(string $username, string $password, string $role): bool {
        $user = $this->users->findByUsername($username);

        if (!$user) {
            return false;
        }

        if (trim(strtolower((string)($user['account_status'] ?? ''))) !== 'active') {
            return false;
        }

        if (strtolower(trim($role)) !== strtolower(trim((string)($user['role'] ?? '')))) {
            return false;
        }

        if (!password_verify($password, (string)($user['password_hash'] ?? ''))) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'displayName' => $user['name'],
            'department' => $user['department_name'],
            'department_id' => $user['department_id'] !== null ? (int)$user['department_id'] : null,
            'department_name' => $user['department_name'],
            'security_type' => $user['security_type'] ?? null,
            'building' => $user['building'] ?? null,
        ];

        return true;
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
