<?php
/**
 * Application route definitions.
 *
 * Expected variables in scope (set by public/index.php):
 *   $router   – Router instance
 *   $request  – Request instance
 *   $response – Response instance
 *
 * Controllers are loaded lazily (only when their route is matched).
 * Both GET and POST are registered for every page that handles form submissions.
 */

// ─────────────────────────────────────────────
// Root  /
// ─────────────────────────────────────────────
$router->get('/', function (Request $req, Response $res): void {
    if (isset($_SESSION['user'])) {
        $res->redirect(role_landing_page($_SESSION['user']['role'] ?? null));
    }
    $res->redirect('login.php');
});

// ─────────────────────────────────────────────
// Authentication
// ─────────────────────────────────────────────
$router->get('/login.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController())->login();
});
$router->post('/login.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController())->login();
});
$router->get('/logout.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/LogoutController.php';
    (new LogoutController())->index();
});

// Legacy clean-URL aliases (kept for any direct links that omit .php)
$router->get('/login', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController())->login();
});
$router->post('/login', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    (new AuthController())->login();
});
$router->get('/logout', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/LogoutController.php';
    (new LogoutController())->index();
});

// ─────────────────────────────────────────────
// Dashboards
// ─────────────────────────────────────────────
$router->get('/dashboard.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DashboardController.php';
    (new DashboardController())->gaDashboard();
});
$router->get('/security-dashboard.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DashboardController.php';
    (new DashboardController())->securityDashboard();
});
$router->get('/department-dashboard.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DashboardController.php';
    (new DashboardController())->departmentDashboard();
});

// ─────────────────────────────────────────────
// Reports – GA / shared
// ─────────────────────────────────────────────
$router->get('/reports.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/ReportsController.php';
    (new ReportsController())->index();
});
$router->get('/returned-reports.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/ReturnedReportsController.php';
    (new ReturnedReportsController())->index();
});
$router->post('/returned-reports.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/ReturnedReportsController.php';
    (new ReturnedReportsController())->index();
});
$router->get('/ga-staff-review.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaStaffReviewController.php';
    (new GaStaffReviewController())->index();
});
$router->post('/ga-staff-review.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaStaffReviewController.php';
    (new GaStaffReviewController())->index();
});
$router->get('/ga-president-approval.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaPresidentApprovalController.php';
    (new GaPresidentApprovalController())->index();
});
$router->post('/ga-president-approval.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaPresidentApprovalController.php';
    (new GaPresidentApprovalController())->index();
});

// ─────────────────────────────────────────────
// Reports – Security
// ─────────────────────────────────────────────
$router->get('/submit-report.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/SubmitReportController.php';
    (new SubmitReportController())->index();
});
$router->post('/submit-report.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/SubmitReportController.php';
    (new SubmitReportController())->index();
});
$router->get('/final-checking.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/FinalCheckingController.php';
    (new FinalCheckingController())->index();
});
$router->post('/final-checking.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/FinalCheckingController.php';
    (new FinalCheckingController())->index();
});
$router->get('/security-reports.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/SecurityReportsController.php';
    (new SecurityReportsController())->index();
});

// ─────────────────────────────────────────────
// Reports – Department
// ─────────────────────────────────────────────
$router->get('/assigned-reports.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/AssignedReportsController.php';
    (new AssignedReportsController())->index();
});
$router->post('/assigned-reports.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/AssignedReportsController.php';
    (new AssignedReportsController())->index();
});
$router->get('/department-action.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DepartmentActionController.php';
    (new DepartmentActionController())->index();
});
$router->post('/department-action.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DepartmentActionController.php';
    (new DepartmentActionController())->index();
});
$router->get('/department-history.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DepartmentHistoryController.php';
    (new DepartmentHistoryController())->index();
});

// ─────────────────────────────────────────────
// History / Print
// ─────────────────────────────────────────────
$router->get('/history.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/HistoryController.php';
    (new HistoryController())->index();
});
$router->get('/print_report.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/PrintReportController.php';
    (new PrintReportController())->show();
});
$router->get('/view-report.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/ReportViewController.php';
    (new ReportViewController())->show();
});
$router->get('/print_report_by_no.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/PrintReportByNoController.php';
    (new PrintReportByNoController())->redirect();
});

// ─────────────────────────────────────────────
// Statistics
// ─────────────────────────────────────────────
$router->get('/statistics.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/StatisticsController.php';
    (new StatisticsController())->index();
});
$router->get('/security-statistics.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/SecurityStatisticsController.php';
    (new SecurityStatisticsController())->index();
});
$router->get('/department-statistics.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DepartmentStatisticsController.php';
    (new DepartmentStatisticsController())->index();
});

// ─────────────────────────────────────────────
// GA Staff sub-pages  (ga_staff/*)
// ─────────────────────────────────────────────
$router->get('/ga_staff/statistics.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaStaffStatisticsController.php';
    (new GaStaffStatisticsController())->index();
});
$router->get('/ga_staff/user_management.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaStaffUserManagementController.php';
    (new GaStaffUserManagementController())->index();
});
$router->post('/ga_staff/user_management.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/GaStaffUserManagementController.php';
    (new GaStaffUserManagementController())->index();
});

// ─────────────────────────────────────────────
// Users & Notifications
// ─────────────────────────────────────────────
$router->get('/users.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/UsersController.php';
    (new UsersController())->index();
});
$router->post('/users.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/UsersController.php';
    (new UsersController())->index();
});
$router->get('/notifications.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/NotificationsController.php';
    (new NotificationsController())->index();
});
$router->post('/notifications.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/NotificationsController.php';
    (new NotificationsController())->index();
});

// ─────────────────────────────────────────────
// Download
// ─────────────────────────────────────────────
$router->get('/download.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DownloadController.php';
    (new DownloadController())->index();
});
$router->post('/download.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/DownloadController.php';
    (new DownloadController())->index();
});

// ─────────────────────────────────────────────
// 404  (direct access to legacy 404.php URL)
// ─────────────────────────────────────────────
$router->get('/404.php', function (Request $req, Response $res): void {
    require_once __DIR__ . '/../app/controllers/NotFoundController.php';
    http_response_code(404);
    (new NotFoundController())->index();
});
