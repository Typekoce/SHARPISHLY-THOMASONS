<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\Logger;
use App\Core\Registry;

// 1. Initialize and Register the Logger
$logger = new Logger();
Registry::set(Logger::class, $logger);

/**
 * 1. Define Route Aliases
 */
$aliases = [
    'upload'     => ['Upload', 'index'], // Changed to UploadController to match your setup
    'job-status' => ['File', 'status'],
    'search'     => ['Search', 'query'],
    'chat'       => ['Chat', 'ask']
];

// 2. Parse the URI path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/')); 

// 3. Normalize Prefix
if (($parts[0] ?? '') === 'php' || ($parts[0] ?? '') === 'api') {
    array_shift($parts);
}

// 4. Resolve Controller, Method, and Parameters
$slug = $parts[0] ?? 'home';

if (isset($aliases[$slug])) {
    [$controllerName, $methodName] = $aliases[$slug];
    $params = array_slice($parts, 1);
} else {
    $controllerName = ucfirst($slug);
    $methodName     = $parts[1] ?? 'index';
    $params         = array_slice($parts, 2);
}

$className = "App\\Controllers\\{$controllerName}Controller";

// 5. Execution Block
if (class_exists($className)) {
    // Note: Logger is now available via BaseController's Registry::get(Logger::class)
    $controller = new $className();
    
    if (method_exists($controller, $methodName)) {
        
        // CRITICAL FIX: Log BEFORE execution so we catch data even if it crashes
        $logger->info("Incoming Route", [
            'controller' => $controllerName,
            'method'     => $methodName,
            'params'     => $params,
            'files'      => array_keys($_FILES), // See which keys are being sent
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        // Execute the controller action
        $controller->{$methodName}(...$params);

    } else {
        header("Content-Type: application/json", true, 404);
        echo json_encode(["error" => "Method '$methodName' not found in $controllerName"]);
    }
} else {
    header("Content-Type: application/json", true, 404);
    echo json_encode(["error" => "Controller '$className' not found"]);
}