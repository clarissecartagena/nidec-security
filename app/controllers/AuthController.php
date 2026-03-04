<?php

require_once __DIR__ . '/../services/AuthService.php';

class AuthController {
    /** @var AuthService */
    private $auth;

    public function __construct(?AuthService $auth = null) {
        $this->auth = $auth ?: new AuthService();
    }

    public function login(): void {
        $error = null;

        // Handle login submission
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role = (string)($_POST['role'] ?? 'security');

            if ($username !== '' && $password !== '' && $role !== '') {
                if ($this->auth->login($username, $password, $role)) {
                    header('Location: ' . role_landing_page($role));
                    exit();
                }

                $error = 'Invalid credentials, role selection, or inactive account.';
            } else {
                $error = 'Please enter your username, password, and role.';
            }
        }

        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user'])) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $path = rtrim(dirname((string)($_SERVER['PHP_SELF'] ?? '')), '/\\');
            $landing = role_landing_page($_SESSION['user']['role'] ?? null);
            header("Location: http://$host$path/$landing");
            exit();
        }

        $pageTitle = 'Login';
        $currentPage = 'login.php';

        require __DIR__ . '/../../views/auth/login.php';
    }

    public function logout(): void {
        $this->auth->logout();
        header('Location: login.php');
        exit();
    }
}
