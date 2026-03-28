<?php
declare(strict_types=1);

/**
 * SHARPISHLY BOOTSTRAP
 * Initialises the Autoloader and Database for the Web UI.
 */

// 1. PSR-4ish Autoloader (Manual as we are not using Composer locally)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 2. Global Exception Handler for JSON responses
set_exception_handler(function ($e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Bootstrap Error: ' . $e->getMessage()
    ]);
    exit;
});

// We pull from the environment variables defined in docker-compose.yml
try {
    $dbConfig = [
        'host' => getenv('DB_HOST') ?: 'sharpishly-db',
        'name' => getenv('DB_NAME') ?: 'sharpishly',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: 'root_password'
    ];

    // This creates the singleton/instance your BaseController uses
    $db = new \App\Services\Db($dbConfig);
    
    // Inject into a global space if your BaseController looks for 'db'
    $GLOBALS['db'] = $db;

} catch (\Throwable $e) {
    throw new \Exception("Database Connection Failed: " . $e->getMessage());
}

/**
 * Note: The PHP Worker loop has been removed from this file 
 * as it is now handled by the Python Neural Worker.
 */