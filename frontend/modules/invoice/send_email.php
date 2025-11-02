<?php
require_once '../../utils/api_client.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['invoice_id'], $input['to'], $input['subject'], $input['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$api_client = new ApiClient();
$response = $api_client->post('/api/invoices/email', [
    'invoice_id' => $input['invoice_id'],
    'template' => $input['template'],
    'to' => $input['to'],
    'subject' => $input['subject'],
    'message' => $input['message']
]);

$result = json_decode($response, true);
echo json_encode([
    'success' => isset($result['success']) ? $result['success'] : false,
    'message' => isset($result['message']) ? $result['message'] : 'Unknown error occurred'
]);