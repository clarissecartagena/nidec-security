<?php

require_once __DIR__ . '/../services/DashboardService.php';

class DashboardController
{
    private DashboardService $service;

    public function __construct(?DashboardService $service = null)
    {
        $this->service = $service ?: new DashboardService();
    }

    public function gaDashboard(): void
    {
        $pageTitle = 'Dashboard';
        $requiredRole = ['ga_president', 'ga_staff'];
        $currentPage = 'dashboard.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $user = getUser();
        $userRole = (string)($user['role'] ?? '');

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $presidentStats = null;
        $presidentRecent = [];
        $presidentPending = [];

        $gaStaffCounts = null;
        $gaStaffWaiting = [];
        $gaStaffReturned = [];

        if ($userRole === 'ga_president') {
            $data = $this->service->getGaPresidentDashboardData($buildingFilter);
            $presidentStats   = $data['stats'];
            $presidentRecent  = $data['recent'];
            $presidentPending = $data['pending'];
        } elseif ($userRole === 'ga_staff') {
            $data = $this->service->getGaStaffDashboardData($buildingFilter);
            $gaStaffCounts = $data['counts'];
            $gaStaffWaiting = $data['waiting'];
            $gaStaffReturned = $data['returned'];
        }

        require __DIR__ . '/../../views/dashboard/ga_dashboard.php';
    }

    public function securityDashboard(): void
    {
        $pageTitle = 'Security Dashboard';
        $requiredRole = 'security';
        $currentPage = 'security-dashboard.php';

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        $currentUser = getUser();
        $uid = (int)($currentUser['id'] ?? 0);

        $data = $this->service->getSecurityDashboardData($uid);
        $stats        = $data['stats'];
        $recent       = $data['recent'];
        $finalChecks  = $data['final_checks'];

        require __DIR__ . '/../../views/dashboard/security_dashboard.php';
    }

    public function departmentDashboard(): void
    {
        $pageTitle = 'Department Dashboard';
        $requiredRole = 'department';
        $currentPage = 'department-dashboard.php';

        require_once __DIR__ . '/../../includes/config.php';

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'department') {
            header('Location: login.php');
            exit;
        }

        $deptId = (int)($currentUser['department_id'] ?? 0);

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        if ($deptId <= 0) {
            http_response_code(500);
            die('Department account is missing a department assignment.');
        }

        $buildingFilter = get_effective_building_filter();
        $selectedBuilding = $buildingFilter ?? 'all';

        $data = $this->service->getDepartmentDashboardData($deptId, $buildingFilter);
        $stats        = $data['stats'];
        $recent       = $data['recent'];
        $needsAction  = $data['needs_action'];

        require __DIR__ . '/../../views/dashboard/department_dashboard.php';
    }
}
