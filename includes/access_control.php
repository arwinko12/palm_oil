<?php
/**
 * Access control — role-based guards, CSRF tokens and redirects.
 *
 * Every protected page should include this file and call
 * `require_role()` (or `require_logged_in()`) before rendering output.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../class/authentication.php';

/** Currently authenticated user, or null. */
function current_user()
{
    return Auth::isLoggedIn()
        ? ['id' => Auth::userId(), 'role' => Auth::role(), 'name' => Auth::name()]
        : null;
}

/** True if the logged-in role is allowed here. */
function can($roles)
{
    if (!Auth::isLoggedIn()) {
        return false;
    }
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array(Auth::role(), $roles, true);
}

/**
 * Destroy the local session when its user row no longer exists or is no
 * longer active (deleted account, re-imported database, deactivated user).
 */
function purge_stale_session()
{
    if (!Auth::isLoggedIn()) {
        return;
    }
    $db   = new Database();
    $stmt = $db->connect()->prepare(
        "SELECT 1 FROM users WHERE user_id = ? AND status = 'active' LIMIT 1"
    );
    $uid = (int) Auth::userId();
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_row()) {
        $_SESSION = [];
        session_destroy();
    }
}

/**
 * Guard a page. Redirects to the portal login when the visitor is not
 * authenticated, or back to their own home when they lack the role.
 *
 * @param array|string|null $roles allowed roles (null = any logged-in user)
 */
function require_role($roles = null)
{
    purge_stale_session();

    if (!Auth::isLoggedIn()) {
        redirect_to(EPALM_ROLE_LOGINS['farmer']); // default guest target
    }

    if ($roles !== null && !can($roles)) {
        // Authenticated but wrong area — send them to their own portal.
        redirect_to(EPALM_ROLES[Auth::role()] ?? BASE_URL . '/index.php');
    }
}

/** Convenience: any logged-in user. */
function require_logged_in()
{
    require_role(null);
}

/** Redirect helper that always exits. */
function redirect_to($url)
{
    header('Location: ' . $url);
    exit;
}

/** Build the login redirect for a specific portal. */
function login_url_for($role)
{
    return EPALM_ROLE_LOGINS[$role] ?? EPALM_ROLE_LOGINS['farmer'];
}

/** Home page for a role after successful login. */
function home_url_for($role)
{
    return EPALM_ROLES[$role] ?? BASE_URL . '/index.php';
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------

/** Get (or lazily create) the CSRF token for this session. */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to embed in forms. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Validate a submitted token. */
function csrf_verify($token)
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------------------------------------------------
// Session identity helpers — map the logged-in user to profile rows.
// (hauling data is keyed on farmer_id / driver_id, not user_id.)
// ---------------------------------------------------------------------

/** farmers.farmer_id for the logged-in user, or null. */
function current_farmer_id()
{
    if (!Auth::isLoggedIn()) {
        return null;
    }
    $db = new Database();
    $stmt = $db->connect()->prepare("SELECT farmer_id FROM farmers WHERE user_id = ? LIMIT 1");
    $uid = (int) Auth::userId();
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int) $row['farmer_id'] : null;
}

/** drivers.driver_id for the logged-in user, or null. */
function current_driver_id()
{
    if (!Auth::isLoggedIn()) {
        return null;
    }
    $db = new Database();
    $stmt = $db->connect()->prepare("SELECT driver_id FROM drivers WHERE user_id = ? LIMIT 1");
    $uid = (int) Auth::userId();
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int) $row['driver_id'] : null;
}

// ---------------------------------------------------------------------
// JSON API helpers
// ---------------------------------------------------------------------

/** Emit a JSON payload and stop. */
function json_out(array $data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/** Fail the request with a JSON error. */
function json_fail($message, $extra = [])
{
    json_out(array_merge(['ok' => false, 'error' => $message], $extra));
}

/** Succeed with optional data payload. */
function json_success($data = [])
{
    json_out(array_merge(['ok' => true, 'error' => null], $data));
}

/** Guard an API endpoint: POST + CSRF (for mutations). */
function require_post_csrf()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_fail('Method not allowed.');
    }
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        json_fail('Invalid or expired security token. Reload and try again.');
    }
}
