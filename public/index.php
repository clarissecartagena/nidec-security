<?php
/**
 * Front Controller — single HTTP entry point.
 *
 * Apache .htaccess rewrites all page requests here.
 * Static assets, uploads, and API files are served directly (bypassed in .htaccess).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/core/Router.php';   // also loads Request + Response

$request  = new Request();
$response = new Response();
$router   = new Router($request, $response);

require __DIR__ . '/../routes/web.php';

$router->dispatch();
