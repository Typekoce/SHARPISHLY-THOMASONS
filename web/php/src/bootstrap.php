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
    $path = $root . '/env.php';
    
    if (!file_exists($path)) {
        // We log a critical error because the app cannot function without this grounding.
        error_log("Bootstrap Error: env.php not found at $path");
        return;
    }

    // Since env.php uses define(), requiring it makes 
    // the constants globally available immediately.
    require_once $path;
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
 * Load environment constants and return as a configuration array.
 */
function get_env(): array {
    $file = PROJECT_ROOT . "/env.php";

    if (!file_exists($file)) {
        // We throw an exception here because the app cannot function without this grounding.
        throw new \Exception("Configuration Error: 'env.php' not found at " . $file);
    }

    require_once $file;

    // Return the constants as an array to keep service constructors clean
    return [
        'db_name' => defined('DB_NAME') ? DB_NAME : null,
        'db_user' => defined('DB_USER') ? DB_USER : null,
        'db_pass' => defined('DB_PASS') ? DB_PASS : null,
        'db_host' => defined('DB_HOST') ? DB_HOST : '127.0.0.1',
        'app_dev' => defined('APP_DEV') ? APP_DEV : 'production',
    ];
}

/**
 * 3. Database & Logger Factory
 */
function initializeServices(): void {
    // Instantiate Logger first so it can be used by other services
    $logger = new \App\Services\Logger();
    $GLOBALS['logger'] = $logger;
    
    $logger->info("Initializing Database connection...");

    $db = new \App\Services\Db(get_env(), $logger);

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