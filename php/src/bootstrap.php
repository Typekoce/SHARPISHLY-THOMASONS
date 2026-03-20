<?php
declare(strict_types=1);
// Location: php/src/bootstrap.php
namespace App;

use App\Services\Location;
use App\Services\Logger;
use App\Db;
use App\Models\HomeModel;

/**
 * ────────────────────────────────────────────────
 *  SHARPISHLY BOOTSTRAP – minimal & predictable
 * ────────────────────────────────────────────────
 */

// 1. Load Location service manually (needed for base path)
$locationFile = __DIR__ . '/Services/Location.php';
if (!file_exists($locationFile)) {
    die("Critical: Location service not found at $locationFile");
}
require_once $locationFile;

// 2. Simple singleton-style registry
class Registry
{
    private static array $instances = [];

    public static function get(string $class, ...$args): object
    {
        $class = ltrim($class, '\\');
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class(...$args);
        }
        return self::$instances[$class];
    }
}

// 3. Set up base path early
$baseDir = rtrim(Registry::get(Location::class)->baseDir(), '/') . '/';

// 4. Autoloader – tests first, then app code
spl_autoload_register(function (string $class) use ($baseDir): void {
    $class = ltrim($class, '\\');

    if (str_starts_with($class, 'App\\Tests\\')) {
        $path = $baseDir . 'tests/unit/' . str_replace('\\', '/', substr($class, 10)) . '.php';
    } elseif (str_starts_with($class, 'App\\')) {
        $path = $baseDir . 'php/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    } else {
        return;
    }

    if (file_exists($path)) {
        require_once $path;
    }
});

// 5. Core initialization
try {
    Registry::get(Db::class); // Ensure DB is connected

    // Run migrations only in CLI (workers, cron, tests, artisan-like commands)
    if (php_sapi_name() === 'cli') {
        Registry::get(HomeModel::class)->migrate();
        error_log("CLI bootstrap: migrations checked.");
    }

    // Optional: warm up expensive services
    if (class_exists('App\Services\VectorService')) {
        Registry::get('App\Services\VectorService');
    }

    Logger::info("Bootstrap completed.", ['sapi' => php_sapi_name()], 'system');

} catch (\Throwable $e) {
    error_log("Bootstrap failed: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        exit(1);
    }
    // In web context → let the error handler / 500 page deal with it
}
