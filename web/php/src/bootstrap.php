<?php
declare(strict_types=1);

namespace App;

use App\Core\Registry;
use App\Services\Db;
use App\Services\DbJson;
use App\Services\Location;
use App\Services\Smarty;
use Throwable;

/**
 * THOMASONS V3 – Bootstrap
 * Core initialization and service registration.
 * * NOTE: Services are registered in order of dependency.
 */

// ────────────────────────────────────────────────
// 1. Define immutable core paths
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
// 3. Register "Passive" Core Services FIRST
// These services have no dependencies and are required by others.
// ────────────────────────────────────────────────
$location = new Location();
Registry::set(Location::class, $location);

$smarty = new Smarty();
Registry::set(Smarty::class, $smarty);

// ────────────────────────────────────────────────
// 4. Database Decision Logic
// ────────────────────────────────────────────────

// Ensure these matches your .env exactly: APP_DEV=false
$isDevMode = (getenv('APP_ENV') === 'development' || getenv('APP_DEV') === 'true');

try {
    if ($isDevMode) {
        // This is likely what is running right now!
        Registry::set('db', new \App\Services\DbJson());
    } else {
        // We NEED this to run for the Migrator to work with MySQL
        Registry::set('db', new \App\Services\Db());
    }
} catch (Throwable $e) {
    error_log("DB Failure: " . $e->getMessage());
    // If MySQL fails, it falls back to DbJson here, which causes the type error in Migrator
    Registry::set('db', new \App\Services\DbJson());
}
// ────────────────────────────────────────────────
// 5. Error & Exception Handling
// ────────────────────────────────────────────────
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    error_log(sprintf("[%s] PHP Error [%d]: %s in %s:%d", 
        date('Y-m-d H:i:s'), $errno, $errstr, $errfile, $errline));
    return false; 
});

set_exception_handler(function (Throwable $e): void {
    error_log("Uncaught " . get_class($e) . ": " . $e->getMessage() . 
              "\nStack trace:\n" . $e->getTraceAsString());

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'status'  => 'error',
        'message' => 'Internal Server Error',
        // Optional: Include trace only in dev mode
        'debug'   => (getenv('APP_ENV') === 'development') ? $e->getMessage() : null
    ]);
    exit;
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . ":" . $error['line']);
    }
});