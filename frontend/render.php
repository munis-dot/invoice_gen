<?php
// frontend/render.php

$page = $_GET['page'] ?? 'dashboard';
// Sanitize and split path components
$parts = explode('/', str_replace('\\', '/', $page));
$parts = array_map(function($part) {
    return str_replace(['..', '\\', '/'], '', $part);
}, $parts);

// Define possible paths
$paths = [];

// Set up the include path for modules
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/modules');

// Special handling for customers module
if ($parts[0] === 'customers') {
    // Remove 'customers' from the path for proper file lookup
    array_shift($parts);
    $subpage = implode(DIRECTORY_SEPARATOR, $parts);
    
    $paths = [
        __DIR__ . "/modules/customers/customer_{$subpage}.php",
        __DIR__ . "/modules/customers/{$subpage}.php",
        __DIR__ . "/modules/customers/{$subpage}/index.php"
    ];
} else {
    // Standard module paths
    $page = implode(DIRECTORY_SEPARATOR, $parts);
    $paths = [
        __DIR__ . "/modules/{$page}.php",
        __DIR__ . "/modules/{$page}/index.php"
    ];
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("Debug - Trying paths: " . print_r($paths, true));
echo "<!-- Debug - Page requested: {$page} -->\n";
echo "<!-- Debug - Looking in paths: " . implode(", ", $paths) . " -->\n";

$found = false;
foreach ($paths as $file) {
    error_log("Checking file: " . $file);
    if (file_exists($file)) {
        error_log("Found file: " . $file);
        try {
            ob_start();
            require $file;
            $content = ob_get_clean();
            echo $content;
            $found = true;
            break;
        } catch (Exception $e) {
            error_log("Error including file {$file}: " . $e->getMessage());
            ob_end_clean();
            echo "<h3 style='color:red;'>⚠️ Error loading page: " . htmlspecialchars($e->getMessage()) . "</h3>";
        }
    }
}

if (!$found) {
    http_response_code(404);
    echo "<h3 style='color:red;'>⚠️ Page not found: " . htmlspecialchars($page) . "</h3>";
    error_log("No valid files found in paths: " . implode(", ", $paths));
}
?>
