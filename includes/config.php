<?php
// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/status_helper.php';

// Base URL helper (supports pages in subfolders like ga_staff/*)
if (!defined('APP_BASE_URL')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptName = str_replace('\\', '/', $scriptName);
    $parts = explode('/', trim($scriptName, '/'));
    $projectDir = basename(dirname(__DIR__));
    $baseUrl = '';
    if (!empty($parts) && $parts[0] === $projectDir) {
        // If accessed through .../public/* (common on XAMPP with project under htdocs),
        // ensure generated URLs include /public so assets and links resolve.
        $baseUrl = '';
        if (preg_match('#^(.*?)/public(?:/|$)#', $scriptName, $m)) {
            $baseUrl = rtrim($m[1], '/') . '/public';
        } else {
            // Fallback: if project lives under /<projectDir>/..., keep that base.
            $parts = explode('/', trim($scriptName, '/'));
            $projectDir = basename(dirname(__DIR__));
            if (!empty($parts) && $parts[0] === $projectDir) {
                $baseUrl = '/' . $projectDir;
            }
        }
    } elseif (!empty($parts) && $parts[0] === 'public') {
        // Alternate deployment: app served from /public/* under the server root
        $baseUrl = '/public';
    }
    define('APP_BASE_URL', $baseUrl);
}

function app_url(string $path = ''): string {
    // Pass through absolute URLs
    if (preg_match('#^(https?:)?//#i', $path)) return $path;
    $path = ltrim($path, '/');
    if ($path === '') return APP_BASE_URL . '/';
    return APP_BASE_URL . '/' . $path;
}

// Helper function to check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user']);
}

// Helper function to get current user
function getUser() {
    return $_SESSION['user'] ?? null;
}

// Helper function to check user role
function hasRole($role) {
    $user = getUser();
    return $user && $user['role'] === $role;
}

function normalize_building($building): ?string {
    $b = strtoupper(trim((string)$building));
    if ($b === '' || $b === 'ALL') return null;
    if (in_array($b, ['NCFL', 'NPFL'], true)) return $b;
    return null;
}

/**
 * Returns the building filter that should be applied to data queries.
 * - Security: always forced to their assigned building.
 * - Other roles: optional, derived from request param `building` (GET/POST).
 */
function get_effective_building_filter(): ?string {
    $user = getUser();
    $role = (string)($user['role'] ?? '');

    if ($role === 'security') {
        return normalize_building($user['building'] ?? null);
    }

    // Allow filters on both GET and POST for pages/APIs
    $requested = $_GET['building'] ?? ($_POST['building'] ?? null);
    return normalize_building($requested);
}


