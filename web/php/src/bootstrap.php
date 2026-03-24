<?php
declare(strict_types=1);

namespace App;

use App\Core\Registry;
use App\Services\Db;
use App\Services\DbJson;
use App\Services\Location;

// 1. Define immutable core paths (MOVE THIS ABOVE AUTOLOADER)
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

// 3. The Decision Logic
$isDevMode = (getenv('APP_DEV') === 'true');

if ($isDevMode) {
    Registry::set('db', new DbJson());
} else {
    try {
        Registry::set('db', new Db());
    } catch (\Exception $e) {
        error_log("MySQL Unavailable: " . $e->getMessage());
        Registry::set('db', new DbJson());
    }
}

// 4. Initialization using "make" for class-based singletons
$location = Registry::make(Location::class);

// ... rest of error handlers ...