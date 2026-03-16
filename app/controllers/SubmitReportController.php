<?php

namespace App\Controllers;


require_once __DIR__ . '/../services/SubmitReportService.php';

class SubmitReportController extends BaseController
{
    private \SubmitReportService $service;

    public function __construct(?\SubmitReportService $service = null)
    {
        $this->service = $service ?: new \SubmitReportService();
    }

    public function index(): void
    {
        $pageTitle = 'Submit Security Report';
        $requiredRole = 'security';
        $currentPage = 'submit-report.php';

        require_once __DIR__ . '/../../includes/config.php';

        $flash = null;
        $flashType = 'success';
        $successReportNo = null;

        $currentUser = getUser();
        if (!isAuthenticated() || ($currentUser['role'] ?? '') !== 'security') {
            header('Location: ' . app_url('login.php'));
            exit;
        }

        $uid = (string)($currentUser['employee_no'] ?? '');
        $userBuilding = (string)($currentUser['entity'] ?? '');
        if (!in_array($userBuilding, ['NCFL', 'NPFL'], true)) {
            $flash = 'Your account is missing an assigned building. Please contact General Affairs.';
            $flashType = 'error';
        }

        $departmentsDb = fetch_departments();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$flash) {
            $publicDirFs = realpath(__DIR__ . '/../../public') ?: (__DIR__ . '/../../public');
            $res = $this->service->handlePost($_POST, $_FILES, $uid, $userBuilding, $publicDirFs);
            $flash = $res['flash'];
            $flashType = $res['flashType'];
            $successReportNo = $res['successReportNo'];
        }

        require_once __DIR__ . '/../../includes/header.php';
        require_once __DIR__ . '/../../includes/sidebar.php';
        require_once __DIR__ . '/../../includes/topnav.php';

        require __DIR__ . '/../../views/reports/submit_report.php';
    }
}
