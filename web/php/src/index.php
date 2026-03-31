<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\Logger;

$logger = new Logger();

/**
 * 2. Define Route Aliases
 * Maps the URL slug to the [ControllerName, MethodName]
 */
$aliases = [
    'upload'     => ['Upload', 'index'],
    'job-status' => ['Upload', 'status'],
    'search'     => ['Search', 'query'],
    'chat'       => ['Chat', 'ask']
];

// 3. Parse the URI
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uriPath, '/')); 

// 4. Strip prefixes (/php/ or /api/)
if (isset($parts[0]) && ($parts[0] === 'php' || $parts[0] === 'api')) {
    array_shift($parts);
}

// 5. Resolve the Route & Parameters
$slug = $parts[0] ?? 'home';
$params = [];

if (isset($aliases[$slug])) {
    [$controllerBase, $methodName] = $aliases[$slug];
    // If URI is /php/job-status/3, '3' becomes the first parameter
    $params = array_slice($parts, 1);
} else {
    $controllerBase = ucfirst($slug);
    $methodName     = $parts[1] ?? 'index';
    $params         = array_slice($parts, 2);
}

$className = "App\\Controllers\\{$controllerBase}Controller";

// 6. Execution
if (class_exists($className)) {
    // Instantiate the controller
    $controller = new $className();
    
    if (method_exists($controller, $methodName)) {
        
        $logger->info("Routing Request", [
            'controller' => $className,
            'method'     => $methodName,
            'params'     => $params
        ]);

        // Spread the parameters into the method
        // e.g., FileController->status('3')
        $controller->{$methodName}(...$params);

    } else {
        header("Content-Type: application/json", true, 404);
        echo json_encode(["error" => "Method '$methodName' not found in $className"]);
    }
} else {
    header("Content-Type: application/json", true, 404);
    echo json_encode(["error" => "Controller '$className' not found"]);
}