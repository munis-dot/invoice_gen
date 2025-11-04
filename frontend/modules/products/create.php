<?php
$config = [
    'endpoint' => '/invoice_gen/backend/public/api/products/show',
    'title' => 'Product',
    'redirectPage' => 'products/list',
    'bulkUploadFormId' => 'productBulkUploadForm',
    'resultDivId' => 'addProductResult',
    'formId' => 'productForm',
    'fields' => [
        ['name' => 'name', 'label' => 'Product Name', 'type' => 'text', 'required' => true],
        ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'required' => true],
        ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'prefix' => '$', 'step' => '0.01', 'required' => true],
        ['name' => 'tax_rate', 'label' => 'Tax Rate (%)', 'type' => 'number', 'suffix' => '%', 'step' => '0.01', 'required' => true],
        ['name' => 'stock', 'label' => 'Stock', 'type' => 'number', 'required' => true],
        ['name' => 'product_type', 'label' => 'Product Type', 'type' => 'select', 'required' => true, 'options' => [
            ['value' => 'physical', 'label' => 'Physical Product'],
            ['value' => 'digital', 'label' => 'Digital Product']
        ]],
        ['name' => 'image_url', 'label' => 'Product Image', 'type' => 'file', 'accept' => 'image/*']
    ]
];
include __DIR__ . '/../../components/generic_manage.php';
?>