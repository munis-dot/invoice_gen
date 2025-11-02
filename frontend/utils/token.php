<?php
/**
 * Generate, store, and verify CSRF/session tokens securely.
 * Compatible with existing AJAX logic.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a strong random token.
 *
 * @param int $length Length of the token in bytes (default 32 = 64 hex chars)
 * @return string Hex-encoded token
 */
function generateToken(int $length = 32): string {
    // Ensure even length to avoid binary-to-hex mismatch
    $length = max(16, ($length % 2 === 0) ? $length : $length + 1);
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        // Fallback in rare case random_bytes() fails
        return hash('sha256', uniqid((string)mt_rand(), true));
    }
}

/**
 * Store a new token in the session.
 *
 * @return string The generated token
 */
function storeToken(): string {
    $_SESSION['token'] = generateToken();    
    return $_SESSION['token'];
}

/**
 * Verify if the given token matches the session token.
 *
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyToken(string $token): bool {
    if (!isset($_SESSION['token'])) {
        return false;
    }
    // hash_equals() prevents timing attacks
    return hash_equals($_SESSION['token'], $token);
}
?>
