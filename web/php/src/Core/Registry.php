<?php
declare(strict_types=1);

namespace App\Core;

class Registry {
    private static array $instances = [];
    private static array $values = [];

    // For storing pre-instantiated objects (like the DB)
    public static function set(string $key, object $instance): void {
        self::$values[$key] = $instance;
    }

    public static function get(string $key) {
        return self::$values[$key] ?? null;
    }

    // For "Lazy Loading" classes by name
    public static function make(string $class, ...$args): object {
        $class = ltrim($class, '\\');
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class(...$args);
        }
        return self::$instances[$class];
    }
}