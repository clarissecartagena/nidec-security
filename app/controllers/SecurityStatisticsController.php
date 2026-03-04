<?php

class SecurityStatisticsController
{
    public function index(): void
    {
        $pageTitle = 'Statistics';
        $requiredRole = 'security';
        $currentPage = 'security-statistics.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'security') {
            header('Location: login.php');
            exit;
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/statistics/security_statistics.php';
    }
}
