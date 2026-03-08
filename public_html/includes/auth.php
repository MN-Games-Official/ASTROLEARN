<?php
/**
 * ASTROLEARN – Authentication Helpers
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Start a secure session if not already active.
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => APP_ENV === 'production',
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_start();
}

/**
 * Hash a password using bcrypt.
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/**
 * Verify a password against its hash.
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Attempt to register a new user. Returns the new user ID on success, or
 * an error string on failure.
 *
 * @return int|string
 */
function registerUser(string $firstName, string $lastName, string $email, string $password, string $role = 'student', ?int $schoolId = null) {
    $db = getDB();

    // Check for existing email
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return 'A user with that email already exists.';
    }

    $hash = hashPassword($password);
    $stmt = $db->prepare(
        'INSERT INTO users (school_id, role, first_name, last_name, email, password_hash)
         VALUES (:school_id, :role, :first_name, :last_name, :email, :password_hash)'
    );
    $stmt->execute([
        ':school_id'     => $schoolId,
        ':role'          => $role,
        ':first_name'    => $firstName,
        ':last_name'     => $lastName,
        ':email'         => $email,
        ':password_hash' => $hash,
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Attempt to log a user in. Returns the user row on success, or false.
 *
 * @return array|false
 */
function loginUser(string $email, string $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !verifyPassword($password, $user['password_hash'])) {
        return false;
    }

    // Update last login
    $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id')
       ->execute([':id' => $user['id']]);

    // Store in session
    initSession();
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];

    return $user;
}

/**
 * Log the current user out.
 */
function logoutUser(): void {
    initSession();
    $_SESSION = [];
    session_destroy();
}

/**
 * Get the current authenticated user, or null.
 */
function currentUser(): ?array {
    initSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Require authentication. Redirects to login page if not logged in.
 */
function requireAuth(): array {
    $user = currentUser();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

/**
 * Require a specific role. Sends 403 if the user lacks the role.
 */
function requireRole(string ...$roles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
    return $user;
}
