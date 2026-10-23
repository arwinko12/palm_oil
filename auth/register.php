<?php
/**
 * Public farmer self-registration.
 *
 * Security: the submitted role is IGNORED and always forced to 'farmer'
 * to prevent privilege escalation. Only the farmer portal uses this.
 * Password is stored with password_hash(); the plaintext never touches
 * the database. Returns JSON: { ok, error }.
 */
require_once __DIR__ . '/../includes/access_control.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['ok' => false, 'error' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $response['error'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    $response['error'] = 'Session expired. Please reload and try again.';
    echo json_encode($response);
    exit;
}

$full_name  = trim($_POST['full_name'] ?? '');
$email      = strtolower(trim($_POST['email'] ?? ''));
$contact_no = trim($_POST['contact_no'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';

// Validation
if ($full_name === '' || $email === '' || $contact_no === '' || $password === '') {
    $response['error'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['error'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}
if (strlen($password) < 8) {
    $response['error'] = 'Password must be at least 8 characters.';
    echo json_encode($response);
    exit;
}
if ($password !== $confirm) {
    $response['error'] = 'Passwords do not match.';
    echo json_encode($response);
    exit;
}

require_once __DIR__ . '/../class/connection.php';
$conn = (new Database())->connect();

// Duplicate email check
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $response['error'] = 'An account with this email already exists.';
    echo json_encode($response);
    exit;
}

$role    = 'farmer'; // forced
$hash    = password_hash($password, PASSWORD_DEFAULT);
$status  = 'active';

$conn->begin_transaction();
try {
    $ins = $conn->prepare(
        "INSERT INTO users (full_name, email, password, phone, role, status)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $ins->bind_param("ssssss", $full_name, $email, $hash, $contact_no, $role, $status);
    $ins->execute();
    $user_id = $conn->insert_id;

    // Placeholder farmer profile so the account has a linked record.
    $farmer = $conn->prepare(
        "INSERT INTO farmers (user_id, fullname, contact_no, farm_location, estimated_tons, distance_to_mill)
         VALUES (?, ?, ?, '', 0, 0)"
    );
    $farmer->bind_param("iss", $user_id, $full_name, $contact_no);
    $farmer->execute();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    $response['error'] = 'Could not create the account. Please try again.';
    echo json_encode($response);
    exit;
}

$response['ok']       = true;
$response['redirect'] = login_url_for('farmer');
echo json_encode($response);
