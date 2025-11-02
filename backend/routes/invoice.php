<?php
require_once __DIR__ . '/../controllers/InvoiceController.php';

$router->add('GET', '/api/invoices', function() {
    (new InvoiceController())->list();
});

$router->add('GET', '/api/invoices/show', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new InvoiceController())->show($id);
});

$router->add('GET','/api/invoice', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] :0;
    (new InvoiceController())->findById($id);
});

$router->add('POST', '/api/invoices', function() {
    (new InvoiceController())->store();
});

$router->add('PUT', '/api/invoices', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new InvoiceController())->update($id);
});

$router->add('DELETE', '/api/invoices', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new InvoiceController())->destroy($id);
});
$router->add('POST', '/api/invoices/generate', function() {
    (new InvoiceController())->generateInvoice();
});

$router->add('POST', '/api/invoices/batch', function() {
    (new InvoiceController())->createBatch();
});

$router->add('GET', '/api/invoices/customer', function() {
    $customerId = isset($_GET['customerId']) ? (int)$_GET['customerId'] : 0;
    (new InvoiceController())->getByCustomerId($customerId);
});