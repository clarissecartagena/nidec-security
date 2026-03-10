<?php

// Role names mapping
$roleNames = [
    'security' => 'Security',
    'ga_staff' => 'General Affairs Staff',
    'ga_president' => 'General Affairs President',
    'department' => 'Department'
];

// Navigation items
$navItems = [
    // GA President module (required pages only)
    ['label' => 'Dashboard', 'path' => 'dashboard.php', 'icon' => 'layout-dashboard', 'roles' => ['ga_president']],
    ['label' => 'GA Pending Reports', 'path' => 'ga-president-approval.php', 'icon' => 'check-circle', 'roles' => ['ga_president']],
    ['label' => 'All Reports', 'path' => 'reports.php', 'icon' => 'list', 'roles' => ['ga_president']],
    ['label' => 'User Management', 'path' => 'users.php', 'icon' => 'users', 'roles' => ['ga_president']],
    ['label' => 'Statistics', 'path' => 'statistics.php', 'icon' => 'bar-chart', 'roles' => ['ga_president']],
    ['label' => 'Notifications', 'path' => 'notifications.php', 'icon' => 'history', 'roles' => ['ga_president']],
    ['label' => 'My Profile', 'path' => 'profile.php', 'icon' => 'person', 'roles' => ['ga_president']],

    // GA Staff module (required pages only)
    ['label' => 'Dashboard', 'path' => 'dashboard.php', 'icon' => 'layout-dashboard', 'roles' => ['ga_staff']],
    ['label' => 'GA Pending Reports', 'path' => 'ga-staff-review.php', 'icon' => 'clipboard-check', 'roles' => ['ga_staff']],
    ['label' => 'All Reports', 'path' => 'reports.php', 'icon' => 'list', 'roles' => ['ga_staff']],
    ['label' => 'Returned Reports', 'path' => 'returned-reports.php', 'icon' => 'history', 'roles' => ['ga_staff']],
    ['label' => 'User Management', 'path' => 'ga_staff/user_management.php', 'icon' => 'users', 'roles' => ['ga_staff']],
    ['label' => 'Statistics', 'path' => 'ga_staff/statistics.php', 'icon' => 'bar-chart', 'roles' => ['ga_staff']],
    ['label' => 'Notifications', 'path' => 'notifications.php', 'icon' => 'history', 'roles' => ['ga_staff']],
    ['label' => 'My Profile', 'path' => 'profile.php', 'icon' => 'person', 'roles' => ['ga_staff']],

    // Security module (required pages only)
    ['label' => 'Dashboard', 'path' => 'security-dashboard.php', 'icon' => 'layout-dashboard', 'roles' => ['security']],
    ['label' => 'Submit Report', 'path' => 'submit-report.php', 'icon' => 'send', 'roles' => ['security']],
    ['label' => 'Final Checking', 'path' => 'final-checking.php', 'icon' => 'clipboard-check', 'roles' => ['security']],
    ['label' => 'All Reports', 'path' => 'security-reports.php', 'icon' => 'list', 'roles' => ['security']],
    ['label' => 'Statistics', 'path' => 'security-statistics.php', 'icon' => 'bar-chart', 'roles' => ['security']],
    ['label' => 'Notifications', 'path' => 'notifications.php', 'icon' => 'history', 'roles' => ['security']],
    ['label' => 'My Profile', 'path' => 'profile.php', 'icon' => 'person', 'roles' => ['security']],

    // Department module (required pages only)
    ['label' => 'Dashboard', 'path' => 'department-dashboard.php', 'icon' => 'layout-dashboard', 'roles' => ['department']],
    ['label' => 'Assigned Reports', 'path' => 'assigned-reports.php', 'icon' => 'clipboard-check', 'roles' => ['department']],
    ['label' => 'Report History', 'path' => 'department-history.php', 'icon' => 'history', 'roles' => ['department']],
    ['label' => 'Statistics', 'path' => 'department-statistics.php', 'icon' => 'bar-chart', 'roles' => ['department']],
    ['label' => 'Notifications', 'path' => 'notifications.php', 'icon' => 'history', 'roles' => ['department']],
    ['label' => 'My Profile', 'path' => 'profile.php', 'icon' => 'person', 'roles' => ['department']],
];

// Icon SVGs
$icons = [
    // Icons are Bootstrap Icons (CDN). Keep keys stable because sidebar uses $item['icon'].
    'layout-dashboard' => '<i class="bi bi-speedometer2" aria-hidden="true"></i>',
    'send' => '<i class="bi bi-send" aria-hidden="true"></i>',
    'clipboard-check' => '<i class="bi bi-clipboard-check" aria-hidden="true"></i>',
    'check-circle' => '<i class="bi bi-check-circle" aria-hidden="true"></i>',
    'file-text' => '<i class="bi bi-file-earmark-text" aria-hidden="true"></i>',
    'shield' => '<i class="bi bi-shield-check" aria-hidden="true"></i>',
    'list' => '<i class="bi bi-list-ul" aria-hidden="true"></i>',
    'history' => '<i class="bi bi-clock-history" aria-hidden="true"></i>',
    'bar-chart' => '<i class="bi bi-bar-chart" aria-hidden="true"></i>',
    'download' => '<i class="bi bi-download" aria-hidden="true"></i>',
    'users' => '<i class="bi bi-people" aria-hidden="true"></i>',
    'person' => '<i class="bi bi-person-circle" aria-hidden="true"></i>',
];

// Report Categories
$reportCategories = [
    'Access Control',
    'Fire Safety',
    'Surveillance',
    'Hazardous Materials',
    'Theft / Loss',
    'Workplace Safety',
    'Emergency Response',
    'Other',
];

// Departments
$departments = [
    'Manufacturing',
    'IT',
    'Facilities',
    'Human Resources',
    'Quality Assurance',
    'Engineering',
    'Administration',
];

// Severity Levels
$severityLevels = ['low', 'medium', 'high', 'critical'];
