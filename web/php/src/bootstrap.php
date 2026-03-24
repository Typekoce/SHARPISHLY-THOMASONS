<?php
declare(strict_types=1);

namespace App;

use App\Services\Db;
use App\Services\DbJson;
use App\Services\Location;
use Throwable;

/**
 * THOMASONS V3 – Bootstrap
 * Core initialization, autoloading, and service registration.
 */

// ────────────────────────────────────────────────
// 1. Define immutable core paths (MUST be before autoloader)
// ────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__, 3) . '/');
define('SRC_ROOT', __DIR__ . '/');

// ────────────────────────────────────────────────
// 2. PSR-4 Autoloader
// ────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefix    = 'App\\';
    $base_dir  = SRC_ROOT;
    $prefixLen = strlen($prefix);

    if (strncmp($prefix, $class, $prefixLen) !== 0) {
        return;
    }

    $relative = substr($class, $prefixLen);
    $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ────────────────────────────────────────────────
// 3. Registry Setup (Simple Singleton Manager)
// ────────────────────────────────────────────────
class Registry
{
    private static array $instances = [];

    public static function set(string $key, object $instance): void
    {
        self::$instances[$key] = $instance;
    }

    public static function get(string $key): object
    {
        if (!isset(self::$instances[$key])) {
            throw new \RuntimeException("Service not registered: $key");
        }
        return self::$instances[$key];
    }

    /**
     * Factory-style make (for classes that need constructor args)
     */
    public static function make(string $class, ...$args): object
    {
        return new $class(...$args);
    }
}

// ────────────────────────────────────────────────
// 4. Database Decision Logic (Real Db vs DbJson fallback)
// ────────────────────────────────────────────────
$isDevMode = (getenv('APP_ENV') === 'development' || getenv('APP_DEV') === 'true');

try {
    if ($isDevMode) {
        // Use JSON mock in development for faster iteration
        Registry::set('db', new DbJson());
        error_log("Using DbJson (development mode)");
    } else {
        Registry::set('db', new Db());
        error_log("Using real MySQL database");
    }
} catch (Throwable $e) {
    error_log("MySQL connection failed: " . $e->getMessage() . " → Falling back to DbJson");
    Registry::set('db', new DbJson());
}

// ────────────────────────────────────────────────
// 5. Core Services Initialization
// ────────────────────────────────────────────────
$location = Registry::make(Location::class);

// ────────────────────────────────────────────────
// 6. Error & Exception Handling
// ────────────────────────────────────────────────
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    error_log(sprintf("[%s] PHP Error [%d]: %s in %s:%d", 
        date('Y-m-d H:i:s'), $errno, $errstr, $errfile, $errline));
    return false; // Let PHP continue with default behavior
});

set_exception_handler(function (Throwable $e): void {
    error_log("Uncaught " . get_class($e) . ": " . $e->getMessage() . 
              "\nStack trace:\n" . $e->getTraceAsString());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        exit(1);
    }

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Internal Server Error'
    ]);
    exit;
});

// Optional: Catch fatal errors on shutdown
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
    }
});

// echo "Bootstrap completed successfully.\n";