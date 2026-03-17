<?php

namespace App\Controllers;

/**
 * Employee Search API Controller
 *
 * Routes GET /api/employee_search.php through the front controller so that
 * the request is always handled within a properly-initialised PHP session
 * context (session started by public/index.php before this controller runs).
 *
 * This fixes the "Unauthorized" response that occurred when the endpoint was
 * served directly via the .htaccess API rewrite rule, which bypassed
 * index.php and could cause session data to be unavailable.
 */
class EmployeeSearchController extends BaseController
{
    public function index(): void
    {
        require __DIR__ . '/../../public/api/employee_search.php';
    }
}
