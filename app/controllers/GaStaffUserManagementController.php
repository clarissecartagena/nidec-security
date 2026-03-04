<?php

class GaStaffUserManagementController
{
    public function index(): void
    {
        $pageTitle = 'User Management';
        $requiredRole = 'ga_staff';
        $currentPage = 'ga_staff/user_management.php';

        require_once __DIR__ . '/../../includes/config.php';
        require __DIR__ . '/../../views/ga_staff/user_management.php';
    }
}
