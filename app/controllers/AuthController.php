<?php
// AuthController.php

require_once __DIR__ . '/../services/AuthService.php';

class AuthController {
    private $auth;

    public function __construct(?AuthService $auth = null) {
        $this->auth = $auth ?: new AuthService();
    }

    public function login(): void {
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($username !== '' && $password !== '') {
                // The Controller calls the Service
                if ($this->auth->login($username, $password)) {
                    $role = $_SESSION['user']['role'] ?? null;
                    header('Location: ' . role_landing_page($role));
                    exit();
                }
                $error = 'Invalid credentials or inactive account.';
            } else {
                $error = 'Please enter your username and password.';
            }
        }

        require __DIR__ . '/../../views/auth/login.php';
    }

    public function logout(): void {
        $this->auth->logout();
        header('Location: login.php');
        exit();
    }
}