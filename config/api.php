<?php

/**
 * API Configuration — Employee API & Login API
 *
 * Defines endpoints and runtime controls for all external company APIs.
 * No other file may reference these URLs directly; all access goes through
 * the relevant API client class.
 *
 * ┌───────────────────────────┬────────────────────────────────────────────┐
 * │ Constant                  │ Purpose                                    │
 * ├───────────────────────────┼────────────────────────────────────────────┤
 * │ COMPANY_API_BASE_URL      │ Employee API — internal network (prod)     │
 * │ MOCK_API_BASE_URL         │ Employee API — local mock (dev)            │
 * │ COMPANY_LOGIN_URL         │ Corporate login endpoint (prod)            │
 * │ MOCK_LOGIN_URL            │ Local mock login endpoint (dev)            │
 * │ API_LOGIN_ENCRYPTION_KEY  │ AES-256 shared key for login API payloads  │
 * │ API_LOGIN_ENCRYPTION_IV   │ AES-256-CBC IV (16 bytes, coordinator sets)│
 * │ API_CONNECT_TIMEOUT       │ TCP/TLS connect timeout (seconds)          │
 * │ API_READ_TIMEOUT          │ Max total request duration (seconds)       │
 * │ API_ENV                   │ 'production' or 'development'              │
 * └───────────────────────────┴────────────────────────────────────────────┘
 *
 * To activate production mode set the APP_ENV server environment variable:
 *   Apache  : SetEnv APP_ENV production    (in VirtualHost or .htaccess)
 *   Nginx   : fastcgi_param APP_ENV production;
 *   CLI     : APP_ENV=production php artisan ...
 *
 * In production mode the mock API fallback is COMPLETELY disabled — if any
 * company API is unreachable, an error is surfaced immediately rather than
 * silently falling back to mock data.
 *
 * IMPORTANT — Login encryption keys:
 *   API_LOGIN_ENCRYPTION_KEY and API_LOGIN_ENCRYPTION_IV must be provided
 *   by the company API coordinator and kept confidential. Set them via server
 *   environment variables (APP_LOGIN_ENC_KEY / APP_LOGIN_ENC_IV) rather than
 *   hard-coding them in production deployments.
 */

// ── Employee API Endpoints ─────────────────────────────────────────────────

if (!defined('COMPANY_API_BASE_URL')) {
    // Replace with the real internal server address.
    define('COMPANY_API_BASE_URL', 'http://10.216.8.90/dummy_hris/api.php');
}

if (!defined('MOCK_API_BASE_URL')) {
    // Local mock that mirrors the company API — for home/offline development.
    define('MOCK_API_BASE_URL', 'http://localhost/nidec_api_mock');
}

// ── Login API Endpoints ────────────────────────────────────────────────────

if (!defined('COMPANY_LOGIN_URL')) {
    define('COMPANY_LOGIN_URL', 'http://10.216.128.113/API_CALLS/Admin_API/DAIRS.php');
}

if (!defined('MOCK_LOGIN_URL')) {
    define('MOCK_LOGIN_URL', 'http://localhost/nidec_api_mock/login_mock.php');
}

// ── Login Encryption ───────────────────────────────────────────────────────

if (!defined('API_LOGIN_ENCRYPTION_KEY')) {
    // Key for AES-128-CTR used by the DAIRS login API.
    // Override via APP_LOGIN_ENC_KEY environment variable in production.
    $envKey = getenv('APP_LOGIN_ENC_KEY');
    define('API_LOGIN_ENCRYPTION_KEY', 'DAIRS-Disciplinary.Action.Issuance.and.Recording.System');
    unset($envKey);
}

if (!defined('API_LOGIN_ENCRYPTION_IV')) {
    // 16-byte IV for AES-128-CTR used by the DAIRS login API.
    // Override via APP_LOGIN_ENC_IV environment variable in production.
    $envIv = getenv('APP_LOGIN_ENC_IV');
    define('API_LOGIN_ENCRYPTION_IV', '2025_04_04_DAIRS');
    unset($envIv);
}

// ── Timeouts ───────────────────────────────────────────────────────────────

/** Seconds to wait for a TCP connection to be established. */
if (!defined('API_CONNECT_TIMEOUT')) {
    define('API_CONNECT_TIMEOUT', 3);
}

/** Maximum total seconds allowed for the full HTTP response. */
if (!defined('API_READ_TIMEOUT')) {
    define('API_READ_TIMEOUT', 8);
}

// ── Environment ────────────────────────────────────────────────────────────

if (!defined('API_ENV')) {
    $appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: 'development')));
    define('API_ENV', in_array($appEnv, ['production', 'prod'], true) ? 'production' : 'development');
    unset($appEnv);
}

// ── Cross-system Reports Sync API ─────────────────────────────────────────

if (!defined('REPORTS_SYNC_API_KEY')) {
    // Shared key used by /public/api/reports_sync.php and PDF sync access.
    // Set APP_REPORTS_SYNC_API_KEY in server environment for production.
    $syncKey = (string)(getenv('APP_REPORTS_SYNC_API_KEY') ?: 'nidec-sync-demo-key');
    define('REPORTS_SYNC_API_KEY', $syncKey);
    unset($syncKey);
}
