<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$router->add('POST', '/api/login', function () {
    (new AuthController())->login();
});
