<?php
require_once '../../utils/api_client.php';

$invoice_id = $_GET['id'] ?? null;
$template = $_GET['template'] ?? 'classic';

if (!$invoice_id) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Missing invoice ID';
    exit;
}

$api_client = new ApiClient();
$response = $api_client->get("/api/invoices/{$invoice_id}/pdf?template={$template}");

if ($response) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="invoice.pdf"');
    echo $response;
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Failed to generate PDF';
}