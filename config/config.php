<?php
/**
 * ePalmHaul — central configuration.
 * Single source of truth for DB credentials, role definitions and
 * secure PHP session bootstrap.
 */

// ---------------------------------------------------------------------
// Database connection settings (XAMPP default)
// ---------------------------------------------------------------------
define('DB_SERVER', 'localhost');
define('DB_USER',   'root');
define('DB_PASS',   '');
define('DB_NAME',   'epalmhaul_db');

// ---------------------------------------------------------------------
// Application base URL (folder this project lives under on XAMPP)
// ---------------------------------------------------------------------
define('BASE_URL', '/epalm_haul');

// ---------------------------------------------------------------------
// Roles and their landing pages (role => portal home)
// ---------------------------------------------------------------------
define('EPALM_ROLES', [
    'admin'       => BASE_URL . '/admin/index.php',
    'farmer'      => BASE_URL . '/farmer/index.php',
    'driver'      => BASE_URL . '/driver/index.php',
    'weighbridge' => BASE_URL . '/weighbridge/index.php',
]);

// Login page for each role portal
define('EPALM_ROLE_LOGINS', [
    'admin'       => BASE_URL . '/admin/login.php',
    'farmer'      => BASE_URL . '/farmer/login.php',
    'driver'      => BASE_URL . '/driver/login.php',
    'weighbridge' => BASE_URL . '/weighbridge/login.php',
]);

// ---------------------------------------------------------------------
// Secure session bootstrap (skipped on the CLI installer)
// ---------------------------------------------------------------------
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('EPALMHAUL_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,   // set true when serving over HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
