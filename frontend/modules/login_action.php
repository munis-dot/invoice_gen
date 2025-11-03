<?php
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../utils/api_client.php';

header('Content-Type: application/json');

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Use relative API path; apiRequest will build the full URL with apiUrl()
$response = apiRequest('http://localhost/invoice_gen/backend/public/api/login', 'POST', ['email' => $email, 'password' => $password]);

if (is_array($response) && !empty($response['success'])) {
    // Successful login
    $token = $response['token'] ?? null;
    $user = $response['user'] ?? null;

    if ($token) {
        setJwtToken($token);
    }
    if (is_array($user)) {
        loginUser($user);
    }

    // Generate CSRF token for forms
    if (function_exists('storeToken')) {
        storeToken();
    }

    header('Location: ../index.php');
    exit;
}

// Handle errors from API
$errMsg = 'Login failed. Please try again.';
if (is_array($response) && !empty($response['message'])) {
    $errMsg = $response['message'];
} elseif (is_array($response) && !empty($response['error'])) {
    $errMsg = $response['error'];
}

echo json_encode([
    'success' => false,
    'message' => $errMsg
]);
exit;

?>
