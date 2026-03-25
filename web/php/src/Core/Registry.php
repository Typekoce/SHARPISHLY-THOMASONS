<?php
declare(strict_types=1);

namespace App\Core;

class Registry {
    private static array $instances = [];
    private static array $values = [];

    public static function set(string $key, object $instance): void {
        self::$values[$key] = $instance;
    }

    public static function get(string $key) {
        if (!isset(self::$values[$key])) {
            // Instead of returning null, we throw a clear error for easier debugging
            throw new \RuntimeException("Service [$key] not found in Registry.");
        }
        return self::$values[$key];
    }

    public static function make(string $class, ...$args): object {
        $class = ltrim($class, '\\');
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class(...$args);
        }
        return self::$instances[$class];
    }
}