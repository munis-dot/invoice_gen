<?php
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['path'])) {
        throw new Exception('Path is required');
    }
    
    $fullPath = __DIR__ . '/../' . $data['path'];
    
    if (!file_exists($fullPath)) {
        if (!mkdir($fullPath, 0777, true)) {
            throw new Exception('Failed to create directory');
        }
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}