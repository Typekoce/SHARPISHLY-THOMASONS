<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// 1. Get the path and split it
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/')); 

// 2. Remove the 'php' or 'api' prefix if present
if (($parts[0] ?? '') === 'php' || ($parts[0] ?? '') === 'api') {
    array_shift($parts);
}

// 3. Map to Controller and Method
$controllerName = ucfirst($parts[0] ?? 'Home');
$methodName     = $parts[1] ?? 'index';
$params         = array_slice($parts, 2);

$className = "App\\Controllers\\{$controllerName}Controller";

// 4. Execute
if (class_exists($className)) {
    $controller = new $className();
    if (method_exists($controller, $methodName)) {
        // Equivalent to your call_user_func request, but object-aware
        $controller->{$methodName}(...$params);
    } else {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(["error" => "Method $methodName not found in $controllerName"]);
    }
} else {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(["error" => "Controller $className not found"]);
}