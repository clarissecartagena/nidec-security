<?php

namespace App\Controllers;


class DepartmentActionController extends BaseController
{
    public function index(): void
    {
        require_once __DIR__ . '/../../includes/config.php';

        if (!isAuthenticated()) {
            header('Location: ' . app_url('login.php'));
            exit;
        }

        $currentUser = getUser();
        if (($currentUser['role'] ?? '') !== 'department') {
            header('Location: ' . app_url('login.php'));
            exit;
        }

        $target = app_url('assigned-reports.php');
        $building = trim((string)($_GET['building'] ?? ''));
        if (in_array($building, ['NCFL', 'NPFL', 'all'], true)) {
            $target .= (str_contains($target, '?') ? '&' : '?') . http_build_query(['building' => $building]);
        }
        header('Location: ' . $target);
        exit;
    }
}
