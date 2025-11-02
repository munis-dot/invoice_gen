<?php

$config = [
    'title' => 'Transaction',
    'redirectPage' => 'transactions/transaction_list',
    'bulkUploadFormId' => 'transactionBulkUploadForm',
    'resultDivId' => 'addTransactionResult',
    'formId' => 'transactionForm',  
    'fields' => [
        ['name' => 'customerId', 'label' => 'Customer Id', 'type' => 'text', 'required' => true],
        ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true],
        ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => '0.01', 'required' => true],
        ['name' => 'paymentMethod', 'label' => 'Payment Method', 'type' => 'select', 'options' => [
            ['value' => 'cash', 'label' => 'Cash'], 
            ['value' => 'card', 'label' => 'Card'],
            ['value' => 'bank', 'label' => 'Bank Transfer']
        ], 'required' => true],
        ['name' => 'invoiceNumber', 'label' => 'Invoice Number', 'type' => 'text', 'required' => true],
        ['name' => 'discount', 'label' => 'Has Discount', 'type' => 'select', 'options' => [
            ['value' => 'true', 'label' => 'Yes'],
            ['value' => 'false', 'label' => 'No']
        ], 'required' => true],
   ]
];
include __DIR__ . '/../../components/generic_manage.php';
?>
