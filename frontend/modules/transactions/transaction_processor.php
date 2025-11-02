<?php
require_once __DIR__ . '/../../utils/generic_processor.php';

// Define entity configuration for transactions
$transactionConfig = [
    'entityName' => 'transactions',
    'apiEndpoint' => 'invoice_gen/backend/api/transactions',
    'batchEndpoint' => 'invoice_gen/backend/api/transactions/batch',
    'successMessage' => 'Transaction added successfully',
    'fields' => [
        'customerId' => [
            'fileField' => 'customerid',
            'required' => true,
            'transform' => 'int',
            'validate' => ['numeric' => true, 'min' => 1]
        ],
        'date' => [
            'fileField' => 'date',
            'required' => true,
            'validate' => ['date' => true]
        ],
        'amount' => [
            'fileField' => 'amount',
            'required' => true,
            'transform' => 'float',
            'validate' => ['numeric' => true, 'min' => 0]
        ],
        'paymentMethod' => [
            'fileField' => 'paymentmethod',
            'required' => true,
            'validate' => ['enum' => ['cash', 'card', 'bank']]
        ],
        'discount' => [
            'fileField' => 'discount',
            'required' => true,
            'transform' => 'bool',
            'validate' => ['enum' => ['true', 'false']]
        ],
        'invoiceNumber' => [
            'fileField' => 'invoicenumber',
            'required' => true
        ]
    ]
];

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new GenericProcessor($transactionConfig);
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
       