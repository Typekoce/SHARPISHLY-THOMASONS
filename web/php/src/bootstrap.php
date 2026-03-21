<?php
declare(strict_types=1);

/**
 * THOMASONS V3 – Bootstrap
 * PSR-4 Autoloader + Core Service Initialization
 * (Logging redirected to standard error output)
 */

namespace App;

// 1. Define immutable core paths
define('APP_ROOT', dirname(__DIR__, 3) . '/');
define('SRC_ROOT', __DIR__ . '/');

// 2. PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix    = 'App\\';
    $base_dir  = SRC_ROOT;
    $prefixLen = strlen($prefix);

    if (strncmp($prefix, $class, $prefixLen) !== 0) return;

    $relative = substr($class, $prefixLen);
    $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 3. Singleton-style Registry
class Registry {
    private static array $instances = [];

    public static function get(string $class, ...$args): object {
        $class = ltrim($class, '\\');
        if (!isset(self::$instances[$class])) {
            if (!class_exists($class)) {
                throw new \RuntimeException("Class not found: $class");
            }
            self::$instances[$class] = new $class(...$args);
        }
        return self::$instances[$class];
    }
}

// 4. Early initialization
use App\Services\Location;
$location = Registry::get(Location::class);

// 5. Error & Exception Handling (Stream to Docker Logs)
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    // Return false to let PHP's default handler send the error to the server log (stderr)
    return false; 
});

set_exception_handler(function (\Throwable $e): void {
    $message = sprintf(
        "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
        get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
    );

    // Send to PHP's system log (which Docker captures)
    error_log($message);

    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        echo "Internal Server Error";
    }
    exit(1);
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    $fatals = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if ($error && in_array($error['type'], $fatals, true)) {
        error_log(sprintf("Fatal Error: %s in %s:%d", $error['message'], $error['file'], $error['line']));
    }
});
