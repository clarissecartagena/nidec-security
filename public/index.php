<?php
/**
 * Front Controller — single HTTP entry point.
 *
 * Apache .htaccess rewrites all page requests here.
 * Static assets, uploads, and API files are served directly (bypassed in .htaccess).
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload dependencies via Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Load legacy configuration and helpers (for backward compatibility)
require_once __DIR__ . '/../includes/config.php';

// Create the Application instance
use App\Core\Application;

$app = new Application();

// Get router from application
$router = $app->getRouter();

// Load routes
require __DIR__ . '/../routes/web.php';

// Run the application
$app->run();
