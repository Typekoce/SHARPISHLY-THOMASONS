<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Services\Logger;

$logger = new Logger();

/**
 * 2. Define Route Aliases
 * Maps the URL slug to the [ControllerName, MethodName]
 */
// $aliases = [
//     'upload'     => ['Upload', 'index'],
//     'job-status' => ['Upload', 'status'],
//     'search'     => ['Search', 'query'],
//     'chat'       => ['Chat', 'ask'],
//     'upload'     => ['Upload', 'index'], 
//     'pentest-diagnostics'   => ['PentestDianostics','index']  
//     // 'chat-stream'=> ['Chat', 'stream'],
// ];

/**
 * 2. Define Route Aliases
 * Maps the URL slug to the [ControllerName, MethodName]
 */
$aliases = [
    'upload'               => ['Upload', 'index'],
    'job-status'           => ['Upload', 'status'],
    'search'               => ['Search', 'query'],
    'chat'                 => ['Chat', 'ask'],
    'pentest-scan'         => ['Pentest', 'scan'],
    'pentest-diagnostics'  => ['PentestDiagnostics', 'treats'],

    // OAuth & Cloud Service Callbacks
    'auth-google-callback'  => ['Auth', 'googleCallback'],
    'auth-hotmail-callback' => ['Auth', 'hotmailCallback'],
    'auth-aws-callback'     => ['Auth', 'awsCallback'],

    // Indeed API calls
    'indeed-api'            => ['IndeedApi', 'index'],
    'auth-indeed-callback' => ['IndeedApi', 'callback'],
    'indeed-token' => ['IndeedApi', 'fetchToken'],
    
    // 'chat-stream'        => ['Chat', 'stream'],
];

// 3. Parse the URI (CLI-aware fallback)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

$parts = explode('/', trim($uriPath, '/'));
//echo "<pre>";
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
//TODO: Enable URL parameters

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
