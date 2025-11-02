<?php

$config = [
    'endpoint' => '/invoice_gen/backend/public/api/customers/show',
    'title' => 'Customer',
    'redirectPage' => 'customers/customer_list',
    'bulkUploadFormId' => 'customerBulkUploadForm',
    'resultDivId' => 'addCustomerResult',
    'formId' => 'customerForm',
    'fields' => [
        ['name' => 'name', 'label' => 'Customer Name', 'type' => 'text', 'required' => true],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
        ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true],
        ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'rows' => '3', 'required' => false]
    ]
];
include __DIR__ . '/../../components/generic_manage.php';
?>