<?php
require_once __DIR__ . './api_client.php'; // adjust path if needed

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // optional, for local testing
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Collect query params
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query for backend API
$params = [
    'page' => $page,
    'limit' => $limit,
    'search' => $search
];

// Call backend API via apiRequest()
$response = apiRequest('/backend/public/index.php/api/customers', 'GET', $params);

// Output backend response directly to JS
echo json_encode($response);
exit;
