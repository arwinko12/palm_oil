<?php
/**
 * Universal logout for all portals.
 * Optional ?role=farmer to control where the user lands afterwards.
 */
require_once __DIR__ . '/../includes/access_control.php';

Auth::logout();

$role = $_GET['role'] ?? '';
$dest = isset(EPALM_ROLE_LOGINS[$role]) ? EPALM_ROLE_LOGINS[$role] : BASE_URL . '/index.php';

redirect_to($dest);
