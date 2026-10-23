<?php
/**
 * Auth — secure authentication for all four ePalmHaul roles.
 *
 * Passwords are verified with password_verify() against the bcrypt /
 * argon2 hash stored in `users.password`. On success the session id is
 * regenerated to prevent session fixation.
 */
require_once __DIR__ . '/connection.php';

class Auth extends Database
{
    public function __construct()
    {
        parent::__construct();
        $this->conn = $this->connect();
    }

    /**
     * Attempt to log a user in.
     *
     * @param string      $email        submitted email
     * @param string      $password     submitted plaintext password
     * @param string|null $expectedRole if set, the account role must match
     * @return array  ['ok' => bool, 'error' => string|null, 'user' => array|null]
     */
    public function attempt($email, $password, $expectedRole = null)
    {
        $stmt = $this->conn->prepare(
            "SELECT user_id, full_name, password, role, status
             FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Same generic message for unknown email and wrong password
        // so the form cannot be used to enumerate accounts.
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->fail('Invalid email or password.');
        }

        if ($user['status'] !== 'active') {
            return $this->fail('This account is not active. Contact an administrator.');
        }

        if ($expectedRole !== null && $user['role'] !== $expectedRole) {
            return $this->fail('This account is not authorized to sign in here.');
        }

        // Success — establish an authenticated session.
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['user_id'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        // Legacy keys kept so existing farmer pages keep working.
        if ($user['role'] === 'farmer') {
            $_SESSION['farmer_id']   = (int) $user['user_id'];
            $_SESSION['farmer_name'] = $user['full_name'];
        }

        $this->touchLastLogin($user['user_id']);
        $this->logActivity($user['user_id'], 'login', 'users', $user['user_id'], 'Signed in');

        return ['ok' => true, 'error' => null, 'user' => $user];
    }

    /** Destroy the current session safely. */
    public static function logout()
    {
        if (isset($_SESSION['user_id'])) {
            $auth = new self();
            $auth->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id'], 'Signed out');
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id'], $_SESSION['role']);
    }

    public static function role()
    {
        return $_SESSION['role'] ?? null;
    }

    public static function userId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function name()
    {
        return $_SESSION['full_name'] ?? null;
    }

    private function touchLastLogin($userId)
    {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }

    /** Best-effort activity log; never blocks login or logout on failure. */
    private function logActivity($userId, $action, $table, $recordId, $details)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("isssss", $userId, $action, $table, $recordId, $details, $ip);
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            // e.g. stale session whose user row no longer exists (FK violation) —
            // logging must never prevent sign-in or sign-out.
            error_log('activity_logs write failed: ' . $e->getMessage());
        }
    }

    private function fail($message)
    {
        return ['ok' => false, 'error' => $message, 'user' => null];
    }
}
