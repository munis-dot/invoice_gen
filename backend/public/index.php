<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../config/db.php';
// :white_check_mark: CORS setup
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
// :white_check_mark: Initialize router
$router = new Router();
// :white_check_mark: Include DB connection (already in db.php)
try {
     $pdo = DB::connect();
    if (!$pdo) {
        throw new Exception("Database connection failed.");
    }
} catch (Exception $e) {
    Response::json(['error' => $e->getMessage()], 500);
    exit;
}
// :white_check_mark: Auto-create tables if not exist
$schemaFile = __DIR__ . '/../database/table/table.php';
if (file_exists($schemaFile)) {
    require_once $schemaFile;
}
// :white_check_mark: Auto-seed data (admin user, etc.)
$seedFile = __DIR__ . '/../database/seed_data.php';
if (file_exists($seedFile)) {
    require_once $seedFile;
}
// :white_check_mark: Include all routes dynamically
$routesDir = __DIR__ . '/../routes';
foreach (glob($routesDir . '/*.php') as $file) {
    require_once $file;
}
// :white_check_mark: Dispatch the request
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
