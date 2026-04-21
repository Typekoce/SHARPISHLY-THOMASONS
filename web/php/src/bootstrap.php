<?php
declare(strict_types=1);

/**
 * SHARPISHLY BOOTSTRAP
 * Encapsulated initialization for Web UI and Migrations.
 */

define('PROJECT_ROOT', dirname(__DIR__, 3));

/**
 * 1. Environment Loader
 */
function initializeEnvironment(string $root): void {
    $path = $root . '/.env';
    if (!file_exists($path)) {
        error_log("Bootstrap: .env not found at $path");
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line || strpos($line, '#') === 0) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim(trim($parts[1]), '"\'');
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

/**
 * 2. PSR-4 Autoloader
 */
function initializeAutoloader(string $baseDir): void {
    spl_autoload_register(function ($class) use ($baseDir) {
        $prefix = 'App\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

/**
 * 3. Database & Logger Factory
 */
function initializeServices(): void {
    // Instantiate Logger first so it can be used by other services
    $logger = new \App\Services\Logger();
    $GLOBALS['logger'] = $logger;
    
    $logger->info("Initializing Database connection...");

    $config = [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS') ?: ''
    ];

    foreach (['name', 'user'] as $key) {
        if ($config[$key] === false || $config[$key] === null) {
            $logger->error("Validation failed: DB_" . strtoupper($key) . " is missing.");
            throw new \Exception("Environment Variable Missing: DB_" . strtoupper($key));
        }
    }

    try {
        $GLOBALS['db'] = new \App\Services\Db($config);
        $logger->info("Database handshake successful.");
    } catch (\Throwable $e) {
        $logger->error("Database connection failed: " . $e->getMessage());
        throw new \Exception("Database Connection Failed: " . $e->getMessage());
    }
}

/**
 * EXECUTION PHASE
 */

set_exception_handler(function ($e) {
    $msg = "Bootstrap Fatal: " . $e->getMessage();
    
    // Check for global instance before falling back to system log
    if (isset($GLOBALS['logger'])) {
        $GLOBALS['logger']->error($msg);
    } else {
        error_log($msg);
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Bootstrap Error: ' . $e->getMessage(),
        'trace'   => $e->getFile() . ' on line ' . $e->getLine()
    ]);
    exit;
});

initializeEnvironment(PROJECT_ROOT);
initializeAutoloader(__DIR__ . '/');
initializeServices();