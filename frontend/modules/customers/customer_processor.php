<?php
require_once __DIR__ . '/../../utils/generic_processor.php';

// Define entity configuration for customers
$customerConfig = [
    'entityName' => 'customers',
    'apiEndpoint' => 'invoice_gen/backend/api/customers',
    'batchEndpoint' => 'invoice_gen/backend/api/customers/batch',
    'successMessage' => 'Customer added successfully',
    'fields' => [
        'name' => [
            'fileField' => 'name',
            'required' => true,
            'transform' => 'string'
        ],
        'email' => [
            'fileField' => 'email',
            'required' => true,
            'validate' => ['email' => true]
        ],
        'phone' => [
            'fileField' => 'phone',
            'required' => true
        ],
        'address' => [
            'fileField' => 'address',
            'required' => false,
            'transform' => 'string'
        ]
    ]
];

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new GenericProcessor($customerConfig);
    header('Content-Type: application/json');
    
    if (isset($_FILES['file'])) {
        // File upload processing
        $result = $processor->processFileUpload($_FILES['file']);
        echo json_encode($result);
    } else {
        // Manual form submission
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $processor->processManualSubmission($data);
        echo json_encode($result);
    }
    exit;
}
?>