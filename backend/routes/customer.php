<?php
require_once __DIR__ . '/../controllers/CustomerController.php';

$router->add('GET', '/api/customers', function() {
    (new CustomerController())->list();
});

$router->add('GET', '/api/customers/show', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new CustomerController())->show($id);
});

$router->add('POST', '/api/customers', function() {
    (new CustomerController())->store();
});

$router->add('PUT', '/api/customers', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new CustomerController())->update($id);
});

$router->add('DELETE', '/api/customers', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new CustomerController())->destroy($id);
});
