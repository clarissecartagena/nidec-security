<?php

class ProfileController
{
    public function index(): void
    {
        $pageTitle   = 'My Profile';
        $currentPage = 'profile.php';

        require_once __DIR__ . '/../../includes/config.php';

        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit;
        }

        $currentUser = getUser();
        $employeeNo  = (string)($currentUser['employee_no'] ?? '');

        $flash     = null;
        $flashType = 'success';

        // Fetch fresh user data from DB (session may not have email/signature_path)
        $dbUser = db_fetch_one(
            'SELECT employee_no, name, email, signature_path, role, username, position, department FROM users WHERE employee_no = ? LIMIT 1',
            's',
            [$employeeNo]
        );

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = (string)($_POST['csrf_token'] ?? '');
            if (!csrf_validate($token)) {
                $flash     = 'Security check failed. Please refresh and try again.';
                $flashType = 'error';
            } else {
                $action = (string)($_POST['action'] ?? '');

                if ($action === 'update_email') {
                    $newEmail = trim((string)($_POST['email'] ?? ''));
                    if ($newEmail === '') {
                        $flash     = 'Email address cannot be empty.';
                        $flashType = 'error';
                    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $flash     = 'Please enter a valid email address.';
                        $flashType = 'error';
                    } else {
                        db_execute(
                            'UPDATE users SET email = ? WHERE employee_no = ? LIMIT 1',
                            'ss',
                            [$newEmail, $employeeNo]
                        );
                        // Refresh db user
                        $dbUser['email'] = $newEmail;
                        $flash     = 'Email updated successfully.';
                        $flashType = 'success';
                    }
                }
            }

            // Re-fetch after possible update
            $dbUser = db_fetch_one(
                'SELECT employee_no, name, email, signature_path, role, username, position, department FROM users WHERE employee_no = ? LIMIT 1',
                's',
                [$employeeNo]
            );
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/profile/profile.php';
    }
}
