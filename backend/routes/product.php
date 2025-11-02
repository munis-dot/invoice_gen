<?php
require_once __DIR__ . '/../controllers/ProductController.php';

$router->add('GET', '/api/products', function() {
    (new ProductController())->list();
});

$router->add('GET', '/api/products/show', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new ProductController())->show($id);
});

$router->add('POST', '/api/products', function() {
    (new ProductController())->store();
});

$router->add('PUT', '/api/products', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new ProductController())->update($id);
});

$router->add('DELETE', '/api/products', function() {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    (new ProductController())->destroy($id);
});
