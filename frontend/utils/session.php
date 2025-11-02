<?php
// Always start the session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,     // Prevent JavaScript access to cookies
        'use_strict_mode' => true,     // Prevent session fixation
        'cookie_secure' => isset($_SERVER['HTTPS']), // Secure cookie in HTTPS
        'cookie_samesite' => 'Lax',    // Helps mitigate CSRF
    ]);
}

/**
 * Ensures a valid session exists.
 * Redirects to login page if not authenticated.
 */
function checkSession(): void {
    $currentPage = basename($_SERVER['PHP_SELF']);

    // Allow access only to login page when not logged in
    // Consider the user authenticated if a valid JWT exists
    if (!isAuthenticated() && $currentPage !== 'login.php') {
        header('Location: modules/login.php');
        exit;
    }

    // Optional: regenerate session ID every 15 minutes for extra safety
    if (!isset($_SESSION['LAST_REGEN']) || time() - $_SESSION['LAST_REGEN'] > 900) {
        session_regenerate_id(true);
        $_SESSION['LAST_REGEN'] = time();
    }
}

/**
 * Stores user data in session securely.
 */
function loginUser(array $user): void {
    // Reset old session ID for security
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    $_SESSION['LAST_REGEN'] = time();
}


/**
 * Store the JWT returned from backend after successful login.
 *
 * @param string $jwt
 * @return void
 */
function setJwtToken(string $jwt): void {
    // Keep JWT separate from other tokens (like CSRF) to avoid conflicts
    $_SESSION['jwt'] = $jwt;
}


/**
 * Return the stored JWT or null if missing.
 *
 * @return string|null
 */
function getJwtToken(): ?string {
    return $_SESSION['jwt'] ?? null;
}


/**
 * Decode the JWT payload (no signature verification) and return as array.
 * Used only to inspect expiry and other public claims on client side.
 *
 * @param string $jwt
 * @return array|null
 */
function decodeJwtPayload(string $jwt): ?array {
    $parts = explode('.', $jwt);
    if (count($parts) < 2) {
        return null;
    }
    $payload = $parts[1];

    // Add padding for base64 url safe
    $payload = strtr($payload, '-_', '+/');
    $pad = strlen($payload) % 4;
    if ($pad) {
        $payload .= str_repeat('=', 4 - $pad);
    }

    $decoded = base64_decode($payload);
    if ($decoded === false) {
        return null;
    }

    $data = json_decode($decoded, true);
    return is_array($data) ? $data : null;
}


/**
 * Returns true when a valid (non-expired) JWT exists in session.
 * If the token is expired it will be cleared and false returned.
 *
 * @return bool
 */
function isAuthenticated(): bool {
    $jwt = getJwtToken();
    if (empty($jwt)) {
        return false;
    }

    $payload = decodeJwtPayload($jwt);
    if ($payload === null) {
        // Not a valid JWT
        unset($_SESSION['jwt']);
        return false;
    }

    // If exp claim exists, validate it
    if (isset($payload['exp']) && is_numeric($payload['exp'])) {
        if (time() > (int)$payload['exp']) {
            // Token expired
            logoutUser();
            return false;
        }
    }

    return true;
}


/**
 * Logs out the user and clears session safely.
 * Ensures JWT and other session data are removed.
 */
function logoutUser(): void {
    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    // Destroy session
    session_destroy();
}
