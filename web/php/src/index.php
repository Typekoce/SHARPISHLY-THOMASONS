<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/**
 * 1. Define Route Aliases
 * Maps a single URI slug to a specific Controller and Method.
 * This keeps the SPA URLs clean (e.g., /php/upload instead of /php/File/upload).
 */
$aliases = [
    'upload'     => ['File', 'upload'],
    'job-status' => ['File', 'status'],
    'search'     => ['Search', 'query']
];

// 2. Parse the URI path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/')); 

// 3. Normalize Prefix (Remove 'php' or 'api' if present)
if (($parts[0] ?? '') === 'php' || ($parts[0] ?? '') === 'api') {
    array_shift($parts);
}

// 4. Resolve Controller, Method, and Parameters
$slug = $parts[0] ?? 'home';

if (isset($aliases[$slug])) {
    // Case A: URI matches an alias (e.g., /php/upload)
    [$controllerName, $methodName] = $aliases[$slug];
    // Parameters start immediately after the slug (index 1)
    $params = array_slice($parts, 1);
} else {
    // Case B: Standard MVC pattern (e.g., /php/User/profile/42)
    $controllerName = ucfirst($slug);
    $methodName     = $parts[1] ?? 'index';
    // Parameters start after Controller and Method (index 2)
    $params         = array_slice($parts, 2);
}

$className = "App\\Controllers\\{$controllerName}Controller";

// 5. Execution Block
if (class_exists($className)) {
    $controller = new $className();
    
    if (method_exists($controller, $methodName)) {
        // Use the elegant PHP 8 spread operator to unpack params
        $controller->{$methodName}(...$params);
    } else {
        header("Content-Type: application/json", true, 404);
        echo json_encode(["error" => "Method '$methodName' not found in $controllerName"]);
    }
} else {
    header("Content-Type: application/json", true, 404);
    echo json_encode(["error" => "Controller '$className' not found"]);
}