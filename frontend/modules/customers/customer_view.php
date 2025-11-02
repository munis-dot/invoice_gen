<?php
require_once __DIR__ . '/../../utils/session.php';

// Get customer ID from URL parameter
$customerId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$customerId) {
    echo "Customer ID is required";
    exit;
}

$config = [
    'endpoint' => "/invoice_gen/backend/public/api/customers/show?id={$customerId}",
    'title' => 'Customer Details',
    'fields' => [
        'id' => 'Customer ID',
        'name' => 'Customer Name',
        'email' => [
            'label' => 'Email',
            'format' => 'email'
        ],
        'phone' => 'Phone Number',
        'address' => [
            'label' => 'Address',
            'format' => 'text'
        ],
        'created_at' => [
            'label' => 'Created Date',
            'format' => 'date'
        ]
    ],
    'image_field' => '',
    'actions' => [
        [
            'label' => 'Edit Customer',
            'link' => "?page=customers/customer_add&id={$customerId}",
            'class' => 'btn edit',
            'icon' => 'edit'
        ],
        [
            'label' => 'Back to List',
            'link' => '?page=customers/customer_list',
            'class' => 'btn back',
            'icon' => 'arrow-left'
        ]
    ]
];

include __DIR__ . '/../../components/generic_view.php';
include __DIR__ . '/../transactions/transaction_list.php';
?>
