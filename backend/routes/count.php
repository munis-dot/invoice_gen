<?php
require_once __DIR__ . '/../models/customer.php';
require_once __DIR__ . '/../models/invoice.php';
require_once __DIR__ . '/../models/product.php';

$router->add('GET', '/api/dashboard', function () {
    $customerCount = (new Customer())->getTotalCount();
    $invoiceCount = (new Invoice())->getTotalCount();
    $productCount = (new Product())->getTotalCount();

    header('Content-Type: application/json');
    echo json_encode([
        "customer" => $customerCount,
        "invoice" => $invoiceCount,
        "product" => $productCount
    ]);

});
