<?php
require_once __DIR__ . '/../utils/api_client.php';

// Allow CORS (optional, useful if frontend runs separately)
header('Content-Type: application/json');

// Read JSON input from JS fetch body
$input = json_decode(file_get_contents('php://input'), true);

// Extract parameters safely
$method = strtoupper($input['method'] ?? $_GET['method'] ?? 'GET');
$url    = $input['url']    ?? $_GET['url']    ?? '';
$data   = $input['data']   ?? [];

// Basic validation
if (empty($url)) {
    echo json_encode(['error' => 'Missing API URL']);
    exit;
}

// Call your reusable apiRequest() function
$response = apiRequest($url, $method, $data);
// Return response as JSON
echo json_encode($response);
?>
