<?php
/**
 * setup_admin.php — secure first-administrator installer.
 *
 * Run once from the command line to create the initial admin account.
 * The password is prompted (hidden where supported) and stored only as
 * a password_hash() value — no credentials are hardcoded in this file.
 *
 * Usage:
 *   php setup_admin.php "Full Name" "admin@example.com"
 *   (you will then be prompted for the password)
 */
require_once __DIR__ . '/class/connection.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This installer must be run from the command line:\n  php setup_admin.php \"Name\" \"email@example.com\"\n");
}

$args    = array_slice($argv, 1);
$name    = trim($args[0] ?? '');
$email   = strtolower(trim($args[1] ?? ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Usage: php setup_admin.php \"Full Name\" \"valid@email.com\"\n");
}

$conn = (new Database())->connect();

// Refuse to silently overwrite an existing admin on the same email.
$check = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    exit("An account already exists for {$email}. No changes made.\n");
}

echo "Password for {$email} (min 8 chars): ";
$password = read_password();

if (strlen($password) < 8) {
    exit("\nPassword too short (minimum 8 characters). No changes made.\n");
}

$hash   = password_hash($password, PASSWORD_DEFAULT);
$role   = 'admin';
$status = 'active';

$stmt = $conn->prepare(
    "INSERT INTO users (full_name, email, password, role, status)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssss", $name, $email, $hash, $role, $status);

if ($stmt->execute()) {
    echo "\nAdministrator created successfully.\n";
    echo "  Name : {$name}\n  Email: {$email}\n";
    echo "You can now sign in at: " . EPALM_ROLE_LOGINS['admin'] . "\n";
} else {
    echo "\nFailed to create administrator: " . $conn->error . "\n";
}

/**
 * Read a password from the terminal, hiding input when possible.
 */
function read_password()
{
    if (function_exists('readline')) {
        // On Windows the console echo cannot easily be disabled; fall back.
        return trim(STDIN ? fgets(STDIN) : readline(''));
    }
    return trim(fgets(STDIN));
}
