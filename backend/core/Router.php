<?php
class Router {
    private array $routes = [];

    public function add(string $method, string $path, callable $callback): void {
        $this->routes[strtoupper($method)][$path] = $callback;
    }

    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH);
        $method = strtoupper($method);
    
        // Automatically remove base folder name if present
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if ($scriptName !== '/' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName));
        }
    
        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
        } else {
            Response::json(['error' => 'Route not found', 'path' => $path], 404);
        }
    }
    
}
