<?php
require_once __DIR__ . '/../../utils/session.php';

// Get customer ID from URL parameter
$invoiceId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$invoiceId) {
    echo "Customer ID is required";
    exit;
}

$config = [
    'endpoint' => "/invoice_gen/backend/public/api/invoices/show?id={$invoiceId}",
    'title' => 'Invoice Details',
    'fields' => [
        'id' => 'Invoice ID',
        'customer_id' => "Customer Id",
        'date' => 'Date',
        'total' => 'Amount',
        'payment_method' => 'Payment Method',
        'invoice_number' => 'Invoice Number',
        'created_at' => [
            'label' => 'Created Date',
            'format' => 'date'
        ]
    ],
    'image_field' => '',
    'actions' => [
        [
            'label' => 'Back to List',
            'link' => '?page=transactions/transaction_list',
            'class' => 'btn back',
            'icon' => 'arrow-left'
        ]
    ]
];

include __DIR__ . '/../../components/generic_view.php';
include __DIR__ . '/../invoice/templates/classic.php';
?>
