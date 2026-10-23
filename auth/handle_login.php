<?php
/**
 * Central AJAX login handler for all four role portals.
 * Returns JSON: { ok, error, redirect }.
 */
require_once __DIR__ . '/../includes/access_control.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['ok' => false, 'error' => '', 'redirect' => ''];

// Only accept POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $response['error'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

// CSRF
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    $response['error'] = 'Session expired. Please reload the page and try again.';
    echo json_encode($response);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';

if ($email === '' || $password === '') {
    $response['error'] = 'Email and password are required.';
    echo json_encode($response);
    exit;
}

if (!isset(EPALM_ROLES[$role])) {
    $response['error'] = 'Unknown portal.';
    echo json_encode($response);
    exit;
}

$auth   = new Auth();
$result = $auth->attempt($email, $password, $role); // enforce role match

if ($result['ok']) {
    $response['ok']       = true;
    $response['redirect'] = home_url_for($role);
} else {
    $response['error'] = $result['error'];
}

echo json_encode($response);
