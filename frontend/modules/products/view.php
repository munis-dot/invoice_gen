<?php
require_once __DIR__ . '/../../utils/session.php';

// Get product ID from URL parameter
$productId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$productId) {
    echo "Product ID is required";
    exit;
}

$config = [
    'endpoint' => "/invoice_gen/backend/public/api/products/show?id={$productId}",
    'title' => 'Product Details',
    'fields' => [
        'id' => 'Product ID',
        'sku' => 'SKU',
        'name' => 'Product Name',
        'description' => 'Description',
        'price' => [
            'label' => 'Price',
            'format' => 'currency'
        ],
        'tax_rate' => [
            'label' => 'Tax Rate',
            'format' => 'percentage'
        ],
        'stock' => [
            'label' => 'Stock Level',
            'format' => 'number'
        ],
        'created_at' => [
            'label' => 'Created Date',
            'format' => 'date'
        ]
    ],
    'image_field' => 'image_url',
    'actions' => [
        [
            'label' => 'Edit Product',
            'link' => "?page=products/create&id={$productId}",
            'class' => 'btn edit'
        ],
        [
            'label' => 'Back to List',
            'link' => '?page=products/list',
            'class' => 'btn back'
        ]
    ]
];

include __DIR__ . '/../../components/generic_view.php';
?>